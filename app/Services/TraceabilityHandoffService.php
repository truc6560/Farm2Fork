<?php

namespace App\Services;

use App\Models\{LoNongSan, DonViDongGoiSscc, ChuyenVanChuyen, PhieuGiaoHang, ChiTietGiaoNhan, CanhBaoChuoiLanh, ThietBiIot};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TraceabilityHandoffService
{
    private function require(bool $condition, string $message): void
    {
        if (!$condition) throw ValidationException::withMessages(['handoff' => $message]);
    }

    public function receiveRaw(int $id, array $data, $user): void
    {
        DB::transaction(function () use ($id, $data, $user) {
            $lot = LoNongSan::lockForUpdate()->findOrFail($id);
            $this->require($lot->loai_lo === 'NGUYEN_LIEU' && $lot->trang_thai === 'MOI_TAO', 'Chỉ tiếp nhận một lần đối với lô nguyên liệu mới tạo.');
            $this->require(!DB::table('tiep_nhan_nguyen_lieu')->where('lo_nong_san_id', $id)->exists(), 'Lô đã có phiếu tiếp nhận.');
            $this->require($data['so_luong_thuc_nhan'] <= $lot->so_luong, 'Thực nhận không được vượt số lượng giao.');
            $this->require($data['so_luong_thuc_nhan'] == $lot->so_luong || !empty($data['ghi_chu']), 'Cần ghi lý do chênh lệch số lượng.');
            $senderOrgId = $data['to_chuc_giao_id'] ?? $lot->to_chuc_nguon_id ?? $user->to_chuc_id ?? 1;
            DB::table('tiep_nhan_nguyen_lieu')->insert([
                'lo_nong_san_id' => $id, 'to_chuc_giao_id' => $senderOrgId,
                'to_chuc_nhan_id' => $user->to_chuc_id, 'nguoi_nhan_id' => $user->tai_khoan_id,
                'so_luong_giao' => $lot->so_luong, 'so_luong_nhan' => $data['so_luong_thuc_nhan'],
                'don_vi_tinh' => $lot->don_vi_tinh, 'thoi_gian_nhan' => now(), 'ghi_chu' => $data['ghi_chu'] ?? null,
            ]);
            $lot->update(['so_luong' => $data['so_luong_thuc_nhan']]);
            app(\App\Services\LotStatusService::class)->transition(
                $lot,
                'CHO_KIEM_DINH',
                $user->tai_khoan_id,
                'Tiếp nhận nguyên liệu từ ' . ($senderOrgId ?? 'nguồn') . '. ' . ($data['ghi_chu'] ?? '')
            );
        });
    }

    public function dispatch(int $id, array $data, $user): int
    {
        return $this->dispatchMany([$id], $data, $user);
    }

    public function dispatchMany(array $ids, array $data, $user): int
    {
        return DB::transaction(function () use ($ids, $data, $user) {
            $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();
            $this->require($ids->isNotEmpty(), 'Cần chọn ít nhất một kiện để xuất kho.');
            $this->require($ids->count() <= 500, 'Một lần chỉ được xuất tối đa 500 kiện.');

            $partner = \App\Models\ToChuc::find($data['to_chuc_nhan_id'] ?? null);
            $this->require($partner !== null && in_array($partner->trang_thai, ['HOAT_DONG', 'DANG_HOAT_DONG'], true), 'Đối tác nhận hàng không tồn tại hoặc không ở trạng thái hoạt động.');
            $trip = ChuyenVanChuyen::lockForUpdate()->findOrFail($data['chuyen_id']);
            $this->require(in_array($trip->trang_thai, ['CHUAN_BI', 'SAN_SANG', 'DA_LAP_KE_HOACH']), 'Chuyến phải ở trạng thái chuẩn bị, chưa khởi hành.');

            $units = DonViDongGoiSscc::whereIn('sscc_id', $ids)->lockForUpdate()->get()->keyBy('sscc_id');
            $this->require($units->count() === $ids->count(), 'Có kiện đã bị xóa hoặc không tồn tại.');

            $delivery = PhieuGiaoHang::create([
                'ma_phieu_giao_hang' => 'GH-'.Str::upper(Str::random(20)), 'chuyen_id' => $trip->chuyen_id,
                'to_chuc_nhan_id' => $data['to_chuc_nhan_id'], 'to_chuc_giao_id' => $user->to_chuc_id,
                'nguoi_lap_id' => $user->tai_khoan_id, 'dia_chi_giao' => $data['dia_chi_giao'],
                'trang_thai' => 'CHO_GIAO', 'ghi_chu' => $data['ghi_chu'] ?? null,
            ]);

            foreach ($ids as $id) {
                $unit = $units->get($id);
                $this->require(app(Gs1Service::class)->validateSscc($unit->ma_sscc)['valid'], 'Mã SSCC '.$unit->ma_sscc.' không hợp lệ; cần đối chiếu và quét lại.');
                $this->require(in_array($unit->trang_thai, ['TRONG_KHO', 'DA_NHAP_KHO']), 'Kiện '.$unit->ma_sscc.' không còn ở kho hoặc đã được xuất.');
                $this->require($user->khoLanh()->where('kho_lanh.kho_lanh_id', $unit->kho_lanh_id)->exists(), 'Bạn không được phân công kho chứa kiện '.$unit->ma_sscc.'.');
                $this->require(
                    !$this->warehouseHasOpenColdChainAlert($unit->kho_lanh_id),
                    'Kho đang có cảnh báo chuỗi lạnh chưa đánh giá; chưa thể xuất kiện '.$unit->ma_sscc.'.'
                );
                $contents = $unit->chiTiet()->get();
                $this->require($contents->isNotEmpty(), 'Kiện '.$unit->ma_sscc.' chưa có lô hàng.');

                $line = ChiTietGiaoNhan::create(['phieu_giao_hang_id' => $delivery->phieu_giao_hang_id, 'sscc_id' => $id, 'trang_thai_giao_nhan' => 'CHO_GIAO']);
                foreach ($contents as $content) {
                    $lot = LoNongSan::lockForUpdate()->findOrFail($content->lo_nong_san_id);
                    $this->require(
                        app(QualityGateService::class)->passedOutboundInspection($lot),
                        'Kiện '.$unit->ma_sscc.' chứa lô '.$lot->ma_lo.' chưa đạt kiểm định xuất xưởng.'
                    );
                    $this->require(!in_array($lot->trang_thai, ['THU_HOI', 'TRA_NHA_CUNG_CAP', 'KHONG_DAT']), 'Kiện '.$unit->ma_sscc.' chứa lô bị chặn xuất hàng.');
                    DB::table('so_luong_giao_nhan')->insert([
                        'chi_tiet_giao_nhan_id' => $line->chi_tiet_giao_nhan_id, 'lo_nong_san_id' => $content->lo_nong_san_id,
                        'so_luong_giao' => $content->so_luong, 'don_vi_tinh' => $content->don_vi_tinh,
                    ]);
                    $this->movement($content->lo_nong_san_id, $id, $unit->kho_lanh_id, $user->tai_khoan_id, 'XUAT', $content->so_luong, $content->don_vi_tinh, $delivery->phieu_giao_hang_id, ['nhiet_do_luc_xuat' => $data['nhiet_do_luc_xuat']]);
                }
                $unit->update(['trang_thai' => 'DANG_VAN_CHUYEN']);
            }

            if ($trip->trang_thai !== 'SAN_SANG') {
                $trip->update(['trang_thai' => 'SAN_SANG']);
            }

            return $delivery->phieu_giao_hang_id;
        });
    }

    public function confirm(int $id, array $data, $user, ?string $proof): void
    {
        DB::transaction(function () use ($id, $data, $user, $proof) {
            $delivery = PhieuGiaoHang::lockForUpdate()->findOrFail($id);
            $this->require((int)$delivery->chuyenVanChuyen?->tai_xe_id === (int)$user->tai_khoan_id, 'Phiếu không thuộc chuyến được giao.');
            $this->require(in_array($delivery->trang_thai, ['CHO_GIAO', 'DANG_GIAO']), 'Phiếu đã kết thúc; không được ghi đè biên bản.');
            $this->require($delivery->chuyenVanChuyen?->trang_thai === 'DANG_VAN_CHUYEN', 'Chuyến chưa vận chuyển hoặc đã kết thúc.');
            $this->require(
                !$this->tripHasOpenColdChainAlert($delivery->chuyen_id),
                'Chuyến còn cảnh báo chuỗi lạnh chưa được đánh giá; chưa thể xác nhận giao.'
            );
            $lines = $delivery->chiTietGiaoNhan()->lockForUpdate()->get();
            $this->require($lines->isNotEmpty(), 'Phiếu chưa có kiện hàng.');
            $allFull = true;
            $anyReceived = false;
            $expectedIds = [];
            foreach ($lines as $line) {
                $unit = DonViDongGoiSscc::lockForUpdate()->find($line->sscc_id);
                $this->require($unit !== null, 'Không tìm thấy kiện SSCC trong phiếu giao.');
                $quantities = DB::table('so_luong_giao_nhan')->where('chi_tiet_giao_nhan_id', $line->chi_tiet_giao_nhan_id)->lockForUpdate()->get();
                $this->require($quantities->isNotEmpty(), 'Phiếu cũ thiếu số lượng giao đã chốt; cần đối soát trước khi xác nhận.');
                $full = true;
                $received = false;
                foreach ($quantities as $quantity) {
                    $expectedIds[] = (string)$quantity->id;
                    $answer = $data['quantities'][$quantity->id] ?? null;
                    $this->require($answer !== null, 'Cần ghi thực nhận cho tất cả lô trong kiện.');
                    $amount = round((float)$answer['received'], 3);
                    if (in_array($unit->trang_thai, ['CACH_LY', 'KHONG_DAT'], true)) {
                        $this->require($amount === 0.0, 'Kiện '.$unit->ma_sscc.' đã bị cách ly/không đạt, chỉ được ghi nhận từ chối và trả về.');
                    } else {
                        $this->require($unit->trang_thai === 'DANG_VAN_CHUYEN', 'Kiện '.$unit->ma_sscc.' không ở trạng thái đang vận chuyển.');
                    }
                    $this->require($amount >= 0 && $amount <= (float)$quantity->so_luong_giao, 'Thực nhận phải nằm trong số lượng đã giao.');
                    $isFull = $amount === round((float)$quantity->so_luong_giao, 3);
                    $this->require($isFull || !empty(trim($answer['reason'] ?? '')), 'Cần ghi lý do nhận thiếu hoặc từ chối.');
                    DB::table('so_luong_giao_nhan')->where('id', $quantity->id)->update(['so_luong_nhan' => $amount, 'ly_do' => $answer['reason'] ?? null]);
                    $full = $full && $isFull;
                    $received = $received || $amount > 0;
                }
                $status = $full ? 'DA_GIAO' : ($received ? 'GIAO_MOT_PHAN' : 'TRA_VE');
                $line->update([
                    'trang_thai_giao_nhan' => $status, 'thoi_gian_giao_nhan' => now(),
                    'ten_nguoi_nhan_thuc_te' => $data['ten_nguoi_nhan_thuc_te'],
                    'tinh_trang_bao_goi' => $data['tinh_trang_bao_goi'],
                    'mo_ta_hu_hong' => $data['mo_ta_hu_hong'] ?? null,
                    'ghi_chu' => $data['ghi_chu'] ?? null, 'anh_minh_chung_url' => $proof,
                ]);
                DonViDongGoiSscc::whereKey($line->sscc_id)->update(['trang_thai' => $status]);
                $allFull = $allFull && $full;
                $anyReceived = $anyReceived || $received;
            }
            $this->require(count(array_diff(array_map('strval', array_keys($data['quantities'])), $expectedIds)) === 0, 'Có dòng số lượng không thuộc phiếu giao.');
            $delivery->update(['trang_thai' => $allFull ? 'DA_GIAO' : ($anyReceived ? 'GIAO_MOT_PHAN' : 'TRA_VE')]);
            \App\Models\SuKienHanhTrinh::create([
                'chuyen_id' => $delivery->chuyen_id, 'nguoi_ghi_nhan_id' => $user->tai_khoan_id,
                'loai_su_kien' => 'GIAO_HANG', 'thoi_gian' => now(),
                'dia_diem_mo_ta' => $delivery->dia_chi_giao,
                'mo_ta' => 'Ghi nhận phiếu '.$delivery->ma_phieu_giao_hang.': '.$delivery->trang_thai,
            ]);
        });
    }

    public function movement($lot, $sscc, $warehouse, $user, $type, $quantity, $unit, $delivery = null, array $metadata = []): void
    {
        $row = [
            'lo_nong_san_id' => $lot, 'sscc_id' => $sscc, 'kho_lanh_id' => $warehouse,
            'tai_khoan_id' => $user, 'loai' => $type, 'so_luong' => $quantity,
            'don_vi_tinh' => $unit, 'phieu_giao_hang_id' => $delivery, 'thoi_gian' => now(),
        ];
        foreach (['nhiet_do_luc_nhan', 'nhiet_do_luc_xuat', 'tinh_trang_bao_goi', 'vi_tri_luu_kho', 'ghi_chu'] as $field) {
            if (array_key_exists($field, $metadata) && $metadata[$field] !== null && $metadata[$field] !== '') {
                $row[$field] = $metadata[$field];
            }
        }
        DB::table('bien_dong_kho')->insert($row);
    }

    private function warehouseHasOpenColdChainAlert(?int $warehouseId): bool
    {
        if (!$warehouseId || !Schema::hasTable('thiet_bi_iot') || !Schema::hasTable('canh_bao_chuoi_lanh')) {
            return false;
        }

        $deviceIds = ThietBiIot::where('kho_lanh_id', $warehouseId)->pluck('thiet_bi_id');
        return CanhBaoChuoiLanh::whereIn('thiet_bi_id', $deviceIds)
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])
            ->exists();
    }

    private function tripHasOpenColdChainAlert(?int $tripId): bool
    {
        return $tripId && Schema::hasTable('canh_bao_chuoi_lanh')
            ? CanhBaoChuoiLanh::where('chuyen_id', $tripId)
                ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])
                ->exists()
            : false;
    }
}

<?php
namespace App\Services;

use App\Models\LoNongSan;
use Illuminate\Support\Facades\DB;

class TraceabilityCoverageService
{
    public function gaps(LoNongSan $lot): array
    {
        $gaps = [];
        $rawIds = $lot->loai_lo === 'NGUYEN_LIEU' ? [$lot->lo_nong_san_id] : $lot->nguyenLieuDauVao->pluck('lo_nong_san_id')->all();
        if (!$lot->to_chuc_nguon_id || !$rawIds) $gaps[] = 'Thiếu tổ chức nguồn hoặc liên kết nguyên liệu.';
        if ($lot->trang_thai !== 'MOI_TAO' && count($rawIds) !== DB::table('tiep_nhan_nguyen_lieu')->whereIn('lo_nong_san_id', $rawIds)->count()) {
            $gaps[] = 'Thiếu chứng từ tiếp nhận nguyên liệu (dữ liệu cũ cần đối soát).';
        }
        $units = $lot->donViSscc;
        if (in_array($lot->trang_thai, ['DANG_VAN_CHUYEN', 'DA_GIAO']) && $units->isEmpty()) $gaps[] = 'Lô đã xuất nhưng chưa liên kết kiện hàng.';
        foreach ($units as $unit) {
            if (!in_array($unit->trang_thai, ['DANG_VAN_CHUYEN', 'DA_GIAO', 'GIAO_MOT_PHAN', 'TRA_VE'])) continue;
            $lines = $unit->chiTietGiaoNhan;
            if ($lines->isEmpty()) $gaps[] = 'Kiện '.$unit->ma_sscc.' đã xuất nhưng chưa có phiếu giao.';
            foreach ($lines as $line) {
                $delivery = $line->phieuGiaoHang;
                if (!$delivery?->to_chuc_nhan_id || !$delivery?->to_chuc_giao_id || !$delivery?->chuyen_id) $gaps[] = 'Phiếu giao thiếu bên giao, bên nhận hoặc chuyến xe.';
                $quantities = DB::table('so_luong_giao_nhan')->where('chi_tiet_giao_nhan_id', $line->chi_tiet_giao_nhan_id)->where('lo_nong_san_id', $lot->lo_nong_san_id)->get();
                if ($quantities->isEmpty()) $gaps[] = 'Thiếu số lượng giao đã chốt của lô.';
                if (in_array($unit->trang_thai, ['DA_GIAO', 'GIAO_MOT_PHAN', 'TRA_VE']) && (!$line->thoi_gian_giao_nhan || $quantities->contains(fn ($q) => $q->so_luong_nhan === null))) $gaps[] = 'Thiếu thời gian hoặc kết quả thực nhận.';
            }
        }
        return array_values(array_unique($gaps));
    }
}

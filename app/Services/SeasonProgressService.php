<?php

namespace App\Services;

use App\Models\LichSuTrangThaiMuaVu;
use App\Models\MuaVu;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeasonProgressService
{
    public function recordHarvest(MuaVu $season): void
    {
        $season = MuaVu::lockForUpdate()->findOrFail($season->mua_vu_id);

        if (!in_array($season->trang_thai, ['DANG_CANH_TAC', 'DANG_THU_HOACH'], true)) {
            throw ValidationException::withMessages([
                'mua_vu_id' => 'Chỉ có thể thu hoạch mùa vụ đang canh tác hoặc đang thu hoạch.',
            ]);
        }

        // Thu hoạch có thể diễn ra nhiều đợt; chỉ người dùng mới chốt hoàn thành vụ.
        if ($season->trang_thai === 'DANG_CANH_TAC') {
            $season->update(['trang_thai' => 'DANG_THU_HOACH']);
        }
    }

    public function startHarvest(MuaVu $season, int $userId, string $reason = 'Bắt đầu giai đoạn thu hoạch nông sản'): MuaVu
    {
        return DB::transaction(function () use ($season, $userId, $reason) {
            $season = MuaVu::lockForUpdate()->findOrFail($season->mua_vu_id);
            if ($season->trang_thai !== 'DANG_CANH_TAC') {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Chỉ có thể chuyển sang thu hoạch đối với mùa vụ đang trong giai đoạn canh tác.',
                ]);
            }

            $this->transition($season, 'DANG_THU_HOACH', $userId, $reason);
            return $season->fresh();
        });
    }

    public function complete(MuaVu $season, int $userId, string $reason): MuaVu
    {
        return DB::transaction(function () use ($season, $userId, $reason) {
            $season = MuaVu::lockForUpdate()->findOrFail($season->mua_vu_id);
            if ($season->trang_thai !== 'DANG_THU_HOACH') {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Chỉ kết thúc mùa vụ đang ở trạng thái Đang thu hoạch.',
                ]);
            }

            $hasHarvest = $season->loNongSan()->where('loai_lo', 'NGUYEN_LIEU')->exists()
                || $season->nhatKyCanhTac()->where('loai_hoat_dong', 'THU_HOACH')->where('trang_thai', 'DA_XAC_NHAN')->exists();
            if (!$hasHarvest) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Cần có lô thu hoạch hoặc nhật ký thu hoạch đã xác nhận trước khi kết thúc mùa vụ.',
                ]);
            }

            $this->transition($season, 'HOAN_THANH', $userId, $reason);
            return $season->fresh();
        });
    }

    public function reopen(MuaVu $season, int $userId, string $reason): MuaVu
    {
        return DB::transaction(function () use ($season, $userId, $reason) {
            $season = MuaVu::lockForUpdate()->findOrFail($season->mua_vu_id);
            if ($season->trang_thai !== 'HOAN_THANH') {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Chỉ có thể mở lại mùa vụ đã hoàn thành.',
                ]);
            }

            $this->transition($season, 'DANG_THU_HOACH', $userId, $reason);
            return $season->fresh();
        });
    }

    public function cancel(MuaVu $season, int $userId, string $reason): MuaVu
    {
        return DB::transaction(function () use ($season, $userId, $reason) {
            $season = MuaVu::lockForUpdate()->findOrFail($season->mua_vu_id);
            if ($season->trang_thai === 'HOAN_THANH') {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Mùa vụ đã hoàn thành không thể hủy.',
                ]);
            }

            if ($season->loNongSan()->where('loai_lo', 'NGUYEN_LIEU')->exists()) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Mùa vụ đã phát sinh lô nông sản trong chuỗi cung ứng, không thể hủy bỏ.',
                ]);
            }

            $this->transition($season, 'HUY', $userId, $reason);
            return $season->fresh();
        });
    }

    private function transition(MuaVu $season, string $newStatus, int $userId, string $reason): void
    {
        $oldStatus = $season->trang_thai;
        $season->update(['trang_thai' => $newStatus]);
        LichSuTrangThaiMuaVu::create([
            'mua_vu_id' => $season->mua_vu_id,
            'tai_khoan_id' => $userId,
            'trang_thai_cu' => $oldStatus,
            'trang_thai_moi' => $newStatus,
            'ly_do' => trim($reason),
            'thoi_gian' => now(),
        ]);
    }
}

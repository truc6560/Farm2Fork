<?php

namespace App\Services;

use App\Models\LoNongSan;

class QualityGateService
{
    /**
     * Lô nguyên liệu chỉ được sơ chế khi phiếu kiểm định gần nhất đã đạt.
     */
    public function passedInboundInspection(LoNongSan $lot): bool
    {
        if ($lot->loai_lo !== 'NGUYEN_LIEU' || $lot->trang_thai !== 'CHO_SO_CHE') {
            return false;
        }

        return $this->latestConclusion($lot, ['NHAP_KHO', 'KIEM_DINH_LAI']) === 'DAT';
    }

    /**
     * Lô thành phẩm chỉ được nhập/xuất kho sau khi QC xuất xưởng đạt.
     */
    public function passedOutboundInspection(LoNongSan $lot): bool
    {
        if ($lot->loai_lo !== 'THANH_PHAM' || $lot->trang_thai !== 'DAT_KIEM_DINH') {
            return false;
        }

        return $this->latestConclusion($lot, ['TRUOC_XUAT_XUONG', 'KIEM_DINH_LAI']) === 'DAT';
    }

    private function latestConclusion(LoNongSan $lot, array $types): ?string
    {
        return $lot->phieuKiemDinh()
            ->whereIn('loai_kiem_dinh', $types)
            ->orderByDesc('ngay_kiem_dinh')
            ->orderByDesc('phieu_id')
            ->value('ket_luan');
    }
}

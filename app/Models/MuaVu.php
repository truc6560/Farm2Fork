<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MuaVu extends Model
{
    use HasFactory;

    protected $table = 'mua_vu';
    protected $primaryKey = 'mua_vu_id';

    protected $fillable = [
        'vung_trong_id',
        'san_pham_id',
        'ma_mua_vu',
        'ten_mua_vu',
        'giong_cay_trong',
        'dien_tich_canh_tac_ha',
        'ngay_gieo',
        'ngay_thu_hoach_du_kien',
        'san_luong_du_kien',
        'don_vi_san_luong',
        'trang_thai',
    ];

    protected $casts = [
        'dien_tich_canh_tac_ha' => 'decimal:3',
        'san_luong_du_kien' => 'decimal:3',
        'ngay_gieo' => 'date',
        'ngay_thu_hoach_du_kien' => 'date',
    ];

    public const TRANG_THAI_LABELS = [
        'CHUAN_BI' => 'Chuẩn bị giống / đất',
        'DANG_CANH_TAC' => 'Đang canh tác',
        'DANG_THU_HOACH' => 'Đang thu hoạch',
        'HOAN_THANH' => 'Đã hoàn thành',
        'HUY' => 'Hủy vụ',
    ];

    public function vungTrong()
    {
        return $this->belongsTo(VungTrong::class, 'vung_trong_id', 'vung_trong_id');
    }

    public function sanPham()
    {
        return $this->belongsTo(DanhMucSanPham::class, 'san_pham_id', 'san_pham_id');
    }

    public function nhatKyCanhTac()
    {
        return $this->hasMany(NhatKyCanhTac::class, 'mua_vu_id', 'mua_vu_id')
            ->orderBy('ngay_thuc_hien', 'desc');
    }

    public function loNongSan()
    {
        return $this->hasMany(LoNongSan::class, 'mua_vu_id', 'mua_vu_id');
    }

    public function lichSuTrangThai()
    {
        return $this->hasMany(LichSuTrangThaiMuaVu::class, 'mua_vu_id', 'mua_vu_id')
            ->orderByDesc('thoi_gian');
    }

    /**
     * Kiểm tra trạng thái cách ly thuốc BVTV / vật tư của mùa vụ tại một ngày nhất định.
     */
    public function getThongTinCachLy($ngayKiemTra = null): array
    {
        $checkDate = $ngayKiemTra ? \Carbon\Carbon::parse($ngayKiemTra) : \Carbon\Carbon::now();

        // Lấy tất cả vật tư sử dụng trong các nhật ký canh tác của mùa vụ (loại trừ các bản ghi bị thay thế)
        $materialUsages = ChiTietSuDungVatTu::with(['vatTu', 'nhatKy'])
            ->whereHas('nhatKy', function ($q) {
                $q->where('mua_vu_id', $this->mua_vu_id)
                    ->where('trang_thai', 'DA_XAC_NHAN');
            })
            ->get();

        $maxQuarantineEnd = null;
        $latestUsage = null;

        foreach ($materialUsages as $item) {
            $appliedDays = (int)($item->thoi_gian_cach_ly_ap_dung_ngay ?: ($item->vatTu?->thoi_gian_cach_ly_ngay ?? 0));
            if ($appliedDays <= 0) {
                continue;
            }

            $usedDate = \Carbon\Carbon::parse($item->thoi_gian_su_dung ?? $item->nhatKy?->ngay_thuc_hien);
            $quarantineEnd = (clone $usedDate)->addDays($appliedDays)->endOfDay();

            if ($maxQuarantineEnd === null || $quarantineEnd->gt($maxQuarantineEnd)) {
                $maxQuarantineEnd = $quarantineEnd;
                $latestUsage = $item;
            }
        }

        if (!$maxQuarantineEnd || !$latestUsage) {
            return [
                'co_lich_su_cach_ly' => false,
                'dang_cach_ly' => false,
                'ngay_het_cach_ly' => null,
                'so_ngay_con_lai' => 0,
                'ten_vat_tu' => null,
                'lieu_luong' => null,
                'ngay_su_dung' => null,
                'so_ngay_cach_ly' => 0,
            ];
        }

        $isUnderQuarantine = $checkDate->lte($maxQuarantineEnd);
        $daysRemaining = $isUnderQuarantine ? max(1, (int)ceil($checkDate->diffInHours($maxQuarantineEnd) / 24)) : 0;

        return [
            'co_lich_su_cach_ly' => true,
            'dang_cach_ly' => $isUnderQuarantine,
            'ngay_het_cach_ly' => $maxQuarantineEnd,
            'so_ngay_con_lai' => $daysRemaining,
            'ten_vat_tu' => $latestUsage->vatTu?->ten_thuong_mai ?? 'Thuốc BVTV / Vật tư',
            'lieu_luong' => $latestUsage->lieu_luong . ' ' . ($latestUsage->don_vi_tinh ?? ''),
            'ngay_su_dung' => \Carbon\Carbon::parse($latestUsage->thoi_gian_su_dung ?? $latestUsage->nhatKy?->ngay_thuc_hien),
            'so_ngay_cach_ly' => (int)($latestUsage->thoi_gian_cach_ly_ap_dung_ngay ?: ($latestUsage->vatTu?->thoi_gian_cach_ly_ngay ?? 0)),
        ];
    }

    public function getTrangThaiBadgeClass(?string $status = null): string
    {
        $st = $status ?? $this->trang_thai;
        return match ($st) {
            'DANG_CANH_TAC' => 'f2f-badge-active',
            'DANG_THU_HOACH' => 'f2f-badge-warning',
            'HOAN_THANH' => 'f2f-badge-success',
            'HUY' => 'f2f-badge-danger',
            default => 'f2f-badge-neutral',
        };
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonViDongGoiSscc extends Model
{
    use HasFactory;

    protected $table = 'don_vi_dong_goi_sscc';
    protected $primaryKey = 'sscc_id';
    public $timestamps = false;

    protected $fillable = [
        'ma_sscc',
        'loai_don_vi',
        'kho_lanh_id',
        'ngay_dong_goi',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'ngay_dong_goi' => 'datetime',
    ];

    public const LOAI_DON_VI_LABELS = [
        'THUNG_CARTON' => 'Thùng carton',
        'PALLET' => 'Pallet tiêu chuẩn',
        'KIEN_HANG' => 'Kiện hàng vận chuyển',
    ];

    public const TRANG_THAI_LABELS = [
        'TRA_VE' => 'Từ chối / Chờ xử lý trả về',
        'GIAO_MOT_PHAN' => 'Giao một phần',
        'DA_DONG_GOI' => 'Đã đóng gói',
        'DA_NHAP_KHO' => 'Đã nhập kho lạnh',
        'TRONG_KHO' => 'Đang lưu kho lạnh',
        'DANG_VAN_CHUYEN' => 'Đang vận chuyển',
        'DA_GIAO' => 'Đã giao hàng',
        'CACH_LY' => 'Cách ly chờ xử lý',
        'KHONG_DAT' => 'Không còn đạt yêu cầu',
    ];

    public function khoLanh()
    {
        return $this->belongsTo(KhoLanh::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function chiTiet()
    {
        return $this->hasMany(ChiTietDongGoiSscc::class, 'sscc_id', 'sscc_id');
    }

    public function chiTietGiaoNhan()
    {
        return $this->hasMany(ChiTietGiaoNhan::class, 'sscc_id', 'sscc_id');
    }

    public function loNongSan()
    {
        return $this->belongsToMany(
            LoNongSan::class,
            'chi_tiet_dong_goi_sscc',
            'sscc_id',
            'lo_nong_san_id'
        )->withPivot('so_luong', 'don_vi_tinh', 'ghi_chu');
    }

    public function getLoaiDonViLabelAttribute(): string
    {
        return self::LOAI_DON_VI_LABELS[$this->loai_don_vi] ?? $this->loai_don_vi;
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai] ?? $this->trang_thai;
    }

    public function getTrangThaiBadgeClass(): string
    {
        return match ($this->trang_thai) {
            'DA_DONG_GOI' => 'f2f-badge-active',
            'DA_NHAP_KHO', 'TRONG_KHO' => 'f2f-badge-success',
            'DANG_VAN_CHUYEN' => 'f2f-badge-warning',
            'DA_GIAO' => 'f2f-badge-success',
            'CACH_LY' => 'f2f-badge-warning',
            'KHONG_DAT' => 'f2f-badge-danger',
            default => 'f2f-badge-neutral',
        };
    }

    /**
     * Human Readable Interpretation (HRI) with AI (00).
     */
    public function getHriAttribute(): string
    {
        return app(\App\Services\Gs1Service::class)->formatHri($this->ma_sscc, true);
    }

    /**
     * Scalable SVG Barcode GS1-128 (Subset C) for this logistics unit.
     */
    public function getBarcodeSvgAttribute(): string
    {
        return app(\App\Services\Gs1Service::class)->generateBarcodeSvg($this->ma_sscc);
    }

    /**
     * Check if this unit was generated under testing/demo prefix.
     */
    public function getIsDemoAttribute(): bool
    {
        return app(\App\Services\Gs1Service::class)->isDemoMode();
    }

    /**
     * Generate standard GS1 SSCC 18-digit code with Check Digit (Modulo 10).
     */
    public static function generateSscc(?string $companyPrefix = null, ?int $extensionDigit = null): string
    {
        return app(\App\Services\Gs1Service::class)->generateNextSscc($extensionDigit, $companyPrefix);
    }
}

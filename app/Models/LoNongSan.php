<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoNongSan extends Model
{
    use HasFactory;

    protected $table = 'lo_nong_san';
    protected $primaryKey = 'lo_nong_san_id';

    protected $fillable = [
        'san_pham_id',
        'mua_vu_id',
        'to_chuc_nguon_id',
        'kho_lanh_id',
        'ma_lo_nha_cung_cap',
        'loai_lo',
        'ma_lo',
        'digital_link',
        'so_luong',
        'don_vi_tinh',
        'ngay_thu_hoach',
        'ngay_san_xuat',
        'han_su_dung',
        'ngay_tao',
        'trang_thai',
        'ly_do_thu_hoi',
        'thoi_diem_thu_hoi',
    ];

    protected $casts = [
        'so_luong' => 'decimal:3',
        'ngay_thu_hoach' => 'date',
        'ngay_san_xuat' => 'date',
        'han_su_dung' => 'date',
        'ngay_tao' => 'datetime',
        'thoi_diem_thu_hoi' => 'datetime',
    ];

    public const TRANG_THAI_LABELS = [
        'MOI_TAO' => 'Mới tạo',
        'CHO_KIEM_DINH' => 'Chờ kiểm định',
        'DAT_KIEM_DINH' => 'Đạt kiểm định',
        'CHO_SO_CHE' => 'Chờ sơ chế',
        'DA_SO_CHE' => 'Đã sơ chế',
        'DANG_DONG_GOI' => 'Đang đóng gói',
        'DA_DONG_GOI' => 'Đã đóng gói',
        'CHO_KIEM_DINH_XUAT_XUONG' => 'Chờ kiểm định xuất xưởng',
        'LUU_KHO' => 'Lưu kho lạnh',
        'DANG_VAN_CHUYEN' => 'Đang vận chuyển',
        'DA_GIAO' => 'Đã giao hàng',
        'CACH_LY' => 'Cách ly chờ đánh giá',
        'KHONG_DAT' => 'Không còn đạt yêu cầu',
        'TRA_NHA_CUNG_CAP' => 'Trả nhà cung cấp',
        'THU_HOI' => 'Thu hồi',
    ];

    public function sanPham()
    {
        return $this->belongsTo(DanhMucSanPham::class, 'san_pham_id', 'san_pham_id');
    }

    public function muaVu()
    {
        return $this->belongsTo(MuaVu::class, 'mua_vu_id', 'mua_vu_id');
    }

    public function toChucNguon()
    {
        return $this->belongsTo(ToChuc::class, 'to_chuc_nguon_id', 'to_chuc_id');
    }

    public function khoLanh()
    {
        return $this->belongsTo(KhoLanh::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    /**
     * Raw materials used to make this finished lot (if loai_lo = THANH_PHAM).
     */
    public function nguyenLieuDauVao()
    {
        return $this->belongsToMany(
            LoNongSan::class,
            'thanh_phan_lo_nong_san',
            'lo_thanh_pham_id',
            'lo_nguyen_lieu_id'
        )->withPivot('khoi_luong_su_dung', 'don_vi_tinh', 'ghi_chu');
    }

    /**
     * Finished product lots produced from this raw lot (if loai_lo = NGUYEN_LIEU).
     */
    public function thanhPhamDauRa()
    {
        return $this->belongsToMany(
            LoNongSan::class,
            'thanh_phan_lo_nong_san',
            'lo_nguyen_lieu_id',
            'lo_thanh_pham_id'
        )->withPivot('khoi_luong_su_dung', 'don_vi_tinh', 'ghi_chu');
    }

    public function chiTietDongGoi()
    {
        return $this->hasMany(ChiTietDongGoiSscc::class, 'lo_nong_san_id', 'lo_nong_san_id');
    }

    public function donViSscc()
    {
        return $this->belongsToMany(
            DonViDongGoiSscc::class,
            'chi_tiet_dong_goi_sscc',
            'lo_nong_san_id',
            'sscc_id'
        )->withPivot('so_luong', 'don_vi_tinh', 'ghi_chu');
    }

    public function phieuKiemDinh()
    {
        return $this->hasMany(PhieuKiemDinh::class, 'lo_nong_san_id', 'lo_nong_san_id');
    }

    public function lichSuTrangThai()
    {
        return $this->hasMany(LichSuTrangThaiLo::class, 'lo_nong_san_id', 'lo_nong_san_id');
    }

    public function getTrangThaiBadgeClass(): string
    {
        return match ($this->trang_thai) {
            'MOI_TAO' => 'f2f-badge-warning',
            'CHO_KIEM_DINH' => 'f2f-badge-warning',
            'DAT_KIEM_DINH' => 'f2f-badge-success',
            'CHO_SO_CHE' => 'f2f-badge-active',
            'DA_SO_CHE' => 'f2f-badge-success',
            'DANG_DONG_GOI' => 'f2f-badge-active',
            'DA_DONG_GOI' => 'f2f-badge-success',
            'CHO_KIEM_DINH_XUAT_XUONG' => 'f2f-badge-warning',
            'LUU_KHO' => 'f2f-badge-neutral',
            'DANG_VAN_CHUYEN' => 'f2f-badge-active',
            'DA_GIAO' => 'f2f-badge-success',
            'CACH_LY' => 'f2f-badge-warning',
            'KHONG_DAT' => 'f2f-badge-danger',
            'TRA_NHA_CUNG_CAP' => 'f2f-badge-danger',
            'THU_HOI' => 'f2f-badge-danger',
            default => 'f2f-badge-neutral',
        };
    }

    public function getDigitalLinkUrlAttribute(): string
    {
        $digitalLinks = app(\App\Services\Gs1DigitalLinkService::class);
        $gtin = $this->sanPham?->gtin;
        if (!$digitalLinks->validGtin($gtin) || !$digitalLinks->validLot($this->ma_lo)) {
            return route('trace.show', ['code' => $this->ma_lo]);
        }

        return $digitalLinks->productLotUrl($gtin, $this->ma_lo);
    }

    public function getQrSvgAttribute(): string
    {
        return \App\Services\QrCodeService::svg($this->digital_link_url, 200, 4);
    }
}


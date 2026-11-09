<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanhBaoChuoiLanh extends Model
{
    use HasFactory;

    protected $table = 'canh_bao_chuoi_lanh';
    protected $primaryKey = 'canh_bao_id';
    public $timestamps = false;

    protected $fillable = [
        'thiet_bi_id',
        'chuyen_id',
        'cau_hinh_id',
        'du_lieu_kich_hoat_id',
        'nguoi_xu_ly_id',
        'loai_canh_bao',
        'gia_tri_thuc_te',
        'nguong_tai_thoi_diem',
        'thoi_gian_phat_hien',
        'trang_thai',
        'hanh_dong_khac_phuc',
        'ket_qua_khac_phuc',
        'danh_gia_lo',
        'ghi_chu_danh_gia',
        'thoi_gian_xu_ly',
    ];

    protected $casts = [
        'thoi_gian_phat_hien' => 'datetime',
        'thoi_gian_xu_ly' => 'datetime',
        'gia_tri_thuc_te' => 'decimal:2',
        'nguong_tai_thoi_diem' => 'decimal:2',
    ];

    public const LOAI_CANH_BAO_LABELS = [
        'NHIET_DO_CAO' => 'Nhiệt độ vượt ngưỡng trên (Quá nhiệt)',
        'NHIET_DO_THAP' => 'Nhiệt độ dưới ngưỡng dưới (Đông đá)',
        'DO_AM_VUOT_NGUONG' => 'Độ ẩm vượt ngưỡng cho phép',
        'MAT_TIN_HIEU' => 'Mất kết nối tín hiệu cảm biến IoT',
    ];

    public const TRANG_THAI_LABELS = [
        'CHUA_XU_LY' => 'Chưa xử lý',
        'DANG_XU_LY' => 'Đang xử lý',
        'DA_XU_LY' => 'Đã khắc phục xong',
        'BO_QUA' => 'Bỏ qua / Cảnh báo giả',
    ];

    public function thietBi()
    {
        return $this->belongsTo(ThietBiIot::class, 'thiet_bi_id', 'thiet_bi_id');
    }

    public function chuyenVanChuyen()
    {
        return $this->belongsTo(ChuyenVanChuyen::class, 'chuyen_id', 'chuyen_id');
    }

    public function nguoiXuLy()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_xu_ly_id', 'tai_khoan_id');
    }

    public function donViSscc()
    {
        return $this->belongsToMany(
            DonViDongGoiSscc::class,
            'canh_bao_sscc',
            'canh_bao_id',
            'sscc_id'
        )->withPivot('ket_qua_danh_gia', 'ghi_chu');
    }

    public function getLoaiCanhBaoLabelAttribute(): string
    {
        return self::LOAI_CANH_BAO_LABELS[$this->loai_canh_bao] ?? $this->loai_canh_bao;
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai] ?? $this->trang_thai;
    }

    public function getTrangThaiBadgeClass(): string
    {
        return match ($this->trang_thai) {
            'CHUA_XU_LY' => 'f2f-badge-danger',
            'DANG_XU_LY' => 'f2f-badge-warning',
            'DA_XU_LY' => 'f2f-badge-success',
            default => 'f2f-badge-neutral',
        };
    }
}


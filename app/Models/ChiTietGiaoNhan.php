<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietGiaoNhan extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_giao_nhan';
    protected $primaryKey = 'chi_tiet_giao_nhan_id';
    public $timestamps = false;

    protected $fillable = [
        'phieu_giao_hang_id',
        'sscc_id',
        'trang_thai_giao_nhan',
        'thoi_gian_giao_nhan',
        'ten_nguoi_nhan_thuc_te',
        'anh_minh_chung_url',
        'tinh_trang_bao_goi',
        'mo_ta_hu_hong',
        'ghi_chu',
    ];

    protected $casts = [
        'thoi_gian_giao_nhan' => 'datetime',
    ];

    public const TRANG_THAI_LABELS = [
        'GIAO_MOT_PHAN' => 'Giao một phần',
        'CHO_GIAO' => 'Chờ giao hàng',
        'DANG_GIAO' => 'Đang giao nhận',
        'DA_GIAO' => 'Đã giao thành công',
        'TRA_VE' => 'Từ chối / Trả về',
    ];

    public const TINH_TRANG_BAO_GOI_LABELS = [
        'NGUYEN_VEN' => 'Bao gói nguyên vẹn, tem niêm phong đạt chuẩn',
        'HU_HONG_MOT_PHAN' => 'Hư hỏng một phần / móp méo vỏ ngoài',
        'HU_HONG_TOAN_BO' => 'Hư hỏng toàn bộ / rách vỡ nặng',
    ];

    public function phieuGiaoHang()
    {
        return $this->belongsTo(PhieuGiaoHang::class, 'phieu_giao_hang_id', 'phieu_giao_hang_id');
    }

    public function donViDongGoi()
    {
        return $this->belongsTo(DonViDongGoiSscc::class, 'sscc_id', 'sscc_id');
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai_giao_nhan] ?? $this->trang_thai_giao_nhan;
    }

    public function getTinhTrangBaoGoiLabelAttribute(): string
    {
        return self::TINH_TRANG_BAO_GOI_LABELS[$this->tinh_trang_bao_goi] ?? $this->tinh_trang_bao_goi;
    }
}


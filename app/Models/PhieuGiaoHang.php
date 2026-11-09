<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuGiaoHang extends Model
{
    use HasFactory;

    protected $table = 'phieu_giao_hang';
    protected $primaryKey = 'phieu_giao_hang_id';
    public $timestamps = true;

    protected $fillable = [
        'ma_phieu_giao_hang',
        'chuyen_id',
        'to_chuc_nhan_id',
        'to_chuc_giao_id',
        'nguoi_lap_id',
        'ten_nguoi_nhan',
        'sdt_nguoi_nhan',
        'dia_chi_giao',
        'ngay_giao_du_kien',
        'yeu_cau_nhiet_do_min',
        'yeu_cau_nhiet_do_max',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'ngay_giao_du_kien' => 'datetime',
        'yeu_cau_nhiet_do_min' => 'decimal:2',
        'yeu_cau_nhiet_do_max' => 'decimal:2',
    ];

    public const TRANG_THAI_LABELS = [
        'TRA_VE' => 'Từ chối / Chờ xử lý trả về',
        'GIAO_MOT_PHAN' => 'Giao một phần',
        'NHAP' => 'Mới tạo phiếu',
        'CHO_GIAO' => 'Chờ giao hàng',
        'DANG_GIAO' => 'Đang trên đường giao',
        'DA_GIAO' => 'Đã giao thành công',
        'HUY' => 'Đã hủy đơn giao',
    ];

    public function chuyenVanChuyen()
    {
        return $this->belongsTo(ChuyenVanChuyen::class, 'chuyen_id', 'chuyen_id');
    }

    public function toChucNhan()
    {
        return $this->belongsTo(ToChuc::class, 'to_chuc_nhan_id', 'to_chuc_id');
    }

    public function nguoiLap()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_lap_id', 'tai_khoan_id');
    }

    public function chiTietGiaoNhan()
    {
        return $this->hasMany(ChiTietGiaoNhan::class, 'phieu_giao_hang_id', 'phieu_giao_hang_id');
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai] ?? $this->trang_thai;
    }

    public function getTrangThaiBadgeClass(): string
    {
        return match ($this->trang_thai) {
            'DA_GIAO' => 'f2f-badge-success',
            'DANG_GIAO' => 'f2f-badge-active',
            'CHO_GIAO', 'NHAP' => 'f2f-badge-warning',
            'HUY' => 'f2f-badge-error',
            default => 'f2f-badge-neutral',
        };
    }
}


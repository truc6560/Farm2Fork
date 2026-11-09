<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChuyenVanChuyen extends Model
{
    use HasFactory;

    protected $table = 'chuyen_van_chuyen';
    protected $primaryKey = 'chuyen_id';
    public $timestamps = false;

    protected $fillable = [
        'ma_chuyen',
        'phuong_tien_id',
        'tai_xe_id',
        'nguoi_lap_id',
        'diem_di',
        'diem_den',
        'thoi_gian_xuat_phat',
        'thoi_gian_giao_du_kien',
        'thoi_gian_giao_thuc_te',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'thoi_gian_xuat_phat' => 'datetime',
        'thoi_gian_giao_du_kien' => 'datetime',
        'thoi_gian_giao_thuc_te' => 'datetime',
    ];

    public const TRANG_THAI_LABELS = [
        'CHUAN_BI' => 'Chuẩn bị xếp hàng',
        'SAN_SANG' => 'Sẵn sàng khởi hành',
        'DANG_VAN_CHUYEN' => 'Đang trên hành trình',
        'HOAN_THANH' => 'Đã hoàn thành chuyến',
        'HUY' => 'Đã hủy chuyến',
    ];

    public function phuongTien()
    {
        return $this->belongsTo(PhuongTien::class, 'phuong_tien_id', 'phuong_tien_id');
    }

    public function taiXe()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'tai_xe_id', 'tai_khoan_id');
    }

    public function nguoiLap()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_lap_id', 'tai_khoan_id');
    }

    public function suKienHanhTrinh()
    {
        return $this->hasMany(SuKienHanhTrinh::class, 'chuyen_id', 'chuyen_id')->orderBy('thoi_gian', 'asc');
    }

    public function phieuGiaoHang()
    {
        return $this->hasMany(PhieuGiaoHang::class, 'chuyen_id', 'chuyen_id');
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai] ?? $this->trang_thai;
    }

    public function getTrangThaiBadgeClass(): string
    {
        return match ($this->trang_thai) {
            'DANG_VAN_CHUYEN' => 'f2f-badge-active',
            'HOAN_THANH' => 'f2f-badge-success',
            'SAN_SANG', 'CHUAN_BI' => 'f2f-badge-warning',
            'HUY' => 'f2f-badge-error',
            default => 'f2f-badge-neutral',
        };
    }
}


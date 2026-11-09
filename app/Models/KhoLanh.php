<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KhoLanh extends Model
{
    use HasFactory;

    protected $table = 'kho_lanh';
    protected $primaryKey = 'kho_lanh_id';

    protected $fillable = [
        'ma_kho',
        'ten_kho',
        'dia_chi',
        'suc_chua',
        'don_vi_suc_chua',
        'trang_thai',
    ];

    protected $casts = [
        'suc_chua' => 'decimal:3',
    ];

    public const TRANG_THAI_LABELS = [
        'DANG_HOAT_DONG' => 'Đang hoạt động',
        'BAO_TRI' => 'Đang bảo trì',
        'TAM_NGUNG' => 'Tạm ngưng tiếp nhận',
        'NGUNG_HOAT_DONG' => 'Ngưng hoạt động',
    ];

    public const DON_VI_LABELS = [
        'kg' => 'Kilogram (kg)',
        'tan' => 'Tấn',
        'm3' => 'Mét khối (m³)',
        'pallet' => 'Pallet chuẩn',
        'thung' => 'Thùng chuẩn',
    ];

    public function getTrangThaiBadgeClass(): string
    {
        return match ($this->trang_thai) {
            'DANG_HOAT_DONG' => 'f2f-badge-success',
            'BAO_TRI' => 'f2f-badge-warning',
            'TAM_NGUNG' => 'f2f-badge-warning',
            'NGUNG_HOAT_DONG' => 'f2f-badge-danger',
            default => 'f2f-badge-neutral',
        };
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai] ?? $this->trang_thai;
    }

    public function loNongSan()
    {
        return $this->hasMany(LoNongSan::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function donViDongGoi()
    {
        return $this->hasMany(DonViDongGoiSscc::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function thietBiIot()
    {
        return $this->hasMany(ThietBiIot::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function nhanVien()
    {
        return $this->belongsToMany(TaiKhoanHeThong::class, 'nhan_vien_kho', 'kho_lanh_id', 'tai_khoan_id')
            ->withPivot('ngay_bat_dau', 'ngay_ket_thuc', 'ghi_chu');
    }
}


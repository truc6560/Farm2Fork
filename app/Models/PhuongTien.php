<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhuongTien extends Model
{
    use HasFactory;

    protected $table = 'phuong_tien';
    protected $primaryKey = 'phuong_tien_id';
    public $timestamps = false;

    protected $fillable = [
        'bien_so',
        'loai_xe',
        'tai_trong',
        'don_vi_tai_trong',
        'co_he_thong_lam_lanh',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'co_he_thong_lam_lanh' => 'boolean',
        'tai_trong' => 'decimal:2',
    ];

    public const TRANG_THAI_LABELS = [
        'SAN_SANG' => 'Sẵn sàng vận hành',
        'DANG_HOAT_DONG' => 'Đang thực hiện chuyến',
        'BAO_DUONG' => 'Đang bảo dưỡng / Sửa chữa',
        'NGUNG_HOAT_DONG' => 'Ngừng hoạt động',
    ];

    public function chuyenVanChuyen()
    {
        return $this->hasMany(ChuyenVanChuyen::class, 'phuong_tien_id', 'phuong_tien_id');
    }

    public function thietBiIot()
    {
        return $this->hasMany(ThietBiIot::class, 'phuong_tien_id', 'phuong_tien_id');
    }

    public function getTrangThaiLabelAttribute(): string
    {
        return self::TRANG_THAI_LABELS[$this->trang_thai] ?? $this->trang_thai;
    }
}


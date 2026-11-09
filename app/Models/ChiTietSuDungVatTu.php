<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietSuDungVatTu extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_su_dung_vat_tu';
    protected $primaryKey = 'chi_tiet_id';
    public $timestamps = false;

    protected $fillable = [
        'nhat_ky_id',
        'vat_tu_id',
        'lieu_luong',
        'don_vi_tinh',
        'thoi_gian_su_dung',
        'thoi_gian_cach_ly_ap_dung_ngay',
        'ghi_chu',
    ];

    protected $casts = [
        'lieu_luong' => 'decimal:4',
        'thoi_gian_su_dung' => 'datetime',
        'thoi_gian_cach_ly_ap_dung_ngay' => 'integer',
    ];

    public function getLuongSuDungAttribute()
    {
        return $this->lieu_luong;
    }

    public function nhatKy()
    {
        return $this->belongsTo(NhatKyCanhTac::class, 'nhat_ky_id', 'nhat_ky_id');
    }

    public function vatTu()
    {
        return $this->belongsTo(DanhMucVatTu::class, 'vat_tu_id', 'vat_tu_id');
    }
}


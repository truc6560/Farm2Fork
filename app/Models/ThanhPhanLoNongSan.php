<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThanhPhanLoNongSan extends Model
{
    use HasFactory;

    protected $table = 'thanh_phan_lo_nong_san';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'lo_thanh_pham_id',
        'lo_nguyen_lieu_id',
        'khoi_luong_su_dung',
        'don_vi_tinh',
        'ghi_chu',
    ];

    protected $casts = [
        'khoi_luong_su_dung' => 'decimal:3',
    ];

    public function loThanhPham()
    {
        return $this->belongsTo(LoNongSan::class, 'lo_thanh_pham_id', 'lo_nong_san_id');
    }

    public function loNguyenLieu()
    {
        return $this->belongsTo(LoNongSan::class, 'lo_nguyen_lieu_id', 'lo_nong_san_id');
    }
}


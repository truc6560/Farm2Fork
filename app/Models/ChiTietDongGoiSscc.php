<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietDongGoiSscc extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_dong_goi_sscc';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'sscc_id',
        'lo_nong_san_id',
        'so_luong',
        'don_vi_tinh',
        'ghi_chu',
    ];

    protected $casts = [
        'so_luong' => 'decimal:3',
    ];

    public function donViDongGoi()
    {
        return $this->belongsTo(DonViDongGoiSscc::class, 'sscc_id', 'sscc_id');
    }

    public function loNongSan()
    {
        return $this->belongsTo(LoNongSan::class, 'lo_nong_san_id', 'lo_nong_san_id');
    }
}


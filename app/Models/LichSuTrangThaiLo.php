<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichSuTrangThaiLo extends Model
{
    use HasFactory;

    protected $table = 'lich_su_trang_thai_lo';
    protected $primaryKey = 'lich_su_id';
    public $timestamps = false;

    protected $fillable = [
        'lo_nong_san_id',
        'tai_khoan_id',
        'trang_thai_cu',
        'trang_thai_moi',
        'ly_do',
        'thoi_gian',
    ];

    protected $casts = [
        'thoi_gian' => 'datetime',
    ];

    public function loNongSan()
    {
        return $this->belongsTo(LoNongSan::class, 'lo_nong_san_id', 'lo_nong_san_id');
    }

    public function nguoiThucHien()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'tai_khoan_id', 'tai_khoan_id');
    }

    public function taiKhoan()
    {
        return $this->nguoiThucHien();
    }
}


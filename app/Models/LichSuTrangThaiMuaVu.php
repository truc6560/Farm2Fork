<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuTrangThaiMuaVu extends Model
{
    protected $table = 'lich_su_trang_thai_mua_vu';
    protected $primaryKey = 'lich_su_id';

    protected $fillable = [
        'mua_vu_id', 'tai_khoan_id', 'trang_thai_cu', 'trang_thai_moi', 'ly_do', 'thoi_gian',
    ];

    protected $casts = ['thoi_gian' => 'datetime'];

    public function nguoiThucHien()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'tai_khoan_id', 'tai_khoan_id');
    }
}

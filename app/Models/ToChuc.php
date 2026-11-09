<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToChuc extends Model
{
    use HasFactory;

    protected $table = 'to_chuc';

    protected $primaryKey = 'to_chuc_id';

    public $timestamps = true;

    protected $fillable = [
        'ma_to_chuc',
        'ten_to_chuc',
        'loai_to_chuc',
        'ma_so_thue',
        'dia_chi',
        'email',
        'so_dien_thoai',
        'trang_thai',
    ];

    public function taiKhoanHeThong()
    {
        return $this->hasMany(TaiKhoanHeThong::class, 'to_chuc_id', 'to_chuc_id');
    }

    public function vungTrong()
    {
        return $this->hasMany(VungTrong::class, 'to_chuc_id', 'to_chuc_id');
    }

    public function loNongSan()
    {
        return $this->hasMany(LoNongSan::class, 'to_chuc_nguon_id', 'to_chuc_id');
    }

    public function phieuGiaoHang()
    {
        return $this->hasMany(PhieuGiaoHang::class, 'to_chuc_nhan_id', 'to_chuc_id');
    }
}

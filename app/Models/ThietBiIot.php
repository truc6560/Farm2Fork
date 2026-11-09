<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThietBiIot extends Model
{
    use HasFactory;

    protected $table = 'thiet_bi_iot';
    protected $primaryKey = 'thiet_bi_id';
    public $timestamps = false;

    protected $fillable = [
        'kho_lanh_id',
        'phuong_tien_id',
        'ma_thiet_bi',
        'loai_thiet_bi',
        'mqtt_topic',
        'tham_chieu_thong_tin_xac_thuc',
        'trang_thai',
        'ngay_dang_ky',
    ];

    protected $casts = [
        'ngay_dang_ky' => 'datetime',
    ];

    public const LOAI_THIET_BI_LABELS = [
        'CAM_BIEN_NHIET_DO_DO_AM' => 'Cảm biến nhiệt độ & độ ẩm',
        'CAM_BIEN_NHIET_DO' => 'Cảm biến nhiệt độ đơn',
        'GPS_TRACKER' => 'Thiết bị định vị GPS & nhiệt độ',
    ];

    public function khoLanh()
    {
        return $this->belongsTo(KhoLanh::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function phuongTien()
    {
        return $this->belongsTo(PhuongTien::class, 'phuong_tien_id', 'phuong_tien_id');
    }

    public function duLieuCamBien()
    {
        return $this->hasMany(DuLieuCamBien::class, 'thiet_bi_id', 'thiet_bi_id');
    }

    public function latestReading()
    {
        return $this->hasOne(DuLieuCamBien::class, 'thiet_bi_id', 'thiet_bi_id')->latestOfMany('thoi_gian_do');
    }

    public function canhBao()
    {
        return $this->hasMany(CanhBaoChuoiLanh::class, 'thiet_bi_id', 'thiet_bi_id');
    }
}


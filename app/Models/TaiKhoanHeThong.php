<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TaiKhoanHeThong extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'tai_khoan_he_thong';

    protected $primaryKey = 'tai_khoan_id';

    public $timestamps = true;

    protected $fillable = [
        'vai_tro_id',
        'to_chuc_id',
        'ho_ten',
        'email',
        'mat_khau_hash',
        'so_dien_thoai',
        'trang_thai',
    ];

    protected $hidden = [
        'mat_khau_hash',
    ];

    public function getAuthPassword(): string
    {
        return $this->mat_khau_hash;
    }

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id', 'vai_tro_id');
    }

    public function toChuc()
    {
        return $this->belongsTo(ToChuc::class, 'to_chuc_id', 'to_chuc_id');
    }

    public function vungTrongPhuTrach()
    {
        return $this->hasMany(VungTrong::class, 'nguoi_phu_trach_id', 'tai_khoan_id');
    }

    public function vungTrong()
    {
        return $this->belongsToMany(VungTrong::class, 'nhan_vien_vung_trong', 'tai_khoan_id', 'vung_trong_id')
            ->withPivot('ngay_phan_cong', 'ghi_chu');
    }

    public function nhatKyCanhTac()
    {
        return $this->hasMany(NhatKyCanhTac::class, 'tai_khoan_id', 'tai_khoan_id');
    }

    public function khoLanh()
    {
        return $this->belongsToMany(KhoLanh::class, 'nhan_vien_kho', 'tai_khoan_id', 'kho_lanh_id')
            ->withPivot('ngay_bat_dau', 'ngay_ket_thuc', 'ghi_chu');
    }

    public function chuyenVanChuyen()
    {
        return $this->hasMany(ChuyenVanChuyen::class, 'tai_xe_id', 'tai_khoan_id');
    }
}

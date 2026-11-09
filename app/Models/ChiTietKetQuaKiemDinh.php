<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietKetQuaKiemDinh extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_ket_qua_kiem_dinh';
    protected $primaryKey = 'ket_qua_id';
    public $timestamps = false;

    protected $fillable = [
        'phieu_id',
        'ten_tieu_chi',
        'gia_tri_tham_chieu',
        'gia_tri_thuc_te',
        'don_vi_do',
        'ket_qua',
        'ghi_chu',
    ];

    public function phieuKiemDinh()
    {
        return $this->belongsTo(PhieuKiemDinh::class, 'phieu_id', 'phieu_id');
    }
}


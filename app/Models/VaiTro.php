<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    protected $table = 'vai_tro';

    protected $primaryKey = 'vai_tro_id';

    public $timestamps = false;

    protected $fillable = [
        'ma_vai_tro',
        'ten_vai_tro',
        'mo_ta',
    ];

    public function taiKhoanHeThong()
    {
        return $this->hasMany(TaiKhoanHeThong::class, 'vai_tro_id', 'vai_tro_id');
    }
}

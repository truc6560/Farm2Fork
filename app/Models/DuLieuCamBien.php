<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuLieuCamBien extends Model
{
    use HasFactory;

    protected $table = 'du_lieu_cam_bien';
    protected $primaryKey = 'du_lieu_id';
    public $timestamps = false;

    protected $fillable = [
        'thiet_bi_id',
        'thoi_gian_do',
        'nhiet_do',
        'do_am',
        'trang_thai_tin_hieu',
        'received_at',
    ];

    protected $casts = [
        'thoi_gian_do' => 'datetime',
        'received_at' => 'datetime',
        'nhiet_do' => 'decimal:2',
        'do_am' => 'decimal:2',
    ];

    public function thietBi()
    {
        return $this->belongsTo(ThietBiIot::class, 'thiet_bi_id', 'thiet_bi_id');
    }
}


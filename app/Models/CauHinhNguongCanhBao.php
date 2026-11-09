<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CauHinhNguongCanhBao extends Model
{
    protected $table = 'cau_hinh_nguong_canh_bao';
    protected $primaryKey = 'cau_hinh_id';

    public $timestamps = false;

    protected $fillable = [
        'san_pham_id',
        'kho_lanh_id',
        'nhiet_do_min',
        'nhiet_do_max',
        'do_am_min',
        'do_am_max',
        'thoi_gian_vuot_nguong_phut',
        'thoi_gian_mat_tin_hieu_phut',
        'hieu_luc_tu',
        'hieu_luc_den',
        'trang_thai',
        'mo_ta',
    ];

    protected $casts = [
        'nhiet_do_min'             => 'decimal:2',
        'nhiet_do_max'             => 'decimal:2',
        'do_am_min'                => 'decimal:2',
        'do_am_max'                => 'decimal:2',
        'thoi_gian_vuot_nguong_phut' => 'integer',
        'thoi_gian_mat_tin_hieu_phut' => 'integer',
        'hieu_luc_tu'              => 'datetime',
        'hieu_luc_den'             => 'datetime',
    ];

    /** Các giá trị trang_thai được coi là "đang hoạt động". */
    public const TRANG_THAI_HIEU_LUC = ['DANG_AP_DUNG', 'HIEU_LUC'];

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function khoLanh()
    {
        return $this->belongsTo(KhoLanh::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id', 'san_pham_id');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    /**
     * Lọc những cấu hình đang có hiệu lực (bao gồm cả DANG_AP_DUNG và HIEU_LUC).
     */
    public function scopeHieuLuc($query)
    {
        return $query->whereIn('trang_thai', self::TRANG_THAI_HIEU_LUC);
    }
}


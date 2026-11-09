<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanhMucVatTu extends Model
{
    use HasFactory;

    protected $table = 'danh_muc_vat_tu';
    protected $primaryKey = 'vat_tu_id';
    public $timestamps = false;

    protected $fillable = [
        'ma_vat_tu',
        'ten_thuong_mai',
        'loai_vat_tu',
        'hoat_chat',
        'don_vi_tinh',
        'thoi_gian_cach_ly_ngay',
        'trang_thai_phap_ly',
        'nguon_du_lieu',
        'ngay_cap_nhat_danh_muc',
        'trang_thai',
    ];

    protected $casts = [
        'ngay_cap_nhat_danh_muc' => 'date',
        'thoi_gian_cach_ly_ngay' => 'integer',
    ];

    public const LOAI_VAT_TU_LABELS = [
        'PHAN_BON' => 'Phân bón',
        'THUOC_BVTV' => 'Thuốc BVTV',
        'CHE_PHAM_SINH_HOC' => 'Chế phẩm sinh học',
        'KHAC' => 'Vật tư khác',
    ];

    public function getTenVatTuAttribute()
    {
        return $this->ten_thuong_mai;
    }

    public function chiTietSuDung()
    {
        return $this->hasMany(ChiTietSuDungVatTu::class, 'vat_tu_id', 'vat_tu_id');
    }
}


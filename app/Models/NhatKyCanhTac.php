<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhatKyCanhTac extends Model
{
    use HasFactory;

    protected $table = 'nhat_ky_canh_tac';
    protected $primaryKey = 'nhat_ky_id';

    protected $fillable = [
        'mua_vu_id',
        'tai_khoan_id',
        'ban_ghi_goc_id',
        'loai_hoat_dong',
        'ngay_thuc_hien',
        'ghi_chu',
        'tep_minh_chung',
        'trang_thai',
        'ly_do_sua',
    ];

    protected $casts = [
        'ngay_thuc_hien' => 'datetime',
    ];

    public const HOAT_DONG_LABELS = [
        'LAM_DAT' => 'Làm đất & xử lý giá thể',
        'BON_LOT' => 'Bón lót',
        'GIEO' => 'Gieo hạt / ươm giống',
        'TRONG_CAY' => 'Trồng cây con / xuống giống',
        'TUOI_NUOC' => 'Tưới nước & vi lượng',
        'BON_THUC' => 'Bón thúc',
        'PHUN_THUOC' => 'Phun thuốc bảo vệ thực vật',
        'TIA_CANH' => 'Tỉa cành / ngắt chồi / thụ phấn',
        'BAO_TRAI' => 'Bao trái / bọc quả',
        'THAP_DEN' => 'Thắp đèn / xử lý ra hoa',
        'KIEM_TRA' => 'Kiểm tra dịch hại / giám sát vườn',
        'THU_HOACH' => 'Thu hoạch nông sản',
        'KHAC' => 'Hoạt động khác',
    ];

    public const TRANG_THAI_LABELS = [
        'NHAP' => 'Bản nháp',
        'DA_XAC_NHAN' => 'Đã xác nhận',
        'DA_THAY_THE' => 'Đã thay thế (Có sửa đổi)',
    ];

    public function muaVu()
    {
        return $this->belongsTo(MuaVu::class, 'mua_vu_id', 'mua_vu_id');
    }

    public function nguoiThucHien()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'tai_khoan_id', 'tai_khoan_id');
    }

    public function banGhiGoc()
    {
        return $this->belongsTo(NhatKyCanhTac::class, 'ban_ghi_goc_id', 'nhat_ky_id');
    }

    public function banGhiSuaDoi()
    {
        return $this->hasMany(NhatKyCanhTac::class, 'ban_ghi_goc_id', 'nhat_ky_id');
    }

    public function chiTietVatTu()
    {
        return $this->hasMany(ChiTietSuDungVatTu::class, 'nhat_ky_id', 'nhat_ky_id');
    }

    public function getHoatDongBadgeColor(): string
    {
        return match ($this->loai_hoat_dong) {
            'BON_LOT', 'BON_THUC' => 'border-emerald-500 text-emerald-800 bg-emerald-50',
            'PHUN_THUOC' => 'border-amber-500 text-amber-800 bg-amber-50',
            'THU_HOACH' => 'border-purple-500 text-purple-800 bg-purple-50',
            'KIEM_TRA' => 'border-sky-500 text-sky-800 bg-sky-50',
            'TRONG_CAY', 'GIEO' => 'border-green-600 text-green-900 bg-green-100',
            default => 'border-slate-400 text-slate-800 bg-slate-50',
        };
    }
}


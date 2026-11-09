<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuKiemDinh extends Model
{
    use HasFactory;

    protected $table = 'phieu_kiem_dinh';
    protected $primaryKey = 'phieu_id';
    public $timestamps = false;

    protected $fillable = [
        'ma_phieu',
        'lo_nong_san_id',
        'nguoi_kiem_dinh_id',
        'loai_kiem_dinh',
        'ngay_kiem_dinh',
        'ket_luan',
        'ghi_chu',
        'huong_xu_ly',
        'ghi_chu_loi',
        'nguoi_khac_phuc_id',
        'thoi_gian_khac_phuc',
        'ket_qua_khac_phuc',
    ];

    protected $casts = [
        'ngay_kiem_dinh' => 'datetime',
        'thoi_gian_khac_phuc' => 'datetime',
    ];

    public const LOAI_KIEM_DINH_LABELS = [
        'NHAP_KHO' => 'Kiểm định nhập kho sơ chế',
        'TRUOC_XUAT_XUONG' => 'Kiểm định trước xuất xưởng',
        'KIEM_DINH_LAI' => 'Tái kiểm định chất lượng',
        'SAU_PHAN_PHOI' => 'Kiểm định giám sát lưu thông',
    ];

    public const KET_LUAN_LABELS = [
        'DAT' => 'Đạt tiêu chuẩn',
        'KHONG_DAT' => 'Không đạt',
        'CHO_KIEM_DINH' => 'Chờ kiểm định',
    ];

    public const HUONG_XU_LY_LABELS = [
        'LOAI_BO_VA_KIEM_LAI' => 'Phân loại lại & Tái kiểm định',
        'TRA_NHA_CUNG_CAP' => 'Trả lại nhà cung cấp / vùng trồng',
        'THU_HOI_LO' => 'Thu hồi & Tiêu hủy an toàn',
        'HA_CAP_CHAT_LUONG' => 'Hạ cấp thương mại / chế biến phụ',
        'XU_LY_BO_SUNG' => 'Xử lý bảo quản bổ sung',
    ];

    public function loNongSan()
    {
        return $this->belongsTo(LoNongSan::class, 'lo_nong_san_id', 'lo_nong_san_id');
    }

    public function nguoiKiemDinh()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_kiem_dinh_id', 'tai_khoan_id');
    }

    public function nguoiKhacPhuc()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_khac_phuc_id', 'tai_khoan_id');
    }

    public function chiTietKetQua()
    {
        return $this->hasMany(ChiTietKetQuaKiemDinh::class, 'phieu_id', 'phieu_id');
    }

    public function getLoaiKiemDinhLabelAttribute(): string
    {
        return self::LOAI_KIEM_DINH_LABELS[$this->loai_kiem_dinh] ?? $this->loai_kiem_dinh;
    }

    public function getKetLuanLabelAttribute(): string
    {
        return self::KET_LUAN_LABELS[$this->ket_luan] ?? $this->ket_luan;
    }

    public function getHuongXuLyLabelAttribute(): ?string
    {
        return $this->huong_xu_ly ? (self::HUONG_XU_LY_LABELS[$this->huong_xu_ly] ?? $this->huong_xu_ly) : null;
    }

    public function getKetLuanBadgeClass(): string
    {
        return match ($this->ket_luan) {
            'DAT' => 'f2f-badge-success',
            'KHONG_DAT' => 'f2f-badge-danger',
            default => 'f2f-badge-warning',
        };
    }

    public static function generateMaPhieu(): string
    {
        $today = now()->format('ymd');
        $prefix = "KD-{$today}-";
        $lastTicket = self::where('ma_phieu', 'like', "{$prefix}%")
            ->orderByDesc('phieu_id')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int)substr($lastTicket->ma_phieu, -3);
            $newNumber = str_pad((string)($lastNumber + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$prefix}{$newNumber}";
    }
}


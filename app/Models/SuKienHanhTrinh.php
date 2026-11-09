<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuKienHanhTrinh extends Model
{
    use HasFactory;

    protected $table = 'su_kien_hanh_trinh';
    protected $primaryKey = 'su_kien_id';
    public $timestamps = false;

    protected $fillable = [
        'chuyen_id',
        'nguoi_ghi_nhan_id',
        'loai_su_kien',
        'thoi_gian',
        'dia_diem_mo_ta',
        'mo_ta',
    ];

    protected $casts = [
        'thoi_gian' => 'datetime',
    ];

    public const LOAI_SU_KIEN_LABELS = [
        'XUAT_PHAT' => 'Khởi hành xuất phát',
        'BOC_HANG' => 'Bốc dỡ & xếp hàng',
        'KIEM_TRA_NHIET_DO' => 'Kiểm tra nhiệt độ thùng lạnh',
        'QUA_TRAM' => 'Qua trạm thu phí / kiểm soát',
        'QUA_DEO' => 'Vượt đèo / đoạn dốc hiểm trở',
        'DUNG_NGHI' => 'Dừng nghỉ kỹ thuật / tài xế',
        'DEN_KHO' => 'Đến kho trung chuyển',
        'DEN_DIEM_GIAO' => 'Đã đến điểm giao nhận',
        'GIAO_HANG' => 'Bàn giao nông sản cho khách',
        'CANH_BAO_NHIET_DO' => 'Cảnh báo dao động nhiệt độ',
        'KHAC_PHUC' => 'Khắc phục sự cố kỹ thuật xe',
        'TIEP_TUC_HANH_TRINH' => 'Tiếp tục hành trình',
        'CAP_NHAT_VI_TRI' => 'Cập nhật tọa độ / vị trí',
        'HOAN_THANH' => 'Kết thúc & hoàn thành chuyến',
    ];

    public function chuyenVanChuyen()
    {
        return $this->belongsTo(ChuyenVanChuyen::class, 'chuyen_id', 'chuyen_id');
    }

    public function nguoiGhiNhan()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_ghi_nhan_id', 'tai_khoan_id');
    }

    public function getLoaiSuKienLabelAttribute(): string
    {
        return self::LOAI_SU_KIEN_LABELS[$this->loai_su_kien] ?? $this->loai_su_kien;
    }
}


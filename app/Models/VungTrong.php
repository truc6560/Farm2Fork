<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VungTrong extends Model
{
    use HasFactory;

    protected $table = 'vung_trong';
    protected $primaryKey = 'vung_trong_id';

    protected $fillable = [
        'to_chuc_id',
        'nguoi_phu_trach_id',
        'ma_so_vung_trong',
        'ten_vung_trong',
        'dia_chi',
        'dien_tich_ha',
        'cay_trong_chinh',
        'so_chung_nhan',
        'loai_chung_nhan',
        'don_vi_cap_chung_nhan',
        'ngay_cap_chung_nhan',
        'ngay_het_han_chung_nhan',
        'tep_chung_nhan',
        'trang_thai',
    ];

    protected $casts = [
        'dien_tich_ha' => 'decimal:3',
        'ngay_cap_chung_nhan' => 'date',
        'ngay_het_han_chung_nhan' => 'date',
    ];

    public const TRANG_THAI_LABELS = [
        'DANG_SU_DUNG' => 'Đang canh tác / sử dụng',
        'TAM_NGUNG' => 'Tạm ngưng hoạt động',
    ];

    /**
     * Mã định danh nội bộ Farm2Fork (ví dụ: F2F-VT-00001)
     */
    public function getMaNoiBoAttribute(): string
    {
        return 'F2F-VT-' . str_pad((string)$this->vung_trong_id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Huy hiệu trạng thái minh chứng mã số vùng trồng
     */
    public function getDinhDanhBadgeAttribute(): array
    {
        if ($this->ma_so_vung_trong && $this->tep_chung_nhan) {
            return [
                'label' => 'Đã có MSVT & Tệp minh chứng',
                'class' => 'f2f-badge-success',
            ];
        }
        if ($this->ma_so_vung_trong) {
            return [
                'label' => 'Đã khai báo MSVT',
                'class' => 'f2f-badge-info',
            ];
        }
        if ($this->tep_chung_nhan) {
            return [
                'label' => 'Có tệp minh chứng (chờ cập nhật MSVT)',
                'class' => 'f2f-badge-warning',
            ];
        }
        return [
            'label' => 'Chưa có định danh chính thức',
            'class' => 'f2f-badge-danger',
        ];
    }

    /**
     * Danh mục và mã số các đơn vị hành chính cấp tỉnh (34 tỉnh, thành sau sáp nhập)
     */
    public const DANH_SACH_MA_TINH = [
        '01' => 'Thành phố Hà Nội',
        '04' => 'Cao Bằng',
        '08' => 'Tuyên Quang',
        '11' => 'Điện Biên',
        '12' => 'Lai Châu',
        '14' => 'Sơn La',
        '15' => 'Lào Cai',
        '19' => 'Thái Nguyên',
        '20' => 'Lạng Sơn',
        '22' => 'Quảng Ninh',
        '24' => 'Bắc Ninh',
        '25' => 'Phú Thọ',
        '31' => 'Thành phố Hải Phòng',
        '33' => 'Hưng Yên',
        '37' => 'Ninh Bình',
        '38' => 'Thanh Hóa',
        '40' => 'Nghệ An',
        '42' => 'Hà Tĩnh',
        '44' => 'Quảng Trị',
        '46' => 'Thành phố Huế',
        '48' => 'Thành phố Đà Nẵng',
        '51' => 'Quảng Ngãi',
        '52' => 'Gia Lai',
        '56' => 'Khánh Hòa',
        '66' => 'Đắk Lắk',
        '68' => 'Lâm Đồng',
        '75' => 'Đồng Nai',
        '79' => 'Thành phố Hồ Chí Minh',
        '80' => 'Tây Ninh',
        '82' => 'Đồng Tháp',
        '86' => 'Vĩnh Long',
        '91' => 'An Giang',
        '92' => 'Thành phố Cần Thơ',
        '96' => 'Cà Mau',
    ];

    /**
     * Bảng mã định danh rút gọn cây trồng chuẩn hóa phổ biến
     */
    public const MA_CAY_TRONG_QUY_CHUAN = [
        'DAUTAY' => 'Dâu tây',
        'XALACH' => 'Xà lách',
        'BCXANH' => 'Bông cải xanh',
        'CACHUA' => 'Cà chua bi / Cà chua',
        'OTCHUONG' => 'Ớt chuông',
        'XOAICAT' => 'Xoài cát Hòa Lộc',
        'THANHLONG' => 'Thanh long',
        'MANGCUT' => 'Măng cụt',
        'BUOI' => 'Bưởi da xanh',
        'SAURIENG' => 'Sầu riêng',
        'BO' => 'Bơ sáp',
        'CAM' => 'Cam sành',
        'CHANHDAY' => 'Chanh dây',
        'CHUOI' => 'Chuối',
        'NHAN' => 'Nhãn',
        'VAI' => 'Vải thiều',
        'CHOMCHOM' => 'Chôm chôm',
        'MIT' => 'Mít',
        'DUALUOI' => 'Dưa lưới',
        'DUAHAU' => 'Dưa hấu',
        'KHOM' => 'Dứa / Khóm',
    ];

    /**
     * Lấy tên tỉnh/thành theo mã hành chính 2 chữ số (danh mục 34 tỉnh, thành)
     */
    public static function getTenTinhThanh(?string $maTinh): ?string
    {
        if (!$maTinh) {
            return null;
        }
        $code = str_pad(trim($maTinh), 2, '0', STR_PAD_LEFT);
        return self::DANH_SACH_MA_TINH[$code] ?? null;
    }

    /**
     * Tự động nhận diện tên tỉnh/thành từ mã MSVT/PUC nếu có tiền tố mã tỉnh
     */
    public function getTenTinhThanhAttribute(): ?string
    {
        $puc = self::parsePuc($this->ma_so_vung_trong);
        if (!empty($puc['province'])) {
            return self::getTenTinhThanh($puc['province']);
        }
        return null;
    }

    /**
     * Phân tách mã số PUC thành các thành phần: [Mã tỉnh] - PUC - [Cây trồng] - [YYYYY]
     */
    public static function parsePuc(?string $puc): array
    {
        if (!$puc) {
            return ['province' => '', 'crop' => '', 'seq' => '00001'];
        }
        if (preg_match('/^([0-9]{2})\s*-\s*PUC\s*-\s*([A-Z0-9_]+)\s*-\s*([0-9]{5})$/i', trim($puc), $matches)) {
            return [
                'province' => $matches[1],
                'crop' => strtoupper($matches[2]),
                'seq' => $matches[3],
            ];
        }
        return ['province' => '', 'crop' => '', 'seq' => '00001'];
    }

    /**
     * Ghép chuẩn hóa mã PUC: [Mã tỉnh]-PUC-[Cây trồng]-[YYYYY]
     */
    public static function formatPuc(string $province, string $crop, string $seq): string
    {
        $prov = str_pad(preg_replace('/[^0-9]/', '', $province), 2, '0', STR_PAD_LEFT);
        $cr = strtoupper(trim(preg_replace('/[^A-Za-z0-9_]/', '', $crop)));
        $sq = str_pad(preg_replace('/[^0-9]/', '', $seq), 5, '0', STR_PAD_LEFT);
        return "{$prov}-PUC-{$cr}-{$sq}";
    }

    public function toChuc()
    {
        return $this->belongsTo(ToChuc::class, 'to_chuc_id', 'to_chuc_id');
    }

    public function nguoiPhuTrach()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_phu_trach_id', 'tai_khoan_id');
    }

    public function nhanVienPhanCong()
    {
        return $this->belongsToMany(TaiKhoanHeThong::class, 'nhan_vien_vung_trong', 'vung_trong_id', 'tai_khoan_id')
            ->withPivot('ngay_phan_cong', 'ghi_chu');
    }

    public function muaVu()
    {
        return $this->hasMany(MuaVu::class, 'vung_trong_id', 'vung_trong_id');
    }

    public function isCertificateExpired(): bool
    {
        if (!$this->ngay_het_han_chung_nhan) {
            return false;
        }
        return Carbon::parse($this->ngay_het_han_chung_nhan)->isPast();
    }

    public function isCertificateExpiringSoon(int $days = 30): bool
    {
        if (!$this->ngay_het_han_chung_nhan) {
            return false;
        }
        $expiry = Carbon::parse($this->ngay_het_han_chung_nhan);
        return $expiry->isFuture() && $expiry->diffInDays(now()) <= $days;
    }
}


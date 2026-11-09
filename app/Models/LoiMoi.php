<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoiMoi extends Model
{
    use HasFactory;

    protected $table = 'loi_moi';

    protected $primaryKey = 'id';

    public const TRANG_THAI_CHO = 'cho';
    public const TRANG_THAI_DA_CHAP_NHAN = 'da_chap_nhan';
    public const TRANG_THAI_HET_HAN = 'het_han';
    public const TRANG_THAI_DA_THU_HOI = 'da_thu_hoi';

    protected $fillable = [
        'email',
        'vai_tro_id',
        'to_chuc_id',
        'kho_lanh_id',
        'phuong_tien_id',
        'vung_trong_id',
        'vung_trong_ids',
        'ma_hash',
        'het_han',
        'trang_thai',
        'nguoi_moi_id',
    ];

    protected $casts = [
        'het_han' => 'datetime',
        'vung_trong_ids' => 'array',
    ];

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id', 'vai_tro_id');
    }

    public function toChuc()
    {
        return $this->belongsTo(ToChuc::class, 'to_chuc_id', 'to_chuc_id');
    }

    public function khoLanh()
    {
        return $this->belongsTo(KhoLanh::class, 'kho_lanh_id', 'kho_lanh_id');
    }

    public function vungTrong()
    {
        return $this->belongsTo(VungTrong::class, 'vung_trong_id', 'vung_trong_id');
    }

    public function phuongTien()
    {
        return $this->belongsTo(PhuongTien::class, 'phuong_tien_id', 'phuong_tien_id');
    }

    public function nguoiMoi()
    {
        return $this->belongsTo(TaiKhoanHeThong::class, 'nguoi_moi_id', 'tai_khoan_id');
    }

    public function isExpired(): bool
    {
        if ($this->trang_thai === self::TRANG_THAI_HET_HAN) {
            return true;
        }

        return $this->het_han ? $this->het_han->isPast() : false;
    }

    public function canBeAccepted(): bool
    {
        return $this->trang_thai === self::TRANG_THAI_CHO && !$this->isExpired();
    }

    public function getTrangThaiBadgeAttribute(): array
    {
        if ($this->trang_thai === self::TRANG_THAI_DA_CHAP_NHAN) {
            return ['label' => 'Đã chấp nhận', 'class' => 'f2f-badge-success'];
        }

        if ($this->trang_thai === self::TRANG_THAI_DA_THU_HOI) {
            return ['label' => 'Đã thu hồi', 'class' => 'f2f-badge-danger'];
        }

        if ($this->isExpired()) {
            return ['label' => 'Hết hạn', 'class' => 'f2f-badge-muted'];
        }

        return ['label' => 'Đang chờ', 'class' => 'f2f-badge-warning'];
    }

    public function getPhamViChiTietAttribute(): string
    {
        $scopes = [];

        if ($this->khoLanh) {
            $scopes[] = 'Kho lạnh: ' . $this->khoLanh->ten_kho;
        }

        if (!empty($this->vung_trong_ids) && is_array($this->vung_trong_ids)) {
            $names = VungTrong::whereIn('vung_trong_id', $this->vung_trong_ids)->pluck('ten_vung_trong')->toArray();
            if (!empty($names)) {
                $scopes[] = 'Vùng trồng: ' . implode(', ', $names);
            }
        } elseif ($this->vungTrong) {
            $scopes[] = 'Vùng trồng: ' . $this->vungTrong->ten_vung_trong;
        }

        if ($this->phuongTien) {
            $scopes[] = 'Phương tiện: ' . $this->phuongTien->bien_so;
        }

        if (empty($scopes) && $this->toChuc) {
            $scopes[] = 'Tổ chức: ' . $this->toChuc->ten_to_chuc;
        }

        return empty($scopes) ? 'Toàn quyền theo vai trò' : implode(' · ', $scopes);
    }
}


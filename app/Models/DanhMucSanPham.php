<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DanhMucSanPham extends Model
{
    use HasFactory;

    protected $table = 'danh_muc_san_pham';
    protected $primaryKey = 'san_pham_id';

    protected $fillable = [
        'ma_san_pham',
        'ten_san_pham',
        'nhom_san_pham',
        'gtin',
        'don_vi_tinh',
        'quy_cach',
        'han_su_dung_mac_dinh_ngay',
        'trang_thai',
    ];

    /**
     * Tìm hoặc tự động tạo mới nông sản theo tên nhập tự do.
     */
    public static function findOrCreateByName(string $name, ?string $nhom = 'Nông sản'): self
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new \InvalidArgumentException('Tên nông sản không được để trống.');
        }

        // Tìm kiếm chính xác không phân biệt hoa thường
        $existing = self::whereRaw('LOWER(TRIM(ten_san_pham)) = ?', [mb_strtolower($cleanName)])->first();
        if ($existing) {
            return $existing;
        }

        // Tự động sinh mã nông sản duy nhất
        $slug = Str::slug($cleanName, '');
        $prefix = strtoupper(substr($slug, 0, 8)) ?: 'SP';
        $code = 'SP-' . $prefix . '-' . strtoupper(Str::random(4));
        while (self::where('ma_san_pham', $code)->exists()) {
            $code = 'SP-' . $prefix . '-' . strtoupper(Str::random(4));
        }

        return self::create([
            'ma_san_pham' => $code,
            'ten_san_pham' => $cleanName,
            'nhom_san_pham' => $nhom ?: 'Nông sản',
            'don_vi_tinh' => 'kg',
            'han_su_dung_mac_dinh_ngay' => 7,
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
    }

    public function muaVu()
    {
        return $this->hasMany(MuaVu::class, 'san_pham_id', 'san_pham_id');
    }

    public function loNongSan()
    {
        return $this->hasMany(LoNongSan::class, 'san_pham_id', 'san_pham_id');
    }
}


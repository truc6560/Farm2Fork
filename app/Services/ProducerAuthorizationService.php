<?php

namespace App\Services;

use App\Models\LoNongSan;
use App\Models\MuaVu;
use App\Models\NhatKyCanhTac;
use App\Models\TaiKhoanHeThong;
use App\Models\VungTrong;
use Illuminate\Support\Facades\DB;

class ProducerAuthorizationService
{
    /**
     * Lấy danh sách ID các vùng trồng được phân công cho người sản xuất.
     * Mọi truy vấn quyền CHỈ đọc từ bảng trung gian nhan_vien_vung_trong.
     *
     * @param TaiKhoanHeThong $user
     * @return int[]
     */
    public static function getAssignedZoneIds(TaiKhoanHeThong $user): array
    {
        return DB::table('nhan_vien_vung_trong')
            ->where('tai_khoan_id', $user->tai_khoan_id)
            ->pluck('vung_trong_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    /**
     * Kiểm tra quyền truy cập vùng trồng
     */
    public static function canAccessZone(TaiKhoanHeThong $user, VungTrong|int $zone): bool
    {
        $zoneId = $zone instanceof VungTrong ? (int) $zone->vung_trong_id : (int) $zone;
        return in_array($zoneId, self::getAssignedZoneIds($user), true);
    }

    /**
     * Bắt buộc có quyền truy cập vùng trồng, nếu không ném 403
     */
    public static function authorizeZone(TaiKhoanHeThong $user, VungTrong|int $zone): void
    {
        if (!self::canAccessZone($user, $zone)) {
            abort(403, 'Bạn không có quyền truy cập hoặc chỉnh sửa vùng trồng này.');
        }
    }

    /**
     * Kiểm tra quyền truy cập mùa vụ (dựa trên vùng trồng của mùa vụ)
     */
    public static function canAccessSeason(TaiKhoanHeThong $user, MuaVu|int $season): bool
    {
        if (is_numeric($season)) {
            $season = MuaVu::find($season);
        }

        if (!$season) {
            return false;
        }

        return self::canAccessZone($user, (int) $season->vung_trong_id);
    }

    /**
     * Bắt buộc có quyền truy cập mùa vụ, nếu không ném 403
     */
    public static function authorizeSeason(TaiKhoanHeThong $user, MuaVu|int $season): void
    {
        if (!self::canAccessSeason($user, $season)) {
            abort(403, 'Bạn không có quyền truy cập hoặc chỉnh sửa mùa vụ này.');
        }
    }

    /**
     * Kiểm tra quyền truy cập nhật ký canh tác (dựa trên vùng trồng của mùa vụ)
     */
    public static function canAccessLog(TaiKhoanHeThong $user, NhatKyCanhTac|int $log): bool
    {
        if (is_numeric($log)) {
            $log = NhatKyCanhTac::with('muaVu')->find($log);
        }

        if (!$log || !$log->muaVu) {
            return false;
        }

        return self::canAccessZone($user, (int) $log->muaVu->vung_trong_id);
    }

    /**
     * Bắt buộc có quyền truy cập nhật ký canh tác, nếu không ném 403
     */
    public static function authorizeLog(TaiKhoanHeThong $user, NhatKyCanhTac|int $log): void
    {
        if (!self::canAccessLog($user, $log)) {
            abort(403, 'Bạn không có quyền truy cập hoặc chỉnh sửa nhật ký canh tác này.');
        }
    }

    /**
     * Kiểm tra quyền truy cập lô thu hoạch nguyên liệu (dựa trên vùng trồng của mùa vụ)
     */
    public static function canAccessLot(TaiKhoanHeThong $user, LoNongSan|int $lot): bool
    {
        if (is_numeric($lot)) {
            $lot = LoNongSan::with('muaVu')->find($lot);
        }

        if (!$lot || !$lot->muaVu) {
            return false;
        }

        return self::canAccessZone($user, (int) $lot->muaVu->vung_trong_id);
    }

    /**
     * Bắt buộc có quyền truy cập lô nông sản, nếu không ném 403
     */
    public static function authorizeLot(TaiKhoanHeThong $user, LoNongSan|int $lot): void
    {
        if (!self::canAccessLot($user, $lot)) {
            abort(403, 'Bạn không có quyền truy cập hoặc xem lô nông sản này.');
        }
    }

    /**
     * Gán phân công vùng trồng cho tài khoản vào bảng trung gian nhan_vien_vung_trong
     */
    public static function assignZoneToUser(int $userId, int $zoneId, ?string $note = null): void
    {
        DB::table('nhan_vien_vung_trong')->updateOrInsert(
            [
                'tai_khoan_id' => $userId,
                'vung_trong_id' => $zoneId,
            ],
            [
                'ngay_phan_cong' => now()->toDateString(),
                'ghi_chu' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}


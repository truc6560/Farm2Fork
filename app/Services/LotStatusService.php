<?php

namespace App\Services;

use App\Models\LichSuTrangThaiLo;
use App\Models\LoNongSan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotStatusService
{
    /**
     * Ma trận các bước chuyển trạng thái hợp lệ.
     */
    protected const ALLOWED_TRANSITIONS = [
        'MOI_TAO' => ['CHO_KIEM_DINH', 'THU_HOI'],
        'CHO_KIEM_DINH' => ['CHO_SO_CHE', 'TRA_NHA_CUNG_CAP', 'THU_HOI'],
        'CHO_SO_CHE' => ['DA_SO_CHE', 'THU_HOI'],
        'DA_SO_CHE' => ['DANG_DONG_GOI', 'CHO_KIEM_DINH_XUAT_XUONG', 'THU_HOI'],
        'DANG_DONG_GOI' => ['CHO_KIEM_DINH_XUAT_XUONG', 'THU_HOI'],
        // DA_DONG_GOI được giữ để tương thích dữ liệu cũ, nhưng phải qua QC xuất xưởng.
        'DA_DONG_GOI' => ['CHO_KIEM_DINH_XUAT_XUONG', 'THU_HOI'],
        'CHO_KIEM_DINH_XUAT_XUONG' => ['DAT_KIEM_DINH', 'THU_HOI'],
        'DAT_KIEM_DINH' => ['TRONG_KHO', 'LUU_KHO', 'CACH_LY', 'KHONG_DAT', 'THU_HOI'],
        'TRONG_KHO' => ['DANG_VAN_CHUYEN', 'CACH_LY', 'KHONG_DAT', 'THU_HOI', 'LUU_KHO'],
        'LUU_KHO' => ['DANG_VAN_CHUYEN', 'CACH_LY', 'KHONG_DAT', 'THU_HOI', 'TRONG_KHO'],
        'DANG_VAN_CHUYEN' => ['DA_GIAO', 'TRONG_KHO', 'LUU_KHO', 'CACH_LY', 'KHONG_DAT', 'THU_HOI'],
        'CACH_LY' => ['DAT_KIEM_DINH', 'KHONG_DAT', 'TRA_NHA_CUNG_CAP', 'THU_HOI'],
        'KHONG_DAT' => ['TRA_NHA_CUNG_CAP', 'THU_HOI'],
        'DA_GIAO' => ['THU_HOI'],
        'TRA_NHA_CUNG_CAP' => [],
        'THU_HOI' => [],
    ];

    /**
     * Thực hiện chuyển trạng thái lô nông sản và ghi lại lịch sử trạng thái một cách nhất quán.
     *
     * @param LoNongSan|int $lotOrId
     * @param string $newStatus
     * @param int|null $userId
     * @param string $reason
     * @param array $extraAttributes Các thuộc tính bổ sung cần cập nhật (ví dụ: kho_lanh_id)
     * @return LoNongSan
     * @throws ValidationException
     */
    public function transition(
        LoNongSan|int $lotOrId,
        string $newStatus,
        ?int $userId,
        string $reason,
        array $extraAttributes = []
    ): LoNongSan {
        return DB::transaction(function () use ($lotOrId, $newStatus, $userId, $reason, $extraAttributes) {
            $lot = $lotOrId instanceof LoNongSan
                ? LoNongSan::lockForUpdate()->findOrFail($lotOrId->lo_nong_san_id)
                : LoNongSan::lockForUpdate()->findOrFail($lotOrId);

            $oldStatus = $lot->trang_thai;

            // Kiểm tra trạng thái có giống nhau không
            if ($oldStatus === $newStatus) {
                if (!empty($extraAttributes)) {
                    $lot->update($extraAttributes);
                }
                return $lot;
            }

            // Kiểm tra tính hợp lệ của bước chuyển
            $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];
            if (!in_array($newStatus, $allowed, true)) {
                $labels = LoNongSan::TRANG_THAI_LABELS;
                $oldLabel = $labels[$oldStatus] ?? $oldStatus;
                $newLabel = $labels[$newStatus] ?? $newStatus;
                throw ValidationException::withMessages([
                    'trang_thai' => "Không thể chuyển lô từ trạng thái '{$oldLabel}' sang '{$newLabel}'.",
                ]);
            }

            // Cập nhật lô
            $updateData = array_merge($extraAttributes, [
                'trang_thai' => $newStatus,
            ]);
            $lot->update($updateData);

            // Ghi nhận lịch sử chuyển trạng thái
            LichSuTrangThaiLo::create([
                'lo_nong_san_id' => $lot->lo_nong_san_id,
                'tai_khoan_id'   => $userId,
                'trang_thai_cu'  => $oldStatus,
                'trang_thai_moi' => $newStatus,
                'ly_do'          => $reason,
                'thoi_gian'      => now(),
            ]);

            return $lot->fresh();
        });
    }

    /**
     * Kiểm tra xem việc chuyển trạng thái có hợp lệ hay không mà không thực thi.
     */
    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$fromStatus] ?? [];
        return in_array($toStatus, $allowed, true);
    }
}


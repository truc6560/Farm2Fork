<?php

namespace App\Support;

final class RegistrationStatuses
{
    public const CHO_XAC_THUC = 'CHO_XAC_THUC';
    public const CHO_DUYET = 'CHO_DUYET';
    public const TU_CHOI = 'TU_CHOI';
    public const YEU_CAU_BO_SUNG = 'YEU_CAU_BO_SUNG';
    public const DA_PHE_DUYET = 'DA_PHE_DUYET';
    public const DA_CAP_TAI_KHOAN = 'DA_CAP_TAI_KHOAN';

    public static function all(): array
    {
        return [
            self::CHO_XAC_THUC,
            self::CHO_DUYET,
            self::TU_CHOI,
            self::YEU_CAU_BO_SUNG,
            self::DA_PHE_DUYET,
            self::DA_CAP_TAI_KHOAN,
        ];
    }

    public static function labels(): array
    {
        return [
            self::CHO_XAC_THUC => 'Chờ xác thực thông tin liên hệ',
            self::CHO_DUYET => 'Chờ duyệt hồ sơ',
            self::TU_CHOI => 'Từ chối',
            self::YEU_CAU_BO_SUNG => 'Yêu cầu bổ sung hồ sơ',
            self::DA_PHE_DUYET => 'Đã phê duyệt',
            self::DA_CAP_TAI_KHOAN => 'Đã cấp tài khoản',
        ];
    }
}

<?php

namespace App\Support;

final class AccountStatus
{
    public const KHOA = 'KHOA';
    public const CHO_KICH_HOAT = 'CHO_KICH_HOAT';
    public const DANG_HOAT_DONG = 'DANG_HOAT_DONG';

    public static function activeStatuses(): array
    {
        return [self::DANG_HOAT_DONG];
    }
}

<?php

namespace App\Services;

use InvalidArgumentException;

class Gs1DigitalLinkService
{
    public function validGtin(?string $gtin): bool
    {
        if ($gtin === null || !preg_match('/^[0-9]{14}$/D', $gtin) || $gtin === '00000000000000') {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $gtin[$i] * ($i % 2 === 0 ? 3 : 1);
        }

        return (10 - $sum % 10) % 10 === (int) $gtin[13];
    }

    public function validLot(?string $lot): bool
    {
        // A deliberately limited subset of the GS1 AI (10) character set.
        // It covers the application's generated lot identifiers and avoids
        // reserved URL path characters in printed Digital Links.
        return $lot !== null && preg_match('/^[A-Za-z0-9_-]{1,20}$/D', $lot) === 1;
    }

    public function productLotUrl(string $gtin, string $lot): string
    {
        if (!$this->validGtin($gtin) || !$this->validLot($lot)) {
            throw new InvalidArgumentException('GTIN-14 hoặc mã lô AI (10) không hợp lệ.');
        }

        return url('/01/' . $gtin . '/10/' . rawurlencode($lot));
    }

    public function ssccUrl(string $sscc): string
    {
        if (!app(Gs1Service::class)->validateSscc($sscc)['valid']) {
            throw new InvalidArgumentException('SSCC AI (00) không hợp lệ.');
        }

        return url('/00/' . $sscc);
    }
}

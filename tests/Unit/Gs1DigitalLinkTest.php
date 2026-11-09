<?php

namespace Tests\Unit;

use App\Services\Gs1DigitalLinkService;
use Tests\TestCase;

class Gs1DigitalLinkTest extends TestCase
{
    public function test_gtin_14_and_lot_are_validated_before_generating_link(): void
    {
        $service = app(Gs1DigitalLinkService::class);

        $this->assertTrue($service->validGtin('09506000134352'));
        $this->assertFalse($service->validGtin('09506000134353'));
        $this->assertFalse($service->validGtin('00000000000000'));
        $this->assertFalse($service->validGtin(null));
        $this->assertTrue($service->validLot('TP-ABC-20261031-XYZ'));
        $this->assertFalse($service->validLot(str_repeat('A', 21)));
        $this->assertFalse($service->validLot('LOT/A'));

        $this->assertStringEndsWith('/01/09506000134352/10/LOT-TEST',
            $service->productLotUrl('09506000134352', 'LOT-TEST'));
        $this->assertStringEndsWith('/00/006141411234567890',
            $service->ssccUrl('006141411234567890'));
    }
}

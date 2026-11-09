<?php

namespace Tests\Feature;

use App\Models\DuLieuCamBien;
use App\Models\ThietBiIot;
use App\Services\IotTelemetryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TelemetryReliabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retransmitted_event_is_recorded_once(): void
    {
        $device = ThietBiIot::where('trang_thai', 'HOAT_DONG')->firstOrFail();
        $payload = ['device_id' => $device->ma_thiet_bi, 'event_id' => 'replay-' . uniqid(),
            'temperature' => 3.0, 'humidity' => 85.0, 'timestamp' => now()->format('Y-m-d H:i:s')];
        $before = DuLieuCamBien::where('thiet_bi_id', $device->thiet_bi_id)->count();
        $service = app(IotTelemetryService::class);
        $this->assertTrue($service->ingest($payload)['success']);
        $this->assertTrue($service->ingest($payload)['success']);
        $this->assertSame($before + 1, DuLieuCamBien::where('thiet_bi_id', $device->thiet_bi_id)->count(),
            'A retransmission of the same event must not duplicate measurement history.');
    }
}

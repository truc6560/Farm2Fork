<?php

namespace Tests\Unit;

use App\Models\DuLieuCamBien;
use App\Models\ThietBiIot;
use App\Services\IotTelemetryService;
use Tests\TestCase;

class IotTelemetryIngestTest extends TestCase
{
    protected IotTelemetryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IotTelemetryService::class);
    }

    public function test_can_ingest_valid_telemetry_payload()
    {
        $payload = json_encode([
            'device_id' => 'KL-DL-01-CB01',
            'temperature' => 2.75,
            'humidity' => 88.5,
            'timestamp' => '2026-10-27 10:00:00',
        ]);

        $result = $this->service->ingest($payload, 'farm2fork/kho/KL-DL-01-CB01/telemetry');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('du_lieu_id', $result);
        $this->assertEquals('KL-DL-01-CB01', $result['ma_thiet_bi']);
        $this->assertEquals(2.75, $result['nhiet_do']);
        $this->assertEquals(88.5, $result['do_am']);

        // Verify record in database
        $record = DuLieuCamBien::find($result['du_lieu_id']);
        $this->assertNotNull($record);
        $this->assertEquals(2.75, (float)$record->nhiet_do);
        $this->assertEquals(88.5, (float)$record->do_am);

        // Clean up test record to preserve existing DB
        $record->delete();
    }

    public function test_can_extract_device_code_from_topic_if_omitted_in_json()
    {
        $payload = json_encode([
            'temperature' => 4.1,
            'humidity' => 75.0,
        ]);

        $result = $this->service->ingest($payload, 'farm2fork/kho/KL-DL-01-CB01/telemetry');

        $this->assertTrue($result['success']);
        $this->assertEquals('KL-DL-01-CB01', $result['ma_thiet_bi']);

        // Clean up
        DuLieuCamBien::destroy($result['du_lieu_id']);
    }

    public function test_rejects_unknown_device()
    {
        $payload = json_encode([
            'device_id' => 'UNKNOWN-DEVICE-999',
            'temperature' => 3.0,
            'humidity' => 80.0,
        ]);

        $result = $this->service->ingest($payload);

        $this->assertFalse($result['success']);
        $this->assertEquals('DEVICE_NOT_FOUND', $result['error_code']);
    }

    public function test_rejects_inactive_or_maintenance_device()
    {
        $device = ThietBiIot::where('trang_thai', '!=', 'HOAT_DONG')->first();

        if ($device) {
            $payload = json_encode([
                'device_id' => $device->ma_thiet_bi,
                'temperature' => 3.0,
                'humidity' => 80.0,
            ]);

            $result = $this->service->ingest($payload);

            $this->assertFalse($result['success']);
            $this->assertEquals('DEVICE_INACTIVE', $result['error_code']);
        } else {
            $this->markTestSkipped('Không có thiết bị ở trạng thái không hoạt động trong DB.');
        }
    }

    public function test_rejects_invalid_or_out_of_bounds_measurements()
    {
        // 1. Non-numeric temperature
        $res1 = $this->service->ingest(json_encode([
            'device_id' => 'KL-DL-01-CB01',
            'temperature' => 'high_heat',
        ]));
        $this->assertFalse($res1['success']);
        $this->assertEquals('INVALID_TEMPERATURE', $res1['error_code']);

        // 2. Out of bounds temperature (> 80°C)
        $res2 = $this->service->ingest(json_encode([
            'device_id' => 'KL-DL-01-CB01',
            'temperature' => 120.0,
        ]));
        $this->assertFalse($res2['success']);
        $this->assertEquals('OUT_OF_BOUNDS_TEMPERATURE', $res2['error_code']);

        // 3. Out of bounds humidity (> 100%)
        $res3 = $this->service->ingest(json_encode([
            'device_id' => 'KL-DL-01-CB01',
            'temperature' => 5.0,
            'humidity' => 150.0,
        ]));
        $this->assertFalse($res3['success']);
        $this->assertEquals('OUT_OF_BOUNDS_HUMIDITY', $res3['error_code']);
    }

    public function test_rejects_invalid_json()
    {
        $result = $this->service->ingest('invalid json payload string');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_JSON', $result['error_code']);
    }
}


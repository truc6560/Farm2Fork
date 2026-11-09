<?php

namespace App\Console\Commands;

use App\Models\DuLieuCamBien;
use App\Services\IotTelemetryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimulateIotIngestCommand extends Command
{
    /**
     * Tên và chữ ký của lệnh Artisan.
     *
     * @var string
     */
    protected $signature = 'iot:test-ingest
                            {device=KL-DL-01-CB01 : Mã thiết bị IoT cần gửi dữ liệu}
                            {--temp=3.2 : Nhiệt độ đo được (°C)}
                            {--hum=88.0 : Độ ẩm đo được (%)}
                            {--topic= : Topic giả lập (tùy chọn)}
                            {--cleanup : Xóa bản ghi thử nghiệm ngay sau khi kiểm tra xong}';

    /**
     * Mô tả lệnh Artisan.
     *
     * @var string
     */
    protected $description = 'Thử nghiệm tiếp nhận gói tin đo lường IoT vào cơ sở dữ liệu (không cần broker MQTT)';

    /**
     * Thực thi lệnh.
     */
    public function handle(IotTelemetryService $telemetryService): int
    {
        $device = (string) $this->argument('device');
        $temp = $this->option('temp');
        $hum = $this->option('hum');
        $topic = $this->option('topic') ?: "farm2fork/kho/{$device}/telemetry";

        $payload = [
            'device_id' => $device,
            'temperature' => is_numeric($temp) ? (float) $temp : $temp,
            'humidity' => is_numeric($hum) ? (float) $hum : $hum,
            'timestamp' => Carbon::now()->format('Y-m-d H:i:s.v'),
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $this->info("=== MÔ PHỎNG TIẾP NHẬN DỮ LIỆU CẢM BIẾN IOT ===");
        $this->line("Topic: {$topic}");
        $this->line("Payload JSON:");
        $this->line($jsonPayload);
        $this->newLine();

        $result = $telemetryService->ingest($jsonPayload, $topic);

        if ($result['success']) {
            $this->info("KẾT QUẢ: Tiếp nhận và lưu dữ liệu THÀNH CÔNG!");
            $this->table(
                ['Trường thông tin', 'Giá trị lưu vào database'],
                [
                    ['ID bản ghi (du_lieu_id)', $result['du_lieu_id']],
                    ['Khóa ngoại thiết bị (thiet_bi_id)', $result['thiet_bi_id']],
                    ['Mã thiết bị (ma_thiet_bi)', $result['ma_thiet_bi']],
                    ['Nhiệt độ (nhiet_do)', $result['nhiet_do'] . ' °C'],
                    ['Độ ẩm (do_am)', ($result['do_am'] !== null ? $result['do_am'] . ' %' : 'N/A')],
                    ['Thời gian đo (thoi_gian_do)', $result['thoi_gian_do']],
                    ['Thời điểm nhận (received_at)', $result['received_at']],
                ]
            );

            if ($this->option('cleanup')) {
                DuLieuCamBien::where('du_lieu_id', $result['du_lieu_id'])->delete();
                $this->warn("Tùy chọn --cleanup được bật: Đã xóa bản ghi ID {$result['du_lieu_id']} để giữ nguyên dữ liệu cơ sở dữ liệu.");
            } else {
                $this->comment("Bản ghi đã được lưu vào bảng 'du_lieu_cam_bien' (ID: {$result['du_lieu_id']}).");
            }

            return 0;
        }

        $this->error("KẾT QUẢ: Gói tin BỊ TỪ CHỐI!");
        $this->line("Mã lỗi: " . ($result['error_code'] ?? 'UNKNOWN'));
        $this->line("Lý do từ chối: " . ($result['error'] ?? 'Không rõ'));
        return 1;
    }
}


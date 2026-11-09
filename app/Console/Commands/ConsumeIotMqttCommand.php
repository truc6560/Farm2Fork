<?php

namespace App\Console\Commands;

use App\Services\IotTelemetryService;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class ConsumeIotMqttCommand extends Command
{
    /**
     * Tên và chữ ký của lệnh Artisan.
     *
     * @var string
     */
    protected $signature = 'iot:consume-mqtt
                            {--host= : Địa chỉ MQTT Broker (mặc định từ config mqtt.host)}
                            {--port= : Cổng MQTT Broker (mặc định từ config mqtt.port)}
                            {--topic= : Topic đăng ký nhận dữ liệu (mặc định từ config mqtt.topic)}
                            {--limit=0 : Số lượng gói tin tối đa cần nhận rồi tự động dừng (0 = chạy liên tục)}';

    /**
     * Mô tả lệnh Artisan.
     *
     * @var string
     */
    protected $description = 'Lắng nghe gói tin đo lường IoT từ MQTT Broker nội bộ và lưu vào cơ sở dữ liệu';

    /**
     * Thực thi lệnh.
     */
    public function handle(IotTelemetryService $telemetryService): int
    {
        $host = (string) ($this->option('host') ?: config('mqtt.host', '127.0.0.1'));
        $port = (int) ($this->option('port') ?: config('mqtt.port', 1883));
        $topic = (string) ($this->option('topic') ?: config('mqtt.topic', 'farm2fork/#'));
        $limit = (int) $this->option('limit');
        $clientId = 'farm2fork_consumer_' . uniqid();

        $this->info("=== BẮT ĐẦU TIẾP NHẬN DỮ LIỆU IOT QUA MQTT ===");
        $this->line("Broker: {$host}:{$port}");
        $this->line("Topic đăng ký: {$topic}");
        $this->line("Client ID: {$clientId}");
        if ($limit > 0) {
            $this->line("Giới hạn: Tự động dừng sau {$limit} gói tin.");
        } else {
            $this->line("Chế độ: Lắng nghe liên tục (Nhấn Ctrl+C để dừng).");
        }
        $this->newLine();

        $mqtt = new MqttClient($host, $port, $clientId);

        $settings = (new ConnectionSettings)
            ->setKeepAliveInterval(60)
            ->setConnectTimeout(10);

        if (config('mqtt.username')) {
            $settings->setUsername(config('mqtt.username'));
            $settings->setPassword(config('mqtt.password'));
        }

        try {
            $this->line("Đang kết nối tới MQTT Broker...");
            $mqtt->connect($settings, true);
            $this->info("Kết nối thành công! Đang lắng nghe dữ liệu từ topic: {$topic}");
        } catch (Throwable $e) {
            $this->error("Không thể kết nối tới MQTT Broker ({$host}:{$port}).");
            $this->line("Chi tiết lỗi: " . $e->getMessage());
            $this->warn("Vui lòng kiểm tra lại dịch vụ Mosquitto trên máy chủ nội bộ.");
            return 1;
        }

        $receivedCount = 0;

        try {
            $mqtt->subscribe($topic, function (string $receivedTopic, string $message) use (
                &$receivedCount,
                $limit,
                $mqtt,
                $telemetryService
            ) {
                $receivedCount++;
                $timestamp = date('H:i:s');
                $this->line("[{$timestamp}] Nhận gói tin #{$receivedCount} từ topic: {$receivedTopic}");

                $result = $telemetryService->ingest($message, $receivedTopic);

                if ($result['success']) {
                    $this->info(
                        "  -> Lưu thành công ID: {$result['du_lieu_id']} " .
                        "| Thiết bị: {$result['ma_thiet_bi']} " .
                        "| Nhiệt độ: {$result['nhiet_do']}°C " .
                        "| Độ ẩm: " . ($result['do_am'] !== null ? $result['do_am'] . '%' : 'N/A')
                    );
                } else {
                    $this->warn("  -> Bị từ chối: [{$result['error_code']}] {$result['error']}");
                }

                if ($limit > 0 && $receivedCount >= $limit) {
                    $this->info("Đã nhận đủ giới hạn {$limit} gói tin. Tiến hành ngắt kết nối.");
                    $mqtt->interrupt();
                }
            }, (int) config('mqtt.qos', 0));

            $mqtt->loop(true);
        } catch (Throwable $e) {
            $this->error("Lỗi trong vòng lặp nhận tin MQTT: " . $e->getMessage());
        } finally {
            if ($mqtt->isConnected()) {
                $mqtt->disconnect();
            }
        }

        $this->info("Đã ngắt kết nối MQTT. Tổng số gói tin đã xử lý: {$receivedCount}.");
        return 0;
    }
}


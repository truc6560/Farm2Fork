<?php

namespace App\Console\Commands;

use App\Models\ThietBiIot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class SimulateIotSensorCommand extends Command
{
    /**
     * Tên và chữ ký của lệnh Artisan.
     *
     * @var string
     */
    protected $signature = 'iot:simulate-sensor
                            {device=KL-DL-01-CB01 : Mã thiết bị IoT cần mô phỏng}
                            {--interval=5 : Chu kỳ gửi dữ liệu tính bằng giây (tối thiểu 1 giây)}
                            {--count=0 : Số bản tin tối đa cần gửi trước khi dừng (0 = gửi liên tục)}
                            {--temp-min=2.0 : Nhiệt độ tối thiểu mô phỏng (°C)}
                            {--temp-max=6.0 : Nhiệt độ tối đa mô phỏng (°C)}
                            {--hum-min=80.0 : Độ ẩm tối thiểu mô phỏng (%)}
                            {--hum-max=90.0 : Độ ẩm tối đa mô phỏng (%)}';

    /**
     * Mô tả lệnh Artisan.
     *
     * @var string
     */
    protected $description = 'Mô phỏng cảm biến IoT phát dữ liệu nhiệt độ và độ ẩm qua MQTT Broker nội bộ (không ghi trực tiếp vào MySQL)';

    /**
     * Thực thi lệnh.
     */
    public function handle(): int
    {
        $deviceCode = trim((string) $this->argument('device'));
        $interval = (int) $this->option('interval');
        $count = (int) $this->option('count');
        $tempMin = (float) $this->option('temp-min');
        $tempMax = (float) $this->option('temp-max');
        $humMin = (float) $this->option('hum-min');
        $humMax = (float) $this->option('hum-max');

        // 1. Kiểm tra tham số chu kỳ gửi
        if ($interval < 1) {
            $this->error("Chu kỳ gửi (--interval) không hợp lệ: phải từ 1 giây trở lên.");
            return 1;
        }

        // 2. Kiểm tra tham số số lượng bản tin
        if ($count < 0) {
            $this->error("Số lượng bản tin (--count) không hợp lệ: phải lớn hơn hoặc bằng 0.");
            return 1;
        }

        // 3. Kiểm tra dải nhiệt độ (-50°C đến 80°C)
        if ($tempMin < -50.0 || $tempMax > 80.0 || $tempMin > $tempMax) {
            $this->error("Khoảng nhiệt độ không hợp lệ: yêu cầu -50°C <= temp-min <= temp-max <= 80°C.");
            return 1;
        }

        // 4. Kiểm tra dải độ ẩm (0% đến 100%)
        if ($humMin < 0.0 || $humMax > 100.0 || $humMin > $humMax) {
            $this->error("Khoảng độ ẩm không hợp lệ: yêu cầu 0% <= hum-min <= hum-max <= 100%.");
            return 1;
        }

        // 5. Kiểm tra thiết bị trong cơ sở dữ liệu
        $device = ThietBiIot::where('ma_thiet_bi', $deviceCode)->first();
        if (!$device) {
            $this->error("Thiết bị '{$deviceCode}' không tồn tại trong bảng 'thiet_bi_iot'.");
            return 1;
        }

        if ($device->trang_thai !== 'HOAT_DONG') {
            $this->error("Thiết bị '{$deviceCode}' đang ở trạng thái '{$device->trang_thai}', không được phép phát dữ liệu.");
            return 1;
        }

        $topic = $device->mqtt_topic;
        if (empty($topic)) {
            $topic = "farm2fork/kho/{$deviceCode}/telemetry";
        }

        $host = (string) config('mqtt.host', '127.0.0.1');
        $port = (int) config('mqtt.port', 1883);
        $clientId = 'farm2fork_sim_' . uniqid();

        $this->info("=== CHƯƠNG TRÌNH MÔ PHỎNG CẢM BIẾN IOT (PHP PUBLISHER) ===");
        $this->warn("LƯU Ý: Đây là DỮ LIỆU MÔ PHỎNG thử nghiệm, không phải số đo từ cảm biến vật lý thực tế.");
        $this->line("Broker MQTT: {$host}:{$port}");
        $this->line("Thiết bị: {$device->ma_thiet_bi} (ID: {$device->thiet_bi_id})");
        $this->line("Topic publish: {$topic}");
        $this->line("Dải nhiệt độ: [{$tempMin}°C - {$tempMax}°C]");
        $this->line("Dải độ ẩm: [{$humMin}% - {$humMax}%]");
        $this->line("Chu kỳ gửi: mỗi {$interval} giây");
        if ($count > 0) {
            $this->line("Giới hạn: Tự động dừng sau {$count} bản tin.");
        } else {
            $this->line("Chế độ: Gửi liên tục (Nhấn Ctrl+C để dừng).");
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
            $this->info("Kết nối Broker thành công! Bắt đầu phát dữ liệu mô phỏng...");
            $this->newLine();
        } catch (Throwable $e) {
            $this->error("Không thể kết nối tới MQTT Broker ({$host}:{$port}).");
            $this->line("Chi tiết lỗi: " . $e->getMessage());
            $this->warn("Vui lòng đảm bảo dịch vụ Mosquitto đang chạy trên máy chủ nội bộ.");
            return 1;
        }

        $running = true;

        // Đăng ký bộ xử lý tín hiệu ngắt trên Windows nếu PHP hỗ trợ
        if (function_exists('sapi_windows_set_ctrl_handler')) {
            sapi_windows_set_ctrl_handler(function (int $event) use (&$running) {
                if ($event === PHP_WINDOWS_EVENT_CTRL_C || $event === PHP_WINDOWS_EVENT_CTRL_BREAK) {
                    $running = false;
                }
            });
        }

        $sentCount = 0;

        try {
            while ($running) {
                $sentCount++;

                // Tạo số đo ngẫu nhiên trong khoảng quy định
                $randFactorTemp = mt_rand(0, 10000) / 10000;
                $temperature = round($tempMin + $randFactorTemp * ($tempMax - $tempMin), 2);

                $randFactorHum = mt_rand(0, 10000) / 10000;
                $humidity = round($humMin + $randFactorHum * ($humMax - $humMin), 2);

                $timestamp = Carbon::now()->format('Y-m-d H:i:s');

                $payload = [
                    'device_id' => $device->ma_thiet_bi,
                    'temperature' => $temperature,
                    'humidity' => $humidity,
                    'timestamp' => $timestamp,
                    'is_simulation' => true,
                ];

                $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

                $mqtt->publish($topic, $payloadJson, (int) config('mqtt.qos', 0));

                $this->info(
                    "[{$timestamp}] Đã publish bản tin #{$sentCount} " .
                    "| Thiết bị: {$device->ma_thiet_bi} " .
                    "| Nhiệt độ: {$temperature}°C " .
                    "| Độ ẩm: {$humidity}%"
                );

                if ($count > 0 && $sentCount >= $count) {
                    $this->newLine();
                    $this->info("Đã hoàn thành gửi đủ {$count} bản tin theo yêu cầu. Dừng chương trình.");
                    break;
                }

                // Chờ theo chu kỳ (ngắt sớm nếu có tín hiệu dừng)
                for ($i = 0; $i < $interval; $i++) {
                    if (!$running) {
                        break;
                    }
                    sleep(1);
                }
            }
        } catch (Throwable $e) {
            $this->error("Lỗi trong quá trình publish dữ liệu: " . $e->getMessage());
        } finally {
            if ($mqtt->isConnected()) {
                $mqtt->disconnect();
            }
        }

        $this->info("Đã ngắt kết nối MQTT an toàn. Tổng số bản tin đã phát: {$sentCount}.");
        return 0;
    }
}


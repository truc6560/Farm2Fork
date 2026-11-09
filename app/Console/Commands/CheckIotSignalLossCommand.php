<?php

namespace App\Console\Commands;

use App\Models\CanhBaoChuoiLanh;
use App\Models\CauHinhNguongCanhBao;
use App\Models\DuLieuCamBien;
use App\Models\ThietBiIot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckIotSignalLossCommand extends Command
{
    protected $signature = 'iot:check-signal-loss
                            {--dry-run : Chỉ hiển thị kết quả, không tạo cảnh báo}';

    protected $description = 'Kiểm tra các thiết bị IoT mất tín hiệu và tạo cảnh báo MAT_TIN_HIEU nếu cần.';

    public function handle(): int
    {
        $isDryRun = (bool)$this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] Chỉ xem kết quả, không ghi cảnh báo thực tế.');
        }

        $devices = ThietBiIot::where('trang_thai', 'HOAT_DONG')->get();

        if ($devices->isEmpty()) {
            $this->info('Không có thiết bị IoT đang hoạt động.');
            return self::SUCCESS;
        }

        $this->info("Kiểm tra {$devices->count()} thiết bị IoT...");

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($devices as $device) {
            try {
                $result = $this->checkDevice($device, $isDryRun);
                if ($result === 'created') {
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            } catch (Throwable $e) {
                Log::error("Lỗi kiểm tra mất tín hiệu thiết bị [{$device->ma_thiet_bi}]: " . $e->getMessage());
                $this->error("Lỗi khi xử lý thiết bị {$device->ma_thiet_bi}: " . $e->getMessage());
            }
        }

        $this->info("Hoàn tất. Cảnh báo mới: {$createdCount}. Bỏ qua: {$skippedCount}.");

        return self::SUCCESS;
    }

    /**
     * Kiểm tra một thiết bị và tạo cảnh báo nếu cần.
     * Trả về 'created' nếu tạo cảnh báo, 'skipped' trong các trường hợp còn lại.
     */
    protected function checkDevice(ThietBiIot $device, bool $isDryRun): string
    {
        // Lấy cấu hình ngưỡng cho thiết bị này
        $threshold = $this->findThreshold($device);

        // Thời gian mat tin hieu (phut) — lấy từ cấu hình, mặc định 60 phút
        $signalLossMinutes = $threshold ? (int)$threshold->thoi_gian_mat_tin_hieu_phut : 60;

        if ($signalLossMinutes <= 0) {
            return 'skipped';
        }

        // Lấy bản ghi telemetry mới nhất
        $latestReading = DuLieuCamBien::where('thiet_bi_id', $device->thiet_bi_id)
            ->orderByDesc('thoi_gian_do')
            ->first(['thoi_gian_do']);

        $minutesSinceLastSignal = $latestReading
            ? Carbon::parse($latestReading->thoi_gian_do)->diffInMinutes(now())
            : PHP_INT_MAX; // Chưa bao giờ có tín hiệu

        $isSignalLost = $minutesSinceLastSignal >= $signalLossMinutes;

        $lastSignalDesc = $latestReading
            ? Carbon::parse($latestReading->thoi_gian_do)->format('d/m/Y H:i') . " ({$minutesSinceLastSignal} phút trước)"
            : 'Chưa có tín hiệu';

        if (!$isSignalLost) {
            $this->line("  OK   {$device->ma_thiet_bi} — tín hiệu cuối: {$lastSignalDesc}");
            return 'skipped';
        }

        // Kiểm tra đã có cảnh báo MAT_TIN_HIEU mở chưa
        $hasOpenAlert = CanhBaoChuoiLanh::where('thiet_bi_id', $device->thiet_bi_id)
            ->where('loai_canh_bao', 'MAT_TIN_HIEU')
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])
            ->exists();

        if ($hasOpenAlert) {
            $this->line("  --   {$device->ma_thiet_bi} — mất tín hiệu ({$minutesSinceLastSignal} phút), đã có cảnh báo mở.");
            return 'skipped';
        }

        // Cần tạo cảnh báo
        $this->warn("  !    {$device->ma_thiet_bi} — MẤT TÍN HIỆU {$minutesSinceLastSignal} phút (ngưỡng: {$signalLossMinutes} phút). Tín hiệu cuối: {$lastSignalDesc}");

        if ($isDryRun) {
            return 'created'; // Không ghi thực tế
        }

        CanhBaoChuoiLanh::create([
            'thiet_bi_id'          => $device->thiet_bi_id,
            'chuyen_id'            => null,
            'cau_hinh_id'          => $threshold?->cau_hinh_id,
            'du_lieu_kich_hoat_id' => null, // Không có dữ liệu kích hoạt khi mất tín hiệu
            'loai_canh_bao'        => 'MAT_TIN_HIEU',
            'gia_tri_thuc_te'      => null,
            'nguong_tai_thoi_diem' => $signalLossMinutes,
            'thoi_gian_phat_hien'  => now(),
            'trang_thai'           => 'CHUA_XU_LY',
        ]);

        Log::info("Cảnh báo MAT_TIN_HIEU: thiết bị [{$device->ma_thiet_bi}] — {$minutesSinceLastSignal} phút không có tín hiệu.");

        return 'created';
    }

    /**
     * Tìm cấu hình ngưỡng phù hợp nhất theo thứ tự ưu tiên:
     * kho_lanh cụ thể > mặc định toàn hệ thống.
     */
    protected function findThreshold(ThietBiIot $device): ?object
    {
        $activeStatuses = CauHinhNguongCanhBao::TRANG_THAI_HIEU_LUC;

        if ($device->kho_lanh_id) {
            $byWarehouse = CauHinhNguongCanhBao::whereIn('trang_thai', $activeStatuses)
                ->where('kho_lanh_id', $device->kho_lanh_id)
                ->whereNull('san_pham_id')
                ->where(function ($q) {
                    $q->whereNull('hieu_luc_tu')->orWhere('hieu_luc_tu', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('hieu_luc_den')->orWhere('hieu_luc_den', '>=', now());
                })
                ->orderByDesc('cau_hinh_id')
                ->first();

            if ($byWarehouse) {
                return $byWarehouse;
            }
        }

        // Mặc định toàn hệ thống
        return CauHinhNguongCanhBao::whereIn('trang_thai', $activeStatuses)
            ->whereNull('kho_lanh_id')
            ->whereNull('san_pham_id')
            ->where(function ($q) {
                $q->whereNull('hieu_luc_tu')->orWhere('hieu_luc_tu', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('hieu_luc_den')->orWhere('hieu_luc_den', '>=', now());
            })
            ->orderByDesc('cau_hinh_id')
            ->first();
    }
}


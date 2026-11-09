<?php

namespace App\Services;

use App\Models\CanhBaoChuoiLanh;
use App\Models\CauHinhNguongCanhBao;
use App\Models\ChuyenVanChuyen;
use App\Models\DuLieuCamBien;
use App\Models\ThietBiIot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class IotTelemetryService
{
    /**
     * Tiếp nhận, xác thực và lưu bản ghi dữ liệu cảm biến IoT từ gói tin MQTT.
     *
     * @param array|string $payload Nội dung gói tin đo lường (JSON string hoặc mảng)
     * @param string|null $topic Topic MQTT nhận được (ví dụ: farm2fork/kho/KL-DL-01-CB01/telemetry)
     * @return array Kết quả xử lý gồm trạng thái, mã bản ghi hoặc thông báo lỗi
     */
    public function ingest(array|string $payload, ?string $topic = null): array
    {
        // 1. Giải mã payload JSON nếu là chuỗi
        $data = $payload;
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [
                    'success'    => false,
                    'error'      => 'Định dạng gói tin không hợp lệ, yêu cầu chuỗi JSON chuẩn.',
                    'error_code' => 'INVALID_JSON',
                ];
            }
            $data = $decoded;
        }

        // 2. Trích xuất mã định danh thiết bị (device_id hoặc ma_thiet_bi hoặc từ topic)
        $deviceCode = $data['device_id'] ?? $data['ma_thiet_bi'] ?? null;
        if (empty($deviceCode) && !empty($topic)) {
            $deviceCode = $this->extractDeviceCodeFromTopic($topic);
        }

        if (empty($deviceCode)) {
            return [
                'success'    => false,
                'error'      => 'Thiếu thông tin mã thiết bị (device_id) trong gói tin hoặc topic MQTT.',
                'error_code' => 'MISSING_DEVICE_ID',
            ];
        }

        $deviceCode = trim((string)$deviceCode);

        // 3. Xác thực thiết bị trong cơ sở dữ liệu
        $device = ThietBiIot::where('ma_thiet_bi', $deviceCode)->first();
        if (!$device) {
            return [
                'success'    => false,
                'error'      => "Thiết bị IoT '{$deviceCode}' không tồn tại trong danh mục hệ thống.",
                'error_code' => 'DEVICE_NOT_FOUND',
            ];
        }

        if ($device->trang_thai !== 'HOAT_DONG') {
            return [
                'success'    => false,
                'error'      => "Thiết bị IoT '{$deviceCode}' đang ở trạng thái '{$device->trang_thai}', từ chối tiếp nhận dữ liệu.",
                'error_code' => 'DEVICE_INACTIVE',
            ];
        }

        // 4. Trích xuất và xác thực số đo nhiệt độ / độ ẩm
        $rawTemp = $data['temperature'] ?? $data['nhiet_do'] ?? $data['temp'] ?? null;
        $rawHum  = $data['humidity']    ?? $data['do_am']    ?? $data['hum']  ?? null;

        if ($rawTemp === null && $rawHum === null) {
            return [
                'success'    => false,
                'error'      => 'Gói tin không chứa giá trị nhiệt độ hoặc độ ẩm đo được.',
                'error_code' => 'MISSING_MEASUREMENTS',
            ];
        }

        $temperature = null;
        if ($rawTemp !== null) {
            if (!is_numeric($rawTemp)) {
                return [
                    'success'    => false,
                    'error'      => "Giá trị nhiệt độ không hợp lệ: '{$rawTemp}'.",
                    'error_code' => 'INVALID_TEMPERATURE',
                ];
            }
            $temperature = round((float)$rawTemp, 2);
            // Giới hạn vật lý khả thi của cảm biến chuỗi lạnh (-50°C đến 80°C)
            if ($temperature < -50.0 || $temperature > 80.0) {
                return [
                    'success'    => false,
                    'error'      => "Nhiệt độ đo được nằm ngoài giới hạn vật lý khả thi (-50°C đến 80°C): {$temperature}°C.",
                    'error_code' => 'OUT_OF_BOUNDS_TEMPERATURE',
                ];
            }
        }

        $humidity = null;
        if ($rawHum !== null) {
            if (!is_numeric($rawHum)) {
                return [
                    'success'    => false,
                    'error'      => "Giá trị độ ẩm không hợp lệ: '{$rawHum}'.",
                    'error_code' => 'INVALID_HUMIDITY',
                ];
            }
            $humidity = round((float)$rawHum, 2);
            // Độ ẩm tương đối phải từ 0% đến 100%
            if ($humidity < 0.0 || $humidity > 100.0) {
                return [
                    'success'    => false,
                    'error'      => "Độ ẩm đo được phải nằm trong khoảng 0% đến 100%: {$humidity}%.",
                    'error_code' => 'OUT_OF_BOUNDS_HUMIDITY',
                ];
            }
        }

        // 5. Xác thực thời gian đo
        $measuredAt = Carbon::now();
        $rawTime = $data['timestamp'] ?? $data['thoi_gian_do'] ?? $data['time'] ?? null;
        if (!empty($rawTime)) {
            try {
                $measuredAt = Carbon::parse($rawTime);
            } catch (Throwable) {
                $measuredAt = Carbon::now();
            }
        }

        // 6. Lưu vào bảng lịch sử cảm biến du_lieu_cam_bien
        try {
            $reading = DuLieuCamBien::create([
                'thiet_bi_id'       => $device->thiet_bi_id,
                'thoi_gian_do'      => $measuredAt,
                'nhiet_do'          => $temperature,
                'do_am'             => $humidity,
                'trang_thai_tin_hieu' => 'BINH_THUONG',
                'received_at'       => Carbon::now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Lỗi lưu dữ liệu cảm biến IoT: ' . $e->getMessage(), [
                'device'  => $deviceCode,
                'payload' => $data,
            ]);

            return [
                'success'    => false,
                'error'      => 'Lỗi lưu trữ cơ sở dữ liệu: ' . $e->getMessage(),
                'error_code' => 'DATABASE_ERROR',
            ];
        }

        // 7. Kiểm tra ngưỡng và tạo cảnh báo (tách biệt, không ảnh hưởng kết quả lưu telemetry)
        try {
            $this->evaluateAlerts($device, $reading, $temperature, $humidity);
        } catch (Throwable $e) {
            // Lỗi tạo cảnh báo KHÔNG được làm mất dữ liệu telemetry
            Log::error('Lỗi đánh giá cảnh báo IoT sau khi lưu telemetry: ' . $e->getMessage(), [
                'device'     => $deviceCode,
                'du_lieu_id' => $reading->du_lieu_id,
            ]);
        }

        return [
            'success'      => true,
            'du_lieu_id'   => $reading->du_lieu_id,
            'thiet_bi_id'  => $device->thiet_bi_id,
            'ma_thiet_bi'  => $device->ma_thiet_bi,
            'nhiet_do'     => $reading->nhiet_do,
            'do_am'        => $reading->do_am,
            'thoi_gian_do' => $reading->thoi_gian_do ? $reading->thoi_gian_do->format('Y-m-d H:i:s.v') : null,
            'received_at'  => $reading->received_at  ? $reading->received_at->format('Y-m-d H:i:s')    : null,
            'topic'        => $topic,
        ];
    }

    // ----------------------------------------------------------------
    // Đánh giá cảnh báo
    // ----------------------------------------------------------------

    /**
     * Tìm cấu hình ngưỡng phù hợp nhất cho thiết bị.
     * Thứ tự ưu tiên: kho_lanh cụ thể > san_pham cụ thể > mặc định toàn hệ thống.
     */
    protected function findThreshold(ThietBiIot $device): ?object
    {
        $activeStatuses = CauHinhNguongCanhBao::TRANG_THAI_HIEU_LUC;
        $productIds = $this->productIdsForDevice($device);

        // Ngưỡng gắn đồng thời kho và loại hàng là cấu hình cụ thể nhất.
        if ($device->kho_lanh_id && $productIds->isNotEmpty()) {
            $byWarehouseAndProduct = CauHinhNguongCanhBao::whereIn('trang_thai', $activeStatuses)
                ->where('kho_lanh_id', $device->kho_lanh_id)
                ->whereIn('san_pham_id', $productIds)
                ->where(function ($q) {
                    $q->whereNull('hieu_luc_tu')->orWhere('hieu_luc_tu', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('hieu_luc_den')->orWhere('hieu_luc_den', '>=', now());
                })
                ->orderByDesc('cau_hinh_id')
                ->first();

            if ($byWarehouseAndProduct) {
                return $byWarehouseAndProduct;
            }
        }

        // 1. Ngưỡng theo kho lạnh
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

        // Nếu kho/xe đang mang một loại hàng có cấu hình riêng toàn hệ thống,
        // ưu tiên cấu hình loại hàng trước ngưỡng mặc định.
        if ($productIds->isNotEmpty()) {
            $byProduct = CauHinhNguongCanhBao::whereIn('trang_thai', $activeStatuses)
                ->whereNull('kho_lanh_id')
                ->whereIn('san_pham_id', $productIds)
                ->where(function ($q) {
                    $q->whereNull('hieu_luc_tu')->orWhere('hieu_luc_tu', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('hieu_luc_den')->orWhere('hieu_luc_den', '>=', now());
                })
                ->orderByDesc('cau_hinh_id')
                ->first();

            if ($byProduct) {
                return $byProduct;
            }
        }

        // 2. Ngưỡng mặc định toàn hệ thống (không gắn kho, không gắn sản phẩm)
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

    protected function productIdsForDevice(ThietBiIot $device)
    {
        if ($device->kho_lanh_id) {
            return DB::table('don_vi_dong_goi_sscc as dv')
                ->join('chi_tiet_dong_goi_sscc as ct', 'ct.sscc_id', '=', 'dv.sscc_id')
                ->join('lo_nong_san as l', 'l.lo_nong_san_id', '=', 'ct.lo_nong_san_id')
                ->where('dv.kho_lanh_id', $device->kho_lanh_id)
                ->whereIn('dv.trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO', 'CACH_LY'])
                ->whereNotNull('l.san_pham_id')
                ->distinct()
                ->pluck('l.san_pham_id');
        }

        $trip = $this->findActiveTrip($device);
        if (!$trip) {
            return collect();
        }

        return DB::table('phieu_giao_hang as p')
            ->join('chi_tiet_giao_nhan as g', 'g.phieu_giao_hang_id', '=', 'p.phieu_giao_hang_id')
            ->join('chi_tiet_dong_goi_sscc as ct', 'ct.sscc_id', '=', 'g.sscc_id')
            ->join('lo_nong_san as l', 'l.lo_nong_san_id', '=', 'ct.lo_nong_san_id')
            ->where('p.chuyen_id', $trip->chuyen_id)
            ->whereNotNull('l.san_pham_id')
            ->distinct()
            ->pluck('l.san_pham_id');
    }

    /**
     * Tìm chuyến vận chuyển đang hoạt động cho thiết bị xe.
     */
    protected function findActiveTrip(ThietBiIot $device): ?ChuyenVanChuyen
    {
        if (!$device->phuong_tien_id) {
            return null;
        }

        return ChuyenVanChuyen::where('phuong_tien_id', $device->phuong_tien_id)
            ->where('trang_thai', 'DANG_VAN_CHUYEN')
            ->orderByDesc('chuyen_id')
            ->first();
    }

    /**
     * Kiểm tra xem đã có cảnh báo mở (chưa xử lý) cho thiết bị và loại cảnh báo này chưa.
     */
    protected function hasOpenAlert(int $deviceId, string $alertType): bool
    {
        return CanhBaoChuoiLanh::where('thiet_bi_id', $deviceId)
            ->where('loai_canh_bao', $alertType)
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])
            ->exists();
    }

    /**
     * Kiểm tra xem đã vi phạm ngưỡng liên tục trong thời gian quy định chưa.
     * Trả về true nếu TẤT CẢ bản ghi trong khoảng thời gian đều vi phạm.
     *
     * @param int    $deviceId
     * @param int    $windowMinutes  Cửa sổ thời gian cần kiểm tra (phút)
     * @param string $alertType      NHIET_DO_CAO | NHIET_DO_THAP | DO_AM_VUOT_NGUONG
     * @param object $threshold      Cấu hình ngưỡng hiện tại
     */
    protected function isPersistentViolation(int $deviceId, int $windowMinutes, string $alertType, object $threshold): bool
    {
        if ($windowMinutes <= 0) {
            return true; // Không có cửa sổ thời gian => cảnh báo ngay lập tức
        }

        $since = Carbon::now()->subMinutes($windowMinutes);

        $readings = DuLieuCamBien::where('thiet_bi_id', $deviceId)
            ->where('thoi_gian_do', '>=', $since)
            ->orderBy('thoi_gian_do')
            ->get(['thoi_gian_do', 'nhiet_do', 'do_am']);

        // Cần ít nhất 2 bản ghi để xác nhận vi phạm liên tục
        if ($readings->count() < 2) {
            return false;
        }

        foreach ($readings as $r) {
            $violates = match ($alertType) {
                'NHIET_DO_CAO'      => $r->nhiet_do !== null && (float)$r->nhiet_do > (float)$threshold->nhiet_do_max,
                'NHIET_DO_THAP'     => $r->nhiet_do !== null && (float)$r->nhiet_do < (float)$threshold->nhiet_do_min,
                'DO_AM_VUOT_NGUONG' => $r->do_am    !== null && (
                    (float)$r->do_am > (float)$threshold->do_am_max ||
                    (float)$r->do_am < (float)$threshold->do_am_min
                ),
                default => false,
            };

            if (!$violates) {
                return false; // Có ít nhất một bản ghi không vi phạm => không liên tục
            }
        }

        return true;
    }

    /**
     * Đánh giá và tạo cảnh báo dựa trên bản ghi vừa lưu.
     */
    protected function evaluateAlerts(ThietBiIot $device, DuLieuCamBien $reading, ?float $temperature, ?float $humidity): void
    {
        $threshold = $this->findThreshold($device);

        if (!$threshold) {
            return; // Không có cấu hình ngưỡng => bỏ qua
        }

        $activeTrip = $this->findActiveTrip($device);
        $windowMinutes = (int)$threshold->thoi_gian_vuot_nguong_phut;

        $alertsToCheck = [];

        // Kiểm tra nhiệt độ cao
        if ($temperature !== null && $threshold->nhiet_do_max !== null) {
            if ($temperature > (float)$threshold->nhiet_do_max) {
                $alertsToCheck[] = [
                    'type'          => 'NHIET_DO_CAO',
                    'actual_value'  => $temperature,
                    'threshold_val' => (float)$threshold->nhiet_do_max,
                ];
            }
        }

        // Kiểm tra nhiệt độ thấp
        if ($temperature !== null && $threshold->nhiet_do_min !== null) {
            if ($temperature < (float)$threshold->nhiet_do_min) {
                $alertsToCheck[] = [
                    'type'          => 'NHIET_DO_THAP',
                    'actual_value'  => $temperature,
                    'threshold_val' => (float)$threshold->nhiet_do_min,
                ];
            }
        }

        // Kiểm tra độ ẩm
        if ($humidity !== null) {
            $humViolated = false;
            $humThreshold = null;

            if ($threshold->do_am_max !== null && $humidity > (float)$threshold->do_am_max) {
                $humViolated = true;
                $humThreshold = (float)$threshold->do_am_max;
            } elseif ($threshold->do_am_min !== null && $humidity < (float)$threshold->do_am_min) {
                $humViolated = true;
                $humThreshold = (float)$threshold->do_am_min;
            }

            if ($humViolated) {
                $alertsToCheck[] = [
                    'type'          => 'DO_AM_VUOT_NGUONG',
                    'actual_value'  => $humidity,
                    'threshold_val' => $humThreshold,
                ];
            }
        }

        foreach ($alertsToCheck as $check) {
            // Bỏ qua nếu đã có cảnh báo mở cùng loại cho thiết bị này
            if ($this->hasOpenAlert($device->thiet_bi_id, $check['type'])) {
                continue;
            }

            // Kiểm tra vi phạm liên tục trong cửa sổ thời gian
            if (!$this->isPersistentViolation($device->thiet_bi_id, $windowMinutes, $check['type'], $threshold)) {
                continue;
            }

            // Tạo cảnh báo
            $alert = CanhBaoChuoiLanh::create([
                'thiet_bi_id'         => $device->thiet_bi_id,
                'chuyen_id'           => $activeTrip?->chuyen_id,
                'cau_hinh_id'         => $threshold->cau_hinh_id,
                'du_lieu_kich_hoat_id' => $reading->du_lieu_id,
                'loai_canh_bao'       => $check['type'],
                'gia_tri_thuc_te'     => $check['actual_value'],
                'nguong_tai_thoi_diem' => $check['threshold_val'],
                'thoi_gian_phat_hien' => now(),
                'trang_thai'          => 'CHUA_XU_LY',
            ]);

            $this->attachAffectedSscc($alert, $device, $activeTrip);

            Log::info('Cảnh báo chuỗi lạnh mới: ' . $check['type'], [
                'device'        => $device->ma_thiet_bi,
                'actual'        => $check['actual_value'],
                'threshold'     => $check['threshold_val'],
                'chuyen_id'     => $activeTrip?->chuyen_id,
            ]);
        }
    }

    protected function attachAffectedSscc(CanhBaoChuoiLanh $alert, ThietBiIot $device, ?ChuyenVanChuyen $activeTrip): void
    {
        if ($device->kho_lanh_id) {
            $ssccIds = DB::table('don_vi_dong_goi_sscc')
                ->where('kho_lanh_id', $device->kho_lanh_id)
                ->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO', 'CACH_LY'])
                ->pluck('sscc_id');
        } elseif ($activeTrip) {
            $ssccIds = DB::table('phieu_giao_hang as p')
                ->join('chi_tiet_giao_nhan as g', 'g.phieu_giao_hang_id', '=', 'p.phieu_giao_hang_id')
                ->where('p.chuyen_id', $activeTrip->chuyen_id)
                ->whereNotNull('g.sscc_id')
                ->distinct()
                ->pluck('g.sscc_id');
        } else {
            $ssccIds = collect();
        }

        if ($ssccIds->isNotEmpty()) {
            DB::table('canh_bao_sscc')->insertOrIgnore(
                $ssccIds->map(fn ($id) => ['canh_bao_id' => $alert->canh_bao_id, 'sscc_id' => $id])->all()
            );
        }
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Trích xuất mã thiết bị từ topic MQTT chuẩn hóa:
     * Ví dụ:
     * - farm2fork/kho/KL-DL-01-CB01/telemetry -> KL-DL-01-CB01
     * - farm2fork/xe/XE-51C28419-CB01/telemetry -> XE-51C28419-CB01
     */
    protected function extractDeviceCodeFromTopic(string $topic): ?string
    {
        $parts = explode('/', trim($topic, '/'));
        // Nếu topic có dạng: farm2fork / [kho|xe] / {ma_thiet_bi} / telemetry
        if (count($parts) >= 3) {
            if ($parts[count($parts) - 1] === 'telemetry') {
                return $parts[count($parts) - 2];
            }
        }
        return null;
    }
}

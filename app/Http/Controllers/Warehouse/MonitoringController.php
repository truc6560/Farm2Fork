<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\CanhBaoChuoiLanh;
use App\Models\DonViDongGoiSscc;
use App\Models\DuLieuCamBien;
use App\Models\KhoLanh;
use App\Models\ThietBiIot;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Display real-time telemetry monitoring and cold chain alerts.
     */
    public function index(Request $request): View
    {
        $warehouseFilter = $request->query('kho_lanh_id');
        $alertStatus = $request->query('trang_thai_canh_bao', '');

        // 1. All cold storage sensors with their latest readings
        $sensorsQuery = ThietBiIot::with(['khoLanh', 'latestReading'])
            ->whereNotNull('kho_lanh_id');

        if ($warehouseFilter) {
            $sensorsQuery->where('kho_lanh_id', $warehouseFilter);
        }

        $sensors = $sensorsQuery->get()->map(function ($sensor) {
            $reading = $sensor->latestReading;
            $whName = $sensor->khoLanh?->ten_kho ?? '';

            // Standard temperature range target based on warehouse type name
            $isDeepCold = str_contains($whName, '0-4') || str_contains($sensor->khoLanh?->ma_kho ?? '', 'DL-01') || str_contains($sensor->khoLanh?->ma_kho ?? '', 'HCM-01');
            $targetMin = $isDeepCold ? 0.0 : 8.0;
            $targetMax = $isDeepCold ? 4.0 : 13.0;

            $temp = (float)($reading?->nhiet_do ?? 0);
            $isTempSafe = ($reading !== null) && ($temp >= ($targetMin - 0.5) && $temp <= ($targetMax + 1.0));

            $sensor->target_min = $targetMin;
            $sensor->target_max = $targetMax;
            $sensor->is_temp_safe = $isTempSafe;
            $sensor->current_temp = $reading?->nhiet_do;
            $sensor->current_humidity = $reading?->do_am;
            $sensor->signal_status = $reading?->trang_thai_tin_hieu ?? 'BINH_THUONG';
            $sensor->last_update = $reading?->thoi_gian_do;

            return $sensor;
        });

        // 2. Cold Chain Alerts
        $alertsQuery = CanhBaoChuoiLanh::with(['thietBi.khoLanh', 'thietBi.phuongTien', 'nguoiXuLy']);

        if ($alertStatus !== '') {
            $alertsQuery->where('trang_thai', $alertStatus);
        }
        if ($warehouseFilter) {
            $alertsQuery->whereHas('thietBi', fn($tQ) => $tQ->where('kho_lanh_id', $warehouseFilter));
        }

        $alerts = $alertsQuery->orderByDesc('thoi_gian_phat_hien')->paginate(10, ['*'], 'alerts_page')->withQueryString();

        // 3. Telemetry history (downsampled to 20-minute intervals)
        $warehouseSensorIds = $sensors->pluck('thiet_bi_id')->toArray();
        $recentTelemetry = collect();

        if (!empty($warehouseSensorIds)) {
            $rawTelemetry = DuLieuCamBien::with('thietBi.khoLanh')
                ->whereIn('thiet_bi_id', $warehouseSensorIds)
                ->orderByDesc('thoi_gian_do')
                ->limit(200)
                ->get();

            // Downsample per device by 20 minutes (1200 seconds)
            $grouped = $rawTelemetry->groupBy('thiet_bi_id');
            foreach ($grouped as $devId => $readings) {
                $lastTime = null;
                foreach ($readings as $r) {
                    $curTime = Carbon::parse($r->thoi_gian_do);
                    if ($lastTime === null || abs($curTime->diffInSeconds($lastTime)) >= 1200) {
                        $recentTelemetry->push($r);
                        $lastTime = $curTime;
                    }
                }
            }
            $recentTelemetry = $recentTelemetry->sortByDesc('thoi_gian_do')->take(20)->values();
        }

        // Summary statistics
        $unhandledAlertsCount = CanhBaoChuoiLanh::where('trang_thai', 'CHUA_XU_LY')->count();
        $resolvedAlertsCount = CanhBaoChuoiLanh::where('trang_thai', 'DA_XU_LY')->count();
        $totalSensorsCount = $sensors->count();
        $activeSensorsCount = $sensors->where('trang_thai', 'HOAT_DONG')->count();

        $warehouses = KhoLanh::orderBy('kho_lanh_id')->get();

        return view('warehouse.monitoring.index', [
            'sensors' => $sensors,
            'alerts' => $alerts,
            'recentTelemetry' => $recentTelemetry,
            'warehouses' => $warehouses,
            'warehouseFilter' => $warehouseFilter,
            'alertStatus' => $alertStatus,
            'unhandledAlertsCount' => $unhandledAlertsCount,
            'resolvedAlertsCount' => $resolvedAlertsCount,
            'totalSensorsCount' => $totalSensorsCount,
            'activeSensorsCount' => $activeSensorsCount,
        ]);
    }

    /**
     * Resolve a cold chain alert.
     */
    public function resolveAlert(Request $request, int $id): RedirectResponse
    {
        $alert = CanhBaoChuoiLanh::with(['thietBi', 'donViSscc.chiTiet.loNongSan'])->findOrFail($id);
        $user = $request->user();

        // Kiểm tra phân quyền: nếu có kho_lanh_id thì NV_KHO_LANH phải được phân công vào kho đó
        if ($alert->thietBi?->kho_lanh_id && $user->vaiTro?->ma_vai_tro === 'NV_KHO_LANH') {
            abort_unless(
                $user->khoLanh()->where('kho_lanh.kho_lanh_id', $alert->thietBi->kho_lanh_id)->exists(),
                403,
                'Bạn không được phân công quản lý kho lạnh của thiết bị này.'
            );
        }
        if ($alert->thietBi?->phuong_tien_id && $user->vaiTro?->ma_vai_tro === 'NV_KHO_LANH') {
            $assignedWarehouseIds = $user->khoLanh()->pluck('kho_lanh.kho_lanh_id');
            $managedTrip = $alert->chuyen_id && DB::table('bien_dong_kho as b')
                ->join('phieu_giao_hang as p', 'p.phieu_giao_hang_id', '=', 'b.phieu_giao_hang_id')
                ->where('p.chuyen_id', $alert->chuyen_id)
                ->whereIn('b.kho_lanh_id', $assignedWarehouseIds)
                ->exists();
            abort_unless($managedTrip, 403, 'Cảnh báo chuyến xe này không thuộc hàng xuất từ kho bạn phụ trách.');
        }

        $validated = $request->validate([
            'hanh_dong_khac_phuc' => ['required', 'string', 'max:2000'],
            'ket_qua_khac_phuc' => ['required', 'string', 'max:2000'],
            'danh_gia_lo' => ['required', 'in:CON_DAT,CACH_LY,KHONG_DAT'],
            'ghi_chu_danh_gia' => ['nullable', 'required_unless:danh_gia_lo,CON_DAT', 'string', 'max:2000'],
        ], [
            'hanh_dong_khac_phuc.required' => 'Vui lòng mô tả hành động khắc phục đã thực hiện.',
            'ket_qua_khac_phuc.required' => 'Vui lòng nhập kết quả sau khi xử lý.',
        ]);

        DB::transaction(function () use ($alert, $validated, $user) {
            $lockedAlert = CanhBaoChuoiLanh::lockForUpdate()->findOrFail($alert->canh_bao_id);
            if (!in_array($lockedAlert->trang_thai, ['CHUA_XU_LY', 'DANG_XU_LY'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'alert' => 'Cảnh báo này đã được xử lý trước đó.',
                ]);
            }

            $units = $alert->donViSscc;
            if ($units->isEmpty()) {
                $units = $this->inferAffectedSscc($alert);
            }

            $processedLotIds = [];
            foreach ($units as $unit) {
                if ($validated['danh_gia_lo'] === 'CACH_LY') {
                    $unit->update(['trang_thai' => 'CACH_LY']);
                } elseif ($validated['danh_gia_lo'] === 'KHONG_DAT') {
                    $unit->update(['trang_thai' => 'KHONG_DAT']);
                }

                if ($validated['danh_gia_lo'] !== 'CON_DAT') {
                    foreach ($unit->chiTiet as $content) {
                        $lot = $content->loNongSan;
                        if (!$lot || isset($processedLotIds[$lot->lo_nong_san_id]) || in_array($lot->trang_thai, ['THU_HOI', 'TRA_NHA_CUNG_CAP', 'KHONG_DAT'], true)) {
                            continue;
                        }
                        $processedLotIds[$lot->lo_nong_san_id] = true;
                        $target = $validated['danh_gia_lo'] === 'CACH_LY' ? 'CACH_LY' : 'KHONG_DAT';
                        app(\App\Services\LotStatusService::class)->transition(
                            $lot,
                            $target,
                            $user->tai_khoan_id,
                            "Đánh giá sau cảnh báo chuỗi lạnh #{$alert->canh_bao_id}: ".($validated['ghi_chu_danh_gia'] ?? $validated['ket_qua_khac_phuc'])
                        );
                    }
                }

                DB::table('canh_bao_sscc')->updateOrInsert(
                    ['canh_bao_id' => $alert->canh_bao_id, 'sscc_id' => $unit->sscc_id],
                    ['ket_qua_danh_gia' => $validated['danh_gia_lo'], 'ghi_chu' => $validated['ghi_chu_danh_gia'] ?? null]
                );
            }

            $lockedAlert->update([
                'hanh_dong_khac_phuc' => $validated['hanh_dong_khac_phuc'],
                'ket_qua_khac_phuc' => $validated['ket_qua_khac_phuc'],
                'danh_gia_lo' => $validated['danh_gia_lo'],
                'ghi_chu_danh_gia' => $validated['ghi_chu_danh_gia'] ?? null,
                'trang_thai' => 'DA_XU_LY',
                'nguoi_xu_ly_id' => $user->tai_khoan_id,
                'thoi_gian_xu_ly' => now(),
            ]);
        });

        return redirect()->route('warehouse.monitoring.index')
            ->with('status', "Đã ghi nhận xử lý thành công cảnh báo #{$alert->canh_bao_id}.");
    }

    private function inferAffectedSscc(CanhBaoChuoiLanh $alert)
    {
        $query = DonViDongGoiSscc::with('chiTiet.loNongSan');

        if ($alert->thietBi?->kho_lanh_id) {
            return $query->where('kho_lanh_id', $alert->thietBi->kho_lanh_id)
                ->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO', 'CACH_LY'])
                ->get();
        }

        if ($alert->chuyen_id) {
            return $query->whereHas('chiTietGiaoNhan.phieuGiaoHang', fn ($delivery) => $delivery->where('chuyen_id', $alert->chuyen_id))->get();
        }

        return collect();
    }
}


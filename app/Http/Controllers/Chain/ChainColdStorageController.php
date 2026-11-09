<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\CanhBaoChuoiLanh;
use App\Models\CauHinhNguongCanhBao;
use App\Models\KhoLanh;
use App\Models\ThietBiIot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChainColdStorageController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Fetch all cold storage facilities with attached resources
        $warehouses = KhoLanh::with([
            'loNongSan' => fn($q) => $q->whereIn('trang_thai', ['LUU_KHO', 'TRONG_KHO']),
            'donViDongGoi' => fn($q) => $q->whereIn('trang_thai', ['DA_NHAP_KHO', 'TRONG_KHO']),
            'thietBiIot.latestReading',
            'nhanVien',
        ])->get();

        // Calculate occupancy, sensor health, and thresholds for each warehouse
        $warehouseCards = $warehouses->map(function ($wh) {
            $totalLots = $wh->loNongSan->count();
            $totalQuantity = $wh->loNongSan->sum('so_luong');
            $totalSscc = $wh->donViDongGoi->count();

            $capacity = (float)($wh->suc_chua ?: 100);
            $occupancyRate = min(100, round(($totalQuantity / $capacity) * 100, 1));

            // Sensors
            $sensors = $wh->thietBiIot;
            $activeSensors = $sensors->where('trang_thai', 'HOAT_DONG')->count();

            // Latest temperature and humidity across sensors
            $latestReading = null;
            foreach ($sensors as $sensor) {
                if ($sensor->latestReading) {
                    if (!$latestReading || $sensor->latestReading->thoi_gian_do > $latestReading->thoi_gian_do) {
                        $latestReading = $sensor->latestReading;
                    }
                }
            }

            // Unresolved alerts for this warehouse
            $unresolvedAlerts = CanhBaoChuoiLanh::whereHas('thietBi', fn($t) => $t->where('kho_lanh_id', $wh->kho_lanh_id))
                ->where('trang_thai', 'CHUA_XU_LY')
                ->count();

            return [
                'model' => $wh,
                'totalLots' => $totalLots,
                'totalQuantity' => $totalQuantity,
                'totalSscc' => $totalSscc,
                'capacity' => $capacity,
                'occupancyRate' => $occupancyRate,
                'activeSensors' => $activeSensors,
                'totalSensors' => $sensors->count(),
                'latestReading' => $latestReading,
                'unresolvedAlerts' => $unresolvedAlerts,
            ];
        });

        // 2. Incident & Resolution Log query
        $alertsQuery = CanhBaoChuoiLanh::with([
            'thietBi.khoLanh',
            'thietBi.phuongTien',
            'chuyenVanChuyen',
            'nguoiXuLy',
        ]);

        if ($request->filled('kho_lanh_id')) {
            $alertsQuery->whereHas('thietBi', fn($t) => $t->where('kho_lanh_id', $request->input('kho_lanh_id')));
        }

        if ($request->filled('trang_thai')) {
            $alertsQuery->where('trang_thai', $request->input('trang_thai'));
        }

        if ($request->filled('loai_canh_bao')) {
            $alertsQuery->where('loai_canh_bao', $request->input('loai_canh_bao'));
        }

        $alerts = $alertsQuery->orderByDesc('thoi_gian_phat_hien')->paginate(10)->withQueryString();

        // Summary counts
        $totalAlertsCount = CanhBaoChuoiLanh::count();
        $unresolvedCount = CanhBaoChuoiLanh::where('trang_thai', 'CHUA_XU_LY')->count();
        $inProgressCount = CanhBaoChuoiLanh::where('trang_thai', 'DANG_XU_LY')->count();
        $resolvedCount = CanhBaoChuoiLanh::where('trang_thai', 'DA_XU_LY')->count();

        return view('chain.cold-storage.index', [
            'warehouseCards' => $warehouseCards,
            'alerts' => $alerts,
            'warehouses' => $warehouses,
            'filters' => $request->all(),
            'totalAlertsCount' => $totalAlertsCount,
            'unresolvedCount' => $unresolvedCount,
            'inProgressCount' => $inProgressCount,
            'resolvedCount' => $resolvedCount,
            'alertTypes' => CanhBaoChuoiLanh::LOAI_CANH_BAO_LABELS,
            'alertStatuses' => CanhBaoChuoiLanh::TRANG_THAI_LABELS,
        ]);
    }
}


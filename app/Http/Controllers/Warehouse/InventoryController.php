<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\DonViDongGoiSscc;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use App\Models\ThietBiIot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display warehouse inventory summary and stock lists.
     */
    public function index(Request $request): View
    {
        $warehouseFilter = $request->query('kho_lanh_id');
        $search = trim($request->query('q', ''));
        $tab = $request->query('tab', 'lots');

        // Retrieve all warehouses with metrics
        $warehouses = KhoLanh::with(['thietBiIot.latestReading'])
            ->orderBy('kho_lanh_id')
            ->get()
            ->map(function ($wh) {
                $storedLotWeight = LoNongSan::where('kho_lanh_id', $wh->kho_lanh_id)
                    ->whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO'])
                    ->sum('so_luong');

                $storedSsccCount = DonViDongGoiSscc::where('kho_lanh_id', $wh->kho_lanh_id)
                    ->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO'])
                    ->count();

                $capacity = (float)($wh->suc_chua ?? 0);
                $utilizationRate = ($capacity > 0) ? min(100, round(($storedLotWeight / $capacity) * 100, 1)) : 0;

                $latestSensor = $wh->thietBiIot->first()?->latestReading;

                $wh->stored_weight = $storedLotWeight;
                $wh->stored_sscc_count = $storedSsccCount;
                $wh->utilization_rate = $utilizationRate;
                $wh->current_temp = $latestSensor?->nhiet_do;
                $wh->current_humidity = $latestSensor?->do_am;
                $wh->last_telemetry_time = $latestSensor?->thoi_gian_do;

                return $wh;
            });

        // 1. In-stock lots query
        $lotsQuery = LoNongSan::with(['sanPham', 'khoLanh', 'toChucNguon'])
            ->whereNotNull('kho_lanh_id')
            ->whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO']);

        if ($warehouseFilter) {
            $lotsQuery->where('kho_lanh_id', $warehouseFilter);
        }
        if ($search !== '') {
            $lotsQuery->where(function ($q) use ($search) {
                $q->where('ma_lo', 'like', "%{$search}%")
                  ->orWhereHas('sanPham', fn($sp) => $sp->where('ten_san_pham', 'like', "%{$search}%"));
            });
        }
        $lots = $lotsQuery->orderByDesc('lo_nong_san_id')->paginate(15, ['*'], 'lots_page')->withQueryString();

        // 2. In-stock SSCC units query
        $ssccQuery = DonViDongGoiSscc::with(['khoLanh', 'chiTiet.loNongSan.sanPham'])
            ->whereNotNull('kho_lanh_id')
            ->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO']);

        if ($warehouseFilter) {
            $ssccQuery->where('kho_lanh_id', $warehouseFilter);
        }
        if ($search !== '') {
            $ssccQuery->where('ma_sscc', 'like', "%{$search}%");
        }
        $ssccUnits = $ssccQuery->orderByDesc('sscc_id')->paginate(15, ['*'], 'sscc_page')->withQueryString();

        // Summary counts
        $totalWeight = LoNongSan::whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO'])->sum('so_luong');
        $totalLots = LoNongSan::whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO'])->count();
        $totalSscc = DonViDongGoiSscc::whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO'])->count();

        return view('warehouse.inventory.index', [
            'warehouses' => $warehouses,
            'warehouseFilter' => $warehouseFilter,
            'search' => $search,
            'tab' => $tab,
            'lots' => $lots,
            'ssccUnits' => $ssccUnits,
            'totalWeight' => $totalWeight,
            'totalLots' => $totalLots,
            'totalSscc' => $totalSscc,
        ]);
    }

    /**
     * Display detailed warehouse profile and inventory audit.
     */
    public function show(Request $request, int $id): View
    {
        $search = trim((string) $request->query('q', ''));
        $itemType = $request->query('item_type', 'all');
        $status = $request->query('trang_thai', '');

        $warehouse = KhoLanh::with([
            'thietBiIot.latestReading',
            'nhanVien',
            'loNongSan.sanPham',
            'donViDongGoi.chiTiet.loNongSan.sanPham',
        ])->findOrFail($id);

        $storedLotsQuery = LoNongSan::with(['sanPham', 'toChucNguon'])
            ->where('kho_lanh_id', $warehouse->kho_lanh_id)
            ->whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO']);

        if ($itemType === 'sscc') {
            $storedLotsQuery->whereRaw('1 = 0');
        }
        if ($status !== '') {
            $storedLotsQuery->where('trang_thai', $status);
        }
        if ($search !== '') {
            $storedLotsQuery->where(function ($query) use ($search) {
                $query->where('ma_lo', 'like', "%{$search}%")
                    ->orWhereHas('sanPham', fn ($product) => $product->where('ten_san_pham', 'like', "%{$search}%"));
            });
        }
        $storedLots = $storedLotsQuery->orderByDesc('lo_nong_san_id')->paginate(15, ['*'], 'lots_page')->withQueryString();

        $storedSsccsQuery = DonViDongGoiSscc::with(['chiTiet.loNongSan.sanPham'])
            ->where('kho_lanh_id', $warehouse->kho_lanh_id)
            ->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO']);

        if ($itemType === 'lots') {
            $storedSsccsQuery->whereRaw('1 = 0');
        }
        if ($status !== '') {
            $storedSsccsQuery->where('trang_thai', $status);
        }
        if ($search !== '') {
            $storedSsccsQuery->where(function ($query) use ($search) {
                $query->where('ma_sscc', 'like', "%{$search}%")
                    ->orWhereHas('chiTiet.loNongSan', function ($lot) use ($search) {
                        $lot->where('ma_lo', 'like', "%{$search}%")
                            ->orWhereHas('sanPham', fn ($product) => $product->where('ten_san_pham', 'like', "%{$search}%"));
                    });
            });
        }
        $storedSsccs = $storedSsccsQuery->orderByDesc('sscc_id')->paginate(15, ['*'], 'sscc_page')->withQueryString();

        $totalStoredWeight = LoNongSan::where('kho_lanh_id', $warehouse->kho_lanh_id)
            ->whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO'])
            ->sum('so_luong');
        $capacity = (float)($warehouse->suc_chua ?? 0);
        $utilizationRate = ($capacity > 0) ? min(100, round(($totalStoredWeight / $capacity) * 100, 1)) : 0;

        return view('warehouse.inventory.show', [
            'warehouse' => $warehouse,
            'storedLots' => $storedLots,
            'storedSsccs' => $storedSsccs,
            'totalStoredWeight' => $totalStoredWeight,
            'utilizationRate' => $utilizationRate,
            'search' => $search,
            'itemType' => $itemType,
            'status' => $status,
        ]);
    }
}


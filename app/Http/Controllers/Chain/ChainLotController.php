<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\DanhMucSanPham;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use App\Models\PhieuKiemDinh;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChainLotController extends Controller
{
    public function index(Request $request): View
    {
        $query = LoNongSan::with([
            'sanPham',
            'muaVu.vungTrong',
            'toChucNguon',
            'khoLanh',
            'phieuKiemDinh',
            'donViSscc',
        ]);

        // Keyword filter
        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('ma_lo', 'like', "%{$keyword}%")
                  ->orWhere('ma_lo_nha_cung_cap', 'like', "%{$keyword}%")
                  ->orWhereHas('sanPham', fn($sp) => $sp->where('ten_san_pham', 'like', "%{$keyword}%"));
            });
        }

        // Lot type filter
        if ($request->filled('loai_lo')) {
            $query->where('loai_lo', $request->input('loai_lo'));
        }

        // Status filter
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        // Product filter
        if ($request->filled('san_pham_id')) {
            $query->where('san_pham_id', $request->input('san_pham_id'));
        }

        // Warehouse filter
        if ($request->filled('kho_lanh_id')) {
            $query->where('kho_lanh_id', $request->input('kho_lanh_id'));
        }

        // Season filter
        if ($request->filled('mua_vu_id')) {
            $query->where('mua_vu_id', $request->input('mua_vu_id'));
        }

        // QC inspection status filter
        if ($request->filled('qc_status')) {
            $qc = $request->input('qc_status');
            $query->whereHas('phieuKiemDinh', fn($pq) => $pq->where('ket_luan', $qc));
        }

        // Traceability data gap filter
        if ($request->boolean('gap_only')) {
            $query->where(function ($q) {
                $q->where(function ($rawQ) {
                    $rawQ->where('loai_lo', 'NGUYEN_LIEU')->whereNull('mua_vu_id');
                })->orWhere(function ($finQ) {
                    $finQ->where('loai_lo', 'THANH_PHAM')->whereDoesntHave('nguyenLieuDauVao');
                })->orWhere(function ($noQc) {
                    $noQc->whereNotIn('trang_thai', ['MOI_TAO', 'CHO_KIEM_DINH'])
                         ->whereDoesntHave('phieuKiemDinh');
                });
            });
        }

        // Date range filter
        if ($request->filled('tu_ngay')) {
            $query->whereDate('ngay_thu_hoach', '>=', $request->input('tu_ngay'));
        }
        if ($request->filled('den_ngay')) {
            $query->whereDate('ngay_thu_hoach', '<=', $request->input('den_ngay'));
        }

        $lots = $query->orderByDesc('lo_nong_san_id')->paginate(15)->withQueryString();

        // Summary counts scoped to filtered warehouse / season if selected
        $statsBase = LoNongSan::query();
        if ($request->filled('kho_lanh_id')) {
            $statsBase->where('kho_lanh_id', $request->input('kho_lanh_id'));
        }
        if ($request->filled('mua_vu_id')) {
            $statsBase->where('mua_vu_id', $request->input('mua_vu_id'));
        }

        $totalCount = (clone $statsBase)->count();
        $rawCount = (clone $statsBase)->where('loai_lo', 'NGUYEN_LIEU')->count();
        $finishedCount = (clone $statsBase)->where('loai_lo', 'THANH_PHAM')->count();
        $warningCount = (clone $statsBase)->whereIn('trang_thai', ['TRA_NHA_CUNG_CAP', 'THU_HOI', 'KHONG_DAT'])->count();

        $selectedWarehouse = $request->filled('kho_lanh_id') ? KhoLanh::find($request->input('kho_lanh_id')) : null;
        $selectedSeason = $request->filled('mua_vu_id') ? \App\Models\MuaVu::find($request->input('mua_vu_id')) : null;

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();
        $warehouses = KhoLanh::whereIn('trang_thai', ['DANG_HOAT_DONG', 'HOAT_DONG'])->orderBy('ten_kho')->get();

        return view('chain.lots.index', [
            'lots' => $lots,
            'filters' => $request->all(),
            'totalCount' => $totalCount,
            'rawCount' => $rawCount,
            'finishedCount' => $finishedCount,
            'warningCount' => $warningCount,
            'selectedWarehouse' => $selectedWarehouse,
            'selectedSeason' => $selectedSeason,
            'products' => $products,
            'warehouses' => $warehouses,
            'statusLabels' => LoNongSan::TRANG_THAI_LABELS,
        ]);
    }

    public function show(int $id): View
    {
        $lot = LoNongSan::with([
            'sanPham',
            'muaVu.vungTrong.toChuc',
            'muaVu.nhatKyCanhTac.nguoiThucHien',
            'muaVu.nhatKyCanhTac.chiTietVatTu.vatTu',
            'toChucNguon',
            'khoLanh.thietBiIot.latestReading',
            'nguyenLieuDauVao.sanPham',
            'thanhPhamDauRa.sanPham',
            'donViSscc.chiTietGiaoNhan.phieuGiaoHang.chuyenVanChuyen.phuongTien.thietBiIot.latestReading',
            'donViSscc.chiTietGiaoNhan.phieuGiaoHang.chuyenVanChuyen.taiXe',
            'donViSscc.chiTietGiaoNhan.phieuGiaoHang.chuyenVanChuyen.suKienHanhTrinh',
            'phieuKiemDinh.chiTietKetQua',
            'phieuKiemDinh.nguoiKiemDinh',
            'phieuKiemDinh.nguoiKhacPhuc',
            'lichSuTrangThai.taiKhoan',
        ])->findOrFail($id);

        // Analyze continuity of traceability chain
        $checks = [
            'has_production' => !empty($lot->mua_vu_id),
            'has_farming_logs' => $lot->muaVu && $lot->muaVu->nhatKyCanhTac->isNotEmpty(),
            'has_material_trace' => $lot->loai_lo === 'NGUYEN_LIEU' ? true : $lot->nguyenLieuDauVao->isNotEmpty(),
            'has_qc' => $lot->phieuKiemDinh->isNotEmpty(),
            'qc_passed' => $lot->phieuKiemDinh->where('ket_luan', 'DAT')->isNotEmpty(),
            'has_storage' => !empty($lot->kho_lanh_id),
            'has_packaging' => $lot->donViSscc->isNotEmpty(),
        ];

        $traceGaps = app(\App\Services\TraceabilityCoverageService::class)->gaps($lot);
        $isCompleteTrace = empty($traceGaps) && $checks['has_production'] && $checks['has_material_trace'];

        // Latest QC record
        $latestQc = $lot->phieuKiemDinh->sortByDesc('phieu_id')->first();

        // Linked SSCC units and transport trips
        $ssccUnits = $lot->donViSscc;
        $trips = collect();
        foreach ($ssccUnits as $sscc) {
            foreach ($sscc->chiTietGiaoNhan as $gn) {
                if ($gn->phieuGiaoHang && $gn->phieuGiaoHang->chuyenVanChuyen) {
                    $trips->push($gn->phieuGiaoHang->chuyenVanChuyen);
                }
            }
        }
        $trips = $trips->unique('chuyen_id');

        return view('chain.lots.show', [
            'lot' => $lot,
            'checks' => $checks,
            'isCompleteTrace' => $isCompleteTrace,
            'traceGaps' => $traceGaps,
            'latestQc' => $latestQc,
            'ssccUnits' => $ssccUnits,
            'trips' => $trips,
            'statusLabels' => LoNongSan::TRANG_THAI_LABELS,
        ]);
    }
}


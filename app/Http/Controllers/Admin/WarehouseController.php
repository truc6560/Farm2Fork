<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KhoLanh;
use App\Models\TaiKhoanHeThong;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    /**
     * Display a listing of cold storage warehouses.
     */
    public function index(Request $request): View
    {
        $query = KhoLanh::with(['nhanVien', 'thietBiIot'])
            ->withCount(['loNongSan', 'donViDongGoi']);

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('ten_kho', 'like', "%{$keyword}%")
                  ->orWhere('ma_kho', 'like', "%{$keyword}%")
                  ->orWhere('dia_chi', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        $warehouses = $query->orderBy('kho_lanh_id')->paginate(10)->withQueryString();

        // Statistical KPI metrics
        $totalCount = KhoLanh::count();
        $activeCount = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->count();
        $maintenanceCount = KhoLanh::whereIn('trang_thai', ['BAO_TRI', 'TAM_NGUNG', 'NGUNG_HOAT_DONG'])->count();
        $totalCapacity = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->sum('suc_chua');

        return view('admin.warehouses.index', [
            'warehouses' => $warehouses,
            'filters' => $request->all(),
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'maintenanceCount' => $maintenanceCount,
            'totalCapacity' => $totalCapacity,
            'statusLabels' => KhoLanh::TRANG_THAI_LABELS,
        ]);
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create(): View
    {
        $warehouseStaff = TaiKhoanHeThong::whereHas('vaiTro', fn($q) => $q->where('ma_vai_tro', 'NV_KHO_LANH'))
            ->where('trang_thai', 'DANG_HOAT_DONG')
            ->orderBy('ho_ten')
            ->get();

        return view('admin.warehouses.create', [
            'warehouseStaff' => $warehouseStaff,
            'statusLabels' => KhoLanh::TRANG_THAI_LABELS,
            'unitLabels' => KhoLanh::DON_VI_LABELS,
        ]);
    }

    /**
     * Store a newly created warehouse in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ten_kho' => ['required', 'string', 'max:150'],
            'ma_kho' => ['required', 'string', 'max:40', 'unique:kho_lanh,ma_kho'],
            'dia_chi' => ['required', 'string', 'max:255'],
            'suc_chua' => ['required', 'numeric', 'min:0.001'],
            'don_vi_suc_chua' => ['required', 'string', 'max:30'],
            'trang_thai' => ['required', 'in:DANG_HOAT_DONG,BAO_TRI,TAM_NGUNG,NGUNG_HOAT_DONG'],
            'nhan_vien_ids' => ['nullable', 'array'],
            'nhan_vien_ids.*' => ['exists:tai_khoan_he_thong,tai_khoan_id'],
        ], [
            'ten_kho.required' => 'Vui lòng nhập tên kho lạnh.',
            'ma_kho.required' => 'Vui lòng nhập mã định danh kho lạnh.',
            'ma_kho.unique' => 'Mã kho lạnh này đã tồn tại trong hệ thống.',
            'dia_chi.required' => 'Vui lòng nhập địa chỉ kho lạnh.',
            'suc_chua.required' => 'Vui lòng nhập sức chứa kho.',
            'suc_chua.min' => 'Sức chứa kho phải lớn hơn 0.',
        ]);

        $warehouse = KhoLanh::create([
            'ten_kho' => $validated['ten_kho'],
            'ma_kho' => strtoupper(trim($validated['ma_kho'])),
            'dia_chi' => $validated['dia_chi'],
            'suc_chua' => $validated['suc_chua'],
            'don_vi_suc_chua' => $validated['don_vi_suc_chua'],
            'trang_thai' => $validated['trang_thai'],
        ]);

        if (!empty($validated['nhan_vien_ids'])) {
            $syncData = [];
            $today = now()->toDateString();
            foreach ($validated['nhan_vien_ids'] as $staffId) {
                $syncData[$staffId] = [
                    'ngay_bat_dau' => $today,
                ];
            }
            $warehouse->nhanVien()->sync($syncData);
        }

        return redirect()->route('admin.warehouses.index')
            ->with('status', "Đã thêm mới thành công kho lạnh: {$warehouse->ten_kho} ({$warehouse->ma_kho})");
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(int $id): View
    {
        $warehouse = KhoLanh::with(['nhanVien', 'thietBiIot.latestReading'])
            ->withCount(['loNongSan', 'donViDongGoi'])
            ->findOrFail($id);

        $warehouseStaff = TaiKhoanHeThong::whereHas('vaiTro', fn($q) => $q->where('ma_vai_tro', 'NV_KHO_LANH'))
            ->where('trang_thai', 'DANG_HOAT_DONG')
            ->orderBy('ho_ten')
            ->get();

        $thresholds = DB::table('cau_hinh_nguong_canh_bao')
            ->where('kho_lanh_id', $id)
            ->orderByDesc('cau_hinh_id')
            ->get();

        return view('admin.warehouses.edit', [
            'warehouse' => $warehouse,
            'warehouseStaff' => $warehouseStaff,
            'thresholds' => $thresholds,
            'statusLabels' => KhoLanh::TRANG_THAI_LABELS,
            'unitLabels' => KhoLanh::DON_VI_LABELS,
        ]);
    }

    /**
     * Update the specified warehouse in database.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $warehouse = KhoLanh::findOrFail($id);

        $validated = $request->validate([
            'ten_kho' => ['required', 'string', 'max:150'],
            'ma_kho' => ['required', 'string', 'max:40', 'unique:kho_lanh,ma_kho,' . $id . ',kho_lanh_id'],
            'dia_chi' => ['required', 'string', 'max:255'],
            'suc_chua' => ['required', 'numeric', 'min:0.001'],
            'don_vi_suc_chua' => ['required', 'string', 'max:30'],
            'trang_thai' => ['required', 'in:DANG_HOAT_DONG,BAO_TRI,TAM_NGUNG,NGUNG_HOAT_DONG'],
            'nhan_vien_ids' => ['nullable', 'array'],
            'nhan_vien_ids.*' => ['exists:tai_khoan_he_thong,tai_khoan_id'],
        ], [
            'ten_kho.required' => 'Vui lòng nhập tên kho lạnh.',
            'ma_kho.required' => 'Vui lòng nhập mã định danh kho lạnh.',
            'ma_kho.unique' => 'Mã kho lạnh này đã tồn tại trong hệ thống.',
            'dia_chi.required' => 'Vui lòng nhập địa chỉ kho lạnh.',
            'suc_chua.required' => 'Vui lòng nhập sức chứa kho.',
            'suc_chua.min' => 'Sức chứa kho phải lớn hơn 0.',
        ]);

        $warehouse->update([
            'ten_kho' => $validated['ten_kho'],
            'ma_kho' => strtoupper(trim($validated['ma_kho'])),
            'dia_chi' => $validated['dia_chi'],
            'suc_chua' => $validated['suc_chua'],
            'don_vi_suc_chua' => $validated['don_vi_suc_chua'],
            'trang_thai' => $validated['trang_thai'],
        ]);

        // Sync staff assigned to this warehouse, preserving original ngay_bat_dau or setting today
        $selectedStaffIds = $request->input('nhan_vien_ids', []);
        $today = now()->toDateString();
        $existingPivots = DB::table('nhan_vien_kho')
            ->where('kho_lanh_id', $warehouse->kho_lanh_id)
            ->pluck('ngay_bat_dau', 'tai_khoan_id')
            ->toArray();

        $syncData = [];
        foreach ($selectedStaffIds as $staffId) {
            $syncData[$staffId] = [
                'ngay_bat_dau' => $existingPivots[$staffId] ?? $today,
            ];
        }
        $warehouse->nhanVien()->sync($syncData);

        return redirect()->route('admin.warehouses.index')
            ->with('status', "Đã cập nhật thành công thông tin kho lạnh: {$warehouse->ten_kho} ({$warehouse->ma_kho})");
    }
}

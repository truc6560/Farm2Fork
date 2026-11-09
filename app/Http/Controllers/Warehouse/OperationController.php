<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\ChuyenVanChuyen;
use App\Models\DonViDongGoiSscc;
use App\Models\KhoLanh;
use App\Models\LichSuTrangThaiLo;
use App\Models\LoNongSan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperationController extends Controller
{
    /**
     * Display inbound receiving and outbound dispatching management.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'inbound');
        $search = trim($request->query('q', ''));
        $warehouseId = $request->query('kho_lanh_id');
        $itemType = $request->query('item_type', 'all');
        $operationType = $request->query('operation_type', '');
        $fromDate = $request->query('from_date', '');
        $toDate = $request->query('to_date', '');

        $user = $request->user();
        $user->loadMissing('khoLanh');

        // Chỉ hiển thị các kho mà nhân viên đang được phân công. Trước đây
        // dropdown hiển thị toàn bộ kho nhưng khi lưu lại chặn theo phân quyền,
        // khiến người dùng chọn được một kho rồi nhận lỗi 403.
        $warehouses = $user->khoLanh()
            ->where('kho_lanh.trang_thai', 'DANG_HOAT_DONG')
            ->orderBy('kho_lanh.kho_lanh_id')
            ->get();
        $assignedWarehouseIds = $warehouses->pluck('kho_lanh_id')->map(fn ($id) => (int) $id);

        if ($warehouseId && !$assignedWarehouseIds->contains((int) $warehouseId)) {
            $warehouseId = null;
        }

        // Active transport trips for outbound dispatch
        $activeTrips = DB::table('chuyen_van_chuyen as c')
            ->leftJoin('phuong_tien as p', 'c.phuong_tien_id', '=', 'p.phuong_tien_id')
            ->leftJoin('tai_khoan_he_thong as tk', 'c.tai_xe_id', '=', 'tk.tai_khoan_id')
            ->whereIn('c.trang_thai', ['CHUAN_BI', 'SAN_SANG', 'DA_LAP_KE_HOACH'])
            ->select('c.*', 'p.bien_so', 'tk.ho_ten as ten_tai_xe')
            ->get();

        // 1. Items waiting for Inbound Receipt (Raw lots or Finished Lots or Packed SSCC)
        $pendingLotsQuery = LoNongSan::with(['sanPham', 'khoLanh', 'toChucNguon'])
            ->whereRaw('1 = 0')
            ->where('loai_lo', 'THANH_PHAM')
            ->where('trang_thai', 'DAT_KIEM_DINH')
            // Thành phẩm đã đóng SSCC phải được tiếp nhận theo kiện để không ghi nhận tồn hai lần.
            ->whereDoesntHave('chiTietDongGoi')
            ->whereHas('phieuKiemDinh', function ($inspection) {
                $inspection->whereIn('loai_kiem_dinh', ['TRUOC_XUAT_XUONG', 'KIEM_DINH_LAI'])
                    ->where('ket_luan', 'DAT');
            });

        if ($search !== '') {
            $pendingLotsQuery->where(function ($q) use ($search) {
                $q->where('ma_lo', 'like', "%{$search}%")
                  ->orWhereHas('sanPham', fn($sp) => $sp->where('ten_san_pham', 'like', "%{$search}%"));
            });
        }
        if ($itemType === 'sscc') {
            $pendingLotsQuery->whereRaw('1 = 0');
        }
        $pendingLotsTotal = (clone $pendingLotsQuery)->count();
        $pendingLots = $pendingLotsQuery->orderByDesc('lo_nong_san_id')->limit(30)->get();

        $pendingSsccQuery = DonViDongGoiSscc::with(['khoLanh', 'chiTiet.loNongSan.sanPham'])
            ->where('trang_thai', 'DA_DONG_GOI')
            ->whereHas('chiTiet')
            ->whereDoesntHave('chiTiet.loNongSan', fn ($lot) => $lot->where('trang_thai', '!=', 'DAT_KIEM_DINH'))
            ->whereHas('chiTiet.loNongSan.phieuKiemDinh', function ($inspection) {
                $inspection->whereIn('loai_kiem_dinh', ['TRUOC_XUAT_XUONG', 'KIEM_DINH_LAI'])
                    ->where('ket_luan', 'DAT');
            });

        if ($search !== '') {
            $pendingSsccQuery->where('ma_sscc', 'like', "%{$search}%");
        }
        if ($itemType === 'lot') {
            $pendingSsccQuery->whereRaw('1 = 0');
        }
        $pendingSsccsTotal = (clone $pendingSsccQuery)->count();
        $pendingSsccs = $pendingSsccQuery->orderByDesc('sscc_id')->limit(30)->get();

        // 2. Items in Warehouse ready for Outbound Dispatch
        $inStockLotsQuery = LoNongSan::with(['sanPham', 'khoLanh', 'toChucNguon'])
            ->whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO'])
            ->whereIn('kho_lanh_id', $assignedWarehouseIds);

        if ($warehouseId) {
            $inStockLotsQuery->where('kho_lanh_id', $warehouseId);
        }
        if ($search !== '') {
            $inStockLotsQuery->where(function ($q) use ($search) {
                $q->where('ma_lo', 'like', "%{$search}%")
                  ->orWhereHas('sanPham', fn($sp) => $sp->where('ten_san_pham', 'like', "%{$search}%"));
            });
        }
        $inStockLots = $inStockLotsQuery->orderByDesc('lo_nong_san_id')->limit(30)->get();

        $inStockSsccQuery = DonViDongGoiSscc::with(['khoLanh', 'chiTiet.loNongSan.sanPham'])
            ->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO'])
            ->whereIn('kho_lanh_id', $assignedWarehouseIds)
            ->whereHas('chiTiet')
            ->whereDoesntHave('chiTiet.loNongSan', fn ($lot) => $lot->where('trang_thai', '!=', 'DAT_KIEM_DINH'));

        if ($warehouseId) {
            $inStockSsccQuery->where('kho_lanh_id', $warehouseId);
        }
        if ($search !== '') {
            $inStockSsccQuery->where('ma_sscc', 'like', "%{$search}%");
        }
        $inStockSsccsTotal = (clone $inStockSsccQuery)->count();
        $inStockSsccs = $inStockSsccQuery->orderByDesc('sscc_id')->limit(30)->get();

        // 3. Recent Operation Logs
        $recentLogsQuery = DB::table('lich_su_trang_thai_lo as ls')
            ->join('lo_nong_san as l', 'ls.lo_nong_san_id', '=', 'l.lo_nong_san_id')
            ->leftJoin('tai_khoan_he_thong as tk', 'ls.tai_khoan_id', '=', 'tk.tai_khoan_id')
            ->leftJoin('danh_muc_san_pham as sp', 'l.san_pham_id', '=', 'sp.san_pham_id')
            ->whereIn('ls.trang_thai_moi', ['TRONG_KHO', 'LUU_KHO', 'DANG_VAN_CHUYEN', 'DA_NHAP_KHO'])
            ->select('ls.*', 'l.ma_lo', 'sp.ten_san_pham', 'tk.ho_ten as nguoi_thuc_hien');

        if ($search !== '') {
            $recentLogsQuery->where(function ($query) use ($search) {
                $query->where('l.ma_lo', 'like', "%{$search}%")
                    ->orWhere('sp.ten_san_pham', 'like', "%{$search}%")
                    ->orWhere('tk.ho_ten', 'like', "%{$search}%")
                    ->orWhere('ls.ly_do', 'like', "%{$search}%");
            });
        }
        if ($operationType === 'NHAP') {
            $recentLogsQuery->whereIn('ls.trang_thai_moi', ['TRONG_KHO', 'LUU_KHO', 'DA_NHAP_KHO']);
        } elseif ($operationType === 'XUAT') {
            $recentLogsQuery->where('ls.trang_thai_moi', 'DANG_VAN_CHUYEN');
        }
        if ($fromDate !== '') {
            $recentLogsQuery->whereDate('ls.thoi_gian', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $recentLogsQuery->whereDate('ls.thoi_gian', '<=', $toDate);
        }
        $recentLogs = $recentLogsQuery->orderByDesc('ls.thoi_gian')->limit(50)->get();

        return view('warehouse.operations.index', [
            'organizations' => \App\Models\ToChuc::orderBy('ten_to_chuc')->get(),
            'tab' => $tab,
            'search' => $search,
            'warehouseId' => $warehouseId,
            'itemType' => $itemType,
            'operationType' => $operationType,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'warehouses' => $warehouses,
            'activeTrips' => $activeTrips,
            'pendingLots' => $pendingLots,
            'pendingSsccs' => $pendingSsccs,
            'pendingItemsTotal' => $pendingLotsTotal + $pendingSsccsTotal,
            'inStockLots' => $inStockLots,
            'inStockSsccs' => $inStockSsccs,
            'inStockSsccsTotal' => $inStockSsccsTotal,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Process receiving a lot or SSCC unit into cold storage.
     */
    public function receive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:sscc'],
            'id' => ['required', 'integer'],
            'kho_lanh_id' => ['required', 'integer', 'exists:kho_lanh,kho_lanh_id'],
            'nhiet_do_luc_nhan' => ['required', 'numeric', 'min:-50', 'max:80'],
            'tinh_trang_bao_goi' => ['required', 'in:NGUYEN_VEN,MOP_NHE,HU_HONG_NANG'],
            'vi_tri_luu_kho' => ['required', 'string', 'max:120'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'kho_lanh_id.required' => 'Vui lòng chọn kho lạnh tiếp nhận.',
        ]);

        $userId = auth()->id();
        $warehouse = KhoLanh::findOrFail($validated['kho_lanh_id']);
        $this->ensureWarehouseAssignment($request, $warehouse);

        DB::transaction(function () use ($validated, $warehouse, $userId) {
            if ($validated['type'] === 'lot') {
                $lot = LoNongSan::lockForUpdate()->findOrFail($validated['id']);
                if (!app(\App\Services\QualityGateService::class)->passedOutboundInspection($lot)
                    || $lot->chiTietDongGoi()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'id' => 'Lô chưa đạt kiểm định xuất xưởng hoặc đã được đóng thành kiện SSCC; hãy nhập kho theo kiện.',
                    ]);
                }
                app(\App\Services\TraceabilityHandoffService::class)->movement(
                    $lot->lo_nong_san_id, null, $warehouse->kho_lanh_id, $userId, 'NHAP', $lot->so_luong, $lot->don_vi_tinh, null,
                    $this->receiptMetadata($validated)
                );
                
                app(\App\Services\LotStatusService::class)->transition(
                    $lot,
                    $validated['tinh_trang_bao_goi'] === 'HU_HONG_NANG' ? 'CACH_LY' : 'TRONG_KHO',
                    $userId,
                    "Tiếp nhận vào kho lạnh {$warehouse->ten_kho}. " . ($validated['ghi_chu'] ?? ''),
                    ['kho_lanh_id' => $warehouse->kho_lanh_id]
                );
            } else {
                $sscc = DonViDongGoiSscc::with('chiTiet.loNongSan')->lockForUpdate()->findOrFail($validated['id']);
                if ($sscc->trang_thai !== 'DA_DONG_GOI') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['id' => 'Kiện không ở trạng thái chờ nhập kho.']);
                }
                $this->ensureSsccPassedOutboundQc($sscc);
                foreach ($sscc->chiTiet as $content) {
                    app(\App\Services\TraceabilityHandoffService::class)->movement(
                        $content->lo_nong_san_id, $sscc->sscc_id, $warehouse->kho_lanh_id, $userId, 'NHAP', $content->so_luong, $content->don_vi_tinh, null,
                        $this->receiptMetadata($validated)
                    );
                }
                $sscc->update([
                    'kho_lanh_id' => $warehouse->kho_lanh_id,
                    'trang_thai' => $validated['tinh_trang_bao_goi'] === 'HU_HONG_NANG' ? 'CACH_LY' : 'TRONG_KHO',
                ]);
            }
        });

        return redirect()->route('warehouse.operations.index', ['tab' => 'inbound'])
            ->with('status', "Đã tiếp nhận thành công vào {$warehouse->ten_kho}.");
    }

    public function receiveBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selection_mode' => ['required', 'in:selected,all_filtered'],
            'items' => ['required_if:selection_mode,selected', 'array', 'max:500'],
            'items.*' => ['string', 'regex:/^sscc:[1-9][0-9]*$/'],
            'kho_lanh_id' => ['required', 'integer', 'exists:kho_lanh,kho_lanh_id'],
            'nhiet_do_luc_nhan' => ['required', 'numeric', 'min:-50', 'max:80'],
            'tinh_trang_bao_goi' => ['required', 'in:NGUYEN_VEN,MOP_NHE,HU_HONG_NANG'],
            'vi_tri_luu_kho' => ['required', 'string', 'max:120'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $warehouse = KhoLanh::findOrFail($validated['kho_lanh_id']);
        $this->ensureWarehouseAssignment($request, $warehouse);

        if ($validated['selection_mode'] === 'all_filtered') {
            $search = trim($validated['q'] ?? '');
            $ssccQuery = DonViDongGoiSscc::where('trang_thai', 'DA_DONG_GOI')
                ->whereHas('chiTiet')
                ->whereDoesntHave('chiTiet.loNongSan', fn ($lot) => $lot->where('trang_thai', '!=', 'DAT_KIEM_DINH'));
            if ($search !== '') {
                $ssccQuery->where('ma_sscc', 'like', "%{$search}%");
            }
            $items = $ssccQuery->pluck('sscc_id')->map(fn ($id) => "sscc:{$id}");
        } else {
            $items = collect($validated['items'] ?? [])->unique()->values();
        }

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Vui lòng chọn ít nhất một lô hoặc kiện để nhập kho.']);
        }
        if ($items->count() > 500) {
            return back()->withErrors(['items' => 'Mỗi lần chỉ được nhập tối đa 500 lô hoặc kiện. Hãy dùng bộ lọc để chia nhỏ.']);
        }

        $userId = $request->user()->tai_khoan_id;
        DB::transaction(function () use ($items, $warehouse, $userId, $validated) {
            foreach ($items as $item) {
                [$type, $id] = explode(':', $item, 2);
                if ($type === 'lot') {
                    $lot = LoNongSan::lockForUpdate()->findOrFail((int) $id);
                    if (!app(\App\Services\QualityGateService::class)->passedOutboundInspection($lot)
                        || $lot->chiTietDongGoi()->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => "Lô {$lot->ma_lo} chưa đạt QC xuất xưởng hoặc phải nhập theo kiện SSCC."]);
                    }
                    app(\App\Services\TraceabilityHandoffService::class)->movement(
                        $lot->lo_nong_san_id, null, $warehouse->kho_lanh_id, $userId, 'NHAP', $lot->so_luong, $lot->don_vi_tinh, null,
                        $this->receiptMetadata($validated)
                    );
                    app(\App\Services\LotStatusService::class)->transition(
                        $lot, $validated['tinh_trang_bao_goi'] === 'HU_HONG_NANG' ? 'CACH_LY' : 'TRONG_KHO', $userId,
                        "Nhập kho hàng loạt vào {$warehouse->ten_kho}. ".($validated['ghi_chu'] ?? ''),
                        ['kho_lanh_id' => $warehouse->kho_lanh_id]
                    );
                } else {
                    $sscc = DonViDongGoiSscc::with('chiTiet.loNongSan')->lockForUpdate()->findOrFail((int) $id);
                    if ($sscc->trang_thai !== 'DA_DONG_GOI') {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => "Kiện {$sscc->ma_sscc} không còn chờ nhập kho."]);
                    }
                    $this->ensureSsccPassedOutboundQc($sscc);
                    foreach ($sscc->chiTiet as $content) {
                        app(\App\Services\TraceabilityHandoffService::class)->movement(
                            $content->lo_nong_san_id, $sscc->sscc_id, $warehouse->kho_lanh_id, $userId, 'NHAP', $content->so_luong, $content->don_vi_tinh, null,
                            $this->receiptMetadata($validated)
                        );
                    }
                    $sscc->update([
                        'kho_lanh_id' => $warehouse->kho_lanh_id,
                        'trang_thai' => $validated['tinh_trang_bao_goi'] === 'HU_HONG_NANG' ? 'CACH_LY' : 'TRONG_KHO',
                    ]);
                }
            }
        });

        return redirect()->route('warehouse.operations.index', ['tab' => 'inbound'])
            ->with('status', "Đã nhập kho thành công {$items->count()} lô/kiện vào {$warehouse->ten_kho}.");
    }

    /**
     * Process outbound dispatch of a lot or SSCC unit for transportation.
     */
    public function dispatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:sscc'],
            'id' => ['required', 'integer', 'exists:don_vi_dong_goi_sscc,sscc_id'],
            'chuyen_id' => ['required', 'integer', 'exists:chuyen_van_chuyen,chuyen_id'],
            'to_chuc_nhan_id' => ['required', 'exists:to_chuc,to_chuc_id'],
            'dia_chi_giao' => ['required', 'string', 'max:255'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);
        $id = app(\App\Services\TraceabilityHandoffService::class)->dispatch($validated['id'], $validated, $request->user());
        return redirect()->route('warehouse.operations.index', ['tab' => 'outbound'])
            ->with('status', "Đã xuất kho và lập phiếu giao #{$id}, gắn với chuyến xe và bên nhận.");
    }

    public function dispatchBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selection_mode' => ['required', 'in:selected,all_filtered'],
            'items' => ['required_if:selection_mode,selected', 'array', 'max:500'],
            'items.*' => ['string', 'regex:/^sscc:[1-9][0-9]*$/'],
            'chuyen_id' => ['required', 'integer', 'exists:chuyen_van_chuyen,chuyen_id'],
            'to_chuc_nhan_id' => ['required', 'exists:to_chuc,to_chuc_id'],
            'dia_chi_giao' => ['required', 'string', 'max:255'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
            'q' => ['nullable', 'string', 'max:100'],
            'filter_kho_lanh_id' => ['nullable', 'integer', 'exists:kho_lanh,kho_lanh_id'],
        ]);

        if ($validated['selection_mode'] === 'all_filtered') {
            $query = DonViDongGoiSscc::whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO']);
            if (!empty($validated['filter_kho_lanh_id'])) {
                $query->where('kho_lanh_id', $validated['filter_kho_lanh_id']);
            }
            if (!empty($validated['q'])) {
                $query->where('ma_sscc', 'like', '%'.trim($validated['q']).'%');
            }
            $ids = $query->pluck('sscc_id');
        } else {
            $ids = collect($validated['items'] ?? [])->map(fn ($item) => (int) explode(':', $item, 2)[1])->unique()->values();
        }

        if ($ids->isEmpty()) {
            return back()->withErrors(['items' => 'Vui lòng chọn ít nhất một kiện để xuất kho.']);
        }
        if ($ids->count() > 500) {
            return back()->withErrors(['items' => 'Mỗi lần chỉ được xuất tối đa 500 kiện. Hãy dùng bộ lọc để chia nhỏ.']);
        }

        $deliveryId = app(\App\Services\TraceabilityHandoffService::class)
            ->dispatchMany($ids->all(), $validated, $request->user());

        return redirect()->route('warehouse.operations.index', ['tab' => 'outbound'])
            ->with('status', "Đã xuất {$ids->count()} kiện và lập chung phiếu giao #{$deliveryId}.");
    }

    private function ensureWarehouseAssignment(Request $request, KhoLanh $warehouse): void
    {
        $isAssigned = $request->user()->khoLanh()
            ->where('kho_lanh.kho_lanh_id', $warehouse->kho_lanh_id)
            ->exists();

        if (!$isAssigned) {
            throw ValidationException::withMessages([
                'kho_lanh_id' => 'Tài khoản của bạn chưa được phân công vào kho lạnh này. Vui lòng chọn kho trong phạm vi phụ trách hoặc liên hệ quản trị viên.',
            ]);
        }

        if ($warehouse->trang_thai !== 'DANG_HOAT_DONG') {
            throw ValidationException::withMessages([
                'kho_lanh_id' => 'Kho lạnh này hiện không ở trạng thái hoạt động nên chưa thể tiếp nhận hàng.',
            ]);
        }
    }

    private function ensureSsccPassedOutboundQc(DonViDongGoiSscc $sscc): void
    {
        if (!app(\App\Services\Gs1Service::class)->validateSscc($sscc->ma_sscc)['valid']) {
            throw ValidationException::withMessages([
                'items' => "Mã SSCC {$sscc->ma_sscc} không hợp lệ. Vui lòng đối chiếu và cập nhật nhãn trước khi quét lại.",
            ]);
        }
        if ($sscc->chiTiet->isEmpty()) {
            throw ValidationException::withMessages(['items' => "Kiện {$sscc->ma_sscc} chưa có lô thành phẩm."]);
        }

        foreach ($sscc->chiTiet as $content) {
            $lot = $content->loNongSan;
            if (!$lot || !app(\App\Services\QualityGateService::class)->passedOutboundInspection($lot)) {
                throw ValidationException::withMessages([
                    'items' => "Kiện {$sscc->ma_sscc} có lô chưa đạt kiểm định xuất xưởng nên chưa thể nhập kho.",
                ]);
            }
        }
    }

    private function receiptMetadata(array $validated): array
    {
        return [
            'nhiet_do_luc_nhan' => $validated['nhiet_do_luc_nhan'] ?? null,
            'tinh_trang_bao_goi' => $validated['tinh_trang_bao_goi'] ?? null,
            'vi_tri_luu_kho' => $validated['vi_tri_luu_kho'] ?? null,
            'ghi_chu' => $validated['ghi_chu'] ?? null,
        ];
    }
}

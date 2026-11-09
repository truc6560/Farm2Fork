<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\DanhMucSanPham;
use App\Models\LichSuTrangThaiMuaVu;
use App\Models\MuaVu;
use App\Models\VungTrong;
use App\Services\SeasonProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChainSeasonController extends Controller
{
    public function index(Request $request)
    {
        $query = MuaVu::with([
            'vungTrong.toChuc',
            'sanPham',
            'lichSuTrangThai.nguoiThucHien',
            'loNongSan',
        ])->withCount(['loNongSan', 'nhatKyCanhTac']);

        // Search keyword
        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            $query->where(fn ($q) => $q->where('ma_mua_vu', 'like', "%{$keyword}%")
                ->orWhere('ten_mua_vu', 'like', "%{$keyword}%")
                ->orWhere('giong_cay_trong', 'like', "%{$keyword}%")
                ->orWhereHas('vungTrong', fn ($zone) => $zone->where('ten_vung_trong', 'like', "%{$keyword}%"))
                ->orWhereHas('sanPham', fn ($sp) => $sp->where('ten_san_pham', 'like', "%{$keyword}%")));
        }

        // Status filter
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        // Planting Zone filter
        if ($request->filled('vung_trong_id')) {
            $query->where('vung_trong_id', $request->input('vung_trong_id'));
        }

        // Product filter
        if ($request->filled('san_pham_id')) {
            $query->where('san_pham_id', $request->input('san_pham_id'));
        }

        // Year filter
        if ($request->filled('nam')) {
            $year = (int)$request->input('nam');
            $query->where(function ($q) use ($year) {
                $q->whereYear('ngay_gieo', $year)
                  ->orWhere(fn($sq) => $sq->whereNull('ngay_gieo')->whereYear('created_at', $year));
            });
        }

        $seasons = $query->orderByDesc('mua_vu_id')->paginate(15)->withQueryString();

        // Calculate statistics
        $totalSeasons = MuaVu::count();
        $activeSeasons = MuaVu::where('trang_thai', 'DANG_CANH_TAC')->count();
        $harvestingSeasons = MuaVu::where('trang_thai', 'DANG_THU_HOACH')->count();
        $completedSeasons = MuaVu::where('trang_thai', 'HOAN_THANH')->count();

        // Check quarantine status
        $quarantineCount = 0;
        foreach (MuaVu::whereIn('trang_thai', ['DANG_CANH_TAC', 'DANG_THU_HOACH'])->get() as $s) {
            $qInfo = $s->getThongTinCachLy();
            if (!empty($qInfo['dang_cach_ly'])) {
                $quarantineCount++;
            }
        }

        $plantingZones = VungTrong::where('trang_thai', 'DANG_SU_DUNG')->orderBy('ten_vung_trong')->get();
        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->orderBy('ten_san_pham')->get();
        $availableYears = MuaVu::selectRaw('DISTINCT YEAR(COALESCE(ngay_gieo, created_at)) as yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter()
            ->values();

        return view('chain.seasons.index', [
            'seasons' => $seasons,
            'filters' => $request->all(),
            'totalSeasons' => $totalSeasons,
            'activeSeasons' => $activeSeasons,
            'harvestingSeasons' => $harvestingSeasons,
            'completedSeasons' => $completedSeasons,
            'quarantineCount' => $quarantineCount,
            'plantingZones' => $plantingZones,
            'products' => $products,
            'availableYears' => $availableYears,
        ]);
    }

    public function create()
    {
        $plantingZones = VungTrong::where('trang_thai', 'DANG_SU_DUNG')->orderBy('ten_vung_trong')->get();
        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->orderBy('ten_san_pham')->get();

        return view('chain.seasons.create', [
            'plantingZones' => $plantingZones,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'vung_trong_id' => ['required', 'exists:vung_trong,vung_trong_id'],
            'san_pham_id' => ['required', 'exists:danh_muc_san_pham,san_pham_id'],
            'ma_mua_vu' => ['nullable', 'string', 'max:40', 'unique:mua_vu,ma_mua_vu'],
            'ten_mua_vu' => ['nullable', 'string', 'max:150'],
            'giong_cay_trong' => ['nullable', 'string', 'max:150'],
            'dien_tich_canh_tac_ha' => ['nullable', 'numeric', 'min:0.001'],
            'ngay_gieo' => ['nullable', 'date'],
            'ngay_thu_hoach_du_kien' => ['nullable', 'date', 'after_or_equal:ngay_gieo'],
            'san_luong_du_kien' => ['nullable', 'numeric', 'min:0'],
            'don_vi_san_luong' => ['required', 'string', 'max:30'],
            'trang_thai' => ['required', 'in:CHUAN_BI,DANG_CANH_TAC,DANG_THU_HOACH'],
        ], [
            'vung_trong_id.required' => 'Vui lòng chọn vùng trồng liên kết.',
            'vung_trong_id.exists' => 'Vùng trồng được chọn không tồn tại.',
            'san_pham_id.required' => 'Vui lòng chọn loại nông sản / cây trồng.',
            'san_pham_id.exists' => 'Mặt hàng nông sản không hợp lệ.',
            'ma_mua_vu.unique' => 'Mã mùa vụ đã tồn tại trong hệ thống.',
            'ngay_thu_hoach_du_kien.after_or_equal' => 'Ngày thu hoạch dự kiến phải sau hoặc bằng ngày bắt đầu / gieo trồng.',
            'dien_tich_canh_tac_ha.min' => 'Diện tích canh tác phải lớn hơn 0.',
            'san_luong_du_kien.min' => 'Sản lượng dự kiến không thể là số âm.',
            'don_vi_san_luong.required' => 'Vui lòng chọn đơn vị sản lượng.',
        ]);

        $zone = VungTrong::findOrFail($validated['vung_trong_id']);
        $product = DanhMucSanPham::findOrFail($validated['san_pham_id']);

        // Auto-generate season code if omitted
        if (empty($validated['ma_mua_vu'])) {
            $prodPrefix = Str::slug($product->ten_san_pham, '');
            $prodPrefix = strtoupper(substr($prodPrefix, 0, 4)) ?: 'SP';
            $validated['ma_mua_vu'] = 'MV-' . $prodPrefix . '-VT' . $zone->vung_trong_id . '-' . date('Y') . strtoupper(Str::random(3));
        }

        // Auto-generate season name if omitted
        if (empty($validated['ten_mua_vu'])) {
            $validated['ten_mua_vu'] = $product->ten_san_pham . ' - ' . $zone->ten_vung_trong . ' (Vụ ' . date('Y') . ')';
        }

        $season = MuaVu::create($validated);

        // Record initial status transition
        LichSuTrangThaiMuaVu::create([
            'mua_vu_id' => $season->mua_vu_id,
            'tai_khoan_id' => $user?->tai_khoan_id,
            'trang_thai_cu' => null,
            'trang_thai_moi' => $season->trang_thai,
            'ly_do' => 'Quản trị viên thiết lập mùa vụ mới trên hệ thống',
            'thoi_gian' => now(),
        ]);

        return redirect()->route('chain.seasons.show', $season->mua_vu_id)
            ->with('status', "Đã tạo thành công mùa vụ: {$season->ten_mua_vu} ({$season->ma_mua_vu})");
    }

    public function show($id)
    {
        $season = MuaVu::with([
            'vungTrong.toChuc',
            'vungTrong.nguoiPhuTrach',
            'vungTrong.nhanVienPhanCong',
            'sanPham',
            'nhatKyCanhTac.nguoiThucHien',
            'nhatKyCanhTac.chiTietVatTu.vatTu',
            'loNongSan.khoLanh',
            'loNongSan.phieuKiemDinh',
            'lichSuTrangThai.nguoiThucHien',
        ])->findOrFail($id);

        $quarantineInfo = $season->getThongTinCachLy();

        // Check quarantine safety alerts from pesticide logs
        $quarantineAlerts = [];
        $pesticideLogs = $season->nhatKyCanhTac
            ->where('loai_hoat_dong', 'PHUN_THUOC')
            ->where('trang_thai', '!=', 'DA_THAY_THE');

        foreach ($pesticideLogs as $log) {
            foreach ($log->chiTietVatTu as $detail) {
                $days = $detail->thoi_gian_cach_ly_ap_dung_ngay ?? $detail->vatTu?->thoi_gian_cach_ly_ngay ?? 0;
                if ($days > 0 && $log->ngay_thuc_hien) {
                    $safeDate = Carbon::parse($log->ngay_thuc_hien)->addDays($days);
                    if ($safeDate->isFuture()) {
                        $remaining = now()->diffInDays($safeDate, false);
                        $quarantineAlerts[] = [
                            'vat_tu' => $detail->vatTu?->ten_thuong_mai ?? 'Thuốc BVTV',
                            'ngay_phun' => $log->ngay_thuc_hien->format('d/m/Y H:i'),
                            'so_ngay' => $days,
                            'ngay_an_toan' => $safeDate->format('d/m/Y H:i'),
                            'con_lai' => ceil($remaining) . ' ngày',
                        ];
                    }
                }
            }
        }

        $totalHarvestedQuantity = $season->loNongSan->sum('so_luong');
        $canDelete = $season->loNongSan->isEmpty() && $season->nhatKyCanhTac->isEmpty();

        return view('chain.seasons.show', [
            'season' => $season,
            'quarantineInfo' => $quarantineInfo,
            'quarantineAlerts' => $quarantineAlerts,
            'totalHarvestedQuantity' => $totalHarvestedQuantity,
            'canDelete' => $canDelete,
        ]);
    }

    public function edit($id)
    {
        $season = MuaVu::with(['vungTrong', 'sanPham', 'loNongSan'])->findOrFail($id);

        $hasLots = $season->loNongSan()->exists();
        $hasLogs = $season->nhatKyCanhTac()->exists();

        $plantingZones = VungTrong::where('trang_thai', 'DANG_SU_DUNG')->orderBy('ten_vung_trong')->get();
        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->orderBy('ten_san_pham')->get();

        return view('chain.seasons.edit', [
            'season' => $season,
            'hasLots' => $hasLots,
            'hasLogs' => $hasLogs,
            'plantingZones' => $plantingZones,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $season = MuaVu::with(['vungTrong', 'sanPham', 'loNongSan'])->findOrFail($id);

        $hasLots = $season->loNongSan()->exists();

        // Validation rules
        $rules = [
            'ten_mua_vu' => ['required', 'string', 'max:150'],
            'giong_cay_trong' => ['nullable', 'string', 'max:150'],
            'dien_tich_canh_tac_ha' => ['nullable', 'numeric', 'min:0.001'],
            'ngay_gieo' => ['nullable', 'date'],
            'ngay_thu_hoach_du_kien' => ['nullable', 'date', 'after_or_equal:ngay_gieo'],
            'san_luong_du_kien' => ['nullable', 'numeric', 'min:0'],
            'don_vi_san_luong' => ['required', 'string', 'max:30'],
            'trang_thai' => ['required', 'in:CHUAN_BI,DANG_CANH_TAC,DANG_THU_HOACH,HOAN_THANH,HUY'],
        ];

        // If no lots yet, allow updating zone, product, and code
        if (!$hasLots) {
            $rules['vung_trong_id'] = ['required', 'exists:vung_trong,vung_trong_id'];
            $rules['san_pham_id'] = ['required', 'exists:danh_muc_san_pham,san_pham_id'];
            $rules['ma_mua_vu'] = ['required', 'string', 'max:40', 'unique:mua_vu,ma_mua_vu,' . $id . ',mua_vu_id'];
        }

        $validated = $request->validate($rules, [
            'ten_mua_vu.required' => 'Vui lòng nhập tên mùa vụ.',
            'vung_trong_id.required' => 'Vui lòng chọn vùng trồng.',
            'san_pham_id.required' => 'Vui lòng chọn mặt hàng nông sản.',
            'ma_mua_vu.required' => 'Vui lòng nhập mã mùa vụ.',
            'ma_mua_vu.unique' => 'Mã mùa vụ đã tồn tại trong hệ thống.',
            'ngay_thu_hoach_du_kien.after_or_equal' => 'Ngày thu hoạch dự kiến phải sau hoặc bằng ngày bắt đầu / gieo trồng.',
        ]);

        // If has lots, safeguard zone and product from changing
        if ($hasLots) {
            if ($validated['trang_thai'] === 'CHUAN_BI') {
                return back()->withInput()->withErrors([
                    'trang_thai' => 'Mùa vụ đã phát sinh lô nông sản trong chuỗi, không thể quay lại trạng thái Chuẩn bị.'
                ]);
            }
            unset($validated['vung_trong_id'], $validated['san_pham_id'], $validated['ma_mua_vu']);
        }

        $oldStatus = $season->trang_thai;
        $newStatus = $validated['trang_thai'];

        $season->update($validated);

        // Record history if status changed
        if ($oldStatus !== $newStatus) {
            LichSuTrangThaiMuaVu::create([
                'mua_vu_id' => $season->mua_vu_id,
                'tai_khoan_id' => $user?->tai_khoan_id,
                'trang_thai_cu' => $oldStatus,
                'trang_thai_moi' => $newStatus,
                'ly_do' => $request->input('ly_do_thay_doi') ?: 'Cập nhật thông tin và trạng thái mùa vụ',
                'thoi_gian' => now(),
            ]);
        }

        return redirect()->route('chain.seasons.show', $season->mua_vu_id)
            ->with('status', "Đã cập nhật thông tin mùa vụ '{$season->ten_mua_vu}' thành công.");
    }

    public function destroy($id)
    {
        $season = MuaVu::withCount(['loNongSan', 'nhatKyCanhTac'])->findOrFail($id);

        if ($season->lo_nong_san_count > 0 || $season->nhat_ky_canh_tac_count > 0) {
            return back()->with('error', "Không thể xóa mùa vụ '{$season->ten_mua_vu}' vì đã có {$season->lo_nong_san_count} lô nông sản và {$season->nhat_ky_canh_tac_count} nhật ký canh tác liên kết trong chuỗi truy xuất nguồn gốc. Vui lòng chuyển trạng thái sang 'Hủy vụ' nếu không tiếp tục canh tác.");
        }

        $seasonName = $season->ten_mua_vu;
        $seasonCode = $season->ma_mua_vu;

        // Delete audit history first then season
        $season->lichSuTrangThai()->delete();
        $season->delete();

        return redirect()->route('chain.seasons.index')
            ->with('status', "Đã xóa thành công mùa vụ '{$seasonName}' ({$seasonCode}).");
    }

    public function startHarvest(Request $request, MuaVu $season, SeasonProgressService $progress)
    {
        $validated = $request->validate([
            'ly_do' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = $validated['ly_do'] ?? 'Bắt đầu giai đoạn thu hoạch nông sản từ vùng trồng';
        $progress->startHarvest($season, (int) $request->user()->tai_khoan_id, $reason);

        return back()->with('status', "Đã chuyển mùa vụ {$season->ma_mua_vu} sang giai đoạn thu hoạch thành công.");
    }

    public function complete(Request $request, MuaVu $season, SeasonProgressService $progress)
    {
        $validated = $request->validate([
            'ly_do' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'ly_do.required' => 'Vui lòng ghi lý do/ghi chú nghiệm thu kết thúc mùa vụ.',
            'ly_do.min' => 'Ghi chú kết thúc cần có ít nhất 5 ký tự.',
        ]);

        $progress->complete($season, (int) $request->user()->tai_khoan_id, $validated['ly_do']);

        return back()->with('status', "Đã nghiệm thu và kết thúc mùa vụ {$season->ma_mua_vu}. Hồ sơ đã chuyển sang lưu trữ kiểm toán.");
    }

    public function reopen(Request $request, MuaVu $season, SeasonProgressService $progress)
    {
        $validated = $request->validate([
            'ly_do' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'ly_do.required' => 'Vui lòng ghi lý do mở lại mùa vụ.',
            'ly_do.min' => 'Lý do mở lại cần có ít nhất 5 ký tự.',
        ]);

        $progress->reopen($season, (int) $request->user()->tai_khoan_id, $validated['ly_do']);

        return back()->with('status', "Đã mở lại mùa vụ {$season->ma_mua_vu} để tiếp tục ghi nhận thu hoạch.");
    }

    public function cancel(Request $request, MuaVu $season, SeasonProgressService $progress)
    {
        $validated = $request->validate([
            'ly_do' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'ly_do.required' => 'Vui lòng nhập lý do hủy mùa vụ.',
            'ly_do.min' => 'Lý do hủy cần có ít nhất 5 ký tự.',
        ]);

        $progress->cancel($season, (int) $request->user()->tai_khoan_id, $validated['ly_do']);

        return back()->with('status', "Đã hủy mùa vụ {$season->ma_mua_vu}.");
    }
}

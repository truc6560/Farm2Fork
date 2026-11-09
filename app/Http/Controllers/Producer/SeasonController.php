<?php

namespace App\Http\Controllers\Producer;

use App\Http\Controllers\Controller;
use App\Models\ChiTietSuDungVatTu;
use App\Models\DanhMucSanPham;
use App\Models\MuaVu;
use App\Models\VungTrong;
use App\Services\ProducerAuthorizationService;
use App\Services\SeasonProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeasonController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $query = MuaVu::with(['vungTrong', 'sanPham', 'nhatKyCanhTac'])
            ->whereIn('vung_trong_id', $assignedZoneIds);

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('ma_mua_vu', 'like', "%{$keyword}%")
                    ->orWhere('ten_mua_vu', 'like', "%{$keyword}%")
                    ->orWhere('giong_cay_trong', 'like', "%{$keyword}%")
                    ->orWhereHas('sanPham', function ($sp) use ($keyword) {
                        $sp->where('ten_san_pham', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('vung_trong_id')) {
            $query->where('vung_trong_id', $request->input('vung_trong_id'));
        }

        if ($request->filled('san_pham_id')) {
            $query->where('san_pham_id', $request->input('san_pham_id'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        $seasons = $query->orderBy('mua_vu_id', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics strictly within user's assigned seasons
        $baseQuery = MuaVu::whereIn('vung_trong_id', $assignedZoneIds);

        $totalSeasons = (clone $baseQuery)->count();
        $activeSeasons = (clone $baseQuery)->where('trang_thai', 'DANG_CANH_TAC')->count();
        $harvestingSeasons = (clone $baseQuery)->where('trang_thai', 'DANG_THU_HOACH')->count();
        $completedSeasons = (clone $baseQuery)->where('trang_thai', 'HOAN_THANH')->count();

        $plantingZones = VungTrong::whereIn('vung_trong_id', $assignedZoneIds)
            ->where('trang_thai', 'DANG_SU_DUNG')
            ->get();

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.seasons.index', [
            'seasons' => $seasons,
            'filters' => $request->all(),
            'totalSeasons' => $totalSeasons,
            'activeSeasons' => $activeSeasons,
            'harvestingSeasons' => $harvestingSeasons,
            'completedSeasons' => $completedSeasons,
            'plantingZones' => $plantingZones,
            'products' => $products,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $plantingZones = VungTrong::whereIn('vung_trong_id', $assignedZoneIds)
            ->where('trang_thai', 'DANG_SU_DUNG')
            ->get();

        if ($plantingZones->isEmpty()) {
            return redirect()->route('producer.planting-zones.create')
                ->with('warning', 'Bạn cần có ít nhất một Vùng trồng được phân công quản lý trước khi thiết lập Mùa vụ.');
        }

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.seasons.create', [
            'plantingZones' => $plantingZones,
            'products' => $products,
            'selectedZoneId' => $request->input('vung_trong_id'),
        ]);
    }

    /**
     * Xác định hoặc tự động tạo mới nông sản dựa trên tên nhập tự do
     */
    private function resolveProduct(Request $request): DanhMucSanPham
    {
        $name = trim((string)($request->input('ten_san_pham') ?? $request->input('loai_nong_san') ?? ''));
        $productId = $request->input('san_pham_id');

        if ($name !== '') {
            return DanhMucSanPham::findOrCreateByName($name);
        }

        if ($productId) {
            $product = DanhMucSanPham::find($productId);
            if ($product) {
                return $product;
            }
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'ten_san_pham' => 'Vui lòng điền tên loại nông sản / trái cây.',
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'vung_trong_id' => ['required', 'exists:vung_trong,vung_trong_id'],
            'ten_san_pham' => ['required_without:san_pham_id', 'nullable', 'string', 'max:200'],
            'san_pham_id' => ['nullable'],
            'ma_mua_vu' => ['nullable', 'string', 'max:40', 'unique:mua_vu,ma_mua_vu'],
            'ten_mua_vu' => ['nullable', 'string', 'max:150'],
            'giong_cay_trong' => ['nullable', 'string', 'max:150'],
            'dien_tich_canh_tac_ha' => ['nullable', 'numeric', 'min:0.001'],
            'ngay_gieo' => ['nullable', 'date'],
            'ngay_thu_hoach_du_kien' => ['nullable', 'date', 'after_or_equal:ngay_gieo'],
            'san_luong_du_kien' => ['nullable', 'numeric', 'min:0'],
            'don_vi_san_luong' => ['required', 'string', 'max:30'],
            'trang_thai' => ['required', 'in:CHUAN_BI,DANG_CANH_TAC'],
        ], [
            'vung_trong_id.required' => 'Vui lòng chọn vùng trồng.',
            'ten_san_pham.required_without' => 'Vui lòng điền tên loại nông sản / trái cây.',
            'ma_mua_vu.unique' => 'Mã mùa vụ đã tồn tại trong hệ thống.',
            'ngay_thu_hoach_du_kien.after_or_equal' => 'Ngày thu hoạch dự kiến phải sau hoặc bằng ngày gieo.',
        ]);

        // Kiểm tra quyền trên vùng trồng được phân công
        ProducerAuthorizationService::authorizeZone($user, (int) $validated['vung_trong_id']);
        $zone = VungTrong::findOrFail($validated['vung_trong_id']);

        $product = $this->resolveProduct($request);
        $validated['san_pham_id'] = $product->san_pham_id;
        unset($validated['ten_san_pham']);

        // Auto-generate season code if omitted
        if (empty($validated['ma_mua_vu'])) {
            $prodPrefix = Str::slug($product->ten_san_pham, '');
            $prodPrefix = strtoupper(substr($prodPrefix, 0, 4)) ?: 'SP';
            $validated['ma_mua_vu'] = 'MV-' . $prodPrefix . '-VT' . $zone->vung_trong_id . '-' . date('Y') . strtoupper(Str::random(2));
        }

        // Auto-generate name if empty
        if (empty($validated['ten_mua_vu'])) {
            $validated['ten_mua_vu'] = $product->ten_san_pham . ' - ' . $zone->ten_vung_trong . ' (Vụ ' . date('Y') . ')';
        }

        $season = MuaVu::create($validated);

        return redirect()->route('producer.seasons.show', $season->mua_vu_id)
            ->with('status', "Đã tạo thành công mùa vụ: {$season->ten_mua_vu} ({$season->ma_mua_vu})");
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $season = MuaVu::with([
            'vungTrong.toChuc',
            'sanPham',
            'nhatKyCanhTac.nguoiThucHien',
            'nhatKyCanhTac.chiTietVatTu.vatTu',
            'loNongSan',
            'lichSuTrangThai.nguoiThucHien'
        ])->findOrFail($id);

        ProducerAuthorizationService::authorizeSeason($user, $season);

        // Check quarantine safety (thời gian cách ly PHI từ các đợt phun thuốc)
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

        $quarantineInfo = $season->getThongTinCachLy();

        return view('producer.seasons.show', [
            'season' => $season,
            'quarantineAlerts' => $quarantineAlerts,
            'quarantineInfo' => $quarantineInfo,
        ]);
    }

    public function edit(Request $request, $id)
    {
        $user = $request->user();
        $season = MuaVu::with('vungTrong')->findOrFail($id);

        ProducerAuthorizationService::authorizeSeason($user, $season);

        if (in_array($season->trang_thai, ['HOAN_THANH', 'HUY'], true)) {
            return redirect()->route('producer.seasons.show', $season->mua_vu_id)
                ->with('warning', 'Mùa vụ đã kết thúc nên chỉ được xem. Quản lý chuỗi phải mở lại trước khi chỉnh sửa.');
        }

        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);
        $plantingZones = VungTrong::whereIn('vung_trong_id', $assignedZoneIds)->get();
        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.seasons.edit', [
            'season' => $season,
            'plantingZones' => $plantingZones,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $season = MuaVu::with('vungTrong')->findOrFail($id);

        ProducerAuthorizationService::authorizeSeason($user, $season);

        if (in_array($season->trang_thai, ['HOAN_THANH', 'HUY'], true)) {
            return back()->withErrors(['trang_thai' => 'Mùa vụ đã kết thúc nên không thể chỉnh sửa.']);
        }

        $validated = $request->validate([
            'vung_trong_id' => ['required', 'exists:vung_trong,vung_trong_id'],
            'ten_san_pham' => ['required_without:san_pham_id', 'nullable', 'string', 'max:200'],
            'san_pham_id' => ['nullable'],
            'ma_mua_vu' => ['required', 'string', 'max:40', 'unique:mua_vu,ma_mua_vu,' . $id . ',mua_vu_id'],
            'ten_mua_vu' => ['nullable', 'string', 'max:150'],
            'giong_cay_trong' => ['nullable', 'string', 'max:150'],
            'dien_tich_canh_tac_ha' => ['nullable', 'numeric', 'min:0.001'],
            'ngay_gieo' => ['nullable', 'date'],
            'ngay_thu_hoach_du_kien' => ['nullable', 'date', 'after_or_equal:ngay_gieo'],
            'san_luong_du_kien' => ['nullable', 'numeric', 'min:0'],
            'don_vi_san_luong' => ['required', 'string', 'max:30'],
            'trang_thai' => ['required', 'in:CHUAN_BI,DANG_CANH_TAC,DANG_THU_HOACH,HUY'],
        ], [
            'vung_trong_id.required' => 'Vui lòng chọn vùng trồng.',
            'ten_san_pham.required_without' => 'Vui lòng điền tên loại nông sản / trái cây.',
            'ma_mua_vu.required' => 'Vui lòng nhập mã mùa vụ.',
            'ma_mua_vu.unique' => 'Mã mùa vụ đã tồn tại trong hệ thống.',
            'ngay_thu_hoach_du_kien.after_or_equal' => 'Ngày thu hoạch dự kiến phải sau hoặc bằng ngày gieo.',
        ]);

        // Kiểm tra quyền vùng trồng mới chọn
        ProducerAuthorizationService::authorizeZone($user, (int) $validated['vung_trong_id']);
        $zone = VungTrong::findOrFail($validated['vung_trong_id']);

        if (in_array($validated['trang_thai'], ['CHUAN_BI', 'DANG_CANH_TAC', 'HUY'], true)
            && ($season->loNongSan()->where('loai_lo', 'NGUYEN_LIEU')->exists()
                || $season->nhatKyCanhTac()->where('loai_hoat_dong', 'THU_HOACH')->where('trang_thai', 'DA_XAC_NHAN')->exists())) {
            return back()->withInput()->withErrors(['trang_thai' => 'Mùa vụ đã có thu hoạch; chỉ có thể để Đang thu hoạch hoặc Đã hoàn thành.']);
        }

        $product = $this->resolveProduct($request);
        $validated['san_pham_id'] = $product->san_pham_id;
        unset($validated['ten_san_pham']);

        if (empty($validated['ten_mua_vu'])) {
            $validated['ten_mua_vu'] = $product->ten_san_pham . ' - ' . $zone->ten_vung_trong . ' (Vụ ' . date('Y') . ')';
        }

        $season->update($validated);

        return redirect()->route('producer.seasons.show', $season->mua_vu_id)
            ->with('status', "Đã cập nhật thông tin mùa vụ: {$season->ten_mua_vu}");
    }

    public function complete(Request $request, MuaVu $season, SeasonProgressService $progress)
    {
        $user = $request->user();
        ProducerAuthorizationService::authorizeSeason($user, $season);
        $validated = $request->validate([
            'ly_do' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'ly_do.required' => 'Vui lòng ghi lý do hoặc ghi chú kết thúc mùa vụ.',
            'ly_do.min' => 'Ghi chú kết thúc cần có ít nhất 10 ký tự.',
        ]);

        $progress->complete($season, (int) $user->tai_khoan_id, $validated['ly_do']);

        return redirect()->route('producer.seasons.show', $season->mua_vu_id)
            ->with('status', 'Đã kết thúc mùa vụ. Hồ sơ được khóa để bảo toàn dữ liệu truy xuất.');
    }
}

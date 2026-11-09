<?php

namespace App\Http\Controllers\Producer;

use App\Http\Controllers\Controller;
use App\Models\DanhMucSanPham;
use App\Models\LoNongSan;
use App\Models\MuaVu;
use App\Services\ProducerAuthorizationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HarvestLotController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $query = LoNongSan::with(['sanPham', 'muaVu.vungTrong', 'toChucNguon'])
            ->where('loai_lo', 'NGUYEN_LIEU')
            ->whereHas('muaVu', function ($q) use ($assignedZoneIds) {
                $q->whereIn('vung_trong_id', $assignedZoneIds);
            });

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('ma_lo', 'like', "%{$keyword}%")
                    ->orWhere('ma_lo_nha_cung_cap', 'like', "%{$keyword}%")
                    ->orWhereHas('sanPham', function ($sp) use ($keyword) {
                        $sp->where('ten_san_pham', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('san_pham_id')) {
            $query->where('san_pham_id', $request->input('san_pham_id'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        $lots = $query->orderBy('lo_nong_san_id', 'desc')->paginate(12)->withQueryString();

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.lots.index', [
            'lots' => $lots,
            'filters' => $request->all(),
            'products' => $products,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $seasons = MuaVu::with(['vungTrong', 'sanPham'])
            ->whereIn('vung_trong_id', $assignedZoneIds)
            ->whereIn('trang_thai', ['DANG_CANH_TAC', 'DANG_THU_HOACH'])
            ->orderBy('mua_vu_id', 'desc')
            ->get();

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.lots.create', [
            'seasons' => $seasons,
            'products' => $products,
            'selectedSeasonId' => $request->input('mua_vu_id'),
        ]);
    }

    /**
     * Xác định hoặc tự động tạo mới nông sản dựa trên tên nhập tự do
     */
    private function resolveProduct(Request $request, ?MuaVu $season = null): DanhMucSanPham
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

        if ($season && $season->sanPham) {
            return $season->sanPham;
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'ten_san_pham' => 'Vui lòng điền tên loại nông sản / trái cây.',
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'mua_vu_id' => ['required', 'exists:mua_vu,mua_vu_id'],
            'ten_san_pham' => ['nullable', 'string', 'max:200'],
            'san_pham_id' => ['nullable'],
            'ma_lo' => ['nullable', 'string', 'max:80'],
            'ma_lo_nha_cung_cap' => ['nullable', 'string', 'max:80'],
            'so_luong' => ['required', 'numeric', 'min:0.01'],
            'don_vi_tinh' => ['required', 'string', 'max:30'],
            'ngay_thu_hoach' => ['required', 'date'],
            'han_su_dung' => ['nullable', 'date', 'after_or_equal:ngay_thu_hoach'],
        ], [
            'mua_vu_id.required' => 'Vui lòng chọn mùa vụ thu hoạch.',
            'so_luong.required' => 'Vui lòng nhập khối lượng thu hoạch.',
            'ngay_thu_hoach.required' => 'Vui lòng nhập ngày thu hoạch.',
        ]);

        $season = MuaVu::with('vungTrong', 'sanPham')->findOrFail($validated['mua_vu_id']);
        ProducerAuthorizationService::authorizeSeason($user, $season);

        // Kiểm tra thời gian cách ly thuốc BVTV / vật tư gần nhất
        $quarantineInfo = $season->getThongTinCachLy($validated['ngay_thu_hoach']);
        if ($quarantineInfo['dang_cach_ly']) {
            $harvestDateStr = Carbon::parse($validated['ngay_thu_hoach'])->format('d/m/Y');
            $safeDateStr = $quarantineInfo['ngay_het_cach_ly']->format('d/m/Y');
            return back()->withInput()->withErrors([
                'ngay_thu_hoach' => "Không thể tạo lô thu hoạch vào ngày {$harvestDateStr} vì chưa hết thời gian cách ly an toàn của thuốc BVTV/vật tư ('{$quarantineInfo['ten_vat_tu']}'). Ngày được phép thu hoạch sớm nhất là sau ngày {$safeDateStr} (còn {$quarantineInfo['so_ngay_con_lai']} ngày cách ly)."
            ]);
        }

        $product = $this->resolveProduct($request, $season);
        $validated['san_pham_id'] = $product->san_pham_id;
        unset($validated['ten_san_pham']);

        if (empty($validated['ma_lo'])) {
            $validated['ma_lo'] = 'LO-' . ($product->ma_san_pham ?? 'NL') . '-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        }

        // Check uniqueness of ma_lo for this product
        $exists = LoNongSan::where('san_pham_id', $product->san_pham_id)
            ->where('ma_lo', $validated['ma_lo'])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['ma_lo' => 'Mã lô này đã tồn tại cho sản phẩm đã chọn.']);
        }

        if (empty($validated['han_su_dung'])) {
            $hsdDays = $product->han_su_dung_mac_dinh_ngay ?? 7;
            $validated['han_su_dung'] = Carbon::parse($validated['ngay_thu_hoach'])->addDays($hsdDays)->toDateString();
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $season, $user, $product) {
            app(\App\Services\SeasonProgressService::class)->recordHarvest($season);
            $lot = LoNongSan::create([
            'san_pham_id' => $product->san_pham_id,
            'mua_vu_id' => $season->mua_vu_id,
            'to_chuc_nguon_id' => $season->vungTrong->to_chuc_id ?? $user->to_chuc_id ?? 1,
            'loai_lo' => 'NGUYEN_LIEU',
            'ma_lo' => $validated['ma_lo'],
            'ma_lo_nha_cung_cap' => $validated['ma_lo_nha_cung_cap'] ?? null,
            'digital_link' => null,
            'so_luong' => $validated['so_luong'],
            'don_vi_tinh' => $validated['don_vi_tinh'],
            'ngay_thu_hoach' => $validated['ngay_thu_hoach'],
            'ngay_san_xuat' => $validated['ngay_thu_hoach'],
            'han_su_dung' => $validated['han_su_dung'],
            'trang_thai' => 'MOI_TAO',
        ]);

            \App\Models\LichSuTrangThaiLo::create([
            'lo_nong_san_id' => $lot->lo_nong_san_id,
            'tai_khoan_id'   => $user->tai_khoan_id,
            'trang_thai_cu'  => null,
            'trang_thai_moi' => 'MOI_TAO',
            'ly_do'          => 'Thu hoạch và khởi tạo lô nguyên liệu mới từ mùa vụ ' . ($season->ten_mua_vu ?? ''),
            'thoi_gian'      => now(),
        ]);

            return redirect()->route('producer.lots.show', $lot->lo_nong_san_id)
                ->with('status', "Đã tạo thành công lô thu hoạch nguyên liệu: {$lot->ma_lo}");
        });
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $lot = LoNongSan::with(['sanPham', 'muaVu.vungTrong.toChuc', 'toChucNguon'])->findOrFail($id);

        ProducerAuthorizationService::authorizeLot($user, $lot);

        return view('producer.lots.show', [
            'lot' => $lot,
        ]);
    }
}

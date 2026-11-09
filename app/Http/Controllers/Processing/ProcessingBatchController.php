<?php

namespace App\Http\Controllers\Processing;

use App\Http\Controllers\Controller;
use App\Models\DanhMucSanPham;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use App\Models\ThanhPhanLoNongSan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessingBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = LoNongSan::with(['sanPham', 'khoLanh', 'nguyenLieuDauVao.toChucNguon', 'donViSscc'])
            ->where('loai_lo', 'THANH_PHAM');

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where('ma_lo', 'like', "%{$keyword}%");
        }

        if ($request->filled('san_pham_id')) {
            $query->where('san_pham_id', $request->input('san_pham_id'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        // Stats summary
        $baseQuery = LoNongSan::where('loai_lo', 'THANH_PHAM');
        $totalBatches = (clone $baseQuery)->count();
        $readyToPack = (clone $baseQuery)->where('trang_thai', 'DA_SO_CHE')->count();
        $packed = (clone $baseQuery)->whereIn('trang_thai', ['DANG_DONG_GOI', 'CHO_KIEM_DINH_XUAT_XUONG', 'DAT_KIEM_DINH'])->count();
        $stored = (clone $baseQuery)->where('trang_thai', 'LUU_KHO')->count();

        $batches = $query->orderBy('lo_nong_san_id', 'desc')->paginate(15)->withQueryString();
        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('processing.batches.index', [
            'batches' => $batches,
            'products' => $products,
            'filters' => $request->all(),
            'totalBatches' => $totalBatches,
            'readyToPack' => $readyToPack,
            'packed' => $packed,
            'stored' => $stored,
        ]);
    }

    public function create(Request $request)
    {
        // Available raw materials ready for processing
        $rawLots = LoNongSan::with(['sanPham', 'muaVu.vungTrong', 'toChucNguon'])
            ->where('loai_lo', 'NGUYEN_LIEU')
            ->where('so_luong', '>', 0)
            ->where('trang_thai', 'CHO_SO_CHE')
            ->whereHas('phieuKiemDinh', function ($inspection) {
                $inspection->whereIn('loai_kiem_dinh', ['NHAP_KHO', 'KIEM_DINH_LAI'])
                    ->where('ket_luan', 'DAT');
            })
            ->orderBy('lo_nong_san_id', 'desc')
            ->get();

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();
        $warehouses = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->get();
        $selectedRawLotId = $request->input('nguyen_lieu_id');

        return view('processing.batches.create', [
            'rawLots' => $rawLots,
            'products' => $products,
            'warehouses' => $warehouses,
            'selectedRawLotId' => $selectedRawLotId,
        ]);
    }

    public function store(Request $request)
    {
        $rawLot = LoNongSan::where('loai_lo', 'NGUYEN_LIEU')->findOrFail($request->input('lo_nguyen_lieu_id'));

        $validated = $request->validate([
            'lo_nguyen_lieu_id' => ['required', 'exists:lo_nong_san,lo_nong_san_id'],
            'khoi_luong_su_dung' => ['required', 'numeric', 'min:0.01', 'max:' . $rawLot->so_luong],
            'san_pham_id' => ['required', 'exists:danh_muc_san_pham,san_pham_id'],
            'ma_lo' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{1,20}$/D', 'unique:lo_nong_san,ma_lo'],
            'so_luong_thanh_pham' => ['required', 'numeric', 'min:0.01', 'lte:khoi_luong_su_dung'],
            'don_vi_tinh' => ['required', 'string', 'max:30'],
            'ngay_san_xuat' => ['required', 'date'],
            'han_su_dung' => ['nullable', 'date', 'after_or_equal:ngay_san_xuat'],
            'kho_lanh_id' => ['nullable', 'exists:kho_lanh,kho_lanh_id'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'khoi_luong_su_dung.max' => "Khối lượng nguyên liệu sử dụng không được vượt quá số lượng hiện còn của lô ({$rawLot->so_luong} {$rawLot->don_vi_tinh}).",
            'han_su_dung.after_or_equal' => 'Hạn sử dụng phải bằng hoặc sau ngày sơ chế / sản xuất.',
            'so_luong_thanh_pham.lte' => 'Khối lượng thành phẩm không được vượt khối lượng nguyên liệu đưa vào sơ chế.',
        ]);

        $product = DanhMucSanPham::findOrFail($validated['san_pham_id']);

        $digitalLinks = app(\App\Services\Gs1DigitalLinkService::class);
        if (!$digitalLinks->validGtin($product->gtin)) {
            return back()->withInput()->withErrors([
                'san_pham_id' => 'Sản phẩm phải có GTIN-14 hợp lệ (đủ 14 chữ số và đúng số kiểm tra) trước khi tạo lô thành phẩm.',
            ]);
        }

        // Auto-generate finished product lot code if empty
        if (empty($validated['ma_lo'])) {
            $prefix = $product->ma_san_pham ? preg_replace('/[^A-Za-z0-9]/', '', $product->ma_san_pham) : 'TP';
            $prefix = strtoupper(substr($prefix, 0, 4));
            $validated['ma_lo'] = 'TP-' . $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(3));
        }

        // Calculate default shelf life if not provided
        if (empty($validated['han_su_dung'])) {
            $defaultDays = $product->han_su_dung_mac_dinh_ngay ?? 10;
            $validated['han_su_dung'] = Carbon::parse($validated['ngay_san_xuat'])->addDays($defaultDays)->toDateString();
        }

        $digitalLink = $digitalLinks->productLotUrl($product->gtin, $validated['ma_lo']);

        return DB::transaction(function () use ($validated, $rawLot, $digitalLink) {
            $rawLot = LoNongSan::lockForUpdate()->findOrFail($rawLot->lo_nong_san_id);
            if (!app(\App\Services\QualityGateService::class)->passedInboundInspection($rawLot)
                || !DB::table('tiep_nhan_nguyen_lieu')->where('lo_nong_san_id', $rawLot->lo_nong_san_id)->exists()
                || $validated['khoi_luong_su_dung'] > $rawLot->so_luong) {
                throw \Illuminate\Validation\ValidationException::withMessages(['lo_nguyen_lieu_id' => 'Lô phải có hồ sơ tiếp nhận, đủ điều kiện sơ chế và đủ số lượng còn lại.']);
            }
            // 1. Create finished product lot
            $finishedLot = LoNongSan::create([
                'san_pham_id' => $validated['san_pham_id'],
                'mua_vu_id' => $rawLot->mua_vu_id,
                'to_chuc_nguon_id' => $rawLot->to_chuc_nguon_id,
                'kho_lanh_id' => $validated['kho_lanh_id'] ?? null,
                'loai_lo' => 'THANH_PHAM',
                'ma_lo' => $validated['ma_lo'],
                'digital_link' => $digitalLink,
                'so_luong' => $validated['so_luong_thanh_pham'],
                'don_vi_tinh' => $validated['don_vi_tinh'],
                'ngay_thu_hoach' => $rawLot->ngay_thu_hoach,
                'ngay_san_xuat' => $validated['ngay_san_xuat'],
                'han_su_dung' => $validated['han_su_dung'],
                'trang_thai' => 'DA_SO_CHE',
            ]);

            // 2. Save composition relationship in thanh_phan_lo_nong_san
            ThanhPhanLoNongSan::create([
                'lo_thanh_pham_id' => $finishedLot->lo_nong_san_id,
                'lo_nguyen_lieu_id' => $rawLot->lo_nong_san_id,
                'khoi_luong_su_dung' => $validated['khoi_luong_su_dung'],
                'don_vi_tinh' => $rawLot->don_vi_tinh,
                'ghi_chu' => $validated['ghi_chu'] ?? 'Sơ chế từ lô nguyên liệu ' . $rawLot->ma_lo,
            ]);

            // 3. Deduct raw lot quantity and update status if fully consumed
            $remainingQty = max(0, (float)$rawLot->so_luong - (float)$validated['khoi_luong_su_dung']);
            if ($remainingQty <= 0) {
                app(\App\Services\LotStatusService::class)->transition(
                    $rawLot, 'DA_SO_CHE', auth()->user()?->tai_khoan_id,
                    'Đã sử dụng hết nguyên liệu để tạo lô thành phẩm '.$finishedLot->ma_lo,
                    ['so_luong' => $remainingQty]
                );
            } else {
                $rawLot->update(['so_luong' => $remainingQty]);
            }

            return redirect()->route('processing.batches.show', $finishedLot->lo_nong_san_id)
                ->with('status', "Đã sơ chế thành công và tạo lô thành phẩm {$finishedLot->ma_lo}.");
        });
    }

    public function show($id)
    {
        $lot = LoNongSan::with([
            'sanPham',
            'khoLanh',
            'nguyenLieuDauVao.sanPham',
            'nguyenLieuDauVao.muaVu.vungTrong.toChuc',
            'nguyenLieuDauVao.toChucNguon',
            'donViSscc.khoLanh',
        ])
        ->where('loai_lo', 'THANH_PHAM')
        ->findOrFail($id);

        return view('processing.batches.show', [
            'lot' => $lot,
        ]);
    }
}


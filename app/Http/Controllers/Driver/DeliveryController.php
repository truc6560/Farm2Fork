<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ChiTietGiaoNhan;
use App\Models\ChuyenVanChuyen;
use App\Models\DonViDongGoiSscc;
use App\Models\PhieuGiaoHang;
use App\Models\SuKienHanhTrinh;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Gs1Service;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    /**
     * Display delivery orders list for the driver.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status', 'all');
        $search = trim($request->query('q', ''));
        $tripId = $request->query('chuyen_id');

        // Trips strictly accessible to driver
        $driverTrips = ChuyenVanChuyen::where('tai_xe_id', $user->tai_khoan_id)->orderByDesc('chuyen_id')->get();
        $tripIds = $driverTrips->pluck('chuyen_id')->toArray();

        // Deliveries query
        $query = PhieuGiaoHang::with([
            'toChucNhan',
            'chuyenVanChuyen.phuongTien',
            'chiTietGiaoNhan.donViDongGoi.chiTiet.loNongSan.sanPham',
        ])->whereIn('chuyen_id', $tripIds);

        if ($tripId) {
            $query->where('chuyen_id', $tripId);
        }

        if ($status === 'pending') {
            $query->whereIn('trang_thai', ['CHO_GIAO', 'DANG_GIAO', 'NHAP']);
        } elseif ($status === 'delivered') {
            $query->where('trang_thai', 'DA_GIAO');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ma_phieu_giao_hang', 'like', "%{$search}%")
                  ->orWhere('ten_nguoi_nhan', 'like', "%{$search}%")
                  ->orWhere('sdt_nguoi_nhan', 'like', "%{$search}%")
                  ->orWhere('dia_chi_giao', 'like', "%{$search}%")
                  ->orWhereHas('toChucNhan', fn($to) => $to->where('ten_to_chuc', 'like', "%{$search}%"));
            });
        }

        $deliveries = $query->orderByRaw("
            CASE 
                WHEN trang_thai = 'DANG_GIAO' THEN 1
                WHEN trang_thai = 'CHO_GIAO' THEN 2
                WHEN trang_thai = 'NHAP' THEN 3
                ELSE 4
            END
        ")->orderByDesc('phieu_giao_hang_id')->paginate(12)->withQueryString();

        // Counts strictly scoped to driver trips
        $baseQuery = PhieuGiaoHang::whereIn('chuyen_id', $tripIds);
        $totalDeliveries = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->whereIn('trang_thai', ['CHO_GIAO', 'DANG_GIAO', 'NHAP'])->count();
        $deliveredCount = (clone $baseQuery)->where('trang_thai', 'DA_GIAO')->count();

        return view('driver.delivery.index', [
            'deliveries' => $deliveries,
            'driverTrips' => $driverTrips,
            'selectedTripId' => $tripId,
            'status' => $status,
            'search' => $search,
            'totalDeliveries' => $totalDeliveries,
            'pendingCount' => $pendingCount,
            'deliveredCount' => $deliveredCount,
        ]);
    }

    /**
     * Show detail of a delivery order.
     */
    public function show(int $id, Request $request): View
    {
        $user = $request->user();
        $delivery = PhieuGiaoHang::with([
            'toChucNhan',
            'nguoiLap',
            'chuyenVanChuyen.phuongTien',
            'chiTietGiaoNhan.donViDongGoi.chiTiet.loNongSan.sanPham',
        ])->findOrFail($id);

        if (!$delivery->chuyenVanChuyen || (int)$delivery->chuyenVanChuyen->tai_xe_id !== (int)$user->tai_khoan_id) {
            abort(403, 'Bạn không được phân công giao hàng cho phiếu này.');
        }

        return view('driver.delivery.show', [
            'delivery' => $delivery,
            'quantities' => DB::table('so_luong_giao_nhan as q')->join('lo_nong_san as l', 'l.lo_nong_san_id', '=', 'q.lo_nong_san_id')->whereIn('q.chi_tiet_giao_nhan_id', $delivery->chiTietGiaoNhan->pluck('chi_tiet_giao_nhan_id'))->select('q.*', 'l.ma_lo')->get(),
            'packingConditions' => ChiTietGiaoNhan::TINH_TRANG_BAO_GOI_LABELS,
        ]);
    }

    /**
     * Confirm delivery of goods to recipient.
     */
    public function confirmDelivery(int $id, Request $request): RedirectResponse
    {
        $user = $request->user();
        $delivery = PhieuGiaoHang::with(['chiTietGiaoNhan', 'chuyenVanChuyen'])->findOrFail($id);

        if (!$delivery->chuyenVanChuyen || (int)$delivery->chuyenVanChuyen->tai_xe_id !== (int)$user->tai_khoan_id) {
            abort(403, 'Bạn không được phân công giao hàng cho phiếu này.');
        }

        $validated = $request->validate([
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*.received' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'quantities.*.reason' => ['nullable', 'string', 'max:500'],
            'sscc_codes' => ['required', 'string', 'max:10000'],
            'ten_nguoi_nhan_thuc_te' => ['required', 'string', 'max:150'],
            'tinh_trang_bao_goi' => ['required', 'string', 'in:' . implode(',', array_keys(ChiTietGiaoNhan::TINH_TRANG_BAO_GOI_LABELS))],
            'mo_ta_hu_hong' => ['nullable', 'string', 'max:1000'],
            'ghi_chu' => ['nullable', 'string', 'max:1000'],
            'anh_minh_chung' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'anh_minh_chung_url' => ['nullable', 'url', 'max:500'],
        ], [
            'ten_nguoi_nhan_thuc_te.required' => 'Vui lòng nhập họ tên người nhận hàng thực tế.',
            'tinh_trang_bao_goi.required' => 'Vui lòng xác nhận tình trạng bao gói khi bàn giao.',
        ]);

        if ($delivery->chuyenVanChuyen->trang_thai !== 'DANG_VAN_CHUYEN') {
            return back()->withErrors(['delivery' => 'Chỉ xác nhận giao nhận khi chuyến đang vận chuyển.'])->withInput();
        }
        $scanned = collect(preg_split('/[\s,;]+/', trim($validated['sscc_codes'])))
            ->filter()->unique()->sort()->values();
        $expected = DonViDongGoiSscc::whereIn('sscc_id', $delivery->chiTietGiaoNhan->pluck('sscc_id'))
            ->pluck('ma_sscc')->map(fn ($code) => (string) $code)->sort()->values();
        if ($scanned->all() !== $expected->all()) {
            return back()->withErrors(['sscc_codes' => 'Phải quét đúng và đủ toàn bộ mã SSCC thuộc phiếu giao này.'])->withInput();
        }
        foreach ($scanned as $code) {
            if (!app(Gs1Service::class)->validateSscc($code)['valid']) {
                return back()->withErrors(['sscc_codes' => "Mã SSCC {$code} không hợp lệ; cần đối chiếu và quét lại."])->withInput();
            }
        }

        // Process proof photo
        $proofUrl = $validated['anh_minh_chung_url'] ?? null;
        if ($request->hasFile('anh_minh_chung')) {
            $file = $request->file('anh_minh_chung');
            $uploadDir = public_path('uploads/delivery_proofs');
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $filename = 'proof_' . $delivery->phieu_giao_hang_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $proofUrl = asset('uploads/delivery_proofs/' . $filename);
        }

        app(\App\Services\TraceabilityHandoffService::class)->confirm($id, $validated, $user, $proofUrl);
        return redirect()->route('driver.delivery.show', $id)->with('status', 'Đã lưu kết quả giao nhận theo từng lô. Biên bản đã chốt không được ghi đè.');
    }
}

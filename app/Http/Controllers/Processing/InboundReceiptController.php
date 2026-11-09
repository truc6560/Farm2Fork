<?php

namespace App\Http\Controllers\Processing;

use App\Http\Controllers\Controller;
use App\Models\DanhMucSanPham;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use Illuminate\Http\Request;

class InboundReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = LoNongSan::with(['sanPham', 'muaVu.vungTrong', 'toChucNguon', 'khoLanh'])
            ->where('loai_lo', 'NGUYEN_LIEU');

        // Filters
        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('ma_lo', 'like', "%{$keyword}%")
                    ->orWhere('ma_lo_nha_cung_cap', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('san_pham_id')) {
            $query->where('san_pham_id', $request->input('san_pham_id'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        // Stats summary
        $baseQuery = LoNongSan::where('loai_lo', 'NGUYEN_LIEU');
        $totalRaw = (clone $baseQuery)->count();
        $pendingReceipt = (clone $baseQuery)->where('trang_thai', 'MOI_TAO')->count();
        $readyToProcess = (clone $baseQuery)->where('trang_thai', 'CHO_SO_CHE')->count();
        $processed = (clone $baseQuery)->where('trang_thai', 'DA_SO_CHE')->count();

        $lots = $query->orderBy('lo_nong_san_id', 'desc')->paginate(15)->withQueryString();
        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();
        $warehouses = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->get();

        return view('processing.inbound.index', [
            'lots' => $lots,
            'products' => $products,
            'warehouses' => $warehouses,
            'organizations' => \App\Models\ToChuc::orderBy('ten_to_chuc')->get(),
            'filters' => $request->all(),
            'totalRaw' => $totalRaw,
            'pendingReceipt' => $pendingReceipt,
            'readyToProcess' => $readyToProcess,
            'processed' => $processed,
        ]);
    }

    public function show($id)
    {
        $lot = LoNongSan::with([
            'sanPham',
            'muaVu.vungTrong.toChuc',
            'toChucNguon',
            'khoLanh',
            'thanhPhamDauRa.sanPham'
        ])
        ->where('loai_lo', 'NGUYEN_LIEU')
        ->findOrFail($id);

        $warehouses = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->get();

        return view('processing.inbound.show', [
            'lot' => $lot,
            'warehouses' => $warehouses,
            'organizations' => \App\Models\ToChuc::orderBy('ten_to_chuc')->get(),
        ]);
    }

    public function receive(Request $request, $id)
    {
        $lot = LoNongSan::where('loai_lo', 'NGUYEN_LIEU')->findOrFail($id);

        $validated = $request->validate([
            'to_chuc_giao_id' => ['nullable', 'exists:to_chuc,to_chuc_id'],
            'so_luong_thuc_nhan' => ['required', 'numeric', 'min:0.01'],
            'kho_lanh_id' => ['nullable', 'exists:kho_lanh,kho_lanh_id'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['to_chuc_giao_id'] = $validated['to_chuc_giao_id'] ?? $lot->to_chuc_nguon_id ?? $request->user()->to_chuc_id ?? 1;

        app(\App\Services\TraceabilityHandoffService::class)->receiveRaw((int)$id, $validated, $request->user());

        return redirect()->route('processing.inbound.show', $lot->lo_nong_san_id)
            ->with('status', "Đã tiếp nhận lô nguyên liệu {$lot->ma_lo} vào xưởng sơ chế thành công.");
    }
}


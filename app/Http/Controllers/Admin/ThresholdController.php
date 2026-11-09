<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ThresholdController extends Controller
{
    public function index(): View
    {
        $thresholds = DB::table('cau_hinh_nguong_canh_bao as ch')
            ->leftJoin('kho_lanh as k', 'k.kho_lanh_id', '=', 'ch.kho_lanh_id')
            ->select('ch.*', 'k.ten_kho', 'k.ma_kho')
            ->orderByDesc('ch.cau_hinh_id')
            ->paginate(20);

        $warehouses = DB::table('kho_lanh')
            ->select('kho_lanh_id', 'ten_kho', 'ma_kho')
            ->orderBy('ten_kho')
            ->get();

        return view('admin.thresholds.index', [
            'thresholds' => $thresholds,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kho_lanh_id' => ['required', 'integer', 'exists:kho_lanh,kho_lanh_id'],
            'nhiet_do_min' => ['required', 'numeric'],
            'nhiet_do_max' => ['required', 'numeric', 'gt:nhiet_do_min'],
            'do_am_min' => ['nullable', 'numeric'],
            'do_am_max' => ['nullable', 'numeric'],
            'thoi_gian_vuot_nguong_phut' => ['nullable', 'integer', 'min:1'],
            'mo_ta' => ['nullable', 'string', 'max:255'],
        ], [
            'kho_lanh_id.required' => 'Vui lòng chọn kho lạnh.',
            'nhiet_do_max.gt' => 'Nhiệt độ tối đa phải lớn hơn nhiệt độ tối thiểu.',
        ]);

        if (isset($validated['do_am_min'], $validated['do_am_max']) && $validated['do_am_min'] !== null && $validated['do_am_max'] !== null) {
            if ((float)$validated['do_am_max'] <= (float)$validated['do_am_min']) {
                return back()->withInput()->withErrors(['do_am_max' => 'Độ ẩm tối đa phải lớn hơn độ ẩm tối thiểu.']);
            }
        }

        DB::table('cau_hinh_nguong_canh_bao')->insert([
            'kho_lanh_id' => $validated['kho_lanh_id'],
            'nhiet_do_min' => $validated['nhiet_do_min'],
            'nhiet_do_max' => $validated['nhiet_do_max'],
            'do_am_min' => $validated['do_am_min'] ?? null,
            'do_am_max' => $validated['do_am_max'] ?? null,
            'thoi_gian_vuot_nguong_phut' => $validated['thoi_gian_vuot_nguong_phut'] ?? 15,
            'trang_thai' => 'HIEU_LUC',
            'mo_ta' => $validated['mo_ta'] ?? null,
        ]);

        return back()->with('status', 'Đã thêm cấu hình ngưỡng cảnh báo thành công.');
    }

    public function edit(int $id): View
    {
        $threshold = DB::table('cau_hinh_nguong_canh_bao as ch')
            ->leftJoin('kho_lanh as k', 'k.kho_lanh_id', '=', 'ch.kho_lanh_id')
            ->select('ch.*', 'k.ten_kho', 'k.ma_kho')
            ->where('ch.cau_hinh_id', $id)
            ->first();

        abort_unless($threshold, 404, 'Không tìm thấy cấu hình ngưỡng cảnh báo.');

        $warehouses = DB::table('kho_lanh')
            ->select('kho_lanh_id', 'ten_kho', 'ma_kho')
            ->orderBy('ten_kho')
            ->get();

        return view('admin.thresholds.edit', [
            'threshold' => $threshold,
            'warehouses' => $warehouses,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $row = DB::table('cau_hinh_nguong_canh_bao')->where('cau_hinh_id', $id)->first();
        abort_unless($row, 404, 'Không tìm thấy cấu hình ngưỡng cảnh báo.');

        $validated = $request->validate([
            'kho_lanh_id' => ['required', 'integer', 'exists:kho_lanh,kho_lanh_id'],
            'nhiet_do_min' => ['required', 'numeric'],
            'nhiet_do_max' => ['required', 'numeric', 'gt:nhiet_do_min'],
            'do_am_min' => ['nullable', 'numeric'],
            'do_am_max' => ['nullable', 'numeric'],
            'thoi_gian_vuot_nguong_phut' => ['nullable', 'integer', 'min:1'],
            'trang_thai' => ['nullable', 'string', 'in:HIEU_LUC,NGUNG,DANG_AP_DUNG'],
            'mo_ta' => ['nullable', 'string', 'max:255'],
        ], [
            'kho_lanh_id.required' => 'Vui lòng chọn kho lạnh.',
            'nhiet_do_max.gt' => 'Nhiệt độ tối đa phải lớn hơn nhiệt độ tối thiểu.',
        ]);

        if (isset($validated['do_am_min'], $validated['do_am_max']) && $validated['do_am_min'] !== null && $validated['do_am_max'] !== null) {
            if ((float)$validated['do_am_max'] <= (float)$validated['do_am_min']) {
                return back()->withInput()->withErrors(['do_am_max' => 'Độ ẩm tối đa phải lớn hơn độ ẩm tối thiểu.']);
            }
        }

        $updateData = [
            'kho_lanh_id' => $validated['kho_lanh_id'],
            'nhiet_do_min' => $validated['nhiet_do_min'],
            'nhiet_do_max' => $validated['nhiet_do_max'],
            'do_am_min' => $validated['do_am_min'] ?? null,
            'do_am_max' => $validated['do_am_max'] ?? null,
            'thoi_gian_vuot_nguong_phut' => $validated['thoi_gian_vuot_nguong_phut'] ?? 15,
            'mo_ta' => $validated['mo_ta'] ?? null,
        ];

        if (!empty($validated['trang_thai'])) {
            $updateData['trang_thai'] = $validated['trang_thai'];
        }

        DB::table('cau_hinh_nguong_canh_bao')
            ->where('cau_hinh_id', $id)
            ->update($updateData);

        return redirect()->route('admin.thresholds.index')
            ->with('status', 'Đã cập nhật cấu hình ngưỡng cảnh báo thành công.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $row = DB::table('cau_hinh_nguong_canh_bao')->where('cau_hinh_id', $id)->first();
        abort_unless($row, 404);

        $next = in_array($row->trang_thai ?? '', ['HIEU_LUC', 'DANG_AP_DUNG']) ? 'NGUNG' : 'HIEU_LUC';
        DB::table('cau_hinh_nguong_canh_bao')->where('cau_hinh_id', $id)->update(['trang_thai' => $next]);

        return back()->with('status', 'Đã cập nhật trạng thái cấu hình.');
    }
}

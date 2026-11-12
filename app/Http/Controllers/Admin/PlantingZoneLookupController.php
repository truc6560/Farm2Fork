<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoanHeThong;
use App\Models\VungTrong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlantingZoneLookupController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'nguoi_san_xuat_id' => ['nullable', 'integer', 'exists:tai_khoan_he_thong,tai_khoan_id'],
            'trang_thai' => ['nullable', 'string', 'in:DANG_SU_DUNG,TAM_NGUNG'],
            'khu_vuc' => ['nullable', 'string', 'max:100'],
        ]);

        $zones = VungTrong::query()
            ->with(['nguoiPhuTrach', 'nhanVienPhanCong', 'toChuc'])
            ->withCount('muaVu')
            ->when($filters['q'] ?? null, function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('ten_vung_trong', 'like', "%{$value}%")
                        ->orWhere('ma_so_vung_trong', 'like', "%{$value}%")
                        ->orWhere('dia_chi', 'like', "%{$value}%")
                        ->orWhere('cay_trong_chinh', 'like', "%{$value}%");
                });
            })
            ->when($filters['nguoi_san_xuat_id'] ?? null, function ($query, $id) {
                $query->where(function ($q) use ($id) {
                    $q->where('nguoi_phu_trach_id', $id)
                        ->orWhereHas('nhanVienPhanCong', fn ($assigned) => $assigned->where('tai_khoan_he_thong.tai_khoan_id', $id));
                });
            })
            ->when($filters['trang_thai'] ?? null, fn ($query, $value) => $query->where('trang_thai', $value))
            ->when($filters['khu_vuc'] ?? null, fn ($query, $value) => $query->where('dia_chi', 'like', "%{$value}%"))
            ->orderByDesc('vung_trong_id')
            ->paginate(15)
            ->withQueryString();

        $producerIds = DB::table('nhan_vien_vung_trong')->distinct()->pluck('tai_khoan_id');
        $producers = TaiKhoanHeThong::whereIn('tai_khoan_id', $producerIds)
            ->orWhereIn('tai_khoan_id', VungTrong::whereNotNull('nguoi_phu_trach_id')->select('nguoi_phu_trach_id'))
            ->orderBy('ho_ten')
            ->get(['tai_khoan_id', 'ho_ten']);

        return view('admin.planting-zones.index', compact('zones', 'filters', 'producers'));
    }

    public function show(int $id)
    {
        $zone = VungTrong::with(['toChuc', 'nguoiPhuTrach', 'nhanVienPhanCong', 'muaVu.sanPham'])
            ->findOrFail($id);

        return view('admin.planting-zones.show', compact('zone'));
    }
}

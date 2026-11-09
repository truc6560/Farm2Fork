<?php

namespace App\Http\Controllers\Producer;

use App\Http\Controllers\Controller;
use App\Models\VungTrong;
use App\Services\ProducerAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlantingZoneController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $query = VungTrong::with(['toChuc', 'nguoiPhuTrach', 'muaVu'])
            ->whereIn('vung_trong_id', $assignedZoneIds);

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('ten_vung_trong', 'like', "%{$keyword}%")
                    ->orWhere('ma_so_vung_trong', 'like', "%{$keyword}%")
                    ->orWhere('dia_chi', 'like', "%{$keyword}%")
                    ->orWhere('cay_trong_chinh', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('loai_chung_nhan')) {
            $query->where('loai_chung_nhan', $request->input('loai_chung_nhan'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        $zones = $query->orderBy('vung_trong_id', 'desc')->paginate(10)->withQueryString();

        // Calculate summary statistics strictly within user's assigned planting zones
        $baseQuery = VungTrong::whereIn('vung_trong_id', $assignedZoneIds);
        $totalZones = (clone $baseQuery)->count();
        $totalArea = (clone $baseQuery)->sum('dien_tich_ha');
        $expiringCerts = (clone $baseQuery)
            ->whereNotNull('ngay_het_han_chung_nhan')
            ->whereBetween('ngay_het_han_chung_nhan', [now(), now()->addDays(30)])
            ->count();

        return view('producer.planting-zones.index', [
            'zones' => $zones,
            'filters' => $request->all(),
            'totalZones' => $totalZones,
            'totalArea' => $totalArea,
            'expiringCerts' => $expiringCerts,
            'allOrgs' => collect(),
            'selectedOrgId' => null,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('producer.planting-zones.create', [
            'defaultOrgId' => $user->to_chuc_id ?? 1,
            'allOrgs' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'ma_so_vung_trong' => ['nullable', 'string', 'max:80', 'unique:vung_trong,ma_so_vung_trong', 'required_without:tep_chung_nhan'],
            'tep_chung_nhan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120', 'required_without:ma_so_vung_trong'],
            'cay_trong_chinh' => ['required', 'string', 'max:150'],
            'dien_tich_ha' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'dia_chi' => ['required', 'string', 'max:255'],
            'ten_vung_trong' => ['nullable', 'string', 'max:200'],
            'so_chung_nhan' => ['nullable', 'string', 'max:100'],
            'loai_chung_nhan' => ['nullable', 'string', 'max:80'],
            'don_vi_cap_chung_nhan' => ['nullable', 'string', 'max:200'],
            'ngay_cap_chung_nhan' => ['nullable', 'date'],
            'ngay_het_han_chung_nhan' => ['nullable', 'date', 'after_or_equal:ngay_cap_chung_nhan'],
            'trang_thai' => ['required', 'in:DANG_SU_DUNG,TAM_NGUNG'],
        ], [
            'ma_so_vung_trong.required_without' => 'Vui lòng gõ mã số vùng trồng (MSVT) hoặc tải lên tệp chứng nhận/tài liệu minh chứng.',
            'ma_so_vung_trong.unique' => 'Mã số vùng trồng này đã tồn tại trong hệ thống.',
            'tep_chung_nhan.required_without' => 'Vui lòng tải lên tệp chứng nhận/tài liệu minh chứng hoặc nhập Mã số vùng trồng (MSVT).',
            'tep_chung_nhan.mimes' => 'Tệp chứng nhận phải có định dạng: pdf, jpg, jpeg hoặc png.',
            'tep_chung_nhan.max' => 'Dung lượng tệp chứng nhận không vượt quá 5MB.',
            'cay_trong_chinh.required' => 'Vui lòng nhập loại cây trồng chủ lực.',
            'dien_tich_ha.required' => 'Vui lòng nhập diện tích canh tác (ha).',
            'dien_tich_ha.gt' => 'Diện tích canh tác phải lớn hơn 0 ha.',
            'dia_chi.required' => 'Vui lòng nhập địa chỉ chi tiết vùng trồng.',
            'ngay_het_han_chung_nhan.after_or_equal' => 'Ngày hết hạn chứng nhận phải sau hoặc bằng ngày cấp.',
        ]);

        $msvt = !empty($validated['ma_so_vung_trong']) ? trim($validated['ma_so_vung_trong']) : null;
        $validated['ma_so_vung_trong'] = $msvt;

        if (!empty($validated['ten_vung_trong'])) {
            $validated['ten_vung_trong'] = trim($validated['ten_vung_trong']);
        } elseif ($msvt) {
            $validated['ten_vung_trong'] = $msvt;
        } else {
            $validated['ten_vung_trong'] = 'Vùng trồng ' . trim($validated['cay_trong_chinh']);
        }

        $filePath = null;
        if ($request->hasFile('tep_chung_nhan')) {
            $file = $request->file('tep_chung_nhan');
            $fileName = 'cert_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('uploads/chung-nhan', $fileName, 'public');
        }

        // Giữ nguyên to_chuc_id trong dữ liệu nhưng không dùng để cấp quyền
        $validated['to_chuc_id'] = $user->to_chuc_id ?? 1;
        $validated['nguoi_phu_trach_id'] = $user->tai_khoan_id;
        $validated['tep_chung_nhan'] = $filePath;

        $zone = VungTrong::create($validated);

        // Người sản xuất tự tạo vùng mới thì vùng đó tự động được gán phân công cho họ
        ProducerAuthorizationService::assignZoneToUser($user->tai_khoan_id, $zone->vung_trong_id, 'Tự tạo vùng trồng mới');

        return redirect()->route('producer.planting-zones.show', $zone->vung_trong_id)
            ->with('status', "Đã lưu hồ sơ vùng trồng thành công: " . ($zone->ma_so_vung_trong ?: $zone->ma_noi_bo));
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $zone = VungTrong::with(['toChuc', 'nguoiPhuTrach', 'muaVu.sanPham', 'muaVu.nhatKyCanhTac'])
            ->findOrFail($id);

        ProducerAuthorizationService::authorizeZone($user, $zone);

        $seasons = $zone->muaVu()->with('sanPham')->orderBy('mua_vu_id', 'desc')->get();

        return view('producer.planting-zones.show', [
            'zone' => $zone,
            'seasons' => $seasons,
        ]);
    }

    public function edit(Request $request, $id)
    {
        $user = $request->user();
        $zone = VungTrong::with('toChuc')->findOrFail($id);

        ProducerAuthorizationService::authorizeZone($user, $zone);

        return view('producer.planting-zones.edit', [
            'zone' => $zone,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $zone = VungTrong::findOrFail($id);

        ProducerAuthorizationService::authorizeZone($user, $zone);

        $hasExistingFile = !empty($zone->tep_chung_nhan);

        $rules = [
            'ma_so_vung_trong' => ['nullable', 'string', 'max:80', 'unique:vung_trong,ma_so_vung_trong,' . $id . ',vung_trong_id'],
            'tep_chung_nhan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'cay_trong_chinh' => ['required', 'string', 'max:150'],
            'dien_tich_ha' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'dia_chi' => ['required', 'string', 'max:255'],
            'ten_vung_trong' => ['nullable', 'string', 'max:200'],
            'so_chung_nhan' => ['nullable', 'string', 'max:100'],
            'loai_chung_nhan' => ['nullable', 'string', 'max:80'],
            'don_vi_cap_chung_nhan' => ['nullable', 'string', 'max:200'],
            'ngay_cap_chung_nhan' => ['nullable', 'date'],
            'ngay_het_han_chung_nhan' => ['nullable', 'date', 'after_or_equal:ngay_cap_chung_nhan'],
            'trang_thai' => ['required', 'in:DANG_SU_DUNG,TAM_NGUNG'],
        ];

        if (!$hasExistingFile) {
            $rules['ma_so_vung_trong'][] = 'required_without:tep_chung_nhan';
            $rules['tep_chung_nhan'][] = 'required_without:ma_so_vung_trong';
        }

        $validated = $request->validate($rules, [
            'ma_so_vung_trong.required_without' => 'Vui lòng gõ mã số vùng trồng (MSVT) hoặc tải lên tệp chứng nhận/tài liệu minh chứng.',
            'ma_so_vung_trong.unique' => 'Mã số vùng trồng này đã tồn tại trong hệ thống.',
            'tep_chung_nhan.required_without' => 'Vui lòng tải lên tệp chứng nhận/tài liệu minh chứng hoặc nhập Mã số vùng trồng (MSVT).',
            'tep_chung_nhan.mimes' => 'Tệp chứng nhận phải có định dạng: pdf, jpg, jpeg hoặc png.',
            'tep_chung_nhan.max' => 'Dung lượng tệp chứng nhận không vượt quá 5MB.',
            'cay_trong_chinh.required' => 'Vui lòng nhập loại cây trồng chủ lực.',
            'dien_tich_ha.required' => 'Vui lòng nhập diện tích canh tác (ha).',
            'dien_tich_ha.gt' => 'Diện tích canh tác phải lớn hơn 0 ha.',
            'dia_chi.required' => 'Vui lòng nhập địa chỉ chi tiết vùng trồng.',
            'ngay_het_han_chung_nhan.after_or_equal' => 'Ngày hết hạn chứng nhận phải sau hoặc bằng ngày cấp.',
        ]);

        $msvt = !empty($validated['ma_so_vung_trong']) ? trim($validated['ma_so_vung_trong']) : null;
        $validated['ma_so_vung_trong'] = $msvt;

        if (!empty($validated['ten_vung_trong'])) {
            $validated['ten_vung_trong'] = trim($validated['ten_vung_trong']);
        } elseif ($msvt) {
            $validated['ten_vung_trong'] = $msvt;
        } else {
            $validated['ten_vung_trong'] = 'Vùng trồng ' . trim($validated['cay_trong_chinh']);
        }

        if ($request->hasFile('tep_chung_nhan')) {
            $file = $request->file('tep_chung_nhan');
            $fileName = 'cert_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $validated['tep_chung_nhan'] = $file->storeAs('uploads/chung-nhan', $fileName, 'public');
        } else {
            unset($validated['tep_chung_nhan']);
        }

        $validated['to_chuc_id'] = $zone->to_chuc_id ?? $user->to_chuc_id ?? 1;
        $validated['nguoi_phu_trach_id'] = $zone->nguoi_phu_trach_id ?? $user->tai_khoan_id;

        $zone->update($validated);

        return redirect()->route('producer.planting-zones.show', $zone->vung_trong_id)
            ->with('status', "Đã cập nhật thông tin vùng trồng: " . ($zone->ma_so_vung_trong ?: $zone->ma_noi_bo));
    }

    public function downloadCertificate(Request $request, $id)
    {
        $user = $request->user();
        $zone = VungTrong::findOrFail($id);

        ProducerAuthorizationService::authorizeZone($user, $zone);

        if (!$zone->tep_chung_nhan || !Storage::disk('public')->exists($zone->tep_chung_nhan)) {
            return back()->with('warning', 'Tệp chứng nhận không tồn tại trên hệ thống.');
        }

        return Storage::disk('public')->download($zone->tep_chung_nhan);
    }
}

<?php

namespace App\Http\Controllers\Producer;

use App\Http\Controllers\Controller;
use App\Models\ChiTietSuDungVatTu;
use App\Models\DanhMucVatTu;
use App\Models\LoNongSan;
use App\Models\MuaVu;
use App\Models\NhatKyCanhTac;
use App\Services\ProducerAuthorizationService;
use App\Services\SeasonProgressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FarmingLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $query = NhatKyCanhTac::with(['muaVu.vungTrong', 'muaVu.sanPham', 'nguoiThucHien', 'chiTietVatTu.vatTu'])
            ->whereHas('muaVu', function ($q) use ($assignedZoneIds) {
                $q->whereIn('vung_trong_id', $assignedZoneIds);
            });

        if ($request->filled('mua_vu_id')) {
            $query->where('mua_vu_id', $request->input('mua_vu_id'));
        }

        if ($request->filled('loai_hoat_dong')) {
            $query->where('loai_hoat_dong', $request->input('loai_hoat_dong'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        if ($request->filled('tu_ngay')) {
            $query->whereDate('ngay_thuc_hien', '>=', $request->input('tu_ngay'));
        }

        if ($request->filled('den_ngay')) {
            $query->whereDate('ngay_thuc_hien', '<=', $request->input('den_ngay'));
        }

        $matchingSeasonIds = null;
        if ($request->filled('loai_hoat_dong') || $request->filled('trang_thai') || $request->filled('tu_ngay') || $request->filled('den_ngay')) {
            $matchingSeasonIds = (clone $query)->distinct()->pluck('mua_vu_id');
        }
        $logs = $query->orderBy('ngay_thuc_hien', 'desc')->paginate(12)->withQueryString();

        // Summary counts
        $baseQuery = NhatKyCanhTac::whereHas('muaVu', function ($q) use ($assignedZoneIds) {
            $q->whereIn('vung_trong_id', $assignedZoneIds);
        });

        $totalLogs = (clone $baseQuery)->count();
        $fertilizeCount = (clone $baseQuery)->whereIn('loai_hoat_dong', ['BON_LOT', 'BON_THUC'])->count();
        $pesticideCount = (clone $baseQuery)->where('loai_hoat_dong', 'PHUN_THUOC')->count();
        $harvestCount = (clone $baseQuery)->where('loai_hoat_dong', 'THU_HOACH')->count();

        $seasons = MuaVu::with(['vungTrong', 'sanPham'])
            ->whereIn('vung_trong_id', $assignedZoneIds)
            ->orderBy('mua_vu_id', 'desc')
            ->get();
        $mobileSeasons = $seasons->filter(function ($season) use ($request, $matchingSeasonIds) {
            return (!$request->filled('mua_vu_id') || (string) $season->mua_vu_id === (string) $request->input('mua_vu_id'))
                && ($matchingSeasonIds === null || $matchingSeasonIds->contains($season->mua_vu_id));
        });

        return view('producer.farming-logs.index', [
            'logs' => $logs,
            'filters' => $request->all(),
            'totalLogs' => $totalLogs,
            'fertilizeCount' => $fertilizeCount,
            'pesticideCount' => $pesticideCount,
            'harvestCount' => $harvestCount,
            'seasons' => $seasons,
            'mobileSeasons' => $mobileSeasons,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $assignedZoneIds = ProducerAuthorizationService::getAssignedZoneIds($user);

        $seasons = MuaVu::with(['vungTrong', 'sanPham'])
            ->whereIn('vung_trong_id', $assignedZoneIds)
            ->whereIn('trang_thai', ['DANG_CANH_TAC', 'DANG_THU_HOACH', 'CHUAN_BI'])
            ->orderBy('mua_vu_id', 'desc')
            ->get();

        if ($seasons->isEmpty()) {
            return redirect()->route('producer.seasons.create')
                ->with('warning', 'Bạn cần có ít nhất một Mùa vụ đang diễn ra để ghi nhật ký canh tác.');
        }

        $supplies = DanhMucVatTu::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.farming-logs.create', [
            'seasons' => $seasons,
            'supplies' => $supplies,
            'selectedSeasonId' => $request->input('mua_vu_id'),
            'defaultActivity' => $request->input('activity', 'TUOI_NUOC'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'mua_vu_id' => ['required', 'exists:mua_vu,mua_vu_id'],
            'loai_hoat_dong' => ['required', 'string', 'max:80'],
            'ngay_thuc_hien' => ['required', 'date'],
            'ghi_chu' => ['nullable', 'string'],
            'tep_minh_chung' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'trang_thai' => ['required', 'in:NHAP,DA_XAC_NHAN'],
            'vat_tu' => ['nullable', 'array'],
            'vat_tu.*.vat_tu_id' => ['required_with:vat_tu.*.lieu_luong', 'exists:danh_muc_vat_tu,vat_tu_id'],
            'vat_tu.*.lieu_luong' => ['nullable', 'numeric', 'min:0'],
            'vat_tu.*.don_vi_tinh' => ['nullable', 'string', 'max:30'],
            'vat_tu.*.thoi_gian_cach_ly' => ['nullable', 'integer', 'min:0'],
            'vat_tu.*.ghi_chu' => ['nullable', 'string', 'max:255'],
            // Optional harvest lot fields
            'create_lot' => ['nullable', 'boolean'],
            'so_luong_thu_hoach' => ['nullable', 'numeric', 'min:0.01'],
            'ma_lo' => ['nullable', 'string', 'max:80'],
        ], [
            'mua_vu_id.required' => 'Vui lòng chọn mùa vụ canh tác.',
            'loai_hoat_dong.required' => 'Vui lòng chọn loại hoạt động canh tác.',
            'ngay_thuc_hien.required' => 'Vui lòng chọn thời điểm thực hiện.',
            'tep_minh_chung.required' => 'Người sản xuất bắt buộc phải chụp hình ảnh minh chứng hoạt động canh tác tại vườn.',
            'tep_minh_chung.image' => 'Tệp minh chứng bắt buộc phải là định dạng hình ảnh (JPG, PNG, WebP).',
        ]);

        $season = MuaVu::with('vungTrong', 'sanPham')->findOrFail($validated['mua_vu_id']);
        ProducerAuthorizationService::authorizeSeason($user, $season);
        if (in_array($season->trang_thai, ['HOAN_THANH', 'HUY'], true)) {
            return back()->withInput()->withErrors(['mua_vu_id' => 'Mùa vụ đã kết thúc nên không thể thêm nhật ký canh tác.']);
        }
        if ($validated['loai_hoat_dong'] === 'THU_HOACH' && $validated['trang_thai'] === 'DA_XAC_NHAN'
            && !in_array($season->trang_thai, ['DANG_CANH_TAC', 'DANG_THU_HOACH'], true)) {
            return back()->withInput()->withErrors(['mua_vu_id' => 'Mùa vụ chưa canh tác hoặc đã kết thúc, không thể xác nhận thu hoạch.']);
        }

        // Kiểm tra an toàn cách ly thuốc nếu hoạt động là THU HOẠCH hoặc chọn tạo lô thu hoạch
        if ($validated['loai_hoat_dong'] === 'THU_HOACH' || $request->boolean('create_lot')) {
            $quarantineInfo = $season->getThongTinCachLy($validated['ngay_thuc_hien']);
            if ($quarantineInfo['dang_cach_ly']) {
                $harvestDateStr = Carbon::parse($validated['ngay_thuc_hien'])->format('d/m/Y');
                $safeDateStr = $quarantineInfo['ngay_het_cach_ly']->format('d/m/Y');
                return back()->withInput()->withErrors([
                    'ngay_thuc_hien' => "Không thể thu hoạch vào ngày {$harvestDateStr} vì chưa hết thời gian cách ly an toàn của thuốc BVTV/vật tư ('{$quarantineInfo['ten_vat_tu']}'). Thời điểm thu hoạch được phép sớm nhất là sau ngày {$safeDateStr}."
                ]);
            }
        }

        // Kiểm tra thời gian cách ly tối thiểu cho từng loại thuốc / vật tư sử dụng
        if (!empty($validated['vat_tu']) && is_array($validated['vat_tu'])) {
            foreach ($validated['vat_tu'] as $index => $item) {
                if (!empty($item['vat_tu_id']) && !empty($item['lieu_luong'])) {
                    $vatTu = DanhMucVatTu::find($item['vat_tu_id']);
                    if ($vatTu && $vatTu->thoi_gian_cach_ly_ngay > 0) {
                        $minDays = (int)$vatTu->thoi_gian_cach_ly_ngay;
                        $appliedDays = isset($item['thoi_gian_cach_ly']) && $item['thoi_gian_cach_ly'] !== '' ? (int)$item['thoi_gian_cach_ly'] : $minDays;
                        if ($appliedDays < $minDays) {
                            return back()->withInput()->withErrors([
                                "vat_tu.{$index}.thoi_gian_cach_ly" => "Vật tư '{$vatTu->ten_thuong_mai}' quy định thời gian cách ly tối thiểu là {$minDays} ngày. Bạn không thể nhập thời gian cách ly thấp hơn quy định."
                            ]);
                        }
                    }
                }
            }
        }

        $filePath = null;
        if ($request->hasFile('tep_minh_chung')) {
            $file = $request->file('tep_minh_chung');
            $fileName = 'log_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('uploads/nhat-ky', $fileName, 'public');
        }

        DB::beginTransaction();
        try {
            $log = NhatKyCanhTac::create([
                'mua_vu_id' => $season->mua_vu_id,
                'tai_khoan_id' => $user->tai_khoan_id,
                'loai_hoat_dong' => $validated['loai_hoat_dong'],
                'ngay_thuc_hien' => $validated['ngay_thuc_hien'],
                'ghi_chu' => $validated['ghi_chu'] ?? null,
                'tep_minh_chung' => $filePath,
                'trang_thai' => $validated['trang_thai'],
            ]);

            // Save supplies used
            if (!empty($validated['vat_tu']) && is_array($validated['vat_tu'])) {
                foreach ($validated['vat_tu'] as $item) {
                    if (!empty($item['vat_tu_id']) && !empty($item['lieu_luong'])) {
                        ChiTietSuDungVatTu::create([
                            'nhat_ky_id' => $log->nhat_ky_id,
                            'vat_tu_id' => $item['vat_tu_id'],
                            'lieu_luong' => $item['lieu_luong'],
                            'don_vi_tinh' => $item['don_vi_tinh'] ?? 'kg',
                            'thoi_gian_su_dung' => $validated['ngay_thuc_hien'],
                            'thoi_gian_cach_ly_ap_dung_ngay' => isset($item['thoi_gian_cach_ly']) && $item['thoi_gian_cach_ly'] !== '' ? (int)$item['thoi_gian_cach_ly'] : 0,
                            'ghi_chu' => $item['ghi_chu'] ?? null,
                        ]);
                    }
                }
            }

            // Create harvest lot if requested during harvest
            if ($validated['loai_hoat_dong'] === 'THU_HOACH' && $request->boolean('create_lot') && !empty($validated['so_luong_thu_hoach'])) {
                app(SeasonProgressService::class)->recordHarvest($season);
                $harvestQty = (float)$validated['so_luong_thu_hoach'];
                $product = $season->sanPham;
                $lotCode = !empty($validated['ma_lo'])
                    ? trim($validated['ma_lo'])
                    : 'LO-' . ($product->ma_san_pham ?? 'NL') . '-' . date('Ymd') . '-' . strtoupper(Str::random(3));

                $hsdDays = $product->han_su_dung_mac_dinh_ngay ?? 7;
                $harvestDate = Carbon::parse($validated['ngay_thuc_hien'])->toDateString();
                $expiryDate = Carbon::parse($harvestDate)->addDays($hsdDays)->toDateString();

                $harvestLot = LoNongSan::create([
                    'san_pham_id' => $season->san_pham_id,
                    'mua_vu_id' => $season->mua_vu_id,
                    'to_chuc_nguon_id' => $season->vungTrong->to_chuc_id ?? $user->to_chuc_id ?? 1,
                    'loai_lo' => 'NGUYEN_LIEU',
                    'ma_lo' => $lotCode,
                    'so_luong' => $harvestQty,
                    'don_vi_tinh' => $season->don_vi_san_luong ?? 'kg',
                    'ngay_thu_hoach' => $harvestDate,
                    'ngay_san_xuat' => $harvestDate,
                    'han_su_dung' => $expiryDate,
                    'trang_thai' => 'MOI_TAO',
                ]);
                \App\Models\LichSuTrangThaiLo::create([
                    'lo_nong_san_id' => $harvestLot->lo_nong_san_id,
                    'tai_khoan_id' => $user->tai_khoan_id,
                    'trang_thai_cu' => null,
                    'trang_thai_moi' => 'MOI_TAO',
                    'ly_do' => 'Tạo từ nhật ký thu hoạch của mùa vụ '.$season->ma_mua_vu,
                    'thoi_gian' => now(),
                ]);
            }

            if ($validated['loai_hoat_dong'] === 'THU_HOACH' && $validated['trang_thai'] === 'DA_XAC_NHAN') {
                app(SeasonProgressService::class)->recordHarvest($season);
            }

            DB::commit();

            return redirect()->route('producer.farming-logs.show', $log->nhat_ky_id)
                ->with('status', 'Đã ghi nhật ký canh tác thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Có lỗi xảy ra khi lưu nhật ký: ' . $e->getMessage()]);
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $log = NhatKyCanhTac::with([
            'muaVu.vungTrong.toChuc',
            'muaVu.sanPham',
            'nguoiThucHien',
            'chiTietVatTu.vatTu',
            'banGhiGoc.nguoiThucHien',
            'banGhiSuaDoi.nguoiThucHien',
        ])->findOrFail($id);

        ProducerAuthorizationService::authorizeLog($user, $log);

        return view('producer.farming-logs.show', [
            'log' => $log,
        ]);
    }

    public function edit(Request $request, $id)
    {
        $user = $request->user();
        $log = NhatKyCanhTac::with(['muaVu.vungTrong', 'chiTietVatTu.vatTu'])->findOrFail($id);

        ProducerAuthorizationService::authorizeLog($user, $log);

        if (in_array($log->muaVu->trang_thai, ['HOAN_THANH', 'HUY'], true)) {
            return back()->withErrors(['trang_thai' => 'Mùa vụ đã kết thúc nên không thể sửa nhật ký.']);
        }

        $supplies = DanhMucVatTu::where('trang_thai', 'DANG_SU_DUNG')->get();

        return view('producer.farming-logs.edit', [
            'log' => $log,
            'supplies' => $supplies,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $log = NhatKyCanhTac::with('muaVu.vungTrong')->findOrFail($id);

        ProducerAuthorizationService::authorizeLog($user, $log);

        if (in_array($log->muaVu->trang_thai, ['HOAN_THANH', 'HUY'], true)) {
            return redirect()->route('producer.farming-logs.show', $log->nhat_ky_id)
                ->with('warning', 'Mùa vụ đã kết thúc nên không thể sửa nhật ký.');
        }

        $photoRule = empty($log->tep_minh_chung) ? 'required' : 'nullable';
        $validated = $request->validate([
            'loai_hoat_dong' => ['required', 'string', 'max:80'],
            'ngay_thuc_hien' => ['required', 'date'],
            'ghi_chu' => ['nullable', 'string'],
            'tep_minh_chung' => [$photoRule, 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'trang_thai' => ['required', 'in:NHAP,DA_XAC_NHAN'],
            'ly_do_sua' => [$log->trang_thai === 'DA_XAC_NHAN' ? 'required' : 'nullable', 'string', 'max:500'],
            'vat_tu' => ['nullable', 'array'],
            'vat_tu.*.vat_tu_id' => ['required_with:vat_tu.*.lieu_luong', 'exists:danh_muc_vat_tu,vat_tu_id'],
            'vat_tu.*.lieu_luong' => ['nullable', 'numeric', 'min:0'],
            'vat_tu.*.don_vi_tinh' => ['nullable', 'string', 'max:30'],
            'vat_tu.*.thoi_gian_cach_ly' => ['nullable', 'integer', 'min:0'],
            'vat_tu.*.ghi_chu' => ['nullable', 'string', 'max:255'],
        ], [
            'tep_minh_chung.required' => 'Người sản xuất bắt buộc phải tải lên hình ảnh minh chứng hoạt động canh tác tại vườn.',
            'tep_minh_chung.image' => 'Tệp minh chứng bắt buộc phải là định dạng hình ảnh (JPG, PNG, WebP).',
            'ly_do_sua.required' => 'Bản ghi này đã được xác nhận trước đó. Vui lòng ghi rõ lý do sửa đổi để đảm bảo tính minh bạch truy xuất.',
        ]);

        // Kiểm tra an toàn cách ly thuốc nếu cập nhật hoạt động là THU HOẠCH
        if ($validated['loai_hoat_dong'] === 'THU_HOACH') {
            $quarantineInfo = $log->muaVu->getThongTinCachLy($validated['ngay_thuc_hien']);
            if ($quarantineInfo['dang_cach_ly']) {
                $harvestDateStr = Carbon::parse($validated['ngay_thuc_hien'])->format('d/m/Y');
                $safeDateStr = $quarantineInfo['ngay_het_cach_ly']->format('d/m/Y');
                return back()->withInput()->withErrors([
                    'ngay_thuc_hien' => "Không thể cập nhật thu hoạch vào ngày {$harvestDateStr} vì chưa hết thời gian cách ly của thuốc BVTV/vật tư ('{$quarantineInfo['ten_vat_tu']}'). Thời điểm thu hoạch an toàn sớm nhất là sau ngày {$safeDateStr}."
                ]);
            }
        }

        // Kiểm tra thời gian cách ly tối thiểu cho từng loại vật tư sử dụng
        if (!empty($validated['vat_tu']) && is_array($validated['vat_tu'])) {
            foreach ($validated['vat_tu'] as $index => $item) {
                if (!empty($item['vat_tu_id']) && !empty($item['lieu_luong'])) {
                    $vatTu = DanhMucVatTu::find($item['vat_tu_id']);
                    if ($vatTu && $vatTu->thoi_gian_cach_ly_ngay > 0) {
                        $minDays = (int)$vatTu->thoi_gian_cach_ly_ngay;
                        $appliedDays = isset($item['thoi_gian_cach_ly']) && $item['thoi_gian_cach_ly'] !== '' ? (int)$item['thoi_gian_cach_ly'] : $minDays;
                        if ($appliedDays < $minDays) {
                            return back()->withInput()->withErrors([
                                "vat_tu.{$index}.thoi_gian_cach_ly" => "Vật tư '{$vatTu->ten_thuong_mai}' quy định thời gian cách ly tối thiểu là {$minDays} ngày. Bạn không thể nhập thời gian cách ly thấp hơn quy định."
                            ]);
                        }
                    }
                }
            }
        }

        if ($request->hasFile('tep_minh_chung')) {
            $file = $request->file('tep_minh_chung');
            $fileName = 'log_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $validated['tep_minh_chung'] = $file->storeAs('uploads/nhat-ky', $fileName, 'public');
        } else {
            unset($validated['tep_minh_chung']);
        }

        DB::beginTransaction();
        try {
            // Immutability: nếu bản ghi đang là DA_XAC_NHAN, áp dụng versioning
            if ($log->trang_thai === 'DA_XAC_NHAN') {
                $oldLog = $log;
                $oldLog->trang_thai = 'DA_THAY_THE';
                $oldLog->save();

                $newLogData = array_merge($validated, [
                    'mua_vu_id' => $log->mua_vu_id,
                    'tai_khoan_id' => $user->tai_khoan_id,
                    'ban_ghi_goc_id' => $log->ban_ghi_goc_id ?? $log->nhat_ky_id,
                    'tep_minh_chung' => $validated['tep_minh_chung'] ?? $log->tep_minh_chung,
                ]);

                $newLog = NhatKyCanhTac::create($newLogData);
                if ($newLog->loai_hoat_dong === 'THU_HOACH' && $newLog->trang_thai === 'DA_XAC_NHAN') {
                    app(SeasonProgressService::class)->recordHarvest($log->muaVu);
                }

                if (!empty($validated['vat_tu']) && is_array($validated['vat_tu'])) {
                    foreach ($validated['vat_tu'] as $item) {
                        if (!empty($item['vat_tu_id']) && !empty($item['lieu_luong'])) {
                            ChiTietSuDungVatTu::create([
                                'nhat_ky_id' => $newLog->nhat_ky_id,
                                'vat_tu_id' => $item['vat_tu_id'],
                                'lieu_luong' => $item['lieu_luong'],
                                'don_vi_tinh' => $item['don_vi_tinh'] ?? 'kg',
                                'thoi_gian_su_dung' => $validated['ngay_thuc_hien'],
                                'thoi_gian_cach_ly_ap_dung_ngay' => isset($item['thoi_gian_cach_ly']) && $item['thoi_gian_cach_ly'] !== '' ? (int)$item['thoi_gian_cach_ly'] : 0,
                                'ghi_chu' => $item['ghi_chu'] ?? null,
                            ]);
                        }
                    }
                }

                DB::commit();

                return redirect()->route('producer.farming-logs.show', $newLog->nhat_ky_id)
                    ->with('status', 'Đã lưu bản sửa đổi nhật ký minh bạch mới thành công!');
            }

            // Nếu đang là NHÁP thì cập nhật trực tiếp
            $log->update($validated);
            if ($log->loai_hoat_dong === 'THU_HOACH' && $log->trang_thai === 'DA_XAC_NHAN') {
                app(SeasonProgressService::class)->recordHarvest($log->muaVu);
            }

            $log->chiTietVatTu()->delete();
            if (!empty($validated['vat_tu']) && is_array($validated['vat_tu'])) {
                foreach ($validated['vat_tu'] as $item) {
                    if (!empty($item['vat_tu_id']) && !empty($item['lieu_luong'])) {
                        ChiTietSuDungVatTu::create([
                            'nhat_ky_id' => $log->nhat_ky_id,
                            'vat_tu_id' => $item['vat_tu_id'],
                            'lieu_luong' => $item['lieu_luong'],
                            'don_vi_tinh' => $item['don_vi_tinh'] ?? 'kg',
                            'thoi_gian_su_dung' => $validated['ngay_thuc_hien'],
                            'thoi_gian_cach_ly_ap_dung_ngay' => isset($item['thoi_gian_cach_ly']) && $item['thoi_gian_cach_ly'] !== '' ? (int)$item['thoi_gian_cach_ly'] : 0,
                            'ghi_chu' => $item['ghi_chu'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('producer.farming-logs.show', $log->nhat_ky_id)
                ->with('status', 'Đã cập nhật nhật ký canh tác thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}

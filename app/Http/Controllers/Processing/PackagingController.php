<?php

namespace App\Http\Controllers\Processing;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDongGoiSscc;
use App\Models\DonViDongGoiSscc;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use App\Rules\Gs1Sscc;
use App\Services\Gs1Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackagingController extends Controller
{
    public function index(Request $request)
    {
        $query = DonViDongGoiSscc::with(['khoLanh', 'loNongSan.sanPham', 'chiTiet']);

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));
            $query->where('ma_sscc', 'like', "%{$keyword}%");
        }

        if ($request->filled('loai_don_vi')) {
            $query->where('loai_don_vi', $request->input('loai_don_vi'));
        }

        if ($request->filled('kho_lanh_id')) {
            $query->where('kho_lanh_id', $request->input('kho_lanh_id'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        // Stats summary
        $totalSscc = DonViDongGoiSscc::count();
        $cartonCount = DonViDongGoiSscc::where('loai_don_vi', 'THUNG_CARTON')->count();
        $palletCount = DonViDongGoiSscc::where('loai_don_vi', 'PALLET')->count();
        $readyInStorage = DonViDongGoiSscc::whereIn('trang_thai', ['DA_NHAP_KHO', 'TRONG_KHO'])->count();

        $units = $query->orderBy('sscc_id', 'desc')->paginate(15)->withQueryString();
        $warehouses = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->get();

        return view('processing.packaging.index', [
            'units' => $units,
            'warehouses' => $warehouses,
            'filters' => $request->all(),
            'totalSscc' => $totalSscc,
            'cartonCount' => $cartonCount,
            'palletCount' => $palletCount,
            'readyInStorage' => $readyInStorage,
        ]);
    }

    public function create(Request $request)
    {
        // Available finished product lots ready for packaging
        $finishedLots = LoNongSan::with(['sanPham', 'khoLanh'])
            ->withSum('chiTietDongGoi as so_luong_da_dong_goi', 'so_luong')
            ->where('loai_lo', 'THANH_PHAM')
            ->where('so_luong', '>', 0)
            ->whereIn('trang_thai', ['DA_SO_CHE', 'DANG_DONG_GOI'])
            ->orderBy('lo_nong_san_id', 'desc')
            ->get()
            ->each(function (LoNongSan $lot) {
                $lot->so_luong_con_lai_dong_goi = max(
                    0,
                    (float) $lot->so_luong - (float) ($lot->so_luong_da_dong_goi ?? 0)
                );
            })
            ->filter(fn (LoNongSan $lot) => $lot->so_luong_con_lai_dong_goi > 0)
            ->values();

        $warehouses = KhoLanh::where('trang_thai', 'DANG_HOAT_DONG')->get();
        $suggestedSscc = DonViDongGoiSscc::generateSscc();
        $selectedLotId = $request->input('lo_id');

        $gs1Service = app(Gs1Service::class);
        $isDemo = $gs1Service->isDemoMode();
        $companyPrefix = $gs1Service->getCompanyPrefix();

        return view('processing.packaging.create', [
            'finishedLots' => $finishedLots,
            'warehouses' => $warehouses,
            'suggestedSscc' => $suggestedSscc,
            'selectedLotId' => $selectedLotId,
            'isDemo' => $isDemo,
            'companyPrefix' => $companyPrefix,
        ]);
    }

    /**
     * AJAX endpoint to generate a fresh, unique standard GS1 SSCC with Modulo 10 check digit.
     */
    public function suggestSscc(Request $request): JsonResponse
    {
        $extension = $request->filled('extension') ? (int)$request->input('extension') : null;
        $sscc = DonViDongGoiSscc::generateSscc(null, $extension);
        $gs1Service = app(Gs1Service::class);

        return response()->json([
            'success' => true,
            'sscc' => $sscc,
            'hri' => $gs1Service->formatHri($sscc, true),
            'is_demo' => $gs1Service->isDemoMode(),
            'company_prefix' => $gs1Service->getCompanyPrefix(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ma_sscc' => ['required', 'string', new Gs1Sscc(), function ($attribute, $value, $fail) {
                if (is_string($value) && ctype_digit($value) && strlen($value) === 18
                    && substr($value, 1, strlen(app(Gs1Service::class)->getCompanyPrefix())) !== app(Gs1Service::class)->getCompanyPrefix()) {
                    $fail('SSCC phải dùng tiền tố doanh nghiệp đã cấu hình cho hệ thống.');
                }
            }, 'unique:don_vi_dong_goi_sscc,ma_sscc'],
            'loai_don_vi' => ['required', 'string', 'max:40'],
            'kho_lanh_id' => ['nullable', 'exists:kho_lanh,kho_lanh_id'],
            'ngay_dong_goi' => ['required', 'date'],
            'ghi_chu' => ['nullable', 'string', 'max:255'],
            'lo_nong_san_id' => ['required', 'integer', 'exists:lo_nong_san,lo_nong_san_id'],
            'khoi_luong_moi_kien' => ['required', 'numeric', 'min:0.01'],
            'dong_goi_phan_du' => ['nullable', 'boolean'],
        ], [
            'lo_nong_san_id.required' => 'Bạn cần chọn lô thành phẩm để đóng gói.',
            'khoi_luong_moi_kien.required' => 'Vui lòng nhập khối lượng tiêu chuẩn của mỗi kiện.',
            'khoi_luong_moi_kien.min' => 'Khối lượng mỗi kiện phải lớn hơn 0.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $lot = LoNongSan::lockForUpdate()->findOrFail($validated['lo_nong_san_id']);
            $packed = (float) $lot->chiTietDongGoi()->sum('so_luong');
            $remaining = max(0, (float) $lot->so_luong - $packed);
            $perPackage = (float) $validated['khoi_luong_moi_kien'];

            if ($lot->loai_lo !== 'THANH_PHAM'
                || !in_array($lot->trang_thai, ['DA_SO_CHE', 'DANG_DONG_GOI'], true)
                || $remaining <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lo_nong_san_id' => 'Lô không còn số lượng phù hợp để đóng gói.',
                ]);
            }

            $fullPackageCount = (int) floor(($remaining + 0.0000001) / $perPackage);
            $remainder = round($remaining - ($fullPackageCount * $perPackage), 3);
            $packageQuantities = array_fill(0, $fullPackageCount, $perPackage);

            if ($request->boolean('dong_goi_phan_du') && $remainder > 0.0001) {
                $packageQuantities[] = $remainder;
            }

            if (empty($packageQuantities)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'khoi_luong_moi_kien' => 'Khối lượng mỗi kiện lớn hơn lượng còn lại. Hãy giảm khối lượng hoặc chọn đóng phần dư.',
                ]);
            }

            if (count($packageQuantities) > 500) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'khoi_luong_moi_kien' => 'Một lần chỉ được tạo tối đa 500 kiện. Hãy tăng khối lượng mỗi kiện.',
                ]);
            }

            $createdUnits = collect();
            foreach ($packageQuantities as $index => $quantity) {
                $sscc = $index === 0 ? $validated['ma_sscc'] : DonViDongGoiSscc::generateSscc();
                $ssccUnit = DonViDongGoiSscc::create([
                    'ma_sscc' => $sscc,
                    'loai_don_vi' => $validated['loai_don_vi'],
                    'kho_lanh_id' => $validated['kho_lanh_id'] ?? null,
                    'ngay_dong_goi' => $validated['ngay_dong_goi'],
                    'trang_thai' => 'DA_DONG_GOI',
                    'ghi_chu' => $validated['ghi_chu'] ?? null,
                ]);

                ChiTietDongGoiSscc::create([
                    'sscc_id' => $ssccUnit->sscc_id,
                    'lo_nong_san_id' => $lot->lo_nong_san_id,
                    'so_luong' => $quantity,
                    'don_vi_tinh' => $lot->don_vi_tinh,
                    'ghi_chu' => $index === count($packageQuantities) - 1 && $quantity < $perPackage
                        ? 'Kiện cuối chứa phần khối lượng còn lại'
                        : null,
                ]);
                $createdUnits->push($ssccUnit);
            }

            $packedNow = array_sum($packageQuantities);
            $leftAfterPacking = max(0, $remaining - $packedNow);
            $nextLotStatus = $leftAfterPacking <= 0.0001
                ? 'CHO_KIEM_DINH_XUAT_XUONG'
                : 'DANG_DONG_GOI';

            app(\App\Services\LotStatusService::class)->transition(
                $lot,
                $nextLotStatus,
                $request->user()?->tai_khoan_id,
                $nextLotStatus === 'CHO_KIEM_DINH_XUAT_XUONG'
                    ? 'Đã đóng gói hết lô '.$lot->ma_lo.'; chuyển chờ kiểm định xuất xưởng'
                    : 'Đã tạo '.$createdUnits->count().' kiện SSCC; lô còn khối lượng chưa đóng gói',
                !empty($validated['kho_lanh_id']) ? ['kho_lanh_id' => $validated['kho_lanh_id']] : []
            );

            $message = 'Đã tạo '.$createdUnits->count().' kiện SSCC, đóng gói '
                .number_format($packedNow, 2).' '.$lot->don_vi_tinh.'.';
            if ($leftAfterPacking > 0.0001) {
                $message .= ' Còn lại '.number_format($leftAfterPacking, 2).' '.$lot->don_vi_tinh.' chưa đóng gói.';
            } else {
                $message .= ' Lô đã được chuyển sang chờ kiểm định xuất xưởng.';
            }

            return redirect()->route('processing.packaging.print-labels', [
                'ids' => $createdUnits->pluck('sscc_id')->all(),
            ])->with('status', $message.' Các tem đã sẵn sàng để in.');
        });
    }

    public function printLabels(Request $request)
    {
        if ($request->boolean('all_filtered')) {
            $allQuery = DonViDongGoiSscc::query();
            if ($request->filled('q')) {
                $keyword = trim($request->query('q'));
                $allQuery->where('ma_sscc', 'like', "%{$keyword}%");
            }
            if ($request->filled('loai_don_vi')) {
                $allQuery->where('loai_don_vi', $request->query('loai_don_vi'));
            }
            if ($request->filled('kho_lanh_id')) {
                $allQuery->where('kho_lanh_id', $request->query('kho_lanh_id'));
            }
            if ($request->filled('trang_thai')) {
                $allQuery->where('trang_thai', $request->query('trang_thai'));
            }

            $ids = $allQuery->orderByDesc('sscc_id')->limit(501)->pluck('sscc_id');
            if ($ids->count() > 500) {
                return redirect()->route('processing.packaging.index', $request->only(['q', 'loai_don_vi', 'kho_lanh_id', 'trang_thai']))
                    ->withErrors(['labels' => 'Mỗi lần được in tối đa 500 tem. Vui lòng dùng bộ lọc để chia nhỏ danh sách.']);
            }
        } else {
            $rawIds = $request->query('ids', []);
            if (is_string($rawIds)) {
                $rawIds = explode(',', $rawIds);
            }

            $ids = collect($rawIds)
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->take(500)
                ->values();
        }

        if ($ids->isEmpty()) {
            return redirect()->route('processing.packaging.index')
                ->withErrors(['labels' => 'Vui lòng chọn ít nhất một kiện để in tem.']);
        }

        $byId = DonViDongGoiSscc::with([
            'khoLanh',
            'chiTiet.loNongSan.sanPham',
        ])->whereIn('sscc_id', $ids)->get()->keyBy('sscc_id');

        $units = $ids->map(fn ($id) => $byId->get($id))->filter()->values();
        if ($units->isEmpty()) {
            abort(404, 'Không tìm thấy kiện SSCC để in tem.');
        }

        return view('processing.packaging.print-labels', compact('units'));
    }

    public function show($id)
    {
        $unit = DonViDongGoiSscc::with([
            'khoLanh',
            'chiTiet.loNongSan.sanPham',
            'chiTiet.loNongSan.nguyenLieuDauVao.toChucNguon'
        ])->findOrFail($id);

        return view('processing.packaging.show', [
            'unit' => $unit,
        ]);
    }
}


<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\ChuyenVanChuyen;
use App\Models\PhuongTien;
use App\Models\TaiKhoanHeThong;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChainTransportController extends Controller
{
    public function index(Request $request): View
    {
        $query = ChuyenVanChuyen::with([
            'phuongTien.thietBiIot.latestReading',
            'taiXe',
            'nguoiLap',
            'phieuGiaoHang.toChucNhan',
            'phieuGiaoHang.chiTietGiaoNhan',
            'suKienHanhTrinh',
        ]);

        $status = $request->query('status', 'all');
        $search = trim($request->query('q', ''));

        if ($status === 'running') {
            $query->where('trang_thai', 'DANG_VAN_CHUYEN');
        } elseif ($status === 'ready') {
            $query->whereIn('trang_thai', ['SAN_SANG', 'CHUAN_BI']);
        } elseif ($status === 'completed') {
            $query->where('trang_thai', 'HOAN_THANH');
        } elseif ($status === 'delayed') {
            $query->where('trang_thai', '!=', 'HOAN_THANH')
                  ->whereNotNull('thoi_gian_giao_du_kien')
                  ->where('thoi_gian_giao_du_kien', '<', now());
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ma_chuyen', 'like', "%{$search}%")
                  ->orWhere('diem_di', 'like', "%{$search}%")
                  ->orWhere('diem_den', 'like', "%{$search}%")
                  ->orWhereHas('phuongTien', fn($p) => $p->where('bien_so', 'like', "%{$search}%"))
                  ->orWhereHas('taiXe', fn($tx) => $tx->where('ho_ten', 'like', "%{$search}%"));
            });
        }

        $trips = $query->orderByRaw("
            CASE 
                WHEN trang_thai = 'DANG_VAN_CHUYEN' THEN 1
                WHEN trang_thai IN ('SAN_SANG', 'CHUAN_BI') THEN 2
                ELSE 3 
            END
        ")->orderByDesc('chuyen_id')->paginate(12)->withQueryString();

        // Calculate progress & schedule status for each trip
        $trips->getCollection()->transform(function ($trip) {
            $totalPgh = $trip->phieuGiaoHang->count();
            $deliveredPgh = $trip->phieuGiaoHang->where('trang_thai', 'DA_GIAO')->count();
            $progressPct = $totalPgh > 0 ? round(($deliveredPgh / $totalPgh) * 100) : 0;

            $isDelayed = $trip->trang_thai !== 'HOAN_THANH'
                && $trip->thoi_gian_giao_du_kien
                && $trip->thoi_gian_giao_du_kien < now();

            $trip->total_pgh = $totalPgh;
            $trip->delivered_pgh = $deliveredPgh;
            $trip->progress_pct = $progressPct;
            $trip->is_delayed = $isDelayed;

            // Vehicle IoT reading
            $vehicleIot = $trip->phuongTien?->thietBiIot?->first();
            $trip->latest_reading = $vehicleIot?->latestReading;

            return $trip;
        });

        // Fleet statistics
        $totalTrips = ChuyenVanChuyen::count();
        $runningCount = ChuyenVanChuyen::where('trang_thai', 'DANG_VAN_CHUYEN')->count();
        $readyCount = ChuyenVanChuyen::whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG'])->count();
        $completedCount = ChuyenVanChuyen::where('trang_thai', 'HOAN_THANH')->count();
        $delayedCount = ChuyenVanChuyen::where('trang_thai', '!=', 'HOAN_THANH')
            ->whereNotNull('thoi_gian_giao_du_kien')
            ->where('thoi_gian_giao_du_kien', '<', now())
            ->count();

        $activeVehicles = PhuongTien::where('trang_thai', 'DANG_HOAT_DONG')->count();
        $totalVehicles = PhuongTien::count();

        return view('chain.transport.index', [
            'trips' => $trips,
            'status' => $status,
            'search' => $search,
            'totalTrips' => $totalTrips,
            'runningCount' => $runningCount,
            'readyCount' => $readyCount,
            'completedCount' => $completedCount,
            'delayedCount' => $delayedCount,
            'activeVehicles' => $activeVehicles,
            'totalVehicles' => $totalVehicles,
        ]);
    }

    public function create(): View
    {
        $busyDriverIds = ChuyenVanChuyen::whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG', 'DANG_VAN_CHUYEN'])
            ->pluck('tai_xe_id');
        $busyVehicleIds = ChuyenVanChuyen::whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG', 'DANG_VAN_CHUYEN'])
            ->pluck('phuong_tien_id');

        $drivers = TaiKhoanHeThong::with(['vaiTro', 'toChuc'])
            ->where('trang_thai', 'DANG_HOAT_DONG')
            ->whereHas('vaiTro', fn ($role) => $role->where('ma_vai_tro', 'TAI_XE'))
            ->whereNotIn('tai_khoan_id', $busyDriverIds)
            ->orderBy('ho_ten')
            ->get();

        $vehicles = PhuongTien::with(['thietBiIot' => fn ($sensor) => $sensor->where('trang_thai', 'HOAT_DONG')])
            ->where('trang_thai', 'SAN_SANG')
            ->where('co_he_thong_lam_lanh', true)
            ->whereHas('thietBiIot', fn ($sensor) => $sensor->where('trang_thai', 'HOAT_DONG'))
            ->whereNotIn('phuong_tien_id', $busyVehicleIds)
            ->orderBy('bien_so')
            ->get();

        return view('chain.transport.create', compact('drivers', 'vehicles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tai_xe_id' => ['required', 'integer', 'exists:tai_khoan_he_thong,tai_khoan_id'],
            'phuong_tien_id' => ['required', 'integer', 'exists:phuong_tien,phuong_tien_id'],
            'diem_di' => ['required', 'string', 'max:255', 'different:diem_den'],
            'diem_den' => ['required', 'string', 'max:255', 'different:diem_di'],
            'thoi_gian_xuat_phat' => ['required', 'date', 'after_or_equal:now'],
            'thoi_gian_giao_du_kien' => ['required', 'date', 'after:thoi_gian_xuat_phat'],
            'ghi_chu' => ['nullable', 'string', 'max:1000'],
        ], [
            'tai_xe_id.required' => 'Vui lòng chọn tài xế phụ trách.',
            'phuong_tien_id.required' => 'Vui lòng chọn xe lạnh.',
            'diem_di.required' => 'Vui lòng nhập điểm xuất phát.',
            'diem_den.required' => 'Vui lòng nhập điểm giao hàng.',
            'diem_di.different' => 'Điểm đi và điểm đến phải khác nhau.',
            'thoi_gian_xuat_phat.after_or_equal' => 'Thời gian dự kiến xuất phát không được ở quá khứ.',
            'thoi_gian_giao_du_kien.after' => 'Thời gian dự kiến giao phải sau thời gian xuất phát.',
        ]);

        $managerId = $request->user()->tai_khoan_id;
        $trip = DB::transaction(function () use ($validated, $managerId) {
            $driver = TaiKhoanHeThong::with('vaiTro')->lockForUpdate()->findOrFail($validated['tai_xe_id']);
            if ($driver->trang_thai !== 'DANG_HOAT_DONG' || $driver->vaiTro?->ma_vai_tro !== 'TAI_XE') {
                throw ValidationException::withMessages(['tai_xe_id' => 'Tài khoản được chọn không phải tài xế đang hoạt động.']);
            }
            if (ChuyenVanChuyen::where('tai_xe_id', $driver->tai_khoan_id)
                ->whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG', 'DANG_VAN_CHUYEN'])->exists()) {
                throw ValidationException::withMessages(['tai_xe_id' => 'Tài xế đang có một chuyến chưa hoàn thành.']);
            }

            $vehicle = PhuongTien::with('thietBiIot')->lockForUpdate()->findOrFail($validated['phuong_tien_id']);
            if ($vehicle->trang_thai !== 'SAN_SANG' || !$vehicle->co_he_thong_lam_lanh) {
                throw ValidationException::withMessages(['phuong_tien_id' => 'Phương tiện phải là xe lạnh đang sẵn sàng.']);
            }
            if (!$vehicle->thietBiIot->contains(fn ($sensor) => $sensor->trang_thai === 'HOAT_DONG')) {
                throw ValidationException::withMessages(['phuong_tien_id' => 'Xe chưa có thiết bị IoT hoạt động.']);
            }
            if (ChuyenVanChuyen::where('phuong_tien_id', $vehicle->phuong_tien_id)
                ->whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG', 'DANG_VAN_CHUYEN'])->exists()) {
                throw ValidationException::withMessages(['phuong_tien_id' => 'Phương tiện đang được phân công cho chuyến chưa hoàn thành.']);
            }

            return ChuyenVanChuyen::create([
                'ma_chuyen' => $this->nextTripCode(),
                'phuong_tien_id' => $vehicle->phuong_tien_id,
                'tai_xe_id' => $driver->tai_khoan_id,
                'nguoi_lap_id' => $managerId,
                'diem_di' => trim($validated['diem_di']),
                'diem_den' => trim($validated['diem_den']),
                'thoi_gian_xuat_phat' => $validated['thoi_gian_xuat_phat'],
                'thoi_gian_giao_du_kien' => $validated['thoi_gian_giao_du_kien'],
                'trang_thai' => 'CHUAN_BI',
                'ghi_chu' => $validated['ghi_chu'] ?? null,
            ]);
        });

        return redirect()->route('chain.transport.index', ['status' => 'ready'])
            ->with('status', "Đã lập chuyến {$trip->ma_chuyen} và phân công tài xế thành công. Kho lạnh có thể chọn chuyến này khi xuất hàng.");
    }

    private function nextTripCode(): string
    {
        $prefix = 'CH-'.now()->format('Ymd').'-';
        $lastCode = ChuyenVanChuyen::where('ma_chuyen', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('ma_chuyen')
            ->value('ma_chuyen');
        $sequence = $lastCode ? ((int) substr($lastCode, -3)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}


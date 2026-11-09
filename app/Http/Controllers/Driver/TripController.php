<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ChuyenVanChuyen;
use App\Models\CanhBaoChuoiLanh;
use App\Models\DonViDongGoiSscc;
use App\Models\PhuongTien;
use App\Models\SuKienHanhTrinh;
use App\Models\ThietBiIot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    /**
     * Display listing of transport trips for the driver.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status', 'all');
        $search = trim($request->query('q', ''));

        // Query trips: Strictly scoped to driver
        $query = ChuyenVanChuyen::with(['phuongTien', 'phieuGiaoHang.chiTietGiaoNhan', 'suKienHanhTrinh'])
            ->where('tai_xe_id', $user->tai_khoan_id);

        if ($status === 'running') {
            $query->where('trang_thai', 'DANG_VAN_CHUYEN');
        } elseif ($status === 'ready') {
            $query->whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG']);
        } elseif ($status === 'completed') {
            $query->where('trang_thai', 'HOAN_THANH');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ma_chuyen', 'like', "%{$search}%")
                  ->orWhere('diem_di', 'like', "%{$search}%")
                  ->orWhere('diem_den', 'like', "%{$search}%")
                  ->orWhereHas('phuongTien', fn($p) => $p->where('bien_so', 'like', "%{$search}%"));
            });
        }

        $trips = $query->orderByRaw("
            CASE 
                WHEN trang_thai = 'DANG_VAN_CHUYEN' THEN 1
                WHEN trang_thai IN ('SAN_SANG', 'CHUAN_BI') THEN 2
                ELSE 3 
            END
        ")->orderByDesc('chuyen_id')->paginate(10)->withQueryString();

        // Statistics strictly scoped to this driver
        $baseQuery = ChuyenVanChuyen::where('tai_xe_id', $user->tai_khoan_id);
        $totalCount = (clone $baseQuery)->count();
        $runningCount = (clone $baseQuery)->where('trang_thai', 'DANG_VAN_CHUYEN')->count();
        $readyCount = (clone $baseQuery)->whereIn('trang_thai', ['CHUAN_BI', 'SAN_SANG'])->count();
        $completedCount = (clone $baseQuery)->where('trang_thai', 'HOAN_THANH')->count();

        return view('driver.trips.index', [
            'trips' => $trips,
            'status' => $status,
            'search' => $search,
            'totalCount' => $totalCount,
            'runningCount' => $runningCount,
            'readyCount' => $readyCount,
            'completedCount' => $completedCount,
            'hasMyTrips' => $totalCount > 0,
        ]);
    }

    /**
     * Show detail of a trip.
     */
    public function show(int $id, Request $request): View
    {
        $user = $request->user();
        $trip = ChuyenVanChuyen::with([
            'phuongTien.thietBiIot.latestReading',
            'taiXe',
            'phieuGiaoHang.toChucNhan',
            'phieuGiaoHang.chiTietGiaoNhan.donViDongGoi.chiTiet.loNongSan.sanPham',
            'suKienHanhTrinh.nguoiGhiNhan',
        ])->findOrFail($id);

        if ((int)$trip->tai_xe_id !== (int)$user->tai_khoan_id) {
            abort(403, 'Bạn không được phân công chuyến vận chuyển này.');
        }

        // Fetch vehicle IoT device & latest temperature reading
        $vehicleIot = null;
        if ($trip->phuongTien) {
            $vehicleIot = ThietBiIot::with('latestReading')
                ->where('phuong_tien_id', $trip->phuong_tien_id)
                ->first();
        }

        // Calculate delivery progression
        $allDeliveries = $trip->phieuGiaoHang;
        $totalDeliveries = $allDeliveries->count();
        $completedDeliveries = $allDeliveries->whereIn('trang_thai', ['DA_GIAO', 'GIAO_MOT_PHAN', 'TRA_VE'])->count();

        // Total SSCC items
        $totalItems = 0;
        $completedItems = 0;
        foreach ($allDeliveries as $del) {
            foreach ($del->chiTietGiaoNhan as $ct) {
                $totalItems++;
                if (in_array($ct->trang_thai_giao_nhan, ['DA_GIAO', 'GIAO_MOT_PHAN', 'TRA_VE'])) {
                    $completedItems++;
                }
            }
        }

        return view('driver.trips.show', [
            'trip' => $trip,
            'vehicleIot' => $vehicleIot,
            'totalDeliveries' => $totalDeliveries,
            'completedDeliveries' => $completedDeliveries,
            'totalItems' => $totalItems,
            'completedItems' => $completedItems,
        ]);
    }

    /**
     * Start the trip (Departure).
     */
    public function startTrip(int $id, Request $request): RedirectResponse
    {
        $user = $request->user();
        $trip = ChuyenVanChuyen::with([
            'phuongTien.thietBiIot.latestReading',
            'phieuGiaoHang.chiTietGiaoNhan.donViDongGoi.chiTiet.loNongSan',
        ])->findOrFail($id);

        if ((int)$trip->tai_xe_id !== (int)$user->tai_khoan_id) {
            abort(403, 'Bạn không được phân công chuyến vận chuyển này.');
        }

        if (!in_array($trip->trang_thai, ['CHUAN_BI', 'SAN_SANG', 'DA_LAP_KE_HOACH', 'DANG_VAN_CHUYEN', 'HOAN_THANH'])) {
            return back()->withErrors(['trip' => 'Chuyến không thể khởi hành ở trạng thái này.']);
        }
        if ($trip->trang_thai === 'DANG_VAN_CHUYEN') {
            return back()->with('warning', 'Chuyến xe này đã bắt đầu xuất phát trước đó.');
        }

        if ($trip->trang_thai === 'HOAN_THANH') {
            return back()->with('warning', 'Chuyến xe này đã hoàn thành, không thể khởi hành lại.');
        }

        $deliveries = $trip->phieuGiaoHang->whereIn('trang_thai', ['NHAP', 'CHO_GIAO']);
        $lines = $deliveries->flatMap->chiTietGiaoNhan;
        if ($deliveries->isEmpty() || $lines->isEmpty()) {
            return back()->withErrors(['trip' => 'Chuyến chưa có phiếu giao và kiện hàng hợp lệ để khởi hành.']);
        }

        $sensor = $trip->phuongTien?->thietBiIot?->first();
        if (!$sensor || $sensor->trang_thai !== 'HOAT_DONG' || !$sensor->latestReading) {
            return back()->withErrors(['trip' => 'Xe chưa có cảm biến IoT hoạt động hoặc chưa có số đo nhiệt độ/độ ẩm.']);
        }
        if ($sensor->latestReading->thoi_gian_do?->lt(now()->subMinutes(60))) {
            return back()->withErrors(['trip' => 'Số đo IoT của xe đã quá 60 phút. Cần đồng bộ cảm biến trước khi khởi hành.']);
        }
        if (CanhBaoChuoiLanh::where('thiet_bi_id', $sensor->thiet_bi_id)
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])->exists()) {
            return back()->withErrors(['trip' => 'Xe đang có cảnh báo chuỗi lạnh chưa được nhân viên kho đánh giá; chưa thể khởi hành.']);
        }

        foreach ($lines as $line) {
            $unit = $line->donViDongGoi;
            if (!$unit || !app(\App\Services\Gs1Service::class)->validateSscc($unit->ma_sscc)['valid']) {
                return back()->withErrors(['trip' => 'Có kiện thiếu hoặc mã SSCC không hợp lệ. Cần đối chiếu và quét lại.']);
            }
            if ($unit->trang_thai !== 'DANG_VAN_CHUYEN') {
                return back()->withErrors(['trip' => "Kiện {$unit->ma_sscc} chưa được kho xuất hợp lệ hoặc đang bị cách ly."]);
            }
            foreach ($unit->chiTiet as $content) {
                if (!app(\App\Services\QualityGateService::class)->passedOutboundInspection($content->loNongSan)) {
                    return back()->withErrors(['trip' => "Kiện {$unit->ma_sscc} chứa lô chưa đạt kiểm định xuất xưởng."]);
                }
            }
        }

        DB::beginTransaction();
        try {
            $now = now();
            $trip->update([
                'trang_thai' => 'DANG_VAN_CHUYEN',
                'thoi_gian_xuat_phat' => $now,
            ]);

            // Update Vehicle status to DANG_HOAT_DONG
            if ($trip->phuongTien) {
                $trip->phuongTien->update(['trang_thai' => 'DANG_HOAT_DONG']);
            }

            // Create journey departure event
            SuKienHanhTrinh::create([
                'chuyen_id' => $trip->chuyen_id,
                'nguoi_ghi_nhan_id' => $user->tai_khoan_id,
                'loai_su_kien' => 'XUAT_PHAT',
                'thoi_gian' => $now,
                'dia_diem_mo_ta' => $trip->diem_di ?: 'Kho xuất phát',
                'mo_ta' => 'Tài xế bấm xuất phát, bắt đầu vận chuyển nông sản theo chuỗi cung ứng lạnh.',
            ]);

            // Update attached SSCC packages to DANG_VAN_CHUYEN
            $ssccIds = [];
            foreach ($trip->phieuGiaoHang as $pgh) {
                if (!in_array($pgh->trang_thai, ['NHAP', 'CHO_GIAO'])) continue;
                $pgh->update(['trang_thai' => 'DANG_GIAO']);
                foreach ($pgh->chiTietGiaoNhan as $item) {
                    $item->update(['trang_thai_giao_nhan' => 'DANG_GIAO']);
                    if ($item->sscc_id) {
                        $ssccIds[] = $item->sscc_id;
                    }
                }
            }

            if (!empty($ssccIds)) {
                DonViDongGoiSscc::whereIn('sscc_id', array_unique($ssccIds))
                    ->update(['trang_thai' => 'DANG_VAN_CHUYEN']);
            }

            DB::commit();

            return redirect()->route('driver.trips.show', $trip->chuyen_id)
                ->with('status', "Đã ghi nhận bắt đầu xuất phát chuyến xe [{$trip->ma_chuyen}]. Hệ thống đã tự động ghi nhận sự kiện xuất phát và cập nhật trạng thái kiện hàng.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Không thể bắt đầu chuyến: ' . $e->getMessage()]);
        }
    }

    /**
     * Finish/Complete the trip.
     */
    public function completeTrip(int $id, Request $request): RedirectResponse
    {
        $user = $request->user();
        $trip = ChuyenVanChuyen::with(['phieuGiaoHang.chiTietGiaoNhan', 'phuongTien'])->findOrFail($id);

        if ((int)$trip->tai_xe_id !== (int)$user->tai_khoan_id) {
            abort(403, 'Bạn không được phân công chuyến vận chuyển này.');
        }

        if ($trip->trang_thai !== 'DANG_VAN_CHUYEN' || $trip->phieuGiaoHang->isEmpty() || $trip->phieuGiaoHang->contains(fn ($p) => !in_array($p->trang_thai, ['DA_GIAO', 'GIAO_MOT_PHAN', 'TRA_VE', 'HUY']))) {
            return back()->withErrors(['trip' => 'Chỉ hoàn tất chuyến đang vận chuyển khi mọi phiếu đã ghi nhận kết quả giao nhận.']);
        }
        if (CanhBaoChuoiLanh::where('chuyen_id', $trip->chuyen_id)
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])->exists()) {
            return back()->withErrors(['trip' => 'Chuyến còn cảnh báo chuỗi lạnh chưa được đánh giá; chưa thể hoàn tất.']);
        }

        DB::beginTransaction();
        try {
            $now = now();
            $trip->update([
                'trang_thai' => 'HOAN_THANH',
                'thoi_gian_giao_thuc_te' => $now,
            ]);

            // Return vehicle status to SAN_SANG
            if ($trip->phuongTien) {
                $trip->phuongTien->update(['trang_thai' => 'SAN_SANG']);
            }

            // Create journey complete event
            SuKienHanhTrinh::create([
                'chuyen_id' => $trip->chuyen_id,
                'nguoi_ghi_nhan_id' => $user->tai_khoan_id,
                'loai_su_kien' => 'HOAN_THANH',
                'thoi_gian' => $now,
                'dia_diem_mo_ta' => $trip->diem_den ?: 'Điểm giao kết thúc',
                'mo_ta' => 'Tài xế xác nhận hoàn tất toàn bộ hành trình vận chuyển và bàn giao.',
            ]);

            DB::commit();

            return redirect()->route('driver.trips.show', $trip->chuyen_id)
                ->with('status', "Đã xác nhận hoàn thành chuyến xe [{$trip->ma_chuyen}]. Cảm ơn bạn đã hoàn tất hành trình chuỗi lạnh an toàn!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Không thể hoàn tất chuyến: ' . $e->getMessage()]);
        }
    }
}


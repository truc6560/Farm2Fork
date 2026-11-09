<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ChuyenVanChuyen;
use App\Models\CanhBaoChuoiLanh;
use App\Models\SuKienHanhTrinh;
use App\Models\ThietBiIot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JourneyController extends Controller
{
    /**
     * Display journey timeline and event logging view for the driver.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tripId = $request->query('chuyen_id');

        // Find available trips for driver - strictly scoped
        $allTrips = ChuyenVanChuyen::with('phuongTien')
            ->where('tai_xe_id', $user->tai_khoan_id)
            ->orderByRaw("
                CASE 
                    WHEN trang_thai = 'DANG_VAN_CHUYEN' THEN 1 
                    WHEN trang_thai IN ('SAN_SANG', 'CHUAN_BI') THEN 2 
                    ELSE 3 
                END
            ")->orderByDesc('chuyen_id')->get();

        // Selected trip
        $selectedTrip = null;
        if ($tripId) {
            $selectedTrip = $allTrips->firstWhere('chuyen_id', (int)$tripId);
            if (!$selectedTrip) {
                // If tripId is specified but doesn't belong to this driver, abort 403
                $checkTrip = ChuyenVanChuyen::find($tripId);
                if ($checkTrip && (int)$checkTrip->tai_xe_id !== (int)$user->tai_khoan_id) {
                    abort(403, 'Bạn không được phân công chuyến vận chuyển này.');
                }
            }
        } else {
            // Default to running trip, or latest trip
            $selectedTrip = $allTrips->firstWhere('trang_thai', 'DANG_VAN_CHUYEN') ?? $allTrips->first();
        }

        // Fetch events for selected trip
        $events = collect();
        $vehicleIot = null;
        $tripAlerts = collect();

        if ($selectedTrip) {
            $events = SuKienHanhTrinh::with('nguoiGhiNhan')
                ->where('chuyen_id', $selectedTrip->chuyen_id)
                ->orderBy('thoi_gian', 'desc')
                ->get();

            if ($selectedTrip->phuong_tien_id) {
                $vehicleIot = ThietBiIot::with('latestReading')
                    ->where('phuong_tien_id', $selectedTrip->phuong_tien_id)
                    ->first();
            }
            $tripAlerts = CanhBaoChuoiLanh::with(['thietBi', 'nguoiXuLy'])
                ->where('chuyen_id', $selectedTrip->chuyen_id)
                ->orderByDesc('thoi_gian_phat_hien')
                ->get();
        }

        return view('driver.journey.index', [
            'trips' => $allTrips,
            'selectedTrip' => $selectedTrip,
            'events' => $events,
            'vehicleIot' => $vehicleIot,
            'eventTypes' => SuKienHanhTrinh::LOAI_SU_KIEN_LABELS,
            'tripAlerts' => $tripAlerts,
        ]);
    }

    /**
     * Record a new journey milestone/event.
     */
    public function storeEvent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chuyen_id' => ['required', 'exists:chuyen_van_chuyen,chuyen_id'],
            'loai_su_kien' => ['required', 'string', 'in:' . implode(',', array_keys(SuKienHanhTrinh::LOAI_SU_KIEN_LABELS))],
            'dia_diem_mo_ta' => ['required', 'string', 'max:255'],
            'thoi_gian' => ['nullable', 'date'],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
        ], [
            'loai_su_kien.required' => 'Vui lòng chọn loại sự kiện hành trình.',
            'dia_diem_mo_ta.required' => 'Vui lòng nhập địa điểm diễn ra sự kiện.',
        ]);

        $user = $request->user();
        $trip = ChuyenVanChuyen::findOrFail($validated['chuyen_id']);

        if ((int)$trip->tai_xe_id !== (int)$user->tai_khoan_id) {
            abort(403, 'Bạn không được phân công chuyến vận chuyển này.');
        }
        if ($trip->trang_thai !== 'DANG_VAN_CHUYEN') {
            return back()->withErrors(['journey' => 'Chỉ được ghi sự kiện khi chuyến đang vận chuyển.']);
        }

        $openAlerts = CanhBaoChuoiLanh::where('chuyen_id', $trip->chuyen_id)
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY']);
        if ($validated['loai_su_kien'] === 'TIEP_TUC_HANH_TRINH' && $openAlerts->exists()) {
            return back()->withErrors(['journey' => 'Chưa thể tiếp tục hành trình khi cảnh báo chuỗi lạnh chưa được nhân viên kho đánh giá.']);
        }
        if ($validated['loai_su_kien'] === 'KHAC_PHUC') {
            $description = trim($validated['mo_ta'] ?? 'Tài xế đã kiểm tra xe lạnh và thực hiện khắc phục.');
            $openAlerts->update([
                'trang_thai' => 'DANG_XU_LY',
                'hanh_dong_khac_phuc' => $description,
            ]);
        }

        $eventTime = !empty($validated['thoi_gian']) ? \Carbon\Carbon::parse($validated['thoi_gian']) : now();

        $event = SuKienHanhTrinh::create([
            'chuyen_id' => $trip->chuyen_id,
            'nguoi_ghi_nhan_id' => $user->tai_khoan_id,
            'loai_su_kien' => $validated['loai_su_kien'],
            'thoi_gian' => $eventTime,
            'dia_diem_mo_ta' => $validated['dia_diem_mo_ta'],
            'mo_ta' => $validated['mo_ta'] ?? null,
        ]);

        $eventLabel = SuKienHanhTrinh::LOAI_SU_KIEN_LABELS[$event->loai_su_kien] ?? $event->loai_su_kien;

        return redirect()->route('driver.journey.index', ['chuyen_id' => $trip->chuyen_id])
            ->with('status', "Đã ghi nhận mốc hành trình [{$eventLabel}] tại {$event->dia_diem_mo_ta} thành công.");
    }
}


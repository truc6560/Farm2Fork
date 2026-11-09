<?php

namespace App\Http\Controllers;

use App\Models\LoNongSan;
use App\Models\DonViDongGoiSscc;
use App\Models\NhatKyCanhTac;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TraceabilityController extends Controller
{
    /**
     * Public consumer traceability page by Lot Code or ID.
     */
    public function show(Request $request, string $code, ?int $resolvedLotId = null)
    {
        $code = trim(urldecode($code));

        $query = LoNongSan::with([
            'sanPham',
            'khoLanh',
            'muaVu.vungTrong.toChuc',
            'toChucNguon',
            'nguyenLieuDauVao.sanPham',
            'nguyenLieuDauVao.muaVu.vungTrong.toChuc',
            'nguyenLieuDauVao.toChucNguon',
            'donViSscc.khoLanh',
        ]);
        if ($resolvedLotId !== null) {
            $query->whereKey($resolvedLotId);
        } else {
            $query->where('ma_lo', $code)->orWhere('lo_nong_san_id', $code);
        }
        $lot = $query->firstOrFail();

        // Traceability context
        $product = $lot->sanPham;
        $isFinished = ($lot->loai_lo === 'THANH_PHAM');

        // Resolve Farm / Origin Organization & Season
        $farm = null;
        $zone = null;
        $season = null;

        if ($isFinished && $lot->nguyenLieuDauVao->isNotEmpty()) {
            $primaryRaw = $lot->nguyenLieuDauVao->first();
            $farm = $primaryRaw->toChucNguon ?? $primaryRaw->muaVu?->vungTrong?->toChuc;
            $zone = $primaryRaw->muaVu?->vungTrong;
            $season = $primaryRaw->muaVu;
        } else {
            $farm = $lot->toChucNguon ?? $lot->muaVu?->vungTrong?->toChuc;
            $zone = $lot->muaVu?->vungTrong;
            $season = $lot->muaVu;
        }

        // 1. Retrieve Farming & Safety Logs (with photos and person in charge)
        $farmingLogs = collect();
        if ($season) {
            $farmingLogs = NhatKyCanhTac::with(['chiTietVatTu.vatTu', 'nguoiThucHien'])
                ->where('mua_vu_id', $season->mua_vu_id)
                ->orderBy('ngay_thuc_hien', 'asc')
                ->get();
        }

        // Determine quarantine safety compliance status
        $quarantineSafe = true;
        $harvestDate = $lot->ngay_thu_hoach;
        $pesticideLogs = collect();

        foreach ($farmingLogs as $log) {
            if ($log->chiTietVatTu) {
                foreach ($log->chiTietVatTu as $detail) {
                    $quarantineDays = (int)($detail->thoi_gian_cach_ly_ap_dung_ngay ?? $detail->vatTu?->thoi_gian_cach_ly_ngay ?? 0);
                    if ($quarantineDays > 0) {
                        $usedDate = $detail->thoi_gian_su_dung ?? $log->ngay_thuc_hien;
                        $safeDate = Carbon::parse($usedDate)->addDays($quarantineDays);
                        $isSafe = true;
                        if ($harvestDate && Carbon::parse($harvestDate)->lt($safeDate)) {
                            $quarantineSafe = false;
                            $isSafe = false;
                        }
                        $pesticideLogs->push([
                            'ten_vat_tu' => $detail->vatTu?->ten_thuong_mai ?? $detail->vatTu?->ten_vat_tu ?? 'Vật tư BVTV',
                            'hoat_chat' => $detail->vatTu?->hoat_chat ?? 'Chưa ghi hoạt chất',
                            'ngay_thuc_hien' => $usedDate,
                            'thoi_gian_cach_ly_ngay' => $quarantineDays,
                            'is_safe' => $isSafe,
                        ]);
                    }
                }
            }
        }

        // Collect all related lot IDs across chain (raw lots + finished lots)
        $lotIds = [$lot->lo_nong_san_id];
        if ($isFinished) {
            $rawIds = DB::table('thanh_phan_lo_nong_san')
                ->where('lo_thanh_pham_id', $lot->lo_nong_san_id)
                ->pluck('lo_nguyen_lieu_id')
                ->toArray();
            $allLotIds = array_unique(array_merge($lotIds, $rawIds));
        } else {
            $finishedIds = DB::table('thanh_phan_lo_nong_san')
                ->where('lo_nguyen_lieu_id', $lot->lo_nong_san_id)
                ->pluck('lo_thanh_pham_id')
                ->toArray();
            $allLotIds = array_unique(array_merge($lotIds, $finishedIds));
        }

        // 2. Status History (Lịch sử trạng thái lô)
        $lotHistory = DB::table('lich_su_trang_thai_lo as ls')
            ->leftJoin('tai_khoan_he_thong as tk', 'ls.tai_khoan_id', '=', 'tk.tai_khoan_id')
            ->whereIn('ls.lo_nong_san_id', $allLotIds)
            ->select('ls.*', 'tk.ho_ten as nguoi_thuc_hien')
            ->orderBy('ls.thoi_gian', 'asc')
            ->get();

        // 3. QC Inspections (Phiếu kiểm định chất lượng & Tiêu chí chi tiết)
        $qcInspections = DB::table('phieu_kiem_dinh as pk')
            ->leftJoin('tai_khoan_he_thong as tk', 'pk.nguoi_kiem_dinh_id', '=', 'tk.tai_khoan_id')
            ->whereIn('pk.lo_nong_san_id', $allLotIds)
            ->select('pk.*', 'tk.ho_ten as ten_nguoi_kiem_dinh')
            ->orderBy('pk.ngay_kiem_dinh', 'asc')
            ->get()
            ->map(function ($qc) {
                $qc->chi_tiet = DB::table('chi_tiet_ket_qua_kiem_dinh')
                    ->where('phieu_id', $qc->phieu_id)
                    ->get();
                return $qc;
            });

        // 4. Packaging Logistics SSCC (Đóng gói Logistics & Mã SSCC)
        $packagingUnits = DB::table('chi_tiet_dong_goi_sscc as ct')
            ->join('don_vi_dong_goi_sscc as sscc', 'ct.sscc_id', '=', 'sscc.sscc_id')
            ->leftJoin('kho_lanh as kl', 'sscc.kho_lanh_id', '=', 'kl.kho_lanh_id')
            ->whereIn('ct.lo_nong_san_id', $allLotIds)
            ->select('sscc.*', 'ct.so_luong as sl_dong_goi', 'ct.don_vi_tinh as dvt_dong_goi', 'kl.ten_kho')
            ->orderBy('sscc.ngay_dong_goi', 'asc')
            ->get();

        $ssccIds = $packagingUnits->pluck('sscc_id')->toArray();

        // 5. Delivery & Final Destination (Phiếu giao hàng & Nơi bán / Điểm nhận)
        $deliveries = collect();
        $tripIds = [];
        if (!empty($ssccIds)) {
            $rawDeliveries = DB::table('chi_tiet_giao_nhan as ct')
                ->join('phieu_giao_hang as pgh', 'ct.phieu_giao_hang_id', '=', 'pgh.phieu_giao_hang_id')
                ->leftJoin('to_chuc as tc', 'pgh.to_chuc_nhan_id', '=', 'tc.to_chuc_id')
                ->leftJoin('don_vi_dong_goi_sscc as sscc', 'ct.sscc_id', '=', 'sscc.sscc_id')
                ->whereIn('ct.sscc_id', $ssccIds)
                ->select(
                    'pgh.*',
                    'ct.*',
                    'tc.ten_to_chuc as ten_diem_ban',
                    'sscc.ma_sscc'
                )
                ->orderBy('ct.thoi_gian_giao_nhan', 'asc')
                ->get();

            $tripIds = $rawDeliveries->pluck('chuyen_id')->filter()->unique()->toArray();

            // Group by phieu_giao_hang_id to present each delivery note clearly with full aggregate metrics
            $deliveries = $rawDeliveries->groupBy('phieu_giao_hang_id')->map(function ($group) use ($allLotIds) {
                $first = clone $group->first();
                $ctIds = $group->pluck('chi_tiet_giao_nhan_id')->toArray();
                $quantities = DB::table('so_luong_giao_nhan')
                    ->whereIn('chi_tiet_giao_nhan_id', $ctIds)
                    ->whereIn('lo_nong_san_id', $allLotIds)
                    ->get();
                $first->tong_so_luong_giao = (float) $quantities->sum('so_luong_giao');
                $first->tong_so_luong_nhan = (float) $quantities->sum('so_luong_nhan');
                $first->don_vi_tinh = $quantities->first()->don_vi_tinh ?? 'kg';
                $first->so_kien_hang = $group->pluck('sscc_id')->filter()->unique()->count();
                $first->danh_sach_sscc = $group->pluck('ma_sscc')->filter()->unique()->values()->all();
                return $first;
            })->values();
        }

        $deliveryQuantities = DB::table('so_luong_giao_nhan')
            ->whereIn('chi_tiet_giao_nhan_id', !empty($rawDeliveries) ? $rawDeliveries->pluck('chi_tiet_giao_nhan_id') : [])
            ->whereIn('lo_nong_san_id', $allLotIds)
            ->get()
            ->groupBy('chi_tiet_giao_nhan_id');

        // 6. Cold Chain Trips & Journey Events (Chuyến xe & Sự kiện hành trình)
        $trips = collect();
        $journeyEvents = collect();
        $vehicleIds = [];
        if (!empty($tripIds)) {
            $trips = DB::table('chuyen_van_chuyen as c')
                ->leftJoin('phuong_tien as p', 'c.phuong_tien_id', '=', 'p.phuong_tien_id')
                ->leftJoin('tai_khoan_he_thong as tk', 'c.tai_xe_id', '=', 'tk.tai_khoan_id')
                ->whereIn('c.chuyen_id', $tripIds)
                ->select(
                    'c.*',
                    'p.bien_so',
                    'p.loai_xe',
                    'p.co_he_thong_lam_lanh',
                    'tk.ho_ten as ten_tai_xe'
                )
                ->get();
            $vehicleIds = $trips->pluck('phuong_tien_id')->filter()->unique()->toArray();

            $journeyEvents = DB::table('su_kien_hanh_trinh as sk')
                ->leftJoin('tai_khoan_he_thong as tk', 'sk.nguoi_ghi_nhan_id', '=', 'tk.tai_khoan_id')
                ->whereIn('sk.chuyen_id', $tripIds)
                ->select('sk.*', 'tk.ho_ten as nguoi_ghi_nhan')
                ->orderBy('sk.thoi_gian', 'asc')
                ->get();
        }

        // 7. Sensor Data (Dữ liệu cảm biến IoT chuỗi lạnh - chu kỳ cập nhật mỗi 20 phút)
        $warehouseIds = array_filter(array_unique(array_merge(
            [$lot->kho_lanh_id],
            $packagingUnits->pluck('kho_lanh_id')->toArray()
        )));

        $deviceIds = DB::table('thiet_bi_iot')
            ->when(empty($warehouseIds) && empty($vehicleIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->where(function ($q) use ($warehouseIds, $vehicleIds) {
                if (!empty($warehouseIds)) {
                    $q->orWhereIn('kho_lanh_id', $warehouseIds);
                }
                if (!empty($vehicleIds)) {
                    $q->orWhereIn('phuong_tien_id', $vehicleIds);
                }
            })
            ->pluck('thiet_bi_id')
            ->toArray();

        $sampledTelemetry = collect();
        $telemetryStats = [
            'min_temp' => null,
            'max_temp' => null,
            'avg_temp' => null,
            'avg_hum' => null,
            'safe_compliance_rate' => null,
            'total_points' => 0,
            'interval_minutes' => 20,
        ];

        if (!empty($deviceIds)) {
            $rawTelemetry = DB::table('du_lieu_cam_bien as cb')
                ->join('thiet_bi_iot as tb', 'cb.thiet_bi_id', '=', 'tb.thiet_bi_id')
                ->leftJoin('kho_lanh as kl', 'tb.kho_lanh_id', '=', 'kl.kho_lanh_id')
                ->leftJoin('phuong_tien as pt', 'tb.phuong_tien_id', '=', 'pt.phuong_tien_id')
                ->whereIn('cb.thiet_bi_id', $deviceIds)
                ->select('cb.*', 'tb.ma_thiet_bi', 'tb.loai_thiet_bi', 'kl.ten_kho', 'pt.bien_so')
                ->orderBy('cb.thoi_gian_do', 'asc')
                ->get();

            // Downsample per IoT device so readings are separated by at least 20 minutes (1200 seconds)
            $grouped = $rawTelemetry->groupBy('thiet_bi_id');
            foreach ($grouped as $devId => $readings) {
                $lastTime = null;
                foreach ($readings as $r) {
                    $curTime = Carbon::parse($r->thoi_gian_do);
                    if ($lastTime === null || abs($curTime->diffInSeconds($lastTime)) >= 1200) {
                        $sampledTelemetry->push($r);
                        $lastTime = $curTime;
                    }
                }
            }

            $sampledTelemetry = $sampledTelemetry->sortBy('thoi_gian_do')->values();

            if ($sampledTelemetry->isNotEmpty()) {
                $validTemps = $sampledTelemetry->pluck('nhiet_do')->filter(fn ($v) => !is_null($v));
                $validHums = $sampledTelemetry->pluck('do_am')->filter(fn ($v) => !is_null($v));
                $safeCount = $validTemps->filter(fn ($t) => $t >= 0.0 && $t <= 6.0)->count();

                $telemetryStats = [
                    'min_temp' => $validTemps->min(),
                    'max_temp' => $validTemps->max(),
                    'avg_temp' => round($validTemps->avg(), 1),
                    'avg_hum' => round($validHums->avg(), 1),
                    'safe_compliance_rate' => $validTemps->count() > 0 ? round(($safeCount / $validTemps->count()) * 100, 1) : null,
                    'total_points' => $sampledTelemetry->count(),
                    'interval_minutes' => 20,
                ];
            }
        }

        // Trace URL & QR SVG
        $traceUrl = $lot->digital_link_url;
        $qrSvg = $lot->qr_svg;

        return view('trace.show', [
            'lot' => $lot,
            'product' => $product,
            'isFinished' => $isFinished,
            'farm' => $farm,
            'zone' => $zone,
            'season' => $season,
            'farmingLogs' => $farmingLogs,
            'pesticideLogs' => $pesticideLogs,
            'quarantineSafe' => $quarantineSafe,
            'lotHistory' => $lotHistory,
            'qcInspections' => $qcInspections,
            'packagingUnits' => $packagingUnits,
            'deliveries' => $deliveries,
            'deliveryQuantities' => $deliveryQuantities,
            'trips' => $trips,
            'journeyEvents' => $journeyEvents,
            'sampledTelemetry' => $sampledTelemetry,
            'telemetryStats' => $telemetryStats,
            'traceUrl' => $traceUrl,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * GS1 Digital Link standard endpoint: /trace/01/{gtin}/10/{lotCode}
     */
    public function showGs1(Request $request, string $gtin, string $lotCode)
    {
        $digitalLinks = app(\App\Services\Gs1DigitalLinkService::class);
        abort_unless($digitalLinks->validGtin($gtin) && $digitalLinks->validLot($lotCode), 404);

        $lotId = LoNongSan::where('ma_lo', $lotCode)
            ->whereHas('sanPham', fn ($q) => $q->where('gtin', $gtin))
            ->value('lo_nong_san_id');
        abort_unless($lotId !== null, 404);

        return $this->show($request, $lotCode, (int) $lotId);
    }

    public function showSscc(string $sscc)
    {
        abort_unless(app(\App\Services\Gs1Service::class)->validateSscc($sscc)['valid'], 404);

        $unit = DonViDongGoiSscc::with(['chiTiet.loNongSan.sanPham'])
            ->where('ma_sscc', $sscc)
            ->firstOrFail();

        return view('trace.sscc', ['unit' => $unit]);
    }
}

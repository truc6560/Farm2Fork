<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\CanhBaoChuoiLanh;
use App\Models\ChuyenVanChuyen;
use App\Models\DanhMucSanPham;
use App\Models\LoNongSan;
use App\Models\PhieuKiemDinh;
use App\Models\ToChuc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChainReportController extends Controller
{
    public function index(Request $request): View
    {
        $tuNgay = $request->input('tu_ngay');
        $denNgay = $request->input('den_ngay');
        $sanPhamId = $request->input('san_pham_id');
        $toChucId = $request->input('to_chuc_id');

        // Base lot query with date and product filters
        $lotQuery = LoNongSan::with(['sanPham', 'toChucNguon', 'phieuKiemDinh', 'khoLanh']);

        if ($tuNgay) {
            $lotQuery->whereDate('ngay_thu_hoach', '>=', $tuNgay);
        }
        if ($denNgay) {
            $lotQuery->whereDate('ngay_thu_hoach', '<=', $denNgay);
        }
        if ($sanPhamId) {
            $lotQuery->where('san_pham_id', $sanPhamId);
        }
        if ($toChucId) {
            $lotQuery->where('to_chuc_nguon_id', $toChucId);
        }

        $lots = (clone $lotQuery)->orderByDesc('lo_nong_san_id')->get();

        // 1. Production KPIs
        $totalLots = $lots->count();
        $totalWeight = $lots->sum('so_luong');
        $rawLotsCount = $lots->where('loai_lo', 'NGUYEN_LIEU')->count();
        $finishedLotsCount = $lots->where('loai_lo', 'THANH_PHAM')->count();

        // 2. QC Inspection KPIs
        $qcQuery = PhieuKiemDinh::query();
        if ($tuNgay) {
            $qcQuery->whereDate('ngay_kiem_dinh', '>=', $tuNgay);
        }
        if ($denNgay) {
            $qcQuery->whereDate('ngay_kiem_dinh', '<=', $denNgay);
        }
        $totalQc = (clone $qcQuery)->count();
        $passedQc = (clone $qcQuery)->where('ket_luan', 'DAT')->count();
        $failedQc = (clone $qcQuery)->where('ket_luan', 'KHONG_DAT')->count();
        $qcPassRate = $totalQc > 0 ? round(($passedQc / $totalQc) * 100, 1) : 0;

        // 3. Transport & On-time Delivery KPIs
        $tripQuery = ChuyenVanChuyen::query();
        if ($tuNgay) {
            $tripQuery->whereDate('thoi_gian_xuat_phat', '>=', $tuNgay);
        }
        if ($denNgay) {
            $tripQuery->whereDate('thoi_gian_xuat_phat', '<=', $denNgay);
        }
        $completedTrips = (clone $tripQuery)->where('trang_thai', 'HOAN_THANH')->get();
        $totalCompleted = $completedTrips->count();
        $onTimeTrips = $completedTrips->filter(function ($trip) {
            if (!$trip->thoi_gian_giao_du_kien || !$trip->thoi_gian_giao_thuc_te) {
                return true;
            }
            return $trip->thoi_gian_giao_thuc_te <= $trip->thoi_gian_giao_du_kien;
        })->count();
        $onTimeRate = $totalCompleted > 0 ? round(($onTimeTrips / $totalCompleted) * 100, 1) : 0;

        // 4. Cold Chain Incident KPIs
        $alertQuery = CanhBaoChuoiLanh::query();
        if ($tuNgay) {
            $alertQuery->whereDate('thoi_gian_phat_hien', '>=', $tuNgay);
        }
        if ($denNgay) {
            $alertQuery->whereDate('thoi_gian_phat_hien', '<=', $denNgay);
        }
        $totalAlerts = (clone $alertQuery)->count();
        $resolvedAlerts = (clone $alertQuery)->where('trang_thai', 'DA_XU_LY')->get();
        $resolvedCount = $resolvedAlerts->count();

        // Calculate average resolution time (hours)
        $avgResolutionHours = 0;
        if ($resolvedCount > 0) {
            $totalMinutes = 0;
            $counted = 0;
            foreach ($resolvedAlerts as $alert) {
                if ($alert->thoi_gian_phat_hien && $alert->thoi_gian_xu_ly) {
                    $diff = $alert->thoi_gian_phat_hien->diffInMinutes($alert->thoi_gian_xu_ly);
                    $totalMinutes += $diff;
                    $counted++;
                }
            }
            if ($counted > 0) {
                $avgResolutionHours = round(($totalMinutes / $counted) / 60, 1);
            }
        }

        // 5. Volume breakdown by product
        $productBreakdown = $lots->groupBy('san_pham_id')->map(function ($items, $spId) {
            $sp = $items->first()->sanPham;
            return [
                'ten_san_pham' => $sp->ten_san_pham ?? 'Khác',
                'ma_san_pham' => $sp->ma_san_pham ?? 'SP',
                'lot_count' => $items->count(),
                'total_qty' => $items->sum('so_luong'),
                'don_vi_tinh' => $items->first()->don_vi_tinh ?? 'kg',
            ];
        })->values();

        // 6. Volume breakdown by source organization
        $orgBreakdown = $lots->groupBy('to_chuc_nguon_id')->map(function ($items, $orgId) {
            $org = $items->first()->toChucNguon;
            return [
                'ten_to_chuc' => $org->ten_to_chuc ?? 'Chưa xác định',
                'lot_count' => $items->count(),
                'total_qty' => $items->sum('so_luong'),
                'don_vi_tinh' => $items->first()->don_vi_tinh ?? 'kg',
            ];
        })->values();

        $products = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->get();
        $organizations = ToChuc::where('trang_thai', 'HOAT_DONG')->get();

        return view('chain.reports.index', [
            'filters' => $request->all(),
            'totalLots' => $totalLots,
            'totalWeight' => $totalWeight,
            'rawLotsCount' => $rawLotsCount,
            'finishedLotsCount' => $finishedLotsCount,
            'totalQc' => $totalQc,
            'passedQc' => $passedQc,
            'failedQc' => $failedQc,
            'qcPassRate' => $qcPassRate,
            'totalCompleted' => $totalCompleted,
            'onTimeTrips' => $onTimeTrips,
            'onTimeRate' => $onTimeRate,
            'totalAlerts' => $totalAlerts,
            'resolvedCount' => $resolvedCount,
            'avgResolutionHours' => $avgResolutionHours,
            'productBreakdown' => $productBreakdown,
            'orgBreakdown' => $orgBreakdown,
            'products' => $products,
            'organizations' => $organizations,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $tuNgay = $request->input('tu_ngay');
        $denNgay = $request->input('den_ngay');
        $sanPhamId = $request->input('san_pham_id');
        $toChucId = $request->input('to_chuc_id');

        $query = LoNongSan::with([
            'sanPham',
            'toChucNguon',
            'khoLanh',
            'phieuKiemDinh',
        ]);

        if ($tuNgay) {
            $query->whereDate('ngay_thu_hoach', '>=', $tuNgay);
        }
        if ($denNgay) {
            $query->whereDate('ngay_thu_hoach', '<=', $denNgay);
        }
        if ($sanPhamId) {
            $query->where('san_pham_id', $sanPhamId);
        }
        if ($toChucId) {
            $query->where('to_chuc_nguon_id', $toChucId);
        }

        $lots = $query->orderByDesc('lo_nong_san_id')->get();

        $filename = 'Bao_cao_chuoi_cung_ung_Farm2Fork_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($lots) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM so Microsoft Excel renders Vietnamese characters correctly
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($handle, [
                'STT',
                'Mã lô',
                'Loại lô',
                'Tên nông sản',
                'Mã GTIN',
                'Đơn vị sản xuất / Nguồn',
                'Số lượng',
                'Đơn vị tính',
                'Ngày thu hoạch / SX',
                'Hạn sử dụng',
                'Trạng thái chuỗi',
                'Kết quả kiểm định QC',
                'Kho lạnh bảo quản',
                'GS1 Digital Link URI',
            ]);

            $index = 1;
            foreach ($lots as $lot) {
                $qc = $lot->phieuKiemDinh->sortByDesc('phieu_id')->first();
                $qcResult = $qc ? PhieuKiemDinh::KET_LUAN_LABELS[$qc->ket_luan] ?? $qc->ket_luan : 'Chưa kiểm định';

                fputcsv($handle, [
                    $index++,
                    $lot->ma_lo,
                    $lot->loai_lo === 'NGUYEN_LIEU' ? 'Nguyên liệu' : 'Thành phẩm',
                    $lot->sanPham->ten_san_pham ?? 'Chưa phân loại',
                    $lot->sanPham->gtin ?? '',
                    $lot->toChucNguon->ten_to_chuc ?? 'N/A',
                    $lot->so_luong,
                    $lot->don_vi_tinh,
                    $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : ($lot->ngay_san_xuat ? $lot->ngay_san_xuat->format('d/m/Y') : ''),
                    $lot->han_su_dung ? $lot->han_su_dung->format('d/m/Y') : '',
                    LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai,
                    $qcResult,
                    $lot->khoLanh->ten_kho ?? 'Chưa nhập kho',
                    $lot->digital_link_url,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}


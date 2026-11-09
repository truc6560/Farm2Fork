<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\CanhBaoChuoiLanh;
use App\Models\ChuyenVanChuyen;
use App\Models\DanhMucSanPham;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use App\Models\PhieuKiemDinh;
use App\Models\ThietBiIot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChainDashboardController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Executive Summary Cards (calculated from real DB records)
        $totalLots = LoNongSan::count();
        $activeLots = LoNongSan::whereNotIn('trang_thai', ['DA_GIAO', 'TRA_NHA_CUNG_CAP', 'THU_HOI'])->count();
        $pendingQcLots = LoNongSan::where('trang_thai', 'CHO_KIEM_DINH')->count();
        $inStorageLots = LoNongSan::where('trang_thai', 'LUU_KHO')->count();
        $inTransitLots = LoNongSan::where('trang_thai', 'DANG_VAN_CHUYEN')->count();
        
        $problemLots = LoNongSan::where(function ($q) {
            $q->whereIn('trang_thai', ['TRA_NHA_CUNG_CAP', 'THU_HOI', 'KHONG_DAT'])
              ->orWhereHas('phieuKiemDinh', function ($pq) {
                  $pq->where('ket_luan', 'KHONG_DAT')->whereNull('thoi_gian_khac_phuc');
              });
        })->count();

        $unresolvedAlerts = CanhBaoChuoiLanh::where('trang_thai', 'CHUA_XU_LY')->count();
        $inProgressAlerts = CanhBaoChuoiLanh::where('trang_thai', 'DANG_XU_LY')->count();

        $activeTrips = ChuyenVanChuyen::where('trang_thai', 'DANG_VAN_CHUYEN')->count();
        $delayedTrips = ChuyenVanChuyen::where('trang_thai', '!=', 'HOAN_THANH')
            ->whereNotNull('thoi_gian_giao_du_kien')
            ->where('thoi_gian_giao_du_kien', '<', now())
            ->count();

        // 2. Multi-Stage Pipeline Breakdown
        $stages = [
            'SAN_XUAT' => [
                'name' => 'Thu hoạch / Nguyên liệu',
                'count' => LoNongSan::where('loai_lo', 'NGUYEN_LIEU')->whereIn('trang_thai', ['MOI_TAO'])->count(),
                'status' => 'Khoản 1 chuỗi',
            ],
            'KIEM_DINH' => [
                'name' => 'Chờ kiểm định QC',
                'count' => $pendingQcLots,
                'status' => 'Đảm bảo chất lượng',
            ],
            'SO_CHE_DONG_GOI' => [
                'name' => 'Sơ chế & Đóng gói',
                'count' => LoNongSan::whereIn('trang_thai', ['CHO_SO_CHE', 'DA_SO_CHE', 'DANG_DONG_GOI', 'CHO_KIEM_DINH_XUAT_XUONG'])->count(),
                'status' => 'Phân loại SSCC',
            ],
            'LUU_KHO' => [
                'name' => 'Lưu kho lạnh',
                'count' => $inStorageLots,
                'status' => 'Giám sát nhiệt độ',
            ],
            'VAN_CHUYEN' => [
                'name' => 'Đang vận chuyển',
                'count' => $inTransitLots,
                'status' => 'Xe lạnh lăn bánh',
            ],
            'DA_GIAO' => [
                'name' => 'Đã giao an toàn',
                'count' => LoNongSan::where('trang_thai', 'DA_GIAO')->count(),
                'status' => 'Hoàn tất bàn giao',
            ],
            'SU_CO' => [
                'name' => 'Tạm giữ / Sự cố',
                'count' => $problemLots,
                'status' => 'Cần ưu tiên xử lý',
            ],
        ];

        // 3. Alerts Breakdown by Type
        $alertsByType = [
            'NHIET_DO_CAO' => CanhBaoChuoiLanh::where('loai_canh_bao', 'NHIET_DO_CAO')->count(),
            'NHIET_DO_THAP' => CanhBaoChuoiLanh::where('loai_canh_bao', 'NHIET_DO_THAP')->count(),
            'DO_AM_VUOT_NGUONG' => CanhBaoChuoiLanh::where('loai_canh_bao', 'DO_AM_VUOT_NGUONG')->count(),
            'MAT_TIN_HIEU' => CanhBaoChuoiLanh::where('loai_canh_bao', 'MAT_TIN_HIEU')->count(),
        ];

        // 4. Lots by Product (Top products with real numbers)
        $lotsByProduct = DanhMucSanPham::withCount('loNongSan')
            ->withSum('loNongSan', 'so_luong')
            ->having('lo_nong_san_count', '>', 0)
            ->orderByDesc('lo_nong_san_count')
            ->limit(6)
            ->get();

        // 5. Recent high-priority issues that require attention
        $recentAlerts = CanhBaoChuoiLanh::with(['thietBi.khoLanh', 'thietBi.phuongTien', 'chuyenVanChuyen'])
            ->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])
            ->orderBy('thoi_gian_phat_hien', 'desc')
            ->limit(5)
            ->get();

        $qcRejections = PhieuKiemDinh::with(['loNongSan.sanPham', 'nguoiKiemDinh'])
            ->where('ket_luan', 'KHONG_DAT')
            ->whereNull('thoi_gian_khac_phuc')
            ->orderBy('phieu_id', 'desc')
            ->limit(5)
            ->get();

        $activeTransportTrips = ChuyenVanChuyen::with(['phuongTien.thietBiIot.latestReading', 'taiXe', 'phieuGiaoHang'])
            ->where('trang_thai', 'DANG_VAN_CHUYEN')
            ->orderBy('chuyen_id', 'desc')
            ->limit(5)
            ->get();

        // 6. Traceability gap detection: Find lots missing critical upstream links
        $gappedLots = LoNongSan::with(['sanPham', 'toChucNguon'])
            ->where(function ($q) {
                // Raw lot without season
                $q->where('loai_lo', 'NGUYEN_LIEU')->whereNull('mua_vu_id');
            })
            ->orWhere(function ($q) {
                // Finished lot without raw components linked
                $q->where('loai_lo', 'THANH_PHAM')
                  ->whereDoesntHave('nguyenLieuDauVao');
            })
            ->limit(5)
            ->get();

        return view('chain.dashboard.index', [
            'totalLots' => $totalLots,
            'activeLots' => $activeLots,
            'pendingQcLots' => $pendingQcLots,
            'inStorageLots' => $inStorageLots,
            'inTransitLots' => $inTransitLots,
            'problemLots' => $problemLots,
            'unresolvedAlerts' => $unresolvedAlerts,
            'inProgressAlerts' => $inProgressAlerts,
            'activeTrips' => $activeTrips,
            'delayedTrips' => $delayedTrips,
            'stages' => $stages,
            'alertsByType' => $alertsByType,
            'lotsByProduct' => $lotsByProduct,
            'recentAlerts' => $recentAlerts,
            'qcRejections' => $qcRejections,
            'activeTransportTrips' => $activeTransportTrips,
            'gappedLots' => $gappedLots,
        ]);
    }
}


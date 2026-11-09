<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\CanhBaoChuoiLanh;
use App\Models\ChuyenVanChuyen;
use App\Models\LoNongSan;
use App\Models\PhieuKiemDinh;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChainAlertController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category', 'all'); // 'all', 'cold_chain', 'qc', 'transport', 'traceability'
        $status = $request->query('status', 'all'); // 'unresolved', 'resolved', 'all'
        $severity = $request->query('severity', 'all');

        // 1. Cold chain alerts
        $coldChainQuery = CanhBaoChuoiLanh::with(['thietBi.khoLanh', 'thietBi.phuongTien', 'chuyenVanChuyen', 'nguoiXuLy']);
        if ($status === 'unresolved') {
            $coldChainQuery->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY']);
        } elseif ($status === 'resolved') {
            $coldChainQuery->where('trang_thai', 'DA_XU_LY');
        }
        $coldChainAlerts = $coldChainQuery->orderByDesc('thoi_gian_phat_hien')->get();

        // 2. QC failure alerts
        $qcQuery = PhieuKiemDinh::with(['loNongSan.sanPham', 'nguoiKiemDinh', 'nguoiKhacPhuc'])
            ->where('ket_luan', 'KHONG_DAT');
        if ($status === 'unresolved') {
            $qcQuery->whereNull('thoi_gian_khac_phuc');
        } elseif ($status === 'resolved') {
            $qcQuery->whereNotNull('thoi_gian_khac_phuc');
        }
        $qcAlerts = $qcQuery->orderByDesc('phieu_id')->get();

        // 3. Transport delay alerts
        $transportAlerts = ChuyenVanChuyen::with(['phuongTien', 'taiXe', 'phieuGiaoHang'])
            ->where('trang_thai', '!=', 'HOAN_THANH')
            ->whereNotNull('thoi_gian_giao_du_kien')
            ->where('thoi_gian_giao_du_kien', '<', now())
            ->orderBy('thoi_gian_giao_du_kien', 'asc')
            ->get();

        // 4. Traceability integrity gaps
        $gapAlerts = LoNongSan::with(['sanPham', 'toChucNguon'])
            ->where(function ($q) {
                $q->where(function ($rawQ) {
                    $rawQ->where('loai_lo', 'NGUYEN_LIEU')->whereNull('mua_vu_id');
                })->orWhere(function ($finQ) {
                    $finQ->where('loai_lo', 'THANH_PHAM')->whereDoesntHave('nguyenLieuDauVao');
                })->orWhere(function ($noQc) {
                    $noQc->whereNotIn('trang_thai', ['MOI_TAO', 'CHO_KIEM_DINH'])
                         ->whereDoesntHave('phieuKiemDinh');
                });
            })
            ->orderByDesc('lo_nong_san_id')
            ->get();

        // Overall counts
        $unresolvedColdChain = CanhBaoChuoiLanh::whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])->count();
        $unresolvedQc = PhieuKiemDinh::where('ket_luan', 'KHONG_DAT')->whereNull('thoi_gian_khac_phuc')->count();
        $unresolvedTransport = $transportAlerts->count();
        $unresolvedGaps = $gapAlerts->count();

        $totalIssues = $unresolvedColdChain + $unresolvedQc + $unresolvedTransport + $unresolvedGaps;
        $resolvedCount = CanhBaoChuoiLanh::where('trang_thai', 'DA_XU_LY')->count() + PhieuKiemDinh::where('ket_luan', 'KHONG_DAT')->whereNotNull('thoi_gian_khac_phuc')->count();

        return view('chain.alerts.index', [
            'category' => $category,
            'status' => $status,
            'severity' => $severity,
            'coldChainAlerts' => $coldChainAlerts,
            'qcAlerts' => $qcAlerts,
            'transportAlerts' => $transportAlerts,
            'gapAlerts' => $gapAlerts,
            'unresolvedColdChain' => $unresolvedColdChain,
            'unresolvedQc' => $unresolvedQc,
            'unresolvedTransport' => $unresolvedTransport,
            'unresolvedGaps' => $unresolvedGaps,
            'totalIssues' => $totalIssues,
            'resolvedCount' => $resolvedCount,
        ]);
    }
}


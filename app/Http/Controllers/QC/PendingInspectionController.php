<?php

namespace App\Http\Controllers\QC;

use App\Http\Controllers\Controller;
use App\Models\LoNongSan;
use App\Models\PhieuKiemDinh;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendingInspectionController extends Controller
{
    /**
     * Display list of lots awaiting quality inspection.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all');
        $search = trim($request->query('q', ''));

        // Chỉ đúng hai cổng QC của quy trình: nguyên liệu sau tiếp nhận và
        // thành phẩm đã đóng gói xong chờ duyệt xuất xưởng.
        $query = LoNongSan::with([
            'sanPham',
            'khoLanh',
            'muaVu.vungTrong.toChuc',
            'toChucNguon',
            'phieuKiemDinh',
        ])
        ->where(function ($q) {
            $q->where(function ($raw) {
                $raw->where('loai_lo', 'NGUYEN_LIEU')->where('trang_thai', 'CHO_KIEM_DINH');
            })->orWhere(function ($finished) {
                $finished->where('loai_lo', 'THANH_PHAM')
                    ->where(function ($status) {
                        $status->where('trang_thai', 'CHO_KIEM_DINH_XUAT_XUONG')
                            ->orWhere(function ($legacy) {
                                $legacy->where('trang_thai', 'DA_DONG_GOI')
                                    ->whereRaw('(SELECT COALESCE(SUM(ct.so_luong), 0) FROM chi_tiet_dong_goi_sscc ct WHERE ct.lo_nong_san_id = lo_nong_san.lo_nong_san_id) >= lo_nong_san.so_luong');
                            });
                    });
            });
        });

        if ($type === 'NGUYEN_LIEU') {
            $query->where('loai_lo', 'NGUYEN_LIEU');
        } elseif ($type === 'THANH_PHAM') {
            $query->where('loai_lo', 'THANH_PHAM');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ma_lo', 'like', "%{$search}%")
                  ->orWhereHas('sanPham', function ($spQ) use ($search) {
                      $spQ->where('ten_san_pham', 'like', "%{$search}%")
                          ->orWhere('ma_san_pham', 'like', "%{$search}%");
                  });
            });
        }

        $lots = $query->orderByDesc('lo_nong_san_id')->paginate(15)->withQueryString();

        // Calculate KPI summaries
        $totalRawPending = LoNongSan::where('loai_lo', 'NGUYEN_LIEU')
            ->where('trang_thai', 'CHO_KIEM_DINH')
            ->count();

        $totalFinishedPending = LoNongSan::where('loai_lo', 'THANH_PHAM')
            ->where(function ($status) {
                $status->where('trang_thai', 'CHO_KIEM_DINH_XUAT_XUONG')
                    ->orWhere(function ($legacy) {
                        $legacy->where('trang_thai', 'DA_DONG_GOI')
                            ->whereRaw('(SELECT COALESCE(SUM(ct.so_luong), 0) FROM chi_tiet_dong_goi_sscc ct WHERE ct.lo_nong_san_id = lo_nong_san.lo_nong_san_id) >= lo_nong_san.so_luong');
                    });
            })
            ->count();

        $totalPending = $totalRawPending + $totalFinishedPending;

        return view('qc.pending.index', [
            'lots' => $lots,
            'type' => $type,
            'search' => $search,
            'totalPending' => $totalPending,
            'totalRawPending' => $totalRawPending,
            'totalFinishedPending' => $totalFinishedPending,
        ]);
    }
}


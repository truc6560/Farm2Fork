<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('vaiTro', 'toChuc');

        $role = $user->vaiTro?->ma_vai_tro;
        $orgId = $user->to_chuc_id;

        if ($role === 'QUAN_LY_CHUOI') {
            return redirect()->route('chain.dashboard');
        }

        $stats = $this->statsForRole($role, $user->getAuthIdentifier(), $orgId);

        return view('dashboard', [
            'user' => $user,
            'role' => $role,
            'navItems' => config('role_menu.' . $role, []),
            'stats' => $stats['cards'] ?? [],
            'alerts' => $stats['alerts'] ?? [],
        ]);
    }

    private function statsForRole(?string $role, int $accountId, mixed $orgId): array
    {
        return match ($role) {
            'ADMIN' => $this->adminStats(),
            'NGUOI_SAN_XUAT' => $this->producerStats($accountId),
            'NV_SO_CHE_DONG_GOI' => $this->processingStats($orgId),
            'QC' => $this->qcStats(),
            'NV_KHO_LANH' => $this->warehouseStats(),
            'TAI_XE' => $this->driverStats($accountId),
            'QUAN_LY_CHUOI' => $this->chainStats(),
            default => ['cards' => [], 'alerts' => []],
        };
    }

    private function card(string $label, mixed $value, string $hint = ''): array
    {
        return compact('label', 'value', 'hint');
    }

    private function adminStats(): array
    {
        $accounts = DB::table('tai_khoan_he_thong')->count();
        $orgs = DB::table('to_chuc')->count();
        $locked = DB::table('tai_khoan_he_thong')->where('trang_thai', 'KHOA')->count();

        return [
            'cards' => [
                $this->card('Tổng tài khoản', $accounts),
                $this->card('Tổng tổ chức', $orgs),
                $this->card('Tài khoản bị khóa', $locked),
            ],
            'alerts' => $locked > 0
                ? ["Có {$locked} tài khoản đang bị khóa cần xử lý."]
                : [],
        ];
    }

    private function producerStats(int $accountId): array
    {
        $plotIds = DB::table('nhan_vien_vung_trong')
            ->where('tai_khoan_id', $accountId)
            ->pluck('vung_trong_id');

        $plots = DB::table('vung_trong')->whereIn('vung_trong_id', $plotIds);
        $seasons = DB::table('mua_vu')->whereIn('vung_trong_id', $plotIds);
        $seasonIds = (clone $seasons)->pluck('mua_vu_id');
        $logs = $seasonIds->isNotEmpty()
            ? DB::table('nhat_ky_canh_tac')->whereIn('mua_vu_id', $seasonIds)->count()
            : 0;
        $lots = $seasonIds->isNotEmpty()
            ? DB::table('lo_nong_san')->whereIn('mua_vu_id', $seasonIds)->where('loai_lo', 'NGUYEN_LIEU')->count()
            : 0;

        $expiringCerts = $plotIds->isNotEmpty()
            ? (clone $plots)
                ->whereNotNull('ngay_het_han_chung_nhan')
                ->whereBetween('ngay_het_han_chung_nhan', [now(), now()->addDays(30)])
                ->count()
            : 0;

        $alerts = [];
        if ($expiringCerts > 0) {
            $alerts[] = "Có {$expiringCerts} vùng trồng có chứng nhận sắp hết hạn trong 30 ngày tới. Vui lòng kiểm tra và gia hạn.";
        }

        return [
            'cards' => [
                $this->card('Vùng trồng của bạn', (clone $plots)->count()),
                $this->card('Mùa vụ của bạn', (clone $seasons)->count()),
                $this->card('Nhật ký canh tác', $logs),
                $this->card('Lô thu hoạch', $lots),
            ],
            'alerts' => $alerts,
        ];
    }

    private function processingStats(mixed $orgId): array
    {
        $pendingRaw = DB::table('lo_nong_san')
            ->where('loai_lo', 'NGUYEN_LIEU')
            ->whereIn('trang_thai', ['MOI_TAO', 'CHO_SO_CHE'])
            ->count();
        $finishedLots = DB::table('lo_nong_san')
            ->where('loai_lo', 'THANH_PHAM')
            ->count();
        $packing = DB::table('don_vi_dong_goi_sscc')->count();

        $alerts = [];
        if ($pendingRaw > 0) {
            $alerts[] = "Có {$pendingRaw} lô nông sản nguyên liệu đang chờ tiếp nhận và sơ chế.";
        }

        return [
            'cards' => [
                $this->card('Nguyên liệu chờ sơ chế', $pendingRaw, 'Lô chuyển đến từ vùng trồng'),
                $this->card('Lô thành phẩm', $finishedLots, 'Đã sơ chế & phân loại'),
                $this->card('Đơn vị đóng gói SSCC', $packing, 'Kiện hàng chuẩn GS1'),
            ],
            'alerts' => $alerts,
        ];
    }

    private function qcStats(): array
    {
        $pendingTickets = DB::table('phieu_kiem_dinh')->where('ket_luan', 'CHO_KIEM_DINH')->count();
        $pendingLots = DB::table('lo_nong_san')->whereIn('trang_thai', ['CHO_KIEM_DINH', 'CHO_KIEM_DINH_XUAT_XUONG'])->count();
        $totalPending = $pendingTickets + $pendingLots;
        $pass = DB::table('phieu_kiem_dinh')->where('ket_luan', 'DAT')->count();
        $fail = DB::table('phieu_kiem_dinh')->where('ket_luan', 'KHONG_DAT')->count();
        $remedyPending = DB::table('phieu_kiem_dinh')->where('ket_luan', 'KHONG_DAT')->whereNull('thoi_gian_khac_phuc')->count();

        $alerts = [];
        if ($totalPending > 0) {
            $alerts[] = "Hiện có {$totalPending} lô nông sản đang chờ kiểm tra chất lượng tại xưởng.";
        }
        if ($remedyPending > 0) {
            $alerts[] = "Có {$remedyPending} phiếu kiểm định không đạt đang chờ kết quả khắc phục.";
        }

        return [
            'cards' => [
                $this->card('Lô chờ kiểm định', $totalPending, 'Cần lấy mẫu và kiểm tra'),
                $this->card('Kết luận đạt chuẩn', $pass, 'Đã cấp chứng nhận kiểm định'),
                $this->card('Không đạt / Cần xử lý', $fail, 'Cần thực hiện hướng khắc phục'),
            ],
            'alerts' => $alerts,
        ];
    }

    private function warehouseStats(): array
    {
        $inStockLots = DB::table('lo_nong_san')->whereIn('trang_thai', ['TRONG_KHO', 'LUU_KHO'])->count();
        $inStockSscc = DB::table('don_vi_dong_goi_sscc')->whereIn('trang_thai', ['TRONG_KHO', 'DA_NHAP_KHO'])->count();
        $warehouses = DB::table('kho_lanh')->count();
        $unhandled = DB::table('canh_bao_chuoi_lanh')->where('trang_thai', 'CHUA_XU_LY')->count();

        $alerts = [];
        if ($unhandled > 0) {
            $alerts[] = "Phát hiện {$unhandled} cảnh báo chuỗi lạnh chưa được xử lý! Vui lòng kiểm tra nhiệt độ kho ngay.";
        }

        return [
            'cards' => [
                $this->card('Lô nông sản lưu kho', $inStockLots, 'Đang bảo quản tại các kho'),
                $this->card('Kiện hàng SSCC lưu kho', $inStockSscc, 'Thùng/Pallet sẵn sàng xuất'),
                $this->card('Tổng số kho lạnh', $warehouses, 'Hệ thống kho bảo quản'),
                $this->card('Cảnh báo chuỗi lạnh', $unhandled, $unhandled > 0 ? 'Cần xử lý khẩn cấp' : 'Nhiệt độ ổn định'),
            ],
            'alerts' => $alerts,
        ];
    }

    private function driverStats(int $accountId): array
    {
        $trips = DB::table('chuyen_van_chuyen')->where('tai_xe_id', $accountId);
        $running = (clone $trips)->where('trang_thai', 'DANG_VAN_CHUYEN')->count();
        $ready = (clone $trips)->whereIn('trang_thai', ['SAN_SANG', 'CHUAN_BI'])->count();
        $done = (clone $trips)->where('trang_thai', 'HOAN_THANH')->count();

        // Deliveries in active trips
        $tripIds = (clone $trips)->pluck('chuyen_id')->toArray();
        $deliveriesQuery = DB::table('phieu_giao_hang')->whereIn('chuyen_id', !empty($tripIds) ? $tripIds : [0]);
        $pendingDeliveries = (clone $deliveriesQuery)->whereIn('trang_thai', ['CHO_GIAO', 'DANG_GIAO'])->count();
        $doneDeliveries = (clone $deliveriesQuery)->where('trang_thai', 'DA_GIAO')->count();

        $alerts = [];
        if ($running > 0) {
            $runningTrip = (clone $trips)->where('trang_thai', 'DANG_VAN_CHUYEN')->first();
            $alerts[] = "Bạn đang thực hiện chuyến xe [{$runningTrip->ma_chuyen}]. Đừng quên ghi nhận các mốc hành trình và kiểm tra nhiệt độ thùng lạnh.";
        } elseif ($ready > 0) {
            $alerts[] = "Bạn có {$ready} chuyến vận chuyển sẵn sàng khởi hành. Vui lòng kiểm tra xe và bấm xuất phát khi bắt đầu lăn bánh.";
        }

        return [
            'cards' => [
                $this->card('Chuyến đang vận chuyển', $running, 'Đang lăn bánh trên đường'),
                $this->card('Chờ khởi hành', $ready, 'Đã chuẩn bị xe và hàng'),
                $this->card('Chuyến đã hoàn thành', $done, 'Hoàn tất giao nhận an toàn'),
                $this->card('Đơn giao cần trả', $pendingDeliveries, 'Phiếu giao đang xử lý'),
            ],
            'alerts' => $alerts,
        ];
    }

    private function chainStats(): array
    {
        return [
            'cards' => [
                $this->card('Tổng lô nông sản', DB::table('lo_nong_san')->count()),
                $this->card('Chuyến vận chuyển', DB::table('chuyen_van_chuyen')->count()),
                $this->card('Cảnh báo chưa xử lý', DB::table('canh_bao_chuoi_lanh')->where('trang_thai', 'CHUA_XU_LY')->count()),
                $this->card('Thiết bị IoT', DB::table('thiet_bi_iot')->count()),
            ],
            'alerts' => [],
        ];
    }
}

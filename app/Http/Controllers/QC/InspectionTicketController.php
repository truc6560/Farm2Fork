<?php

namespace App\Http\Controllers\QC;

use App\Http\Controllers\Controller;
use App\Models\ChiTietKetQuaKiemDinh;
use App\Models\LichSuTrangThaiLo;
use App\Models\LoNongSan;
use App\Models\PhieuKiemDinh;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InspectionTicketController extends Controller
{
    /**
     * Display a listing of inspection certificates.
     */
    public function index(Request $request): View
    {
        $search = trim($request->query('q', ''));
        $type = $request->query('loai_kiem_dinh', '');
        $conclusion = $request->query('ket_luan', '');
        $fromDate = $request->query('from_date', '');
        $toDate = $request->query('to_date', '');

        $query = PhieuKiemDinh::with([
            'loNongSan.sanPham',
            'loNongSan.toChucNguon',
            'nguoiKiemDinh',
            'chiTietKetQua',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ma_phieu', 'like', "%{$search}%")
                  ->orWhereHas('loNongSan', function ($lotQ) use ($search) {
                      $lotQ->where('ma_lo', 'like', "%{$search}%")
                           ->orWhereHas('sanPham', function ($spQ) use ($search) {
                               $spQ->where('ten_san_pham', 'like', "%{$search}%");
                           });
                  })
                  ->orWhereHas('nguoiKiemDinh', function ($uQ) use ($search) {
                      $uQ->where('ho_ten', 'like', "%{$search}%");
                  });
            });
        }

        if ($type !== '') {
            $query->where('loai_kiem_dinh', $type);
        }

        if ($conclusion !== '') {
            $query->where('ket_luan', $conclusion);
        }

        if ($fromDate !== '') {
            $query->whereDate('ngay_kiem_dinh', '>=', $fromDate);
        }

        if ($toDate !== '') {
            $query->whereDate('ngay_kiem_dinh', '<=', $toDate);
        }

        $tickets = $query->orderByDesc('ngay_kiem_dinh')->paginate(15)->withQueryString();

        // Statistics
        $totalTickets = PhieuKiemDinh::count();
        $totalPass = PhieuKiemDinh::where('ket_luan', 'DAT')->count();
        $totalFail = PhieuKiemDinh::where('ket_luan', 'KHONG_DAT')->count();
        $totalPendingRemedy = PhieuKiemDinh::where('ket_luan', 'KHONG_DAT')->whereNull('thoi_gian_khac_phuc')->count();

        return view('qc.inspections.index', [
            'tickets' => $tickets,
            'search' => $search,
            'type' => $type,
            'conclusion' => $conclusion,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totalTickets' => $totalTickets,
            'totalPass' => $totalPass,
            'totalFail' => $totalFail,
            'totalPendingRemedy' => $totalPendingRemedy,
        ]);
    }

    /**
     * Show form for creating a new inspection ticket.
     */
    public function create(Request $request): View
    {
        $selectedLotId = $request->query('lo_id');
        $selectedLot = null;

        if ($selectedLotId) {
            $selectedLot = LoNongSan::with(['sanPham', 'khoLanh', 'muaVu.vungTrong.toChuc', 'toChucNguon'])
                ->where(function ($query) {
                    $query->where(function ($raw) {
                        $raw->where('loai_lo', 'NGUYEN_LIEU')->where('trang_thai', 'CHO_KIEM_DINH');
                    })->orWhere(function ($finished) {
                        $finished->where('loai_lo', 'THANH_PHAM')
                            ->whereIn('trang_thai', ['CHO_KIEM_DINH_XUAT_XUONG', 'DA_DONG_GOI']);
                    });
                })
                ->find($selectedLotId);
        }

        // Không cho chọn lô ngoài đúng cổng kiểm định của quy trình.
        $availableLots = LoNongSan::with([
            'sanPham',
            'khoLanh',
            'muaVu.vungTrong.toChuc',
            'toChucNguon',
            'nguyenLieuDauVao.sanPham',
            'phieuKiemDinh',
        ])
            ->where(function ($query) {
                $query->where(function ($raw) {
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
            })
            ->orderByDesc('lo_nong_san_id')
            ->limit(100)
            ->get();

        $lotProfiles = $availableLots->mapWithKeys(function (LoNongSan $lot) {
            $zone = $lot->muaVu?->vungTrong;
            $origin = $lot->toChucNguon ?? $zone?->toChuc;

            return [$lot->lo_nong_san_id => [
                'code' => $lot->ma_lo,
                'product' => $lot->sanPham?->ten_san_pham ?? 'Chưa xác định',
                'product_code' => $lot->sanPham?->ma_san_pham ?? '—',
                'gtin' => $lot->sanPham?->gtin ?: 'Chưa cấp',
                'type' => $lot->loai_lo === 'NGUYEN_LIEU' ? 'Lô nguyên liệu' : 'Lô thành phẩm',
                'quantity' => number_format((float) $lot->so_luong, 2) . ' ' . $lot->don_vi_tinh,
                'origin' => $origin?->ten_to_chuc ?? 'Chưa xác định',
                'season' => $lot->muaVu
                    ? trim(($lot->muaVu->ten_mua_vu ?? 'Mùa vụ') . ' · ' . ($lot->muaVu->ma_mua_vu ?? ''))
                    : 'Không áp dụng',
                'zone' => $zone
                    ? trim($zone->ten_vung_trong . ($zone->ma_so_vung_trong ? ' · ' . $zone->ma_so_vung_trong : ''))
                    : 'Không áp dụng',
                'harvest_date' => $lot->ngay_thu_hoach?->format('d/m/Y') ?? '—',
                'production_date' => $lot->ngay_san_xuat?->format('d/m/Y') ?? '—',
                'warehouse' => $lot->khoLanh?->ten_kho ?? 'Chưa xếp kho',
                'status' => LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai,
                'input_lots' => $lot->nguyenLieuDauVao->pluck('ma_lo')->values()->all(),
                'trace_url' => $lot->digital_link_url,
                'has_prior_inspection' => $lot->phieuKiemDinh->isNotEmpty(),
            ]];
        });

        // Standard inspection criteria templates
        $defaultCriteriaInbound = [
            [
                'ten_tieu_chi' => 'Cảm quan (màu sắc, mùi, dị vật)',
                'gia_tri_tham_chieu' => 'Không dị vật, không mùi lạ',
                'gia_tri_thuc_te' => 'Đạt, không dị vật',
                'don_vi_do' => '',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
            [
                'ten_tieu_chi' => 'Nhiệt độ lõi sản phẩm',
                'gia_tri_tham_chieu' => '0-4',
                'gia_tri_thuc_te' => '2.0',
                'don_vi_do' => '°C',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
            [
                'ten_tieu_chi' => 'Độ Brix (độ ngọt)',
                'gia_tri_tham_chieu' => '>= 7',
                'gia_tri_thuc_te' => '9.0',
                'don_vi_do' => '°Brix',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
            [
                'ten_tieu_chi' => 'Độ chín / độ tươi tự nhiên',
                'gia_tri_tham_chieu' => '>= 80',
                'gia_tri_thuc_te' => '90.0',
                'don_vi_do' => '%',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
            [
                'ten_tieu_chi' => 'Tỷ lệ dập nát, hư hỏng cơ học',
                'gia_tri_tham_chieu' => '<= 5',
                'gia_tri_thuc_te' => '2.5',
                'don_vi_do' => '%',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
            [
                'ten_tieu_chi' => 'Dư lượng BVTV (test nhanh phòng lab)',
                'gia_tri_tham_chieu' => 'Âm tính',
                'gia_tri_thuc_te' => 'Âm tính',
                'don_vi_do' => '',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
        ];

        $defaultCriteriaOutbound = array_merge($defaultCriteriaInbound, [
            [
                'ten_tieu_chi' => 'Khối lượng tịnh đơn vị bán lẻ',
                'gia_tri_tham_chieu' => 'Chuẩn quy cách (sai số <= 2%)',
                'gia_tri_thuc_te' => 'Đạt chuẩn (cân mẫu 10 đơn vị)',
                'don_vi_do' => '',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
            [
                'ten_tieu_chi' => 'Nhãn định danh và mã truy xuất QR/GS1',
                'gia_tri_tham_chieu' => 'Quét được, đúng thông tin lô',
                'gia_tri_thuc_te' => 'Quét được, khớp 100% dữ liệu',
                'don_vi_do' => '',
                'ket_qua' => 'DAT',
                'ghi_chu' => '',
            ],
        ]);

        return view('qc.inspections.create', [
            'selectedLot' => $selectedLot,
            'availableLots' => $availableLots,
            'lotProfiles' => $lotProfiles,
            'defaultCriteriaInbound' => $defaultCriteriaInbound,
            'defaultCriteriaOutbound' => $defaultCriteriaOutbound,
            'suggestedCode' => PhieuKiemDinh::generateMaPhieu(),
        ]);
    }

    /**
     * Store a newly created inspection certificate in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lo_nong_san_id' => ['required', 'integer', 'exists:lo_nong_san,lo_nong_san_id'],
            'loai_kiem_dinh' => ['required', 'string', 'in:NHAP_KHO,TRUOC_XUAT_XUONG,KIEM_DINH_LAI,SAU_PHAN_PHOI'],
            'ngay_kiem_dinh' => ['required', 'date'],
            'ket_luan' => ['required', 'string', 'in:DAT,KHONG_DAT,CHO_KIEM_DINH'],
            'huong_xu_ly' => ['nullable', 'string', 'max:80'],
            'ghi_chu_loi' => ['nullable', 'string', 'max:2000'],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.ten_tieu_chi' => ['required', 'string', 'max:150'],
            'criteria.*.gia_tri_tham_chieu' => ['nullable', 'string', 'max:255'],
            'criteria.*.gia_tri_thuc_te' => ['nullable', 'string', 'max:255'],
            'criteria.*.don_vi_do' => ['nullable', 'string', 'max:30'],
            'criteria.*.ket_qua' => ['required', 'string', 'in:DAT,KHONG_DAT'],
            'criteria.*.ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'lo_nong_san_id.required' => 'Vui lòng chọn lô nông sản cần kiểm định.',
            'loai_kiem_dinh.required' => 'Vui lòng chọn loại hình kiểm định.',
            'ngay_kiem_dinh.required' => 'Vui lòng chọn ngày giờ kiểm định.',
            'ket_luan.required' => 'Vui lòng đưa ra kết luận kiểm định chất lượng.',
            'criteria.required' => 'Phiếu kiểm định phải có ít nhất một tiêu chí đánh giá.',
            'criteria.*.ten_tieu_chi.required' => 'Tên tiêu chí không được để trống.',
        ]);

        if ($validated['ket_luan'] === 'KHONG_DAT' && empty($validated['huong_xu_ly'])) {
            return back()->withInput()->withErrors(['huong_xu_ly' => 'Khi kết luận KHÔNG ĐẠT, bắt buộc phải chọn hướng xử lý tiếp theo.']);
        }

        if ($validated['ket_luan'] === 'DAT'
            && collect($validated['criteria'])->contains(fn ($criterion) => $criterion['ket_qua'] !== 'DAT')) {
            return back()->withInput()->withErrors([
                'ket_luan' => 'Không thể kết luận ĐẠT khi vẫn còn chỉ tiêu có kết quả KHÔNG ĐẠT.',
            ]);
        }
        if ($validated['ket_luan'] === 'DAT'
            && collect($validated['criteria'])->contains(fn ($criterion) => trim((string) ($criterion['gia_tri_thuc_te'] ?? '')) === '')) {
            return back()->withInput()->withErrors([
                'criteria' => 'Muốn kết luận ĐẠT phải nhập giá trị thực tế cho tất cả chỉ tiêu kiểm định.',
            ]);
        }

        $requestedLot = LoNongSan::with(['sanPham', 'nguyenLieuDauVao'])->findOrFail($validated['lo_nong_san_id']);
        $isInbound = $requestedLot->loai_lo === 'NGUYEN_LIEU';
        $requiredStatuses = $isInbound
            ? ['CHO_KIEM_DINH']
            : ['CHO_KIEM_DINH_XUAT_XUONG', 'DA_DONG_GOI'];
        $allowedTypes = $isInbound ? ['NHAP_KHO', 'KIEM_DINH_LAI'] : ['TRUOC_XUAT_XUONG', 'KIEM_DINH_LAI'];

        if (!in_array($requestedLot->trang_thai, $requiredStatuses, true)) {
            return back()->withInput()->withErrors([
                'lo_nong_san_id' => 'Lô chưa đến bước kiểm định này hoặc đã được xử lý trước đó.',
            ]);
        }
        if (!in_array($validated['loai_kiem_dinh'], $allowedTypes, true)) {
            return back()->withInput()->withErrors([
                'loai_kiem_dinh' => $isInbound
                    ? 'Lô nguyên liệu chỉ được kiểm định nhập xưởng hoặc tái kiểm định.'
                    : 'Lô thành phẩm chỉ được kiểm định trước xuất xưởng hoặc tái kiểm định.',
            ]);
        }

        if ($validated['ket_luan'] === 'DAT') {
            $dossierErrors = [];
            if ($isInbound) {
                if (!DB::table('tiep_nhan_nguyen_lieu')->where('lo_nong_san_id', $requestedLot->lo_nong_san_id)->exists()) {
                    $dossierErrors[] = 'chưa có phiếu tiếp nhận và khối lượng thực nhận';
                }
                if (!$requestedLot->mua_vu_id && !$requestedLot->to_chuc_nguon_id) {
                    $dossierErrors[] = 'chưa xác định mùa vụ hoặc tổ chức nguồn';
                }
            } else {
                if ($requestedLot->nguyenLieuDauVao->isEmpty()) {
                    $dossierErrors[] = 'chưa liên kết lô nguyên liệu đầu vào';
                }
                if (!app(\App\Services\Gs1DigitalLinkService::class)->validGtin($requestedLot->sanPham?->gtin)
                    || empty($requestedLot->digital_link)) {
                    $dossierErrors[] = 'chưa có GTIN/Digital Link hợp lệ';
                }
                $packedQuantity = (float) $requestedLot->chiTietDongGoi()->sum('so_luong');
                if ($packedQuantity + 0.0001 < (float) $requestedLot->so_luong) {
                    $dossierErrors[] = 'chưa đóng gói hết khối lượng thành phẩm';
                }
            }

            if ($dossierErrors !== []) {
                return back()->withInput()->withErrors([
                    'ket_luan' => 'Không thể kết luận ĐẠT vì hồ sơ nguồn gốc chưa hoàn chỉnh: '.implode('; ', $dossierErrors).'.',
                ]);
            }
        }

        $hasPriorInspection = $requestedLot->phieuKiemDinh()->exists();
        if ($validated['loai_kiem_dinh'] === 'KIEM_DINH_LAI' && !$hasPriorInspection) {
            return back()->withInput()->withErrors([
                'loai_kiem_dinh' => 'Chỉ chọn tái kiểm định khi lô đã có phiếu kiểm định trước đó.',
            ]);
        }
        if ($validated['loai_kiem_dinh'] !== 'KIEM_DINH_LAI' && $hasPriorInspection) {
            return back()->withInput()->withErrors([
                'loai_kiem_dinh' => 'Lô đã có phiếu kiểm định; lần đánh giá tiếp theo phải chọn Tái kiểm định.',
            ]);
        }

        $userId = auth()->id();
        $ticket = null;

        DB::transaction(function () use ($validated, $userId, &$ticket) {
            $lot = LoNongSan::lockForUpdate()->findOrFail($validated['lo_nong_san_id']);
            $requiredStatuses = $lot->loai_lo === 'NGUYEN_LIEU'
                ? ['CHO_KIEM_DINH']
                : ['CHO_KIEM_DINH_XUAT_XUONG', 'DA_DONG_GOI'];
            if (!in_array($lot->trang_thai, $requiredStatuses, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lo_nong_san_id' => 'Trạng thái lô đã thay đổi. Vui lòng tải lại danh sách kiểm định.',
                ]);
            }

            // Chuẩn hóa từng lô cũ tại thời điểm QC thao tác, không cập nhật hàng loạt dữ liệu lịch sử.
            if ($lot->loai_lo === 'THANH_PHAM' && $lot->trang_thai === 'DA_DONG_GOI') {
                $packed = (float) $lot->chiTietDongGoi()->sum('so_luong');
                if ($packed + 0.0001 < (float) $lot->so_luong) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'lo_nong_san_id' => 'Lô cũ chưa được đóng gói hết nên chưa thể kiểm định xuất xưởng.',
                    ]);
                }
                $lot = app(\App\Services\LotStatusService::class)->transition(
                    $lot,
                    'CHO_KIEM_DINH_XUAT_XUONG',
                    $userId,
                    'Chuẩn hóa trạng thái lô đóng gói cũ trước khi kiểm định xuất xưởng'
                );
            }
            $oldStatus = $lot->trang_thai;

            // Generate unique ticket code
            $maPhieu = PhieuKiemDinh::generateMaPhieu();

            $ticket = PhieuKiemDinh::create([
                'ma_phieu' => $maPhieu,
                'lo_nong_san_id' => $lot->lo_nong_san_id,
                'nguoi_kiem_dinh_id' => $userId,
                'loai_kiem_dinh' => $validated['loai_kiem_dinh'],
                'ngay_kiem_dinh' => Carbon::parse($validated['ngay_kiem_dinh']),
                'ket_luan' => $validated['ket_luan'],
                'ghi_chu' => $validated['ghi_chu'] ?? null,
                'huong_xu_ly' => ($validated['ket_luan'] === 'KHONG_DAT') ? ($validated['huong_xu_ly'] ?? null) : null,
                'ghi_chu_loi' => ($validated['ket_luan'] === 'KHONG_DAT') ? ($validated['ghi_chu_loi'] ?? null) : null,
            ]);

            // Save criteria details
            foreach ($validated['criteria'] as $row) {
                ChiTietKetQuaKiemDinh::create([
                    'phieu_id' => $ticket->phieu_id,
                    'ten_tieu_chi' => trim($row['ten_tieu_chi']),
                    'gia_tri_tham_chieu' => trim($row['gia_tri_tham_chieu'] ?? ''),
                    'gia_tri_thuc_te' => trim($row['gia_tri_thuc_te'] ?? ''),
                    'don_vi_do' => trim($row['don_vi_do'] ?? ''),
                    'ket_qua' => $row['ket_qua'],
                    'ghi_chu' => trim($row['ghi_chu'] ?? ''),
                ]);
            }

            // Determine new status for the lot
            $newStatus = $oldStatus;
            $reason = "Kết luận phiếu kiểm định {$maPhieu}: " . PhieuKiemDinh::KET_LUAN_LABELS[$ticket->ket_luan];

            if ($ticket->ket_luan === 'DAT') {
                if ($lot->loai_lo === 'NGUYEN_LIEU') {
                    // Raw lot passed inspection -> ready for sorting / processing
                    $newStatus = 'CHO_SO_CHE';
                    $reason = "Đạt kiểm định chất lượng nhập kho, sẵn sàng đưa vào sơ chế";
                } else {
                    // Thành phẩm chỉ được duyệt xuất sau khi đã đóng gói hoàn tất.
                    $newStatus = 'DAT_KIEM_DINH';
                    $reason = "Đạt kiểm định chất lượng trước xuất xưởng, được phép nhập kho và lập phiếu giao hàng";
                }
            } elseif ($ticket->ket_luan === 'KHONG_DAT') {
                if ($ticket->huong_xu_ly === 'TRA_NHA_CUNG_CAP') {
                    $newStatus = $lot->loai_lo === 'NGUYEN_LIEU' ? 'TRA_NHA_CUNG_CAP' : 'THU_HOI';
                    $reason = $lot->loai_lo === 'NGUYEN_LIEU'
                        ? "Không đạt kiểm định: Trả lại nhà cung cấp/vùng trồng. " . ($ticket->ghi_chu_loi ?? '')
                        : "Không đạt kiểm định xuất xưởng: Loại khỏi luồng phân phối. " . ($ticket->ghi_chu_loi ?? '');
                } elseif ($ticket->huong_xu_ly === 'THU_HOI_LO') {
                    $newStatus = 'THU_HOI';
                    $reason = "Không đạt kiểm định: Thu hồi và tiêu hủy an toàn. " . ($ticket->ghi_chu_loi ?? '');
                } else {
                    $newStatus = $lot->loai_lo === 'NGUYEN_LIEU'
                        ? 'CHO_KIEM_DINH'
                        : 'CHO_KIEM_DINH_XUAT_XUONG';
                    $reason = "Không đạt kiểm định: " . ($ticket->huong_xu_ly_label ?? '') . ". " . ($ticket->ghi_chu_loi ?? '');
                }
            }

            // Update lot if status changed
            if ($newStatus !== $oldStatus) {
                app(\App\Services\LotStatusService::class)->transition(
                    $lot,
                    $newStatus,
                    $userId,
                    $reason
                );
            }
        });

        return redirect()->route('qc.inspections.show', $ticket->phieu_id)
            ->with('status', "Đã tạo phiếu kiểm định {$ticket->ma_phieu} thành công và cập nhật trạng thái lô hàng.");
    }

    /**
     * Display detailed inspection certificate view.
     */
    public function show(int $id): View
    {
        $ticket = PhieuKiemDinh::with([
            'loNongSan.sanPham',
            'loNongSan.khoLanh',
            'loNongSan.muaVu.vungTrong.toChuc',
            'loNongSan.toChucNguon',
            'loNongSan.nguyenLieuDauVao.sanPham',
            'nguoiKiemDinh',
            'nguoiKhacPhuc',
            'chiTietKetQua',
        ])->findOrFail($id);

        return view('qc.inspections.show', [
            'ticket' => $ticket,
            'lot' => $ticket->loNongSan,
        ]);
    }

    /**
     * Record remediation / corrective action for a failed inspection ticket.
     */
    public function remedy(Request $request, int $id): RedirectResponse
    {
        $ticket = PhieuKiemDinh::with('loNongSan')->findOrFail($id);

        $validated = $request->validate([
            'ket_qua_khac_phuc' => ['required', 'string', 'max:2000'],
            'chuyen_trang_thai' => ['required', 'string', 'in:CHO_KIEM_DINH,CHO_KIEM_DINH_XUAT_XUONG,GIU_NGUYEN'],
        ], [
            'ket_qua_khac_phuc.required' => 'Vui lòng nhập kết quả hoặc biện pháp khắc phục đã thực hiện.',
        ]);

        $userId = auth()->id();
        $lot = $ticket->loNongSan;
        $oldStatus = $lot->trang_thai;

        $expectedPendingStatus = $lot->loai_lo === 'NGUYEN_LIEU'
            ? 'CHO_KIEM_DINH'
            : 'CHO_KIEM_DINH_XUAT_XUONG';
        if ($validated['chuyen_trang_thai'] !== 'GIU_NGUYEN'
            && $validated['chuyen_trang_thai'] !== $expectedPendingStatus) {
            return back()->withErrors([
                'chuyen_trang_thai' => 'Khắc phục chỉ đưa lô về hàng chờ tái kiểm; không được cấp kết quả đạt để bỏ qua kiểm định.',
            ]);
        }

        DB::transaction(function () use ($ticket, $lot, $validated, $userId, $oldStatus) {
            $ticket->update([
                'nguoi_khac_phuc_id' => $userId,
                'thoi_gian_khac_phuc' => now(),
                'ket_qua_khac_phuc' => $validated['ket_qua_khac_phuc'],
            ]);

            if ($validated['chuyen_trang_thai'] !== 'GIU_NGUYEN' && $validated['chuyen_trang_thai'] !== $oldStatus) {
                app(\App\Services\LotStatusService::class)->transition(
                    $lot,
                    $validated['chuyen_trang_thai'],
                    $userId,
                    "Khắc phục lỗi sau kiểm định phiếu {$ticket->ma_phieu}: " . $validated['ket_qua_khac_phuc']
                );
            }
        });

        return redirect()->route('qc.inspections.show', $ticket->phieu_id)
            ->with('status', 'Đã ghi nhận kết quả khắc phục chất lượng cho phiếu kiểm định.');
    }
}


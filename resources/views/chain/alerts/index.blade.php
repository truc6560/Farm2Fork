@extends('layouts.dashboard', ['title' => 'Trung tâm Cảnh báo Rủi ro', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Trung Tâm Cảnh Báo Rủi Ro</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Giám Sát Rủi Ro & Điều Hành Xử Lý Sự Cố Toàn Chuỗi
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Tổng hợp đa chiều các cảnh báo nhiệt độ chuỗi lạnh, sự cố chất lượng QC, chậm trễ vận chuyển và đứt quãng truy xuất
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('chain.cold-storage.index') }}" class="f2f-btn-secondary text-xs">
                Kho lạnh & IoT
            </a>
            <a href="{{ route('chain.dashboard') }}" class="f2f-btn-secondary text-xs">
                Về Dashboard
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng sự cố cần chú ý</span>
            <strong class="f2f-stat-value {{ $totalIssues > 0 ? 'text-rose-600' : 'text-stone-900' }}">
                {{ $totalIssues }}
            </strong>
            <span class="f2f-stat-hint">Cần ưu tiên xử lý</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Vi phạm nhiệt độ IoT</span>
            <strong class="f2f-stat-value {{ $unresolvedColdChain > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ $unresolvedColdChain }}
            </strong>
            <span class="f2f-stat-hint">Kho & thùng xe</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Không đạt kiểm định QC</span>
            <strong class="f2f-stat-value {{ $unresolvedQc > 0 ? 'text-amber-600' : 'text-stone-800' }}">
                {{ $unresolvedQc }}
            </strong>
            <span class="f2f-stat-hint">Chờ khắc phục lỗi</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chuyến xe quá hạn</span>
            <strong class="f2f-stat-value {{ $unresolvedTransport > 0 ? 'text-amber-600' : 'text-stone-800' }}">
                {{ $unresolvedTransport }}
            </strong>
            <span class="f2f-stat-hint">Trễ hẹn bàn giao</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đã giải quyết xong</span>
            <strong class="f2f-stat-value text-emerald-700">{{ $resolvedCount }}</strong>
            <span class="f2f-stat-hint">Đã đóng biên bản sự cố</span>
        </div>
    </div>

    {{-- Category Tabs --}}
    <div class="flex flex-wrap items-center gap-1.5 text-xs border-b border-stone-200 pb-2">
        <a href="{{ route('chain.alerts.index', ['category' => 'all']) }}" class="px-3.5 py-1.5 rounded-lg font-semibold {{ $category === 'all' ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            Tất cả sự cố ({{ $totalIssues }})
        </a>
        <a href="{{ route('chain.alerts.index', ['category' => 'cold_chain']) }}" class="px-3.5 py-1.5 rounded-lg font-semibold {{ $category === 'cold_chain' ? 'bg-rose-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            Chuỗi lạnh & Nhiệt độ ({{ $coldChainAlerts->count() }})
        </a>
        <a href="{{ route('chain.alerts.index', ['category' => 'qc']) }}" class="px-3.5 py-1.5 rounded-lg font-semibold {{ $category === 'qc' ? 'bg-amber-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            Chất lượng QC ({{ $qcAlerts->count() }})
        </a>
        <a href="{{ route('chain.alerts.index', ['category' => 'transport']) }}" class="px-3.5 py-1.5 rounded-lg font-semibold {{ $category === 'transport' ? 'bg-indigo-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            Vận chuyển chậm trễ ({{ $transportAlerts->count() }})
        </a>
        <a href="{{ route('chain.alerts.index', ['category' => 'traceability']) }}" class="px-3.5 py-1.5 rounded-lg font-semibold {{ $category === 'traceability' ? 'bg-stone-800 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
            Đứt quãng truy xuất ({{ $gapAlerts->count() }})
        </a>
    </div>

    {{-- CATEGORY 1: COLD CHAIN ALERTS --}}
    @if($category === 'all' || $category === 'cold_chain')
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700">
                        1. Cảnh Báo Chuỗi Lạnh & Vi Phạm Nhiệt Độ Cảm Biến IoT
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Các trường hợp nhiệt độ vượt ngưỡng hoặc mất kết nối cảm biến</p>
                </div>
                <span class="f2f-badge-danger text-xs">{{ $coldChainAlerts->where('trang_thai', 'CHUA_XU_LY')->count() }} chưa xử lý</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Thời gian</th>
                            <th class="py-2.5 px-3">Loại vi phạm</th>
                            <th class="py-2.5 px-3">Vị trí (Kho/Xe)</th>
                            <th class="py-2.5 px-3">Giá trị đo vs Ngưỡng</th>
                            <th class="py-2.5 px-3">Mức độ rủi ro</th>
                            <th class="py-2.5 px-3">Trạng thái</th>
                            <th class="py-2.5 px-3">Khắc phục</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($coldChainAlerts as $alert)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-2.5 px-3 font-mono text-stone-500">
                                    {{ $alert->thoi_gian_phat_hien ? $alert->thoi_gian_phat_hien->format('d/m/Y H:i') : '' }}
                                </td>
                                <td class="py-2.5 px-3 font-bold text-stone-900">
                                    {{ $alert->loai_canh_bao_label }}
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $alert->thietBi?->khoLanh?->ten_kho ?? ($alert->chuyenVanChuyen?->ma_chuyen ?? 'Thiết bị ' . ($alert->thietBi?->ma_thiet_bi ?? '')) }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-rose-700">
                                    {{ $alert->gia_tri_thuc_te }}°C (Chuẩn: {{ $alert->nguong_tai_thoi_diem }}°C)
                                </td>
                                <td class="py-2.5 px-3">
                                    @if(in_array($alert->loai_canh_bao, ['NHIET_DO_CAO', 'NHIET_DO_THAP']))
                                        <span class="text-rose-700 font-bold">Cao (Nguy hiểm)</span>
                                    @else
                                        <span class="text-amber-700 font-semibold">Trung bình</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="{{ $alert->getTrangThaiBadgeClass() }} text-[11px]">
                                        {{ $alert->trang_thai_label }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($alert->hanh_dong_khac_phuc)
                                        <div class="font-medium text-stone-800">{{ $alert->hanh_dong_khac_phuc }}</div>
                                        @if($alert->ket_qua_khac_phuc)
                                            <div class="text-[11px] text-emerald-700">{{ $alert->ket_qua_khac_phuc }}</div>
                                        @endif
                                    @else
                                        <span class="text-stone-400 italic text-[11px]">Chưa tiếp nhận</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-stone-500">
                                    Không có cảnh báo chuỗi lạnh nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- CATEGORY 2: QC FAILURES --}}
    @if($category === 'all' || $category === 'qc')
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-amber-800">
                        2. Sự Cố Kiểm Định Chất Lượng Không Đạt Chuẩn
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Lô nông sản bị từ chối kiểm định chất lượng hoặc chờ xử lý khắc phục</p>
                </div>
                <span class="f2f-badge-warning text-xs">{{ $qcAlerts->whereNull('thoi_gian_khac_phuc')->count() }} lô chưa xử lý</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Mã phiếu QC</th>
                            <th class="py-2.5 px-3">Mã lô nông sản</th>
                            <th class="py-2.5 px-3">Sản phẩm</th>
                            <th class="py-2.5 px-3">Hướng xử lý đề xuất</th>
                            <th class="py-2.5 px-3">Mô tả vi phạm</th>
                            <th class="py-2.5 px-3">Trạng thái khắc phục</th>
                            <th class="py-2.5 px-3 text-right">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($qcAlerts as $ticket)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-stone-900">
                                    {{ $ticket->ma_phieu }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-stone-800">
                                    {{ $ticket->loNongSan?->ma_lo ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $ticket->loNongSan?->sanPham?->ten_san_pham ?? '' }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-rose-700">
                                    {{ $ticket->huong_xu_ly_label ?? 'Chưa xác định' }}
                                </td>
                                <td class="py-2.5 px-3 text-stone-600 italic">
                                    {{ $ticket->ghi_chu_loi ?: 'Không đạt chỉ tiêu kiểm định' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($ticket->thoi_gian_khac_phuc)
                                        <span class="f2f-badge-success text-[11px]">Đã khắc phục: {{ $ticket->ket_qua_khac_phuc }}</span>
                                    @else
                                        <span class="f2f-badge-danger text-[11px]">Chưa giải quyết</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <a href="{{ route('chain.lots.show', $ticket->lo_nong_san_id) }}" class="f2f-btn-secondary text-[11px] py-1 px-2">
                                        Xem lô
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-stone-500">
                                    Không có sự cố kiểm định chất lượng nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- CATEGORY 3: TRANSPORT DELAYS --}}
    @if($category === 'all' || $category === 'transport')
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-indigo-800">
                        3. Chuyến Xe Vận Chuyển Chậm Trễ Tiến Độ
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Các chuyến xe đã quá thời gian dự kiến giao hàng nhưng chưa hoàn thành</p>
                </div>
                <span class="f2f-badge-warning text-xs">{{ $transportAlerts->count() }} chuyến quá hạn</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Mã chuyến</th>
                            <th class="py-2.5 px-3">Phương tiện</th>
                            <th class="py-2.5 px-3">Tài xế & SĐT</th>
                            <th class="py-2.5 px-3">Lộ trình</th>
                            <th class="py-2.5 px-3">Thời gian dự kiến</th>
                            <th class="py-2.5 px-3">Thời gian trễ</th>
                            <th class="py-2.5 px-3">Trạng thái chuyến</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($transportAlerts as $tr)
                            @php
                                $delayHours = now()->diffInHours($tr->thoi_gian_giao_du_kien);
                            @endphp
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-stone-900">
                                    {{ $tr->ma_chuyen }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-stone-800">
                                    {{ $tr->phuongTien?->bien_so ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $tr->taiXe?->ho_ten ?? 'N/A' }} ({{ $tr->taiXe?->sdt ?? 'Không có SĐT' }})
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $tr->diem_di }} → {{ $tr->diem_den }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-stone-700">
                                    {{ $tr->thoi_gian_giao_du_kien ? $tr->thoi_gian_giao_du_kien->format('d/m/Y H:i') : '' }}
                                </td>
                                <td class="py-2.5 px-3 font-bold text-rose-700">
                                    Quá hạn ~{{ $delayHours }} giờ
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="f2f-badge-warning text-[11px]">{{ $tr->trang_thai }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-stone-500">
                                    Không có chuyến xe nào bị chậm trễ lịch trình.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- CATEGORY 4: TRACEABILITY INTEGRITY GAPS --}}
    @if($category === 'all' || $category === 'traceability')
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        4. Dấu Hiệu Đứt Quãng Chuỗi Truy Xuất Nguồn Gốc
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Lô nông sản thiếu mắt xích liên kết cần đối chiếu hồ sơ xác nhận</p>
                </div>
                <span class="f2f-badge-neutral text-xs">{{ $gapAlerts->count() }} lô</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Mã lô</th>
                            <th class="py-2.5 px-3">Loại lô</th>
                            <th class="py-2.5 px-3">Sản phẩm</th>
                            <th class="py-2.5 px-3">Đơn vị tạo</th>
                            <th class="py-2.5 px-3">Vấn đề phát hiện</th>
                            <th class="py-2.5 px-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($gapAlerts as $gap)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-stone-900">
                                    {{ $gap->ma_lo }}
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $gap->loai_lo }}
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $gap->sanPham?->ten_san_pham ?? '' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    {{ $gap->toChucNguon?->ten_to_chuc ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3 text-amber-800 font-medium">
                                    @if($gap->loai_lo === 'THANH_PHAM')
                                        Thành phẩm chưa có liên kết lô nguyên liệu đầu vào trong hệ thống
                                    @else
                                        Lô nguyên liệu chưa gắn mùa vụ canh tác
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <a href="{{ route('chain.lots.show', $gap->lo_nong_san_id) }}" class="f2f-btn-secondary text-[11px] py-1 px-2">
                                        Kiểm tra
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-stone-500">
                                    Toàn bộ các lô nông sản đều có dữ liệu liên kết mắt xích đầy đủ.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection


@extends('layouts.dashboard', ['title' => 'Tổng quan Quản lý Chuỗi Cung ứng', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Trung tâm Điều hành</span>
                <span>/</span>
                <span class="text-stone-800">Tổng quan Chuỗi Lạnh</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Bảng Điều hành Giám sát Chuỗi Cung ứng & Truy xuất Nguồn gốc
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Giám sát toàn diện trạng thái lô nông sản từ vùng trồng, sơ chế, kiểm định QC, lưu kho lạnh đến giao nhận
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('chain.lots.index') }}" class="f2f-btn-secondary text-xs">
                Giám sát lô hàng
            </a>
            <a href="{{ route('chain.alerts.index') }}" class="f2f-btn-secondary text-xs">
                Trung tâm cảnh báo
            </a>
            <a href="{{ route('chain.reports.index') }}" class="f2f-btn-primary text-xs">
                Báo cáo quản trị
            </a>
        </div>
    </div>

    {{-- Executive Summary KPI Cards (8 Key Indicators) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số lô nông sản</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($totalLots) }}</strong>
            <span class="f2f-stat-hint">Gồm nguyên liệu & thành phẩm</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Lô đang hoạt động</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($activeLots) }}</strong>
            <span class="f2f-stat-hint">Đang lưu thông trong chuỗi</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chờ kiểm định QC</span>
            <strong class="f2f-stat-value {{ $pendingQcLots > 0 ? 'text-amber-600' : 'text-stone-800' }}">
                {{ number_format($pendingQcLots) }}
            </strong>
            <span class="f2f-stat-hint">Cần lấy mẫu và kiểm tra</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Lưu trữ kho lạnh</span>
            <strong class="f2f-stat-value text-sky-700">{{ number_format($inStorageLots) }}</strong>
            <span class="f2f-stat-hint">Bảo quản tại hệ thống kho</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang vận chuyển</span>
            <strong class="f2f-stat-value text-indigo-700">{{ number_format($inTransitLots) }}</strong>
            <span class="f2f-stat-hint">Trên các chuyến xe lạnh</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tạm giữ / Sự cố</span>
            <strong class="f2f-stat-value {{ $problemLots > 0 ? 'text-rose-600' : 'text-stone-800' }}">
                {{ number_format($problemLots) }}
            </strong>
            <span class="f2f-stat-hint">Cần xử lý hoặc khắc phục</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cảnh báo chưa xử lý</span>
            <strong class="f2f-stat-value {{ $unresolvedAlerts > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ number_format($unresolvedAlerts) }}
            </strong>
            <span class="f2f-stat-hint">{{ $inProgressAlerts }} cảnh báo đang xử lý</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chuyến xe lăn bánh</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($activeTrips) }}</strong>
            <span class="f2f-stat-hint {{ $delayedTrips > 0 ? 'text-amber-700 font-semibold' : '' }}">
                {{ $delayedTrips > 0 ? $delayedTrips . ' chuyến quá hạn dự kiến' : 'Đúng tiến độ hành trình' }}
            </span>
        </div>
    </div>

    {{-- SECTION: Multi-Stage Supply Chain Pipeline --}}
    <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                    Tiến Trình Phân Bổ Lô Hàng Xuyên Suốt Chuỗi Cung Ứng
                </h2>
                <p class="text-xs text-stone-500 mt-0.5">Số lượng lô hàng thực tế ghi nhận tại từng công đoạn xử lý</p>
            </div>
            <a href="{{ route('chain.lots.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">
                Xem chi tiết danh sách lô
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            @foreach($stages as $key => $st)
                <div class="p-3.5 rounded-lg border {{ $key === 'SU_CO' && $st['count'] > 0 ? 'border-rose-200 bg-rose-50/50' : 'border-stone-200 bg-stone-50/70' }} flex flex-col justify-between">
                    <div>
                        <span class="text-[11px] font-semibold text-stone-500 uppercase tracking-wider block">
                            {{ $st['status'] }}
                        </span>
                        <strong class="text-xs font-bold text-stone-800 mt-1 block">
                            {{ $st['name'] }}
                        </strong>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-2xl font-black {{ $key === 'SU_CO' && $st['count'] > 0 ? 'text-rose-600' : 'text-stone-900' }}">
                            {{ $st['count'] }}
                        </span>
                        <span class="text-[11px] text-stone-500">lô</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Visual Distributions (2 columns) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Product breakdown --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Phân Bổ Lô Theo Nhóm Nông Sản
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Cơ cấu mặt hàng và tổng sản lượng lưu thông</p>
                </div>
                <span class="text-xs font-semibold text-stone-600">Top mặt hàng</span>
            </div>

            <div class="space-y-3.5">
                @forelse($lotsByProduct as $p)
                    @php
                        $percentage = $totalLots > 0 ? round(($p->lo_nong_san_count / $totalLots) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-stone-800">{{ $p->ten_san_pham }} ({{ $p->ma_san_pham }})</span>
                            <span class="text-stone-600">
                                <strong>{{ $p->lo_nong_san_count }} lô</strong> · {{ number_format($p->lo_nong_san_sum_so_luong ?? 0, 1) }} kg ({{ $percentage }}%)
                            </span>
                        </div>
                        <div class="w-full bg-stone-100 rounded-full h-2">
                            <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-stone-500">
                        Chưa ghi nhận dữ liệu nông sản trong hệ thống.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Alert distribution by type --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Cảnh Báo Chuỗi Lạnh Theo Loại Sự Cố
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Phân loại vi phạm điều kiện nhiệt độ và kết nối</p>
                </div>
                <a href="{{ route('chain.alerts.index') }}" class="text-xs font-semibold text-rose-600 hover:underline">
                    Xem trung tâm cảnh báo
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50">
                    <span class="text-[11px] font-semibold text-stone-500 uppercase tracking-wider block">Nhiệt độ cao</span>
                    <strong class="text-2xl font-black text-rose-600 mt-1 block">{{ $alertsByType['NHIET_DO_CAO'] }}</strong>
                    <span class="text-[11px] text-stone-500 mt-1 block">Quá ngưỡng bảo quản tối đa</span>
                </div>

                <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50">
                    <span class="text-[11px] font-semibold text-stone-500 uppercase tracking-wider block">Nhiệt độ thấp</span>
                    <strong class="text-2xl font-black text-blue-600 mt-1 block">{{ $alertsByType['NHIET_DO_THAP'] }}</strong>
                    <span class="text-[11px] text-stone-500 mt-1 block">Dưới ngưỡng (nguy cơ đông đá)</span>
                </div>

                <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50">
                    <span class="text-[11px] font-semibold text-stone-500 uppercase tracking-wider block">Độ ẩm vượt ngưỡng</span>
                    <strong class="text-2xl font-black text-amber-600 mt-1 block">{{ $alertsByType['DO_AM_VUOT_NGUONG'] }}</strong>
                    <span class="text-[11px] text-stone-500 mt-1 block">Ẩm mốc hoặc khô héo</span>
                </div>

                <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50">
                    <span class="text-[11px] font-semibold text-stone-500 uppercase tracking-wider block">Mất tín hiệu IoT</span>
                    <strong class="text-2xl font-black text-stone-700 mt-1 block">{{ $alertsByType['MAT_TIN_HIEU'] }}</strong>
                    <span class="text-[11px] text-stone-500 mt-1 block">Cảm biến ngắt kết nối</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Operational Watchlists --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Active Alerts requiring urgent response --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700">
                        Cảnh Báo Chuỗi Lạnh Chưa Khắc Phục
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Các điểm vi phạm nhiệt độ cần bộ phận kho/vận chuyển xử lý ngay</p>
                </div>
                <span class="f2f-badge-danger text-xs">{{ $unresolvedAlerts }} sự cố</span>
            </div>

            <div class="space-y-2.5">
                @forelse($recentAlerts as $alert)
                    <div class="p-3 rounded-lg border border-rose-100 bg-rose-50/40 text-xs flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="font-bold text-stone-900">
                                {{ $alert->loai_canh_bao_label }}
                            </div>
                            <div class="text-stone-600">
                                Vị trí: 
                                <strong>
                                    {{ $alert->thietBi?->khoLanh?->ten_kho ?? ($alert->chuyenVanChuyen?->ma_chuyen ?? 'Thiết bị IoT ' . ($alert->thietBi?->ma_thiet_bi ?? '')) }}
                                </strong>
                                · Đo: <span class="font-semibold text-rose-700">{{ $alert->gia_tri_thuc_te }}°C</span> (Ngưỡng: {{ $alert->nguong_tai_thoi_diem }}°C)
                            </div>
                            <div class="text-[11px] text-stone-500">
                                Phát hiện lúc: {{ $alert->thoi_gian_phat_hien ? $alert->thoi_gian_phat_hien->format('d/m/Y H:i') : 'N/A' }}
                            </div>
                        </div>
                        <div>
                            <span class="{{ $alert->getTrangThaiBadgeClass() }} text-[11px]">
                                {{ $alert->trang_thai_label }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-stone-500">
                        Chuỗi lạnh đang hoạt động ổn định, không có cảnh báo chưa xử lý.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- QC Rejections Watchlist --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-amber-800">
                        Lô Nông Sản Không Đạt Kiểm Định QC
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Các lô chất lượng chưa đạt tiêu chuẩn đang chờ kết quả khắc phục</p>
                </div>
                <span class="f2f-badge-warning text-xs">{{ $qcRejections->count() }} lô</span>
            </div>

            <div class="space-y-2.5">
                @forelse($qcRejections as $ticket)
                    <div class="p-3 rounded-lg border border-amber-100 bg-amber-50/40 text-xs flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="font-bold text-stone-900">
                                Lô: {{ $ticket->loNongSan?->ma_lo ?? 'N/A' }} · {{ $ticket->loNongSan?->sanPham?->ten_san_pham ?? '' }}
                            </div>
                            <div class="text-stone-600">
                                Phiếu kiểm định: <strong>{{ $ticket->ma_phieu }}</strong> · Hướng xử lý: <strong>{{ $ticket->huong_xu_ly_label ?? 'Chờ phân loại' }}</strong>
                            </div>
                            @if($ticket->ghi_chu_loi)
                                <div class="text-stone-700 italic">
                                    Lỗi: "{{ $ticket->ghi_chu_loi }}"
                                </div>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('chain.lots.show', $ticket->lo_nong_san_id) }}" class="f2f-btn-secondary text-[11px] py-1 px-2">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-stone-500">
                        Không có lô nông sản nào đang bị từ chối kiểm định.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Traceability Gap Alerts --}}
    @if($gappedLots->isNotEmpty())
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="f2f-badge-warning text-xs">Cảnh báo tính toàn vẹn chuỗi</span>
                    <h2 class="text-sm font-bold text-amber-900">
                        Phát Hiện Lô Hàng Có Dấu Hiệu Đứt Quãng Thông Tin Truy Xuất
                    </h2>
                </div>
                <a href="{{ route('chain.lots.index', ['gap_only' => 1]) }}" class="text-xs font-semibold text-amber-800 hover:underline">
                    Xem tất cả lô thiếu liên kết
                </a>
            </div>
            <p class="text-xs text-amber-800">
                Hệ thống phát hiện một số lô hàng thiếu thông tin nguồn gốc (ví dụ: thành phẩm nhưng không liên kết nguyên liệu đầu vào, hoặc lô thiếu dữ liệu kiểm định). Quản lý chuỗi cần lưu ý đối chiếu hồ sơ:
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                @foreach($gappedLots as $gapLot)
                    <div class="bg-white p-3 rounded-lg border border-amber-200 text-xs">
                        <div class="font-bold text-stone-900">{{ $gapLot->ma_lo }}</div>
                        <div class="text-stone-600 mt-0.5">{{ $gapLot->sanPham?->ten_san_pham ?? 'Chưa phân loại' }} ({{ $gapLot->loai_lo }})</div>
                        <div class="mt-2 text-stone-500 text-[11px]">
                            @if($gapLot->loai_lo === 'THANH_PHAM')
                                Chưa liên kết lô nguyên liệu đầu vào
                            @else
                                Chưa gắn thông tin mùa vụ sản xuất
                            @endif
                        </div>
                        <a href="{{ route('chain.lots.show', $gapLot->lo_nong_san_id) }}" class="mt-2 inline-block font-semibold text-emerald-700 hover:underline text-[11px]">
                            Kiểm tra lô này
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection


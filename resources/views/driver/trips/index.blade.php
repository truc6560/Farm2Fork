@extends('layouts.dashboard', ['title' => 'Chuyến vận chuyển', 'breadcrumb' => 'Farm2Fork · Tài xế nội bộ'])

@section('content')
<div class="space-y-6">
    {{-- Header & Actions --}}
    <div class="f2f-driver-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Vận tải chuỗi lạnh</span>
                <span>/</span>
                <span class="text-stone-800">Chuyến vận chuyển</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Quản lý Chuyến Vận Chuyển Chuỗi Lạnh
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Theo dõi lịch trình điều phối, trạng thái phương tiện thùng lạnh và điều hành chuyến xe lăn bánh an toàn
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('driver.journey.index') }}" class="f2f-btn-secondary text-xs">
                Ghi nhận hành trình
            </a>
            <a href="{{ route('driver.delivery.index') }}" class="f2f-btn-secondary text-xs">
                Giao nhận hàng hóa
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="f2f-alert f2f-alert-success">
            {{ session('status') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="f2f-alert f2f-alert-warning">
            {{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="f2f-alert f2f-alert-error">
            <ul class="list-disc list-inside text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Fast Stats Cards --}}
    <div class="f2f-stat-grid f2f-driver-stats">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chuyến đang vận chuyển</span>
            <strong class="f2f-stat-value text-emerald-800">{{ $runningCount }}</strong>
            <span class="f2f-stat-hint">Đang di chuyển trên đường</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chờ khởi hành</span>
            <strong class="f2f-stat-value text-amber-700">{{ $readyCount }}</strong>
            <span class="f2f-stat-hint">Đã sẵn sàng xếp hàng & xe</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đã hoàn thành</span>
            <strong class="f2f-stat-value text-stone-800">{{ $completedCount }}</strong>
            <span class="f2f-stat-hint">Giao hàng an toàn thành công</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng lượt phân công</span>
            <strong class="f2f-stat-value text-stone-800">{{ $totalCount }}</strong>
            <span class="f2f-stat-hint">Hồ sơ chuyến xe hệ thống</span>
        </div>
    </div>

    {{-- Main Table Panel --}}
    <div class="f2f-card" style="padding:16px">
        {{-- Navigation Tabs & Filter --}}
        <div class="f2f-driver-list-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 pb-3">
            <div class="f2f-driver-tabs flex items-center gap-1.5 sm:gap-2">
                <a href="{{ route('driver.trips.index', ['status' => 'all']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'all' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Tất cả ({{ $totalCount }})
                </a>
                <a href="{{ route('driver.trips.index', ['status' => 'running']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'running' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Đang chạy ({{ $runningCount }})
                </a>
                <a href="{{ route('driver.trips.index', ['status' => 'ready']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'ready' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Chờ khởi hành ({{ $readyCount }})
                </a>
                <a href="{{ route('driver.trips.index', ['status' => 'completed']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'completed' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Đã hoàn thành ({{ $completedCount }})
                </a>
            </div>

            <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc chuyến xe" aria-controls="trip-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
            <div class="f2f-mobile-filter-panel" id="trip-filters" data-mobile-filter>
                <div class="f2f-mobile-filter-head"><h3>Bộ lọc chuyến xe</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
                <form method="GET" action="{{ route('driver.trips.index') }}" class="flex items-center gap-2 text-xs">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input class="f2f-input text-xs" type="text" name="q" value="{{ $search }}" placeholder="Tìm mã chuyến, biển số xe, điểm đến...">
                    <button type="submit" class="f2f-btn-primary text-xs" style="padding:6px 12px">Lọc</button>
                    @if($search !== '')
                        <a href="{{ route('driver.trips.index', ['status' => $status]) }}" class="f2f-btn-secondary text-xs" style="padding:6px 10px">Đặt lại</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Trips Table --}}
        <div class="f2f-table-container mt-4">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Mã chuyến xe</th>
                        <th>Phương tiện & Xe lạnh</th>
                        <th>Lộ trình (Điểm đi - Điểm đến)</th>
                        <th>Thời gian</th>
                        <th>Tiến độ giao</th>
                        <th>Trạng thái</th>
                        <th style="text-align:right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trips as $trip)
                        @php
                            $totalDels = $trip->phieuGiaoHang->count();
                            $doneDels = $trip->phieuGiaoHang->where('trang_thai', 'DA_GIAO')->count();
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('driver.trips.show', $trip->chuyen_id) }}" class="font-mono font-bold text-xs text-emerald-800 hover:underline">
                                    {{ $trip->ma_chuyen }}
                                </a>
                                @if($trip->ghi_chu)
                                    <div class="text-[11px] text-stone-500 mt-0.5 line-clamp-1">{{ $trip->ghi_chu }}</div>
                                @endif
                            </td>
                            <td>
                                @if($trip->phuongTien)
                                    <div class="text-xs font-bold text-stone-900 font-mono">{{ $trip->phuongTien->bien_so }}</div>
                                    <div class="text-[11px] text-stone-500">{{ $trip->phuongTien->loai_xe }}</div>
                                    @if($trip->phuongTien->co_he_thong_lam_lanh)
                                        <span class="inline-block mt-0.5 text-[10px] text-emerald-700 font-semibold bg-emerald-50 px-1.5 py-0.2 rounded">Thùng lạnh đạt chuẩn</span>
                                    @endif
                                @else
                                    <span class="text-xs text-stone-400">Chưa gắn xe</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-xs text-stone-800">
                                    <div class="font-medium text-stone-600 line-clamp-1">Từ: {{ $trip->diem_di ?? 'Kho phân phối' }}</div>
                                    <div class="font-bold text-stone-900 line-clamp-1 mt-0.5">Đến: {{ $trip->diem_den ?? 'Điểm giao' }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs font-mono text-stone-700">
                                    @if($trip->thoi_gian_xuat_phat)
                                        <div>Khởi hành: {{ $trip->thoi_gian_xuat_phat->format('H:i - d/m/Y') }}</div>
                                    @else
                                        <div class="text-stone-400">Dự kiến: {{ $trip->thoi_gian_giao_du_kien ? $trip->thoi_gian_giao_du_kien->format('H:i - d/m/Y') : 'Chưa xếp' }}</div>
                                    @endif
                                    @if($trip->thoi_gian_giao_thuc_te)
                                        <div class="text-emerald-700">Đến: {{ $trip->thoi_gian_giao_thuc_te->format('H:i - d/m/Y') }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-xs">
                                    <span class="font-bold text-stone-800">{{ $doneDels }}/{{ $totalDels }}</span> đơn giao
                                    @if($totalDels > 0)
                                        <div class="w-20 bg-stone-200 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ ($doneDels / $totalDels) * 100 }}%"></div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="{{ $trip->getTrangThaiBadgeClass() }}">
                                    {{ $trip->trang_thai_label }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <div class="f2f-actions-cell flex items-center justify-end gap-1.5">
                                    <a href="{{ route('driver.trips.show', $trip->chuyen_id) }}" class="f2f-btn-secondary" style="padding:4px 8px;font-size:11px">
                                        Chi tiết
                                    </a>

                                    @if(in_array($trip->trang_thai, ['SAN_SANG', 'CHUAN_BI']))
                                        <form method="POST" action="{{ route('driver.trips.start', $trip->chuyen_id) }}" onsubmit="return confirm('Xác nhận bắt đầu xuất phát chuyến xe này?')">
                                            @csrf
                                            <button type="submit" class="f2f-btn-primary" style="padding:4px 8px;font-size:11px">
                                                Xuất phát
                                            </button>
                                        </form>
                                    @elseif($trip->trang_thai === 'DANG_VAN_CHUYEN')
                                        <form method="POST" action="{{ route('driver.trips.complete', $trip->chuyen_id) }}" onsubmit="return confirm('Xác nhận đã hoàn thành toàn bộ chuyến xe và các điểm giao?')">
                                            @csrf
                                            <button type="submit" class="f2f-btn-primary" style="padding:4px 8px;font-size:11px;background-color:#047857">
                                                Hoàn thành
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="f2f-table-empty">
                                Không tìm thấy chuyến vận chuyển nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($trips->hasPages())
            <div class="mt-4 pt-3 border-t border-stone-200">
                {{ $trips->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


@extends('layouts.dashboard', ['title' => 'Giao nhận hàng hóa', 'breadcrumb' => 'Farm2Fork · Tài xế nội bộ · Giao nhận'])

@section('content')
<div class="space-y-6">
    {{-- Header & Fast Actions --}}
    <div class="f2f-driver-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Vận tải chuỗi lạnh</span>
                <span>/</span>
                <span class="text-stone-800">Giao nhận hàng hóa</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Quản lý Nghiệp vụ Giao nhận & Biên bản Bàn giao
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Bàn giao nông sản cho hệ thống siêu thị, nhà hàng, kho phân phối; chụp ảnh biên bản ký nhận và lưu vết GS1 SSCC
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('driver.trips.index') }}" class="f2f-btn-secondary text-xs">
                Chuyến vận chuyển
            </a>
            <a href="{{ route('driver.journey.index') }}" class="f2f-btn-secondary text-xs">
                Hành trình & Sự kiện
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="f2f-alert f2f-alert-success">
            {{ session('status') }}
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

    {{-- Stats Cards --}}
    <div class="f2f-stat-grid f2f-driver-stats">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cần giao nhận</span>
            <strong class="f2f-stat-value text-amber-700">{{ $pendingCount }}</strong>
            <span class="f2f-stat-hint">Đang trên đường hoặc chờ trả</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đã giao thành công</span>
            <strong class="f2f-stat-value text-emerald-800">{{ $deliveredCount }}</strong>
            <span class="f2f-stat-hint">Đã ký biên bản bàn giao</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số phiếu giao</span>
            <strong class="f2f-stat-value text-stone-800">{{ $totalDeliveries }}</strong>
            <span class="f2f-stat-hint">Hồ sơ giao hàng phân công</span>
        </div>
    </div>

    {{-- Main Filter & Deliveries Table --}}
    <div class="f2f-card" style="padding:16px">
        <div class="f2f-driver-list-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 pb-3">
            {{-- Tabs --}}
            <div class="f2f-driver-tabs flex items-center gap-1.5 sm:gap-2">
                <a href="{{ route('driver.delivery.index', ['status' => 'all', 'chuyen_id' => $selectedTripId]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'all' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Tất cả ({{ $totalDeliveries }})
                </a>
                <a href="{{ route('driver.delivery.index', ['status' => 'pending', 'chuyen_id' => $selectedTripId]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'pending' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Đang & Chờ giao ({{ $pendingCount }})
                </a>
                <a href="{{ route('driver.delivery.index', ['status' => 'delivered', 'chuyen_id' => $selectedTripId]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'delivered' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Đã giao hoàn tất ({{ $deliveredCount }})
                </a>
            </div>

            {{-- Filter Form --}}
            <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc giao nhận" aria-controls="delivery-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
            <div class="f2f-mobile-filter-panel" id="delivery-filters" data-mobile-filter>
                <div class="f2f-mobile-filter-head"><h3>Bộ lọc giao nhận</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
                <form method="GET" action="{{ route('driver.delivery.index') }}" class="flex flex-wrap items-center gap-2 text-xs">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select name="chuyen_id" class="f2f-input text-xs" onchange="this.form.submit()">
                        <option value="">-- Tất cả chuyến xe --</option>
                        @foreach($driverTrips as $trip)
                            <option value="{{ $trip->chuyen_id }}" @selected($selectedTripId == $trip->chuyen_id)>
                                {{ $trip->ma_chuyen }} ({{ $trip->phuongTien->bien_so ?? 'Xe' }})
                            </option>
                        @endforeach
                    </select>
                    <input class="f2f-input text-xs" type="text" name="q" value="{{ $search }}" placeholder="Tìm mã phiếu, người nhận, địa chỉ...">
                    <button type="submit" class="f2f-btn-primary text-xs" style="padding:6px 12px">Lọc</button>
                    @if($search !== '' || $selectedTripId)
                        <a href="{{ route('driver.delivery.index', ['status' => $status]) }}" class="f2f-btn-secondary text-xs" style="padding:6px 10px">Đặt lại</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Deliveries Table --}}
        <div class="f2f-table-container mt-4">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Mã phiếu giao</th>
                        <th>Khách hàng & Người nhận</th>
                        <th>Địa chỉ giao hàng</th>
                        <th>Chuyến xe</th>
                        <th>Kiện hàng GS1 SSCC</th>
                        <th>Yêu cầu nhiệt độ</th>
                        <th>Trạng thái</th>
                        <th style="text-align:right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $del)
                        <tr>
                            <td>
                                <a href="{{ route('driver.delivery.show', $del->phieu_giao_hang_id) }}" class="font-mono font-bold text-xs text-emerald-800 hover:underline">
                                    {{ $del->ma_phieu_giao_hang }}
                                </a>
                            </td>
                            <td>
                                <div class="text-xs font-bold text-stone-900">
                                    {{ $del->toChucNhan->ten_to_chuc ?? $del->ten_nguoi_nhan }}
                                </div>
                                <div class="text-[11px] text-stone-600">
                                    Liên hệ: {{ $del->ten_nguoi_nhan }}
                                    @if($del->sdt_nguoi_nhan)
                                        <span class="font-mono text-stone-500">({{ $del->sdt_nguoi_nhan }})</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-xs text-stone-800 line-clamp-2 max-w-[260px]">
                                    {{ $del->dia_chi_giao }}
                                </div>
                            </td>
                            <td>
                                @if($del->chuyenVanChuyen)
                                    <div class="text-xs font-mono font-semibold text-stone-800">
                                        {{ $del->chuyenVanChuyen->ma_chuyen }}
                                    </div>
                                    <div class="text-[11px] text-stone-500 font-mono">
                                        {{ $del->chuyenVanChuyen->phuongTien->bien_so ?? 'Xe' }}
                                    </div>
                                @else
                                    <span class="text-xs text-stone-400">Chưa gắn</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-xs">
                                    <strong class="text-stone-900">{{ $del->chiTietGiaoNhan->count() }}</strong> kiện hàng
                                    <div class="text-[11px] text-stone-500">
                                        Đã giao: {{ $del->chiTietGiaoNhan->where('trang_thai_giao_nhan', 'DA_GIAO')->count() }} kiện
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs font-mono text-emerald-800 font-semibold">
                                    {{ $del->yeu_cau_nhiet_do_min !== null ? $del->yeu_cau_nhiet_do_min . '°C' : '0°C' }}
                                    -
                                    {{ $del->yeu_cau_nhiet_do_max !== null ? $del->yeu_cau_nhiet_do_max . '°C' : '4°C' }}
                                </div>
                            </td>
                            <td>
                                <span class="{{ $del->getTrangThaiBadgeClass() }}">
                                    {{ $del->trang_thai_label }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <a href="{{ route('driver.delivery.show', $del->phieu_giao_hang_id) }}" class="f2f-btn-primary f2f-mobile-full-action" style="padding:4px 10px;font-size:11px">
                                    {{ $del->trang_thai === 'DA_GIAO' ? 'Xem biên bản' : 'Ký nhận bàn giao' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="f2f-table-empty">
                                Không có phiếu giao hàng nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($deliveries->hasPages())
            <div class="mt-4 pt-3 border-t border-stone-200">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


@extends('layouts.dashboard', ['title' => 'Giám sát Vận chuyển & Giao nhận', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Giám sát Vận Chuyển Lạnh</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Giám Sát Hành Trình Đội Xe & Giao Nhận Nông Sản
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Theo dõi thời gian thực vị trí các chuyến xe lạnh, nhiệt độ thùng xe, lộ trình và tiến độ bàn giao hàng hóa
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('chain.transport.create') }}" class="f2f-btn-primary text-xs">
                + Lập chuyến & phân công
            </a>
            <a href="{{ route('chain.reports.index') }}" class="f2f-btn-secondary text-xs">
                Báo cáo giao hàng
            </a>
            <a href="{{ route('chain.dashboard') }}" class="f2f-btn-secondary text-xs">
                Về Dashboard
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số chuyến xe</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($totalTrips) }}</strong>
            <span class="f2f-stat-hint">Toàn bộ hành trình</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang lăn bánh</span>
            <strong class="f2f-stat-value text-indigo-700">{{ number_format($runningCount) }}</strong>
            <span class="f2f-stat-hint">Đang di chuyển trên đường</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chờ khởi hành</span>
            <strong class="f2f-stat-value text-stone-800">{{ number_format($readyCount) }}</strong>
            <span class="f2f-stat-hint">Sẵn sàng xuất phát</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Chậm tiến độ / Quá hạn</span>
            <strong class="f2f-stat-value {{ $delayedCount > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ number_format($delayedCount) }}
            </strong>
            <span class="f2f-stat-hint">Cần liên hệ tài xế</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đội xe lạnh hoạt động</span>
            <strong class="f2f-stat-value text-emerald-700">{{ $activeVehicles }} / {{ $totalVehicles }}</strong>
            <span class="f2f-stat-hint">Xe trang bị thùng bảo ôn</span>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        {{-- Status Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <a href="{{ route('chain.transport.index', ['status' => 'all', 'q' => $search]) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ $status === 'all' ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                Tất cả ({{ $totalTrips }})
            </a>
            <a href="{{ route('chain.transport.index', ['status' => 'running', 'q' => $search]) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ $status === 'running' ? 'bg-indigo-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                Đang vận chuyển ({{ $runningCount }})
            </a>
            <a href="{{ route('chain.transport.index', ['status' => 'ready', 'q' => $search]) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ $status === 'ready' ? 'bg-stone-800 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                Chờ xuất phát ({{ $readyCount }})
            </a>
            <a href="{{ route('chain.transport.index', ['status' => 'delayed', 'q' => $search]) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ $status === 'delayed' ? 'bg-rose-600 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                Trễ hạn ({{ $delayedCount }})
            </a>
            <a href="{{ route('chain.transport.index', ['status' => 'completed', 'q' => $search]) }}" class="px-3 py-1.5 rounded-lg font-semibold {{ $status === 'completed' ? 'bg-emerald-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                Đã hoàn thành ({{ $completedCount }})
            </a>
        </div>

        {{-- Search Input --}}
        <form method="GET" action="{{ route('chain.transport.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Mã chuyến, biển số, tài xế..." class="f2f-input text-xs w-48 sm:w-60">
            <button type="submit" class="f2f-btn-primary text-xs">
                Tìm kiếm
            </button>
        </form>
    </div>

    {{-- Trips Table --}}
    <div class="bg-white rounded-xl border border-stone-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Mã chuyến xe</th>
                        <th class="py-3 px-4">Phương tiện & Thùng lạnh</th>
                        <th class="py-3 px-4">Tài xế phụ trách</th>
                        <th class="py-3 px-4">Lộ trình vận chuyển</th>
                        <th class="py-3 px-4">Lịch trình & Tiến độ</th>
                        <th class="py-3 px-4">Giao nhận đơn hàng</th>
                        <th class="py-3 px-4">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-stone-700">
                    @forelse($trips as $trip)
                        <tr class="hover:bg-stone-50/70 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-stone-900 font-mono text-sm">
                                    {{ $trip->ma_chuyen }}
                                </div>
                                <div class="text-[11px] text-stone-500 mt-0.5">
                                    {{ $trip->suKienHanhTrinh->count() }} mốc hành trình
                                </div>
                                @if($trip->nguoiLap)
                                    <div class="text-[11px] text-stone-500 mt-0.5">Điều phối: {{ $trip->nguoiLap->ho_ten }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-semibold text-stone-800">
                                    {{ $trip->phuongTien?->bien_so ?? 'Chưa gán xe' }}
                                </div>
                                <div class="text-[11px] text-stone-500">
                                    {{ $trip->phuongTien?->loai_xe ?? 'Xe tải' }} · Tải trọng {{ $trip->phuongTien?->tai_trong }} {{ $trip->phuongTien?->don_vi_tai_trong }}
                                </div>
                                @if($trip->latest_reading)
                                    <div class="mt-1 font-bold text-emerald-700 text-[11px]">
                                        Nhiệt độ thùng: {{ $trip->latest_reading->nhiet_do }}°C
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-medium text-stone-800">
                                    {{ $trip->taiXe?->ho_ten ?? 'Chưa phân công' }}
                                </div>
                                @if($trip->taiXe?->sdt)
                                    <div class="text-[11px] text-stone-500">SĐT: {{ $trip->taiXe->sdt }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="text-stone-800">
                                    <span class="text-stone-500 text-[11px] block">Từ:</span>
                                    <strong>{{ $trip->diem_di ?: 'Kho xuất phát' }}</strong>
                                </div>
                                <div class="text-stone-800 mt-1">
                                    <span class="text-stone-500 text-[11px] block">Đến:</span>
                                    <strong>{{ $trip->diem_den ?: 'Điểm giao' }}</strong>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="text-stone-600">
                                    Xuất phát: {{ $trip->thoi_gian_xuat_phat ? $trip->thoi_gian_xuat_phat->format('d/m/Y H:i') : 'Chưa' }}
                                </div>
                                <div class="text-stone-600 mt-0.5">
                                    Dự kiến giao: {{ $trip->thoi_gian_giao_du_kien ? $trip->thoi_gian_giao_du_kien->format('d/m/Y H:i') : 'N/A' }}
                                </div>
                                @if($trip->is_delayed)
                                    <span class="inline-block mt-1 font-bold text-rose-700 text-[11px]">
                                        Chậm trễ so với dự kiến
                                    </span>
                                @elseif($trip->trang_thai === 'HOAN_THANH')
                                    <span class="inline-block mt-1 font-semibold text-emerald-700 text-[11px]">
                                        Đã hoàn thành lúc {{ $trip->thoi_gian_giao_thuc_te ? $trip->thoi_gian_giao_thuc_te->format('d/m/Y H:i') : '' }}
                                    </span>
                                @else
                                    <span class="inline-block mt-1 text-stone-500 text-[11px]">
                                        Đúng lịch trình
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-semibold text-stone-800">
                                    {{ $trip->delivered_pgh }} / {{ $trip->total_pgh }} phiếu giao
                                </div>
                                <div class="w-24 bg-stone-100 rounded-full h-1.5 mt-1.5">
                                    <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ $trip->progress_pct }}%"></div>
                                </div>
                                <span class="text-[11px] text-stone-500 mt-0.5 block">{{ $trip->progress_pct }}% hoàn thành</span>
                            </td>

                            <td class="py-3 px-4">
                                @if($trip->trang_thai === 'DANG_VAN_CHUYEN')
                                    <span class="f2f-badge-active text-[11px]">Đang vận chuyển</span>
                                @elseif($trip->trang_thai === 'HOAN_THANH')
                                    <span class="f2f-badge-success text-[11px]">Đã hoàn thành</span>
                                @else
                                    <span class="f2f-badge-warning text-[11px]">{{ $trip->trang_thai_label }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-stone-500">
                                Không tìm thấy chuyến vận chuyển nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trips->hasPages())
            <div class="p-4 border-t border-stone-200">
                {{ $trips->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


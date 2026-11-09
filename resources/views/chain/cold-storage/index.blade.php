@extends('layouts.dashboard', ['title' => 'Giám sát Kho Lạnh & IoT', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Giám sát Kho Lạnh & Chuỗi Lạnh</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Bảo Quản Kho Lạnh & Giám Sát Cảm Biến IoT
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Theo dõi sức chứa, nhiệt độ, độ ẩm thời gian thực tại các kho bảo quản và xử lý các cảnh báo vi phạm
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('chain.alerts.index', ['category' => 'cold_chain']) }}" class="f2f-btn-secondary text-xs">
                Xem tất cả sự cố
            </a>
            <a href="{{ route('chain.dashboard') }}" class="f2f-btn-secondary text-xs">
                Về Dashboard
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số kho lạnh</span>
            <strong class="f2f-stat-value text-stone-900">{{ $warehouses->count() }}</strong>
            <span class="f2f-stat-hint">Hệ thống kho bảo quản</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cảnh báo chưa xử lý</span>
            <strong class="f2f-stat-value {{ $unresolvedCount > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ $unresolvedCount }}
            </strong>
            <span class="f2f-stat-hint">Cần can thiệp nhiệt độ khẩn cấp</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang tiến hành xử lý</span>
            <strong class="f2f-stat-value text-amber-600">{{ $inProgressCount }}</strong>
            <span class="f2f-stat-hint">Kỹ thuật viên đang can thiệp</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đã khắc phục xong</span>
            <strong class="f2f-stat-value text-emerald-700">{{ $resolvedCount }}</strong>
            <span class="f2f-stat-hint">Khôi phục dải nhiệt độ an toàn</span>
        </div>
    </div>

    {{-- WAREHOUSE FACILITIES CARDS --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                Tình Trạng Vận Hành & Sức Chứa Các Kho Lạnh
            </h2>
            <span class="text-xs text-stone-500">Giám sát sức chứa và cảm biến gắn tại kho</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($warehouseCards as $item)
                @php
                    $wh = $item['model'];
                @endphp
                <div class="bg-white rounded-xl border {{ $item['unresolvedAlerts'] > 0 ? 'border-rose-300 ring-1 ring-rose-200' : 'border-stone-200' }} p-5 shadow-sm space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-xs font-bold text-stone-500">{{ $wh->ma_kho }}</span>
                                <h3 class="text-base font-bold text-stone-900 mt-0.5">{{ $wh->ten_kho }}</h3>
                                <div class="text-xs text-stone-500 mt-0.5">{{ $wh->dia_chi }}</div>
                            </div>
                            <div>
                                @if($item['unresolvedAlerts'] > 0)
                                    <span class="f2f-badge-danger text-[11px]">
                                        {{ $item['unresolvedAlerts'] }} cảnh báo
                                    </span>
                                @else
                                    <span class="f2f-badge-success text-[11px]">
                                        Ổn định
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Occupancy rate --}}
                        <div class="mt-4 space-y-1.5 text-xs">
                            <div class="flex justify-between text-stone-600">
                                <span>Tỷ lệ sử dụng sức chứa:</span>
                                <strong>{{ $item['occupancyRate'] }}%</strong>
                            </div>
                            <div class="w-full bg-stone-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $item['occupancyRate'] > 90 ? 'bg-amber-500' : 'bg-emerald-600' }}" style="width: {{ $item['occupancyRate'] }}%"></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-stone-500">
                                <span>Đang lưu: {{ number_format($item['totalQuantity'], 1) }} kg</span>
                                <span>Sức chứa: {{ number_format($item['capacity']) }} {{ $wh->don_vi_suc_chua }}</span>
                            </div>
                        </div>

                        {{-- Latest IoT reading --}}
                        <div class="mt-4 pt-3 border-t border-stone-100 text-xs space-y-1">
                            <span class="text-stone-500 block">Chỉ số cảm biến mới nhất:</span>
                            @if($item['latestReading'])
                                <div class="flex items-baseline gap-4 mt-1">
                                    <div>
                                        <span class="text-stone-500 text-[11px]">Nhiệt độ:</span>
                                        <strong class="text-sm font-bold text-stone-900 ml-1">{{ $item['latestReading']->nhiet_do }}°C</strong>
                                    </div>
                                    <div>
                                        <span class="text-stone-500 text-[11px]">Độ ẩm:</span>
                                        <strong class="text-sm font-bold text-stone-900 ml-1">{{ $item['latestReading']->do_am }}%</strong>
                                    </div>
                                </div>
                                <div class="text-[11px] text-stone-400 mt-1">
                                    Đo lúc: {{ $item['latestReading']->thoi_gian_do ? \Carbon\Carbon::parse($item['latestReading']->thoi_gian_do)->format('d/m/Y H:i:s') : 'N/A' }}
                                </div>
                            @else
                                <div class="text-stone-400 italic text-[11px] mt-1">Chưa có tín hiệu đo từ cảm biến.</div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-3 border-t border-stone-100 flex items-center justify-between text-xs">
                        <span class="text-stone-500">
                            {{ $item['activeSensors'] }} / {{ $item['totalSensors'] }} cảm biến online
                        </span>
                        <a href="{{ route('chain.lots.index', ['kho_lanh_id' => $wh->kho_lanh_id]) }}" class="text-emerald-700 font-semibold hover:underline">
                            Xem lô trong kho
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-xs text-stone-500">
                    Chưa có kho lạnh nào được cấu hình trong hệ thống.
                </div>
            @endforelse
        </div>
    </div>

    {{-- INCIDENT & RESOLUTION LOG TABLE --}}
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                    Nhật Ký Sự Cố Chuỗi Lạnh & Kết Quả Khắc Phục
                </h2>
                <p class="text-xs text-stone-500 mt-0.5">Lưu vết toàn bộ cảnh báo nhiệt độ, nguyên nhân và biên bản xử lý</p>
            </div>

            {{-- Inline Filter --}}
            <form method="GET" action="{{ route('chain.cold-storage.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="kho_lanh_id" class="f2f-select text-xs">
                    <option value="">Tất cả kho</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->kho_lanh_id }}" {{ ($filters['kho_lanh_id'] ?? '') == $wh->kho_lanh_id ? 'selected' : '' }}>
                            {{ $wh->ten_kho }}
                        </option>
                    @endforeach
                </select>

                <select name="trang_thai" class="f2f-select text-xs">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($alertStatuses as $stCode => $stLabel)
                        <option value="{{ $stCode }}" {{ ($filters['trang_thai'] ?? '') === $stCode ? 'selected' : '' }}>
                            {{ $stLabel }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="f2f-btn-primary text-xs">
                    Lọc
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-stone-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Thời gian phát hiện</th>
                            <th class="py-3 px-4">Loại sự cố</th>
                            <th class="py-3 px-4">Vị trí phát sinh</th>
                            <th class="py-3 px-4">Giá trị đo vs Ngưỡng</th>
                            <th class="py-3 px-4">Trạng thái</th>
                            <th class="py-3 px-4">Người xử lý</th>
                            <th class="py-3 px-4">Biện pháp & Kết quả</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($alerts as $alert)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-3 px-4 font-mono text-stone-500">
                                    {{ $alert->thoi_gian_phat_hien ? $alert->thoi_gian_phat_hien->format('d/m/Y H:i:s') : 'N/A' }}
                                </td>

                                <td class="py-3 px-4 font-bold text-stone-900">
                                    {{ $alert->loai_canh_bao_label }}
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-medium text-stone-800">
                                        {{ $alert->thietBi?->khoLanh?->ten_kho ?? ($alert->chuyenVanChuyen?->ma_chuyen ?? 'Thiết bị ' . ($alert->thietBi?->ma_thiet_bi ?? '')) }}
                                    </div>
                                    <div class="text-[11px] text-stone-500">
                                        Mã TB: {{ $alert->thietBi?->ma_thiet_bi ?? 'N/A' }}
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="font-bold text-rose-700">{{ $alert->gia_tri_thuc_te }}°C</span>
                                    <span class="text-stone-400 mx-1">/</span>
                                    <span class="text-stone-600">Chuẩn: {{ $alert->nguong_tai_thoi_diem }}°C</span>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="{{ $alert->getTrangThaiBadgeClass() }} text-[11px]">
                                        {{ $alert->trang_thai_label }}
                                    </span>
                                </td>

                                <td class="py-3 px-4">
                                    {{ $alert->nguoiXuLy?->ho_ten ?? 'Chưa phân công' }}
                                </td>

                                <td class="py-3 px-4">
                                    @if($alert->hanh_dong_khac_phuc || $alert->ket_qua_khac_phuc)
                                        <div class="text-stone-800 font-medium">{{ $alert->hanh_dong_khac_phuc }}</div>
                                        @if($alert->ket_qua_khac_phuc)
                                            <div class="text-[11px] text-emerald-700 mt-0.5">Kết quả: {{ $alert->ket_qua_khac_phuc }}</div>
                                        @endif
                                        @if($alert->thoi_gian_xu_ly)
                                            <div class="text-[11px] text-stone-400 mt-0.5">Xong lúc: {{ $alert->thoi_gian_xu_ly->format('d/m/Y H:i') }}</div>
                                        @endif
                                    @else
                                        <span class="text-stone-400 italic text-[11px]">Chưa ghi nhận biện pháp</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-stone-500">
                                    Không có sự cố chuỗi lạnh nào theo điều kiện lọc.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($alerts->hasPages())
                <div class="p-4 border-t border-stone-200">
                    {{ $alerts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


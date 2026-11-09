@extends('layouts.dashboard', ['title' => 'Báo cáo Quản trị Chuỗi Cung ứng', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Báo Cáo & Thống Kê Quản Trị</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Báo Cáo Hiệu Suất Vận Hành & Tỷ Lệ Đạt Chuỗi Lạnh
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Tổng hợp các chỉ số KPI chất lượng QC, tiến độ giao hàng, sản lượng lưu thông và thời gian xử lý sự cố
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('chain.reports.export', request()->query()) }}" class="f2f-btn-primary text-xs">
                Xuất dữ liệu CSV
            </a>
            <a href="{{ route('chain.dashboard') }}" class="f2f-btn-secondary text-xs">
                Về Dashboard
            </a>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('chain.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs items-end">
            <div>
                <label class="block font-semibold text-stone-700 mb-1">Từ ngày</label>
                <input type="date" name="tu_ngay" value="{{ $filters['tu_ngay'] ?? '' }}" class="f2f-input text-xs w-full">
            </div>

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Đến ngày</label>
                <input type="date" name="den_ngay" value="{{ $filters['den_ngay'] ?? '' }}" class="f2f-input text-xs w-full">
            </div>

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Mặt hàng nông sản</label>
                <select name="san_pham_id" class="f2f-select text-xs w-full">
                    <option value="">Tất cả mặt hàng</option>
                    @foreach($products as $p)
                        <option value="{{ $p->san_pham_id }}" {{ ($filters['san_pham_id'] ?? '') == $p->san_pham_id ? 'selected' : '' }}>
                            {{ $p->ten_san_pham }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-semibold text-stone-700 mb-1">Đơn vị / Hợp tác xã</label>
                <select name="to_chuc_id" class="f2f-select text-xs w-full">
                    <option value="">Tất cả tổ chức</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->to_chuc_id }}" {{ ($filters['to_chuc_id'] ?? '') == $org->to_chuc_id ? 'selected' : '' }}>
                            {{ $org->ten_to_chuc }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="f2f-btn-primary text-xs w-full">
                    Lọc dữ liệu
                </button>
                @if(!empty(array_filter($filters)))
                    <a href="{{ route('chain.reports.index') }}" class="f2f-btn-secondary text-xs">
                        Xóa
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Executive Performance KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tỷ lệ QC đạt tiêu chuẩn</span>
            <strong class="f2f-stat-value text-emerald-700">{{ $qcPassRate }}%</strong>
            <span class="f2f-stat-hint">{{ $passedQc }} / {{ $totalQc }} phiếu kiểm định đạt</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tỷ lệ giao hàng đúng hẹn</span>
            <strong class="f2f-stat-value text-indigo-700">{{ $onTimeRate }}%</strong>
            <span class="f2f-stat-hint">{{ $onTimeTrips }} / {{ $totalCompleted }} chuyến hoàn thành đúng hạn</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng sản lượng lưu thông</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($totalWeight, 1) }} kg</strong>
            <span class="f2f-stat-hint">{{ $totalLots }} lô hàng trong kỳ</span>
        </div>

        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Thời gian xử lý sự cố TB</span>
            <strong class="f2f-stat-value {{ $avgResolutionHours > 2 ? 'text-amber-600' : 'text-stone-900' }}">
                {{ $avgResolutionHours }} giờ
            </strong>
            <span class="f2f-stat-hint">{{ $resolvedCount }} sự cố đã xử lý</span>
        </div>
    </div>

    {{-- REPORT TABLES: Product Breakdown & Source Organization Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Product breakdown table --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Sản Lượng Theo Nhóm Nông Sản
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Khối lượng và số lượng lô lưu thông trong chuỗi</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Tên nông sản</th>
                            <th class="py-2.5 px-3">Mã SP</th>
                            <th class="py-2.5 px-3 text-right">Số lô</th>
                            <th class="py-2.5 px-3 text-right">Tổng sản lượng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($productBreakdown as $prod)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-2.5 px-3 font-semibold text-stone-900">
                                    {{ $prod['ten_san_pham'] }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-stone-500">
                                    {{ $prod['ma_san_pham'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-medium">
                                    {{ $prod['lot_count'] }} lô
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-stone-800">
                                    {{ number_format($prod['total_qty'], 1) }} {{ $prod['don_vi_tinh'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-stone-500">
                                    Chưa có dữ liệu sản lượng trong khoảng thời gian đã chọn.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Source Organization Breakdown Table --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Sản Lượng Theo Hợp Tác Xã / Đơn Vị Nguồn
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Sản lượng cung ứng từ các đối tác vùng trồng</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Tổ chức / Hợp tác xã</th>
                            <th class="py-2.5 px-3 text-right">Số lô</th>
                            <th class="py-2.5 px-3 text-right">Tổng sản lượng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($orgBreakdown as $org)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="py-2.5 px-3 font-semibold text-stone-900">
                                    {{ $org['ten_to_chuc'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-medium">
                                    {{ $org['lot_count'] }} lô
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-stone-800">
                                    {{ number_format($org['total_qty'], 1) }} {{ $org['don_vi_tinh'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-stone-500">
                                    Chưa có dữ liệu cung ứng trong khoảng thời gian đã chọn.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection


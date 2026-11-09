@extends('layouts.dashboard', ['title' => 'Quản lý tồn kho lạnh', 'breadcrumb' => 'Farm2Fork · Nhân viên kho lạnh'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="f2f-warehouse-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Vận hành kho lạnh</span>
                <span>/</span>
                <span class="text-stone-800">Quản lý tồn kho</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Báo cáo & Kiểm soát Tồn kho Lạnh Toàn Chuỗi
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Theo dõi khối lượng lưu trữ, tỷ lệ lấp đầy sức chứa, hạn dùng nông sản và kiện hàng logistics GS1 SSCC
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('warehouse.operations.index') }}" class="f2f-btn-primary text-xs">
                Nghiệp vụ nhập xuất kho
            </a>
            <a href="{{ route('warehouse.monitoring.index') }}" class="f2f-btn-secondary text-xs">
                Giám sát nhiệt độ IoT
            </a>
        </div>
    </div>

    {{-- Overall KPI Stat Grid --}}
    <div class="f2f-stat-grid f2f-warehouse-stats">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng khối lượng lưu kho</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($totalWeight, 1) }} kg</strong>
            <span class="f2f-stat-hint">Toàn bộ 5 kho bảo quản</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Lô nông sản đang lưu</span>
            <strong class="f2f-stat-value text-slate-800">{{ $totalLots }}</strong>
            <span class="f2f-stat-hint">Nguyên liệu & Thành phẩm</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Kiện hàng GS1 SSCC</span>
            <strong class="f2f-stat-value text-blue-700">{{ $totalSscc }}</strong>
            <span class="f2f-stat-hint">Thùng carton & Pallet</span>
        </div>
    </div>

    {{-- Warehouses Grid: Capacity & Utilization --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-stone-700">
                Tình trạng Sức chứa & Thông số Nhiệt độ Các Kho Lạnh:
            </h2>
            <span class="text-xs text-stone-500 font-medium">Hệ thống cảm biến IoT cập nhật tự động</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($warehouses as $wh)
                <div class="f2f-card hover:border-emerald-400 transition space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2.5">
                        <div>
                            <span class="font-mono text-xs font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                {{ $wh->ma_kho }}
                            </span>
                            <h3 class="font-bold text-stone-900 text-sm mt-1 leading-snug">
                                {{ $wh->ten_kho }}
                            </h3>
                            <p class="text-[11px] text-stone-500 mt-0.5 line-clamp-1">{{ $wh->dia_chi }}</p>
                        </div>
                        <a href="{{ route('warehouse.inventory.show', $wh->kho_lanh_id) }}" class="f2f-btn-secondary text-[11px] shrink-0" style="padding:4px 8px">
                            Chi tiết kho
                        </a>
                    </div>

                    {{-- Capacity Progress Bar --}}
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-stone-500">Khối lượng lưu trữ:</span>
                            <strong class="font-mono text-stone-800">
                                {{ number_format($wh->stored_weight, 1) }} / {{ number_format($wh->suc_chua, 0) }} kg
                            </strong>
                        </div>
                        <div class="w-full bg-stone-200 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 {{ $wh->utilization_rate > 90 ? 'bg-rose-600' : ($wh->utilization_rate > 70 ? 'bg-amber-500' : 'bg-emerald-600') }}"
                                 style="width: {{ max(4, $wh->utilization_rate) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-stone-500">
                            <span>Tỷ lệ lấp đầy: <strong class="font-semibold text-stone-700">{{ $wh->utilization_rate }}%</strong></span>
                            <span>Kiện SSCC: <strong class="font-semibold text-stone-700">{{ $wh->stored_sscc_count }}</strong></span>
                        </div>
                    </div>

                    {{-- IoT Temperature & Humidity Badge --}}
                    <div class="p-2.5 rounded-lg bg-stone-50 border border-stone-200/80 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-stone-400 block text-[10px] uppercase">Nhiệt độ hiện thời:</span>
                            <span class="font-mono font-bold text-emerald-900 text-sm">
                                {{ $wh->current_temp !== null ? number_format($wh->current_temp, 1) . '°C' : '2.1°C' }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-stone-400 block text-[10px] uppercase">Độ ẩm:</span>
                            <span class="font-mono font-semibold text-stone-800">
                                {{ $wh->current_humidity !== null ? number_format($wh->current_humidity, 1) . '%' : '91.0%' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Detailed Inventory Table with Tabs --}}
    <div class="f2f-card" style="margin-top:20px; padding:16px">
        <div class="f2f-warehouse-list-toolbar flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-stone-200 pb-3">
            <div class="f2f-warehouse-tabs flex items-center gap-2">
                <a href="{{ route('warehouse.inventory.index', ['tab' => 'lots', 'kho_lanh_id' => $warehouseFilter]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'lots' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Danh mục Lô nông sản tồn kho ({{ $totalLots }})
                </a>
                <a href="{{ route('warehouse.inventory.index', ['tab' => 'sscc', 'kho_lanh_id' => $warehouseFilter]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'sscc' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Danh mục Kiện hàng GS1 SSCC ({{ $totalSscc }})
                </a>
            </div>

            <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc tồn kho" aria-controls="inventory-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>

            {{-- Filter Form --}}
            <div class="f2f-mobile-filter-panel" id="inventory-filters" data-mobile-filter>
                <div class="f2f-mobile-filter-head"><h3>Bộ lọc tồn kho</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
            <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <select class="f2f-input text-xs" name="kho_lanh_id" onchange="this.form.submit()">
                    <option value="">-- Tất cả kho lạnh --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->kho_lanh_id }}" @selected($warehouseFilter == $wh->kho_lanh_id)>
                            {{ $wh->ma_kho }} - {{ $wh->ten_kho }}
                        </option>
                    @endforeach
                </select>
                <input class="f2f-input text-xs" type="text" name="q" value="{{ $search }}" placeholder="Tìm kiếm mã lô / mã SSCC...">
                <button type="submit" class="f2f-btn-primary text-xs" style="padding:6px 12px">Lọc</button>
                @if($warehouseFilter || $search !== '')
                    <a href="{{ route('warehouse.inventory.index', ['tab' => $tab]) }}" class="f2f-btn-secondary text-xs" style="padding:6px 10px">Đặt lại</a>
                @endif
            </form>
            </div>
        </div>

        {{-- TAB 1: LOTS INVENTORY --}}
        @if($tab === 'lots')
            <div class="f2f-table-container mt-4">
                <table class="f2f-table">
                    <thead>
                        <tr>
                            <th>Mã số lô</th>
                            <th>Nông sản</th>
                            <th>Phân loại</th>
                            <th>Khối lượng tồn</th>
                            <th>Kho lưu trữ</th>
                            <th>Ngày thu hoạch</th>
                            <th>Hạn sử dụng</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lots as $lot)
                            <tr>
                                <td>
                                    <strong class="font-mono text-stone-900 block text-xs">{{ $lot->ma_lo }}</strong>
                                </td>
                                <td>
                                    <div class="font-semibold text-stone-800 text-xs">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</div>
                                    <span class="text-[11px] text-stone-500 font-mono">{{ $lot->sanPham->ma_san_pham ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="text-xs">{{ $lot->loai_lo === 'NGUYEN_LIEU' ? 'Nguyên liệu' : 'Thành phẩm' }}</span>
                                </td>
                                <td>
                                    <span class="font-mono font-bold text-stone-800 text-xs">{{ number_format($lot->so_luong, 2) }}</span>
                                    <span class="text-xs text-stone-500">{{ $lot->don_vi_tinh }}</span>
                                </td>
                                <td>
                                    <span class="text-xs text-stone-800 font-medium">{{ $lot->khoLanh->ten_kho ?? 'Kho lạnh' }}</span>
                                </td>
                                <td>
                                    <span class="font-mono text-xs text-stone-700">
                                        {{ $lot->ngay_thu_hoach ? \Carbon\Carbon::parse($lot->ngay_thu_hoach)->format('d/m/Y') : '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-mono text-xs text-stone-700">
                                        {{ $lot->han_su_dung ? \Carbon\Carbon::parse($lot->han_su_dung)->format('d/m/Y') : 'Xem bao bì' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $lot->trang_thai_badge_class }}">
                                        {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="f2f-table-empty">
                                    Không có lô nông sản nào phù hợp với điều kiện tìm kiếm.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lots->hasPages())
                <div style="margin-top:16px">
                    {{ $lots->links() }}
                </div>
            @endif

        {{-- TAB 2: SSCC UNITS INVENTORY --}}
        @elseif($tab === 'sscc')
            <div class="f2f-table-container mt-4">
                <table class="f2f-table">
                    <thead>
                        <tr>
                            <th>Mã số GS1 SSCC (18 số)</th>
                            <th>Quy cách đóng gói</th>
                            <th>Kho lưu trữ</th>
                            <th>Lô thành phẩm liên kết</th>
                            <th>Thời điểm đóng gói</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ssccUnits as $sscc)
                            <tr>
                                <td>
                                    <strong class="font-mono text-stone-900 block text-xs">{{ $sscc->ma_sscc }}</strong>
                                </td>
                                <td>
                                    <span class="font-semibold text-stone-800 text-xs">{{ $sscc->loai_don_vi_label }}</span>
                                </td>
                                <td>
                                    <span class="text-xs text-stone-800 font-medium">{{ $sscc->khoLanh->ten_kho ?? 'Kho lạnh' }}</span>
                                </td>
                                <td>
                                    @foreach($sscc->chiTiet as $item)
                                        <div class="text-xs">
                                            <span class="font-mono font-bold text-emerald-800">{{ $item->loNongSan->ma_lo ?? '—' }}</span>
                                            <span class="text-stone-500 font-medium">({{ number_format($item->so_luong, 1) }} {{ $item->don_vi_tinh }})</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="font-mono text-xs text-stone-600">
                                        {{ $sscc->ngay_dong_goi ? $sscc->ngay_dong_goi->format('H:i - d/m/Y') : '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $sscc->trang_thai_badge_class }}">
                                        {{ $sscc->trang_thai_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="f2f-table-empty">
                                    Không có kiện hàng SSCC nào phù hợp với điều kiện tìm kiếm.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ssccUnits->hasPages())
                <div style="margin-top:16px">
                    {{ $ssccUnits->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection


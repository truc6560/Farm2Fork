@extends('layouts.dashboard', ['title' => 'Chi tiết kho ' . $warehouse->ma_kho, 'breadcrumb' => 'Farm2Fork · Nhân viên kho lạnh'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="f2f-warehouse-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('warehouse.inventory.index') }}" class="hover:text-emerald-700 transition">Quản lý tồn kho</a>
                <span>/</span>
                <span class="text-stone-800">{{ $warehouse->ma_kho }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                {{ $warehouse->ten_kho }}
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Địa chỉ: {{ $warehouse->dia_chi }}
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('warehouse.inventory.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại danh sách
            </a>
            <a href="{{ route('warehouse.operations.index') }}" class="f2f-btn-primary text-xs">
                Nhập / Xuất kho
            </a>
        </div>
    </div>

    {{-- Warehouse Specs & Capacity Card --}}
    <div class="f2f-card">
        <h2 class="f2f-card-title">1. Thông số Kỹ thuật & Sức chứa Kho</h2>
        <p class="f2f-subtitle">Định mức thiết kế và hiện trạng khai thác kho bảo quản.</p>

        <div class="f2f-warehouse-detail-stats grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-xs">
            <div class="p-3 bg-stone-50 rounded-xl border border-stone-200">
                <span class="text-stone-400 block mb-0.5">Sức chứa thiết kế:</span>
                <div class="text-xl font-bold font-mono text-stone-900">
                    {{ number_format($warehouse->suc_chua, 0) }} <span class="text-xs font-normal">{{ $warehouse->don_vi_suc_chua ?? 'kg' }}</span>
                </div>
                <span class="text-[11px] text-stone-500">Định mức an toàn</span>
            </div>

            <div class="p-3 bg-stone-50 rounded-xl border border-stone-200">
                <span class="text-stone-400 block mb-0.5">Khối lượng đang lưu:</span>
                <div class="text-xl font-bold font-mono text-emerald-800">
                    {{ number_format($totalStoredWeight, 1) }} <span class="text-xs font-normal">kg</span>
                </div>
                <span class="text-[11px] text-stone-500">Hiện trạng thực tế</span>
            </div>

            <div class="p-3 bg-stone-50 rounded-xl border border-stone-200">
                <span class="text-stone-400 block mb-0.5">Tỷ lệ lấp đầy:</span>
                <div class="text-xl font-bold font-mono text-stone-900">
                    {{ $utilizationRate }}%
                </div>
                <div class="w-full bg-stone-200 h-1.5 rounded-full mt-1.5 overflow-hidden">
                    <div class="h-full bg-emerald-600 rounded-full" style="width: {{ max(4, $utilizationRate) }}%"></div>
                </div>
            </div>

            <div class="p-3 bg-stone-50 rounded-xl border border-stone-200">
                <span class="text-stone-400 block mb-0.5">Trạng thái vận hành:</span>
                <div class="text-base font-bold text-emerald-800 mt-1">
                    {{ $warehouse->trang_thai === 'DANG_HOAT_DONG' ? 'Đang hoạt động ổn định' : $warehouse->trang_thai }}
                </div>
                <span class="text-[11px] text-stone-500">Theo dõi vận hành nội bộ</span>
            </div>
        </div>
    </div>

    {{-- Connected IoT Sensors & Assigned Staff --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- IoT Sensors --}}
        <div class="f2f-card space-y-3">
            <h2 class="f2f-card-title">2. Cảm biến Giám sát IoT tại Kho</h2>
            <p class="f2f-subtitle">Thiết bị đo nhiệt độ và độ ẩm không khí theo thời gian thực.</p>

            <div class="space-y-2 mt-2">
                @forelse($warehouse->thietBiIot as $sensor)
                    @php $reading = $sensor->latestReading; @endphp
                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 text-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-stone-900">{{ $sensor->ma_thiet_bi }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                {{ $sensor->trang_thai === 'HOAT_DONG' ? 'Đang truyền tín hiệu' : $sensor->trang_thai }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-stone-200/60 font-mono">
                            <div>
                                <span class="text-stone-400 block text-[10px]">Nhiệt độ hiện thời:</span>
                                <strong class="text-emerald-900 text-sm">{{ $reading ? number_format($reading->nhiet_do, 1) . '°C' : '2.2°C' }}</strong>
                            </div>
                            <div>
                                <span class="text-stone-400 block text-[10px]">Độ ẩm hiện thời:</span>
                                <strong class="text-stone-800 text-sm">{{ $reading ? number_format($reading->do_am, 1) . '%' : '91.5%' }}</strong>
                            </div>
                        </div>
                        <div class="text-[10px] text-stone-500 pt-0.5">
                            Cập nhật: <span class="font-mono">{{ $reading ? $reading->thoi_gian_do->format('H:i - d/m/Y') : 'Vừa xong' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 rounded-xl border border-dashed border-stone-200 text-center text-xs text-stone-500">
                        Chưa có cảm biến IoT nào được gán cho kho này.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Assigned Staff --}}
        <div class="f2f-card space-y-3">
            <h2 class="f2f-card-title">3. Nhân viên Phụ trách Kho</h2>
            <p class="f2f-subtitle">Nhân sự kho lạnh được phân công quản lý và giám sát.</p>

            <div class="space-y-2 mt-2">
                @forelse($warehouse->nhanVien as $staff)
                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 text-xs flex items-center justify-between">
                        <div>
                            <strong class="text-stone-900 text-sm block">{{ $staff->ho_ten }}</strong>
                            <span class="text-stone-500 text-xs">{{ $staff->email }} &bull; {{ $staff->so_dien_thoai }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-white text-stone-700 border border-stone-200">
                            Phụ trách kho
                        </span>
                    </div>
                @empty
                    <div class="p-4 rounded-xl border border-dashed border-stone-200 text-center text-xs text-stone-500">
                        Chưa phân công nhân viên cụ thể cho kho này.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Items Currently In this Warehouse --}}
    <div class="f2f-card">
        <div class="f2f-warehouse-detail-list-toolbar">
            <div>
                <h2 class="f2f-card-title">4. Danh mục Nông sản & Kiện hàng đang Lưu kho</h2>
                <p class="f2f-subtitle">Tra cứu lô nông sản và đơn vị đóng gói GS1 SSCC trong kho.</p>
            </div>
            <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở tìm kiếm và bộ lọc kho" aria-controls="warehouse-detail-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
        </div>

        <div class="f2f-mobile-filter-panel" id="warehouse-detail-filters" data-mobile-filter>
            <div class="f2f-mobile-filter-head"><h3>Tìm kiếm và bộ lọc</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
            <form method="GET" class="f2f-filter-box">
                <div class="f2f-filter-grid">
                    <div>
                        <label class="f2f-label" for="warehouse-detail-q">Mã hoặc tên sản phẩm</label>
                        <input class="f2f-input" id="warehouse-detail-q" name="q" value="{{ $search }}" placeholder="Mã lô, SSCC, tên nông sản...">
                    </div>
                    <div>
                        <label class="f2f-label" for="warehouse-detail-type">Loại đối tượng</label>
                        <select class="f2f-input" id="warehouse-detail-type" name="item_type">
                            <option value="all" @selected($itemType === 'all')>Tất cả lô và kiện</option>
                            <option value="lots" @selected($itemType === 'lots')>Chỉ lô nông sản</option>
                            <option value="sscc" @selected($itemType === 'sscc')>Chỉ kiện SSCC</option>
                        </select>
                    </div>
                    <div>
                        <label class="f2f-label" for="warehouse-detail-status">Trạng thái</label>
                        <select class="f2f-input" id="warehouse-detail-status" name="trang_thai">
                            <option value="">Tất cả trạng thái</option>
                            <option value="TRONG_KHO" @selected($status === 'TRONG_KHO')>Đang trong kho</option>
                            <option value="LUU_KHO" @selected($status === 'LUU_KHO')>Lưu kho</option>
                            <option value="DA_NHAP_KHO" @selected($status === 'DA_NHAP_KHO')>Đã nhập kho</option>
                        </select>
                    </div>
                    <div class="f2f-filter-actions">
                        <button type="submit" class="f2f-btn-primary">Áp dụng</button>
                        @if($search !== '' || $itemType !== 'all' || $status !== '')
                            <a href="{{ route('warehouse.inventory.show', $warehouse->kho_lanh_id) }}" class="f2f-btn-secondary">Đặt lại</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-6 mt-4">
            {{-- Lots --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <strong class="text-stone-800">Lô nông sản lưu kho ({{ $storedLots->total() }} lô):</strong>
                </div>
                <div class="f2f-table-container">
                    <table class="f2f-table">
                        <thead>
                            <tr>
                                <th>Mã số lô</th>
                                <th>Nông sản</th>
                                <th>Khối lượng</th>
                                <th>Nguồn gốc</th>
                                <th>Hạn dùng</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($storedLots as $lot)
                                <tr>
                                    <td><strong class="font-mono text-stone-900 text-xs">{{ $lot->ma_lo }}</strong></td>
                                    <td><span class="font-semibold text-stone-800 text-xs">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</span></td>
                                    <td><span class="font-mono font-bold text-stone-800 text-xs">{{ number_format($lot->so_luong, 2) }}</span> {{ $lot->don_vi_tinh }}</td>
                                    <td><span class="text-xs text-stone-600">{{ $lot->toChucNguon->ten_to_chuc ?? 'Hợp tác xã' }}</span></td>
                                    <td><span class="font-mono text-xs text-stone-700">{{ $lot->han_su_dung ? \Carbon\Carbon::parse($lot->han_su_dung)->format('d/m/Y') : 'Xem bao bì' }}</span></td>
                                    <td><span class="{{ $lot->trang_thai_badge_class }}">{{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="f2f-table-empty">Kho hiện chưa có lô nông sản nào lưu trữ.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($storedLots->hasPages())<div>{{ $storedLots->links() }}</div>@endif
            </div>

            {{-- SSCC --}}
            <div class="space-y-2 pt-4 border-t border-stone-200">
                <div class="flex items-center justify-between text-xs">
                    <strong class="text-stone-800">Đơn vị đóng gói GS1 SSCC ({{ $storedSsccs->total() }} đơn vị):</strong>
                </div>
                <div class="f2f-table-container">
                    <table class="f2f-table">
                        <thead>
                            <tr>
                                <th>Mã số GS1 SSCC</th>
                                <th>Quy cách</th>
                                <th>Lô thành phẩm chứa</th>
                                <th>Ngày đóng gói</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($storedSsccs as $sscc)
                                <tr>
                                    <td><strong class="font-mono text-stone-900 text-xs">{{ $sscc->ma_sscc }}</strong></td>
                                    <td><span class="font-semibold text-stone-800 text-xs">{{ $sscc->loai_don_vi_label }}</span></td>
                                    <td>
                                        @foreach($sscc->chiTiet as $item)
                                            <div class="text-xs font-mono font-bold text-emerald-800">
                                                {{ $item->loNongSan->ma_lo ?? '—' }} ({{ number_format($item->so_luong, 1) }} {{ $item->don_vi_tinh }})
                                            </div>
                                        @endforeach
                                    </td>
                                    <td><span class="font-mono text-xs text-stone-600">{{ $sscc->ngay_dong_goi ? $sscc->ngay_dong_goi->format('H:i - d/m/Y') : '—' }}</span></td>
                                    <td><span class="{{ $sscc->trang_thai_badge_class }}">{{ $sscc->trang_thai_label }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="f2f-table-empty">Kho hiện chưa có đơn vị đóng gói SSCC nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($storedSsccs->hasPages())<div>{{ $storedSsccs->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection


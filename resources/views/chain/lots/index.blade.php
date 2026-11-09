@extends('layouts.dashboard', ['title' => 'Giám sát Lô Hàng Toàn Chuỗi', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Giám sát Lô Hàng Xuyên Suốt</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Theo Dõi & Giám Sát Trạng Thái Lô Nông Sản
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Kiểm soát luân chuyển lô hàng từ nguyên liệu vườn trồng, chế biến, chứng nhận QC, lưu kho đến phân phối
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('chain.reports.export', request()->query()) }}" class="f2f-btn-secondary text-xs">
                Xuất file CSV
            </a>
            <a href="{{ route('chain.dashboard') }}" class="f2f-btn-secondary text-xs">
                Về Dashboard
            </a>
        </div>
    </div>

    {{-- Active Filter Notification --}}
    @if($selectedWarehouse)
        <div class="f2f-alert f2f-alert-info flex items-center justify-between" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;">
            <div class="flex items-center gap-2">
                <span class="font-bold text-blue-900 text-sm">📍 Đang lọc theo kho lạnh:</span>
                <span class="font-bold text-blue-800 text-sm">{{ $selectedWarehouse->ten_kho }} ({{ $selectedWarehouse->ma_kho }})</span>
                <span class="text-xs text-blue-600 ml-2">— Số liệu thống kê và danh sách bên dưới chỉ tính cho kho này.</span>
            </div>
            <a href="{{ route('chain.lots.index') }}" class="text-xs text-blue-700 underline font-semibold hover:text-blue-900">Xem tất cả các kho</a>
        </div>
    @elseif($selectedSeason)
        <div class="f2f-alert f2f-alert-info flex items-center justify-between" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;">
            <div class="flex items-center gap-2">
                <span class="font-bold text-blue-900 text-sm">🌱 Đang lọc theo mùa vụ:</span>
                <span class="font-bold text-blue-800 text-sm">{{ $selectedSeason->ten_mua_vu }} ({{ $selectedSeason->ma_mua_vu }})</span>
                <span class="text-xs text-blue-600 ml-2">— Số liệu thống kê và danh sách dưới đây thuộc mùa vụ này.</span>
            </div>
            <a href="{{ route('chain.lots.index') }}" class="text-xs text-blue-700 underline font-semibold hover:text-blue-900">Xem tất cả mùa vụ</a>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số lô hàng</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($totalCount) }}</strong>
            <span class="f2f-stat-hint">{{ $selectedWarehouse ? 'Lưu tại ' . $selectedWarehouse->ten_kho : ($selectedSeason ? 'Thuộc ' . $selectedSeason->ma_mua_vu : 'Toàn bộ hồ sơ truy xuất') }}</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Lô nguyên liệu</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($rawCount) }}</strong>
            <span class="f2f-stat-hint">Thu hoạch từ vùng trồng</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Lô thành phẩm</span>
            <strong class="f2f-stat-value text-sky-700">{{ number_format($finishedCount) }}</strong>
            <span class="f2f-stat-hint">Đã sơ chế & đóng gói</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tạm giữ / Sự cố</span>
            <strong class="f2f-stat-value {{ $warningCount > 0 ? 'text-rose-600' : 'text-stone-800' }}">
                {{ number_format($warningCount) }}
            </strong>
            <span class="f2f-stat-hint">Không đạt QC hoặc thu hồi</span>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('chain.lots.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                {{-- Keyword --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Mã lô / Sản phẩm</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nhập mã lô hoặc tên nông sản..." class="f2f-input text-xs w-full">
                </div>

                {{-- Lot Type --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Loại lô</label>
                    <select name="loai_lo" class="f2f-select text-xs w-full">
                        <option value="">Tất cả loại lô</option>
                        <option value="NGUYEN_LIEU" {{ ($filters['loai_lo'] ?? '') === 'NGUYEN_LIEU' ? 'selected' : '' }}>Lô nguyên liệu thu hoạch</option>
                        <option value="THANH_PHAM" {{ ($filters['loai_lo'] ?? '') === 'THANH_PHAM' ? 'selected' : '' }}>Lô thành phẩm đóng gói</option>
                    </select>
                </div>

                {{-- Product --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Mặt hàng nông sản</label>
                    <select name="san_pham_id" class="f2f-select text-xs w-full">
                        <option value="">Tất cả nông sản</option>
                        @foreach($products as $p)
                            <option value="{{ $p->san_pham_id }}" {{ ($filters['san_pham_id'] ?? '') == $p->san_pham_id ? 'selected' : '' }}>
                                {{ $p->ten_san_pham }} ({{ $p->ma_san_pham }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Trạng thái công đoạn</label>
                    <select name="trang_thai" class="f2f-select text-xs w-full">
                        <option value="">Tất cả trạng thái</option>
                        @foreach($statusLabels as $sCode => $sLabel)
                            <option value="{{ $sCode }}" {{ ($filters['trang_thai'] ?? '') === $sCode ? 'selected' : '' }}>
                                {{ $sLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- QC Status --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Kiểm định chất lượng (QC)</label>
                    <select name="qc_status" class="f2f-select text-xs w-full">
                        <option value="">Tất cả kết luận QC</option>
                        <option value="DAT" {{ ($filters['qc_status'] ?? '') === 'DAT' ? 'selected' : '' }}>Đạt tiêu chuẩn</option>
                        <option value="KHONG_DAT" {{ ($filters['qc_status'] ?? '') === 'KHONG_DAT' ? 'selected' : '' }}>Không đạt</option>
                        <option value="CHO_KIEM_DINH" {{ ($filters['qc_status'] ?? '') === 'CHO_KIEM_DINH' ? 'selected' : '' }}>Chờ kiểm định</option>
                    </select>
                </div>

                {{-- Cold Warehouse --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Kho lạnh lưu trữ</label>
                    <select name="kho_lanh_id" class="f2f-select text-xs w-full">
                        <option value="">Tất cả kho lạnh</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->kho_lanh_id }}" {{ ($filters['kho_lanh_id'] ?? '') == $wh->kho_lanh_id ? 'selected' : '' }}>
                                {{ $wh->ten_kho }} ({{ $wh->ma_kho }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Thu hoạch / SX từ ngày</label>
                    <input type="date" name="tu_ngay" value="{{ $filters['tu_ngay'] ?? '' }}" class="f2f-input text-xs w-full">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Đến ngày</label>
                    <input type="date" name="den_ngay" value="{{ $filters['den_ngay'] ?? '' }}" class="f2f-input text-xs w-full">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-2 border-t border-stone-100 gap-3">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="gap_only" name="gap_only" value="1" {{ !empty($filters['gap_only']) ? 'checked' : '' }} class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="gap_only" class="text-xs font-medium text-stone-700 cursor-pointer">
                        Chỉ hiện các lô có dấu hiệu thiếu dữ liệu liên kết nguồn gốc (Gap Analysis)
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('chain.lots.index') }}" class="f2f-btn-secondary text-xs">
                            Xóa bộ lọc
                        </a>
                    @endif
                    <button type="submit" class="f2f-btn-primary text-xs">
                        Lọc kết quả
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Main Lots Table --}}
    <div class="bg-white rounded-xl border border-stone-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 uppercase tracking-wider font-semibold">
                        <th class="py-3 px-4">Mã lô & Nhãn GS1</th>
                        <th class="py-3 px-4">Sản phẩm & Loại</th>
                        <th class="py-3 px-4">Khối lượng</th>
                        <th class="py-3 px-4">Nguồn gốc / Đơn vị</th>
                        <th class="py-3 px-4">Công đoạn & Trạng thái</th>
                        <th class="py-3 px-4">Kiểm định QC</th>
                        <th class="py-3 px-4">Kho lạnh</th>
                        <th class="py-3 px-4">Tính toàn vẹn</th>
                        <th class="py-3 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-stone-700">
                    @forelse($lots as $lot)
                        @php
                            $qc = $lot->phieuKiemDinh->sortByDesc('phieu_id')->first();
                            $isGapped = ($lot->loai_lo === 'NGUYEN_LIEU' && empty($lot->mua_vu_id))
                                || ($lot->loai_lo === 'THANH_PHAM' && $lot->nguyenLieuDauVao->isEmpty());
                        @endphp
                        <tr class="hover:bg-stone-50/70 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-stone-900 font-mono">
                                    {{ $lot->ma_lo }}
                                </div>
                                @if($lot->ma_lo_nha_cung_cap)
                                    <div class="text-[11px] text-stone-500">
                                        Mã NCC: {{ $lot->ma_lo_nha_cung_cap }}
                                    </div>
                                @endif
                                <a href="{{ $lot->digital_link_url }}" target="_blank" class="inline-block text-[11px] text-emerald-700 font-semibold hover:underline mt-0.5" title="Mở trang truy xuất GS1 công khai">
                                    Mở GS1 Digital Link
                                </a>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-semibold text-stone-800">
                                    {{ $lot->sanPham?->ten_san_pham ?? 'Chưa phân loại' }}
                                </div>
                                <div class="text-[11px] text-stone-500">
                                    {{ $lot->loai_lo === 'NGUYEN_LIEU' ? 'Nguyên liệu thô' : 'Thành phẩm phân loại' }}
                                </div>
                            </td>

                            <td class="py-3 px-4 font-semibold text-stone-800">
                                {{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}
                            </td>

                            <td class="py-3 px-4">
                                <div class="text-stone-800 font-medium">
                                    {{ $lot->toChucNguon?->ten_to_chuc ?? 'N/A' }}
                                </div>
                                @if($lot->muaVu)
                                    <div class="text-[11px] text-stone-500">
                                        Vụ: {{ $lot->muaVu->ten_mua_vu }} ({{ $lot->muaVu->vungTrong?->ten_vung_trong ?? '' }})
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <span class="{{ $lot->getTrangThaiBadgeClass() }} text-[11px]">
                                    {{ $statusLabels[$lot->trang_thai] ?? $lot->trang_thai }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                @if($qc)
                                    <span class="{{ $qc->getKetLuanBadgeClass() }} text-[11px]">
                                        {{ $qc->ket_luan_label }}
                                    </span>
                                @else
                                    <span class="f2f-badge-neutral text-[11px]">Chưa kiểm</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($lot->khoLanh)
                                    <div class="font-medium text-stone-800">{{ $lot->khoLanh->ten_kho }}</div>
                                    <div class="text-[11px] text-stone-500">{{ $lot->khoLanh->ma_kho }}</div>
                                @else
                                    <span class="text-stone-400 italic text-[11px]">Chưa vào kho</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                @if($isGapped)
                                    <span class="f2f-badge-warning text-[11px]" title="Thiếu liên kết thành phần hoặc vụ canh tác">
                                        Đứt quãng
                                    </span>
                                @else
                                    <span class="f2f-badge-success text-[11px]">
                                        Đầy đủ
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('chain.lots.show', $lot->lo_nong_san_id) }}" class="f2f-btn-secondary text-[11px] py-1 px-2.5">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-stone-500">
                                Không tìm thấy lô nông sản nào khớp với điều kiện tìm kiếm.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lots->hasPages())
            <div class="p-4 border-t border-stone-200">
                {{ $lots->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


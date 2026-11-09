@extends('layouts.dashboard', ['title' => 'Đối tác Nhận hàng', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Đối tác nhận hàng</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">Quản Lý Đối Tác Nhận Hàng</h1>
            <p class="mt-0.5 text-xs text-stone-600">Danh sách khách hàng, đơn vị phân phối và đối tác cuối chuỗi tiếp nhận nông sản</p>
        </div>
        <a href="{{ route('chain.partners.create') }}" class="f2f-btn-primary text-xs">Thêm đối tác</a>
    </div>

    {{-- Flash --}}
    @if(session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    {{-- KPI --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng đối tác</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang hoạt động</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($stats['hoat_dong']) }}</strong>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tạm ngưng</span>
            <strong class="f2f-stat-value text-amber-700">{{ number_format($stats['tam_ngung']) }}</strong>
        </div>
    </div>

    {{-- Bộ lọc --}}
    <form method="GET" action="{{ route('chain.partners.index') }}" class="flex flex-col sm:flex-row gap-3">
        <input type="text" name="q" value="{{ $search }}"
            placeholder="Tìm tên, mã, email, SĐT..."
            class="f2f-input text-sm flex-1 max-w-xs">
        <select name="status" class="f2f-input text-sm w-auto">
            <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
            <option value="HOAT_DONG"      @selected($status === 'HOAT_DONG')>Đang hoạt động</option>
            <option value="TAM_NGUNG"      @selected($status === 'TAM_NGUNG')>Tạm ngưng</option>
            <option value="NGUNG_HOAT_DONG" @selected($status === 'NGUNG_HOAT_DONG')>Ngưng hoạt động</option>
        </select>
        <button type="submit" class="f2f-btn-secondary text-sm">Lọc</button>
        @if($search || $status !== 'all')
            <a href="{{ route('chain.partners.index') }}" class="f2f-btn-secondary text-sm">Xóa bộ lọc</a>
        @endif
    </form>

    {{-- Bảng --}}
    <div class="f2f-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên đối tác</th>
                        <th>MST</th>
                        <th>Liên hệ</th>
                        <th>Địa chỉ</th>
                        <th>Phiếu giao</th>
                        <th>Trạng thái</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                        <tr>
                            <td class="font-mono text-xs text-stone-600">{{ $partner->ma_to_chuc ?? '—' }}</td>
                            <td class="font-medium text-stone-900">{{ $partner->ten_to_chuc }}</td>
                            <td class="text-stone-600 text-sm">{{ $partner->ma_so_thue ?? '—' }}</td>
                            <td>
                                <div class="text-sm">{{ $partner->so_dien_thoai ?? '' }}</div>
                                <div class="text-xs text-stone-500">{{ $partner->email ?? '' }}</div>
                            </td>
                            <td class="text-sm text-stone-600 max-w-[180px] truncate" title="{{ $partner->dia_chi }}">
                                {{ $partner->dia_chi ?? '—' }}
                            </td>
                            <td class="text-center">
                                <span class="text-sm font-medium {{ $partner->tong_phieu_giao > 0 ? 'text-indigo-700' : 'text-stone-400' }}">
                                    {{ $partner->tong_phieu_giao }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $badge = match($partner->trang_thai) {
                                        'HOAT_DONG'       => 'f2f-badge-success',
                                        'TAM_NGUNG'       => 'f2f-badge-warning',
                                        'NGUNG_HOAT_DONG' => 'f2f-badge-danger',
                                        default           => 'f2f-badge-neutral',
                                    };
                                    $label = match($partner->trang_thai) {
                                        'HOAT_DONG'       => 'Hoạt động',
                                        'TAM_NGUNG'       => 'Tạm ngưng',
                                        'NGUNG_HOAT_DONG' => 'Ngưng',
                                        default           => $partner->trang_thai,
                                    };
                                @endphp
                                <span class="{{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('chain.partners.edit', $partner->to_chuc_id) }}"
                                   class="f2f-btn-secondary text-xs">Sửa</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-stone-500 py-8">
                                Chưa có đối tác nào.
                                <a href="{{ route('chain.partners.create') }}" class="text-indigo-600 underline ml-1">Thêm ngay</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($partners->hasPages())
            <div class="px-4 py-3 border-t border-stone-100">{{ $partners->links() }}</div>
        @endif
    </div>
</div>
@endsection


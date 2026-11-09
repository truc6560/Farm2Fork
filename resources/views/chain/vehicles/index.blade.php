@extends('layouts.dashboard', ['title' => 'Quản lý Phương tiện', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị chuỗi</span>
                <span>/</span>
                <span class="text-stone-800">Phương tiện vận chuyển</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">Quản Lý Đội Xe Lạnh</h1>
            <p class="mt-0.5 text-xs text-stone-600">Quản lý danh mục phương tiện vận chuyển chuỗi cung ứng lạnh</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('chain.vehicles.create') }}" class="f2f-btn-primary text-xs">Thêm phương tiện</a>
            <a href="{{ route('chain.transport.index') }}" class="f2f-btn-secondary text-xs">Giám sát vận chuyển</a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    {{-- KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng phương tiện</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Sẵn sàng</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($stats['san_sang']) }}</strong>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang hoạt động</span>
            <strong class="f2f-stat-value text-indigo-700">{{ number_format($stats['hoat_dong']) }}</strong>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang bảo dưỡng</span>
            <strong class="f2f-stat-value text-amber-700">{{ number_format($stats['bao_duong']) }}</strong>
        </div>
    </div>

    {{-- Bộ lọc --}}
    <form method="GET" action="{{ route('chain.vehicles.index') }}" class="flex flex-col sm:flex-row gap-3">
        <input type="text" name="q" value="{{ $search }}"
            placeholder="Tìm biển số, loại xe..."
            class="f2f-input text-sm flex-1 max-w-xs">
        <select name="status" class="f2f-input text-sm w-auto">
            <option value="all" @selected($status === 'all')>Tất cả trạng thái</option>
            @foreach($trangThaiLabels as $val => $label)
                <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="f2f-btn-secondary text-sm">Lọc</button>
        @if($search || $status !== 'all')
            <a href="{{ route('chain.vehicles.index') }}" class="f2f-btn-secondary text-sm">Xóa bộ lọc</a>
        @endif
    </form>

    {{-- Bảng --}}
    <div class="f2f-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Biển số</th>
                        <th>Loại xe</th>
                        <th>Tải trọng</th>
                        <th>Hệ thống lạnh</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                        <tr>
                            <td class="font-mono font-semibold text-stone-900">{{ $vehicle->bien_so }}</td>
                            <td>{{ $vehicle->loai_xe }}</td>
                            <td>{{ number_format($vehicle->tai_trong, 0) }} {{ $vehicle->don_vi_tai_trong }}</td>
                            <td>
                                @if($vehicle->co_he_thong_lam_lanh)
                                    <span class="f2f-badge-success">Có</span>
                                @else
                                    <span class="f2f-badge-neutral">Không</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($vehicle->trang_thai) {
                                        'SAN_SANG'         => 'f2f-badge-success',
                                        'DANG_HOAT_DONG'   => 'f2f-badge-info',
                                        'BAO_DUONG'        => 'f2f-badge-warning',
                                        'NGUNG_HOAT_DONG'  => 'f2f-badge-danger',
                                        default            => 'f2f-badge-neutral',
                                    };
                                @endphp
                                <span class="{{ $badgeClass }}">{{ $trangThaiLabels[$vehicle->trang_thai] ?? $vehicle->trang_thai }}</span>
                            </td>
                            <td class="text-stone-500 text-xs">{{ $vehicle->ghi_chu ?? '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('chain.vehicles.edit', $vehicle->phuong_tien_id) }}"
                                   class="f2f-btn-secondary text-xs">Sửa</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-stone-500 py-8">
                                Chưa có phương tiện nào.
                                <a href="{{ route('chain.vehicles.create') }}" class="text-indigo-600 underline ml-1">Thêm ngay</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vehicles->hasPages())
            <div class="px-4 py-3 border-t border-stone-100">{{ $vehicles->links() }}</div>
        @endif
    </div>
</div>
@endsection


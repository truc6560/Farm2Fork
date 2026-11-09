@extends('layouts.dashboard', ['title' => 'Quản lý Kho Lạnh', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Quản trị hệ thống</span>
                <span>/</span>
                <span class="text-stone-800">Cơ sở vật chất</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Quản Lý Kho Lạnh & Hạ Tầng Bảo Quản
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Danh mục kho bảo quản nông sản, phân công nhân viên phụ trách và thiết lập thông số vận hành
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.thresholds.index') }}" class="f2f-btn-secondary text-xs">
                Cấu hình ngưỡng nhiệt độ
            </a>
            <a href="{{ route('admin.warehouses.create') }}" class="f2f-btn-primary text-xs">
                Thêm kho lạnh mới
            </a>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session('status'))
        <div class="f2f-alert f2f-alert-success">
            {{ session('status') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if (isset($errors) && $errors->any())
        <div class="f2f-alert f2f-alert-error">
            <ul class="list-disc list-inside text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số kho lạnh</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($totalCount) }}</strong>
            <span class="f2f-stat-hint">Hệ thống kho toàn chuỗi</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang hoạt động</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($activeCount) }}</strong>
            <span class="f2f-stat-hint">Sẵn sàng tiếp nhận & bảo quản</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Bảo trì / Tạm ngưng</span>
            <strong class="f2f-stat-value {{ $maintenanceCount > 0 ? 'text-amber-600' : 'text-stone-800' }}">
                {{ number_format($maintenanceCount) }}
            </strong>
            <span class="f2f-stat-hint">Đang bảo dưỡng kỹ thuật</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng sức chứa thiết kế</span>
            <strong class="f2f-stat-value text-sky-700">{{ number_format($totalCapacity) }}</strong>
            <span class="f2f-stat-hint">Khối lượng bảo quản tối đa (kg)</span>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.warehouses.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 text-xs flex-1">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo tên kho, mã kho, địa chỉ..." class="f2f-input text-xs w-full sm:w-80">

                <select name="trang_thai" class="f2f-select text-xs">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statusLabels as $sCode => $sLabel)
                        <option value="{{ $sCode }}" {{ ($filters['trang_thai'] ?? '') === $sCode ? 'selected' : '' }}>
                            {{ $sLabel }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="f2f-btn-primary text-xs">
                    Lọc
                </button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('admin.warehouses.index') }}" class="f2f-btn-secondary text-xs">
                        Xóa lọc
                    </a>
                @endif
            </div>

            <div class="text-xs text-stone-500 text-right">
                Hiển thị <strong>{{ $warehouses->count() }}</strong> / {{ $warehouses->total() }} kho lạnh
            </div>
        </form>
    </div>

    {{-- Main Warehouses Table --}}
    <div class="bg-white rounded-xl border border-stone-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 uppercase tracking-wider font-semibold">
                        <th class="py-3 px-4">Mã kho</th>
                        <th class="py-3 px-4">Tên kho lạnh</th>
                        <th class="py-3 px-4">Địa chỉ kho</th>
                        <th class="py-3 px-4">Sức chứa thiết kế</th>
                        <th class="py-3 px-4">Nhân viên phụ trách</th>
                        <th class="py-3 px-4">Cảm biến IoT</th>
                        <th class="py-3 px-4">Trạng thái</th>
                        <th class="py-3 px-4 text-right whitespace-nowrap" style="min-width: 110px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 text-stone-700">
                    @forelse($warehouses as $wh)
                        <tr class="hover:bg-stone-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold text-stone-900">
                                {{ $wh->ma_kho }}
                            </td>

                            <td class="py-3 px-4 font-semibold text-stone-900">
                                {{ $wh->ten_kho }}
                                <div class="text-[11px] text-stone-500 font-normal mt-0.5">
                                    {{ $wh->lo_nong_san_count }} lô hàng · {{ $wh->don_vi_dong_goi_count }} kiện SSCC
                                </div>
                            </td>

                            <td class="py-3 px-4 text-stone-600 max-w-xs">
                                {{ $wh->dia_chi }}
                            </td>

                            <td class="py-3 px-4">
                                <span class="font-bold text-stone-800">{{ number_format($wh->suc_chua) }}</span>
                                <span class="text-stone-500">{{ $wh->don_vi_suc_chua }}</span>
                            </td>

                            <td class="py-3 px-4">
                                @if($wh->nhanVien->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($wh->nhanVien as $staff)
                                            <span class="inline-block px-2 py-0.5 rounded bg-stone-100 text-stone-800 text-[11px] font-medium border border-stone-200">
                                                {{ $staff->ho_ten }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-stone-400 italic text-[11px]">Chưa phân công</span>
                                @endif
                            </td>

                            <td class="py-3 px-4">
                                <span class="font-semibold text-stone-800">{{ $wh->thietBiIot->count() }}</span>
                                <span class="text-stone-500">thiết bị</span>
                            </td>

                            <td class="py-3 px-4">
                                <span class="{{ $wh->getTrangThaiBadgeClass() }} text-[11px]">
                                    {{ $wh->trang_thai_label }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end">
                                    <a href="{{ route('admin.warehouses.edit', $wh->kho_lanh_id) }}" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-200 bg-white text-stone-700 hover:text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50 text-xs font-semibold shadow-xs transition whitespace-nowrap">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <span>Chỉnh sửa</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-stone-500">
                                Không tìm thấy kho lạnh nào phù hợp với bộ lọc tìm kiếm.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warehouses->hasPages())
            <div class="p-4 border-t border-stone-200">
                {{ $warehouses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

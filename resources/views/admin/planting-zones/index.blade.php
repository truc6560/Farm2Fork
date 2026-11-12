@extends('layouts.dashboard', ['title' => 'Tra cứu vùng trồng', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-stone-900">Tra cứu vùng trồng</h1>
        <p class="mt-1 text-sm text-stone-600">Xem các vùng trồng nội bộ, người phụ trách và mùa vụ liên quan.</p>
    </div>

    <form method="GET" action="{{ route('admin.planting-zones.index') }}" class="f2f-card p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 items-end">
        <div class="xl:col-span-2">
            <label class="f2f-label" for="zone-q">Tên, mã hoặc cây trồng</label>
            <input class="f2f-input w-full" id="zone-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm vùng trồng...">
        </div>
        <div>
            <label class="f2f-label" for="zone-person">Người sản xuất</label>
            <select class="f2f-input w-full" id="zone-person" name="nguoi_san_xuat_id">
                <option value="">Tất cả</option>
                @foreach($producers as $producer)
                    <option value="{{ $producer->tai_khoan_id }}" @selected(($filters['nguoi_san_xuat_id'] ?? '') == $producer->tai_khoan_id)>{{ $producer->ho_ten }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f2f-label" for="zone-state">Trạng thái</label>
            <select class="f2f-input w-full" id="zone-state" name="trang_thai">
                <option value="">Tất cả</option>
                @foreach(\App\Models\VungTrong::TRANG_THAI_LABELS as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['trang_thai'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="f2f-label" for="zone-area">Khu vực / địa chỉ</label>
            <input class="f2f-input w-full" id="zone-area" name="khu_vuc" value="{{ $filters['khu_vuc'] ?? '' }}" placeholder="Ví dụ: Đà Lạt">
        </div>
        <div class="xl:col-span-5 flex flex-wrap gap-2">
            <button type="submit" class="f2f-btn-primary">Tìm kiếm</button>
            <a href="{{ route('admin.planting-zones.index') }}" class="f2f-btn-secondary">Xóa lọc</a>
        </div>
    </form>

    <div class="f2f-card overflow-hidden">
        <div class="p-4 border-b border-stone-200 text-sm text-stone-600">Tìm thấy <strong>{{ $zones->total() }}</strong> vùng trồng</div>
        <div class="divide-y divide-stone-100">
            @forelse($zones as $zone)
                @php($ownerNames = $zone->nhanVienPhanCong->pluck('ho_ten')->join(', '))
                <a href="{{ route('admin.planting-zones.show', $zone->vung_trong_id) }}" class="block p-4 hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-emerald-700">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-2">
                        <div>
                            <div class="font-semibold text-stone-900">{{ $zone->ten_vung_trong }} <span class="text-xs font-normal text-stone-500">{{ $zone->ma_noi_bo }}</span></div>
                            <div class="text-sm text-stone-600 mt-1">{{ $zone->dia_chi ?: 'Chưa có địa chỉ' }}</div>
                            <div class="text-xs text-stone-500 mt-1">{{ $zone->cay_trong_chinh ?: 'Chưa ghi cây trồng' }} · {{ $zone->dien_tich_ha }} ha · {{ $zone->mua_vu_count }} mùa vụ</div>
                        </div>
                        <div class="text-sm sm:text-right">
                            <div class="font-medium text-emerald-800">{{ $zone->nguoiPhuTrach?->ho_ten ?: ($ownerNames ?: 'Chưa phân công') }}</div>
                            <div class="text-xs text-stone-500 mt-1">{{ \App\Models\VungTrong::TRANG_THAI_LABELS[$zone->trang_thai] ?? $zone->trang_thai }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <p class="p-8 text-center text-stone-600">Không có vùng trồng phù hợp bộ lọc.</p>
            @endforelse
        </div>
    </div>
    {{ $zones->links() }}
</div>
@endsection

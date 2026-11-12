@extends('layouts.dashboard', ['title' => 'Chi tiết vùng trồng', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="space-y-5">
    <a href="{{ route('admin.planting-zones.index') }}" class="inline-flex text-sm font-semibold text-emerald-800 hover:underline">← Danh sách vùng trồng</a>
    <div>
        <h1 class="text-2xl font-bold text-stone-900">{{ $zone->ten_vung_trong }}</h1>
        <p class="text-sm text-stone-600 mt-1">{{ $zone->ma_noi_bo }} · {{ \App\Models\VungTrong::TRANG_THAI_LABELS[$zone->trang_thai] ?? $zone->trang_thai }}</p>
    </div>
    <section class="f2f-card p-5">
        <h2 class="text-lg font-bold text-stone-900 mb-4">Thông tin vùng trồng</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div><dt class="text-stone-500">Địa chỉ / khu vực</dt><dd class="font-medium text-stone-900 mt-1">{{ $zone->dia_chi ?: 'Chưa cập nhật' }}</dd></div>
            <div><dt class="text-stone-500">Diện tích</dt><dd class="font-medium text-stone-900 mt-1">{{ $zone->dien_tich_ha }} ha</dd></div>
            <div><dt class="text-stone-500">Cây trồng chính</dt><dd class="font-medium text-stone-900 mt-1">{{ $zone->cay_trong_chinh ?: 'Chưa cập nhật' }}</dd></div>
            <div><dt class="text-stone-500">Đơn vị nội bộ</dt><dd class="font-medium text-stone-900 mt-1">{{ $zone->toChuc?->ten_to_chuc ?? 'Chưa cập nhật' }}</dd></div>
            <div><dt class="text-stone-500">Mã số vùng trồng được khai báo</dt><dd class="font-medium text-stone-900 mt-1">{{ $zone->ma_so_vung_trong ?: 'Chưa khai báo' }}</dd></div>
            <div><dt class="text-stone-500">Người phụ trách chính</dt><dd class="font-medium text-stone-900 mt-1">{{ $zone->nguoiPhuTrach?->ho_ten ?? 'Chưa phân công' }}</dd></div>
        </dl>
        <p class="text-xs text-stone-500 mt-5">Mã số vùng trồng được hiển thị theo dữ liệu đã khai báo; trang này không xác minh giá trị pháp lý của mã.</p>
    </section>
    <section class="f2f-card p-5">
        <h2 class="text-lg font-bold text-stone-900 mb-3">Người đang phụ trách</h2>
        @forelse($zone->nhanVienPhanCong as $person)
            <div class="py-2 border-b border-stone-100 last:border-0 text-sm text-stone-800">{{ $person->ho_ten }} <span class="text-stone-500">· Phân công từ {{ $person->pivot->ngay_phan_cong ?: 'chưa rõ ngày' }}</span></div>
        @empty
            <p class="text-sm text-stone-600">Chưa có phân công trong danh sách nhân viên vùng trồng.</p>
        @endforelse
    </section>
    <section class="f2f-card p-5">
        <h2 class="text-lg font-bold text-stone-900 mb-3">Mùa vụ liên quan ({{ $zone->muaVu->count() }})</h2>
        @forelse($zone->muaVu->sortByDesc('mua_vu_id') as $season)
            <div class="py-3 border-b border-stone-100 last:border-0">
                <div class="font-semibold text-sm text-stone-900">{{ $season->ten_mua_vu ?: $season->ma_mua_vu }} <span class="font-normal text-stone-500">· {{ $season->ma_mua_vu }}</span></div>
                <div class="text-xs text-stone-600 mt-1">{{ $season->sanPham?->ten_san_pham ?? 'Chưa ghi sản phẩm' }} · {{ $season->trang_thai }}</div>
            </div>
        @empty
            <p class="text-sm text-stone-600">Vùng trồng này chưa có mùa vụ.</p>
        @endforelse
    </section>
</div>
@endsection

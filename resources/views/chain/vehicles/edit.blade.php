@extends('layouts.dashboard', ['title' => 'Sửa Phương tiện', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
<div class="max-w-xl space-y-6">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
            <a href="{{ route('chain.vehicles.index') }}" class="hover:text-stone-800">Phương tiện</a>
            <span>/</span>
            <span class="text-stone-800">Chỉnh sửa</span>
        </div>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">Chỉnh Sửa: {{ $vehicle->bien_so }}</h1>
    </div>

    <form method="POST" action="{{ route('chain.vehicles.update', $vehicle->phuong_tien_id) }}" class="f2f-card p-6 space-y-5">
        @csrf
        @method('PUT')
        @include('chain.vehicles._form', ['vehicle' => $vehicle])

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="f2f-btn-primary">Lưu thay đổi</button>
            <a href="{{ route('chain.vehicles.index') }}" class="f2f-btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection


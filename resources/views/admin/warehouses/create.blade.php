@extends('layouts.dashboard', ['title' => 'Thêm Kho Lạnh Mới', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('admin.warehouses.index') }}" class="hover:underline">Quản lý kho lạnh</a>
                <span>/</span>
                <span class="text-stone-800">Thêm mới</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 mt-1">
                Thêm Kho Lạnh Mới
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Khai báo cơ sở hạ tầng kho lạnh mới trong mạng lưới chuỗi cung ứng Farm2Fork
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.warehouses.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại danh sách
            </a>
        </div>
    </div>

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

    {{-- Main Create Form --}}
    <div class="bg-white rounded-xl border border-stone-200 p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.warehouses.store') }}" class="space-y-6">
            @csrf

            <div class="border-b border-stone-100 pb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                    Thông Tin Cơ Bản Kho Lạnh
                </h2>
                <p class="text-xs text-stone-500 mt-0.5">Nhập các thông số kỹ thuật và địa điểm đặt kho</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                {{-- Tên kho --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Tên kho lạnh <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="ten_kho" value="{{ old('ten_kho') }}" required class="f2f-input text-xs w-full" placeholder="Ví dụ: Kho lạnh trung tâm TP.HCM (0-4°C)">
                    <span class="text-[11px] text-stone-400">Tên mô tả rõ đặc điểm, vị trí hoặc dải nhiệt độ vận hành.</span>
                </div>

                {{-- Mã kho --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Mã định danh kho <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="ma_kho" value="{{ old('ma_kho') }}" required class="f2f-input text-xs w-full font-mono uppercase" placeholder="Ví dụ: KL-HCM-03">
                    <span class="text-[11px] text-stone-400">Mã kho viết hoa duy nhất, không trùng lặp với các kho đã có.</span>
                </div>

                {{-- Sức chứa --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Sức chứa thiết kế <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.001" min="0.001" name="suc_chua" value="{{ old('suc_chua') }}" required class="f2f-input text-xs w-full" placeholder="Ví dụ: 15000">
                    <span class="text-[11px] text-stone-400">Sức chứa tối đa theo đơn vị đo lường tương ứng.</span>
                </div>

                {{-- Đơn vị sức chứa --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Đơn vị tính sức chứa <span class="text-rose-500">*</span>
                    </label>
                    <select name="don_vi_suc_chua" required class="f2f-select text-xs w-full">
                        @foreach($unitLabels as $uCode => $uLabel)
                            <option value="{{ $uCode }}" {{ old('don_vi_suc_chua', 'kg') === $uCode ? 'selected' : '' }}>
                                {{ $uLabel }} ({{ $uCode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Địa chỉ kho --}}
                <div class="md:col-span-2 space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Địa chỉ thực tế của kho <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="dia_chi" value="{{ old('dia_chi') }}" required class="f2f-input text-xs w-full" placeholder="Ví dụ: Lô B2, Khu Công Nghiệp Tân Bình, Tây Thạnh, Tân Phú, TP.HCM">
                </div>

                {{-- Trạng thái vận hành --}}
                <div class="md:col-span-2 space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Trạng thái vận hành ban đầu <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                        @foreach($statusLabels as $sCode => $sLabel)
                            <label class="p-3 rounded-lg border cursor-pointer transition flex items-center gap-2.5 text-xs {{ old('trang_thai', 'DANG_HOAT_DONG') === $sCode ? 'border-emerald-600 bg-emerald-50/60 font-bold text-emerald-900' : 'border-stone-200 hover:bg-stone-50 text-stone-700' }}">
                                <input type="radio" name="trang_thai" value="{{ $sCode }}" {{ old('trang_thai', 'DANG_HOAT_DONG') === $sCode ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                                <span>{{ $sLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Phân công nhân viên kho --}}
            <div class="pt-4 border-t border-stone-100 space-y-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Phân Công Nhân Viên Quản Lý Kho
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Chọn các nhân sự phụ trách vận hành và tiếp nhận lô nông sản tại kho này</p>
                </div>

                @php
                    $assignedStaffIds = old('nhan_vien_ids', []);
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    @forelse($warehouseStaff as $staff)
                        <label class="p-3 rounded-lg border cursor-pointer transition flex items-start gap-2.5 {{ in_array($staff->tai_khoan_id, $assignedStaffIds) ? 'border-emerald-500 bg-emerald-50/50' : 'border-stone-200 hover:bg-stone-50' }}">
                            <input type="checkbox" name="nhan_vien_ids[]" value="{{ $staff->tai_khoan_id }}" {{ in_array($staff->tai_khoan_id, $assignedStaffIds) ? 'checked' : '' }} class="mt-0.5 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <strong class="text-stone-900 block">{{ $staff->ho_ten }}</strong>
                                <span class="text-stone-500 text-[11px] block">{{ $staff->email }}</span>
                                <span class="text-stone-400 text-[11px] block mt-0.5">SĐT: {{ $staff->so_dien_thoai ?: 'Chưa cập nhật' }}</span>
                            </div>
                        </label>
                    @empty
                        <div class="col-span-full p-4 bg-stone-50 rounded-lg text-stone-500 text-xs">
                            Chưa có tài khoản nào thuộc vai trò Nhân viên kho lạnh trong hệ thống.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-stone-100">
                <a href="{{ route('admin.warehouses.index') }}" class="f2f-btn-secondary text-xs">
                    Hủy bỏ
                </a>
                <button type="submit" class="f2f-btn-primary text-xs">
                    Tạo kho lạnh mới
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

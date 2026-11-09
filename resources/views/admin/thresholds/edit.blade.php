@extends('layouts.dashboard', ['title' => 'Chỉnh sửa ngưỡng cảnh báo', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('admin.thresholds.index') }}" class="hover:underline">Ngưỡng cảnh báo</a>
                <span>/</span>
                <span class="text-stone-800">Chỉnh sửa cấu hình #{{ $threshold->cau_hinh_id }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 mt-1">
                <h1 class="text-2xl font-bold tracking-tight text-stone-900">
                    Chỉnh Sửa Ngưỡng Cảnh Báo
                </h1>
                @php
                    $isActive = in_array($threshold->trang_thai ?? '', ['HIEU_LUC', 'DANG_AP_DUNG']);
                @endphp
                <span class="f2f-badge {{ $isActive ? 'f2f-badge-success' : 'f2f-badge-neutral' }} text-xs">
                    {{ $isActive ? 'Hiệu lực' : 'Tạm ngưng' }}
                </span>
                @if($threshold->ten_kho)
                    <span class="font-mono text-xs font-bold text-stone-500 bg-stone-100 px-2 py-0.5 rounded border border-stone-200">
                        {{ $threshold->ten_kho }} ({{ $threshold->ma_kho ?? 'Kho #' . $threshold->kho_lanh_id }})
                    </span>
                @endif
            </div>
            <p class="mt-0.5 text-xs text-stone-600">
                Cập nhật tiêu chuẩn an toàn nhiệt độ, độ ẩm và thời gian cảnh báo cho kho lạnh
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.thresholds.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại danh sách
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

    {{-- Main Edit Form --}}
    <div class="bg-white rounded-xl border border-stone-200 p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.thresholds.update', $threshold->cau_hinh_id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="border-b border-stone-100 pb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                    Thông Số Quy Chuẩn Ngưỡng Giám Sát IoT
                </h2>
                <p class="text-xs text-stone-500 mt-0.5">Hệ thống sẽ kích hoạt cảnh báo vi phạm khi chỉ số cảm biến vượt ra ngoài phạm vi này</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                {{-- Kho lạnh --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Kho lạnh áp dụng <span class="text-rose-500">*</span>
                    </label>
                    <select class="f2f-select text-xs w-full" name="kho_lanh_id" required>
                        <option value="">-- Chọn kho lạnh --</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->kho_lanh_id }}" {{ old('kho_lanh_id', $threshold->kho_lanh_id) == $w->kho_lanh_id ? 'selected' : '' }}>
                                {{ $w->ten_kho }} ({{ $w->ma_kho ?? 'KL-' . $w->kho_lanh_id }})
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-stone-400">Kho lạnh được áp dụng quy chuẩn kiểm soát nhiệt độ này.</span>
                </div>

                {{-- Trạng thái --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Trạng thái áp dụng <span class="text-rose-500">*</span>
                    </label>
                    <select class="f2f-select text-xs w-full" name="trang_thai" required>
                        <option value="HIEU_LUC" {{ in_array(old('trang_thai', $threshold->trang_thai), ['HIEU_LUC', 'DANG_AP_DUNG']) ? 'selected' : '' }}>
                            Hiệu lực (Đang kích hoạt giám sát)
                        </option>
                        <option value="NGUNG" {{ old('trang_thai', $threshold->trang_thai) === 'NGUNG' ? 'selected' : '' }}>
                            Tạm ngưng (Không kích hoạt cảnh báo)
                        </option>
                    </select>
                    <span class="text-[11px] text-stone-400">Bật hoặc ngưng kích hoạt theo dõi tự động cho ngưỡng này.</span>
                </div>

                {{-- Mô tả quy chuẩn --}}
                <div class="md:col-span-2 space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Mô tả quy chuẩn / Tiêu chuẩn áp dụng
                    </label>
                    <input class="f2f-input text-xs w-full" type="text" name="mo_ta" value="{{ old('mo_ta', $threshold->mo_ta) }}" placeholder="Ví dụ: Tiêu chuẩn bảo quản rau quả tươi xuất khẩu (0-4°C)">
                    <span class="text-[11px] text-stone-400">Mô tả rõ loại nông sản hoặc tiêu chuẩn kỹ thuật (HACCP, GlobalGAP, TCVN...).</span>
                </div>

                {{-- Nhiệt độ min --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Nhiệt độ tối thiểu (°C) <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input text-xs w-full" type="number" step="0.1" name="nhiet_do_min" value="{{ old('nhiet_do_min', $threshold->nhiet_do_min) }}" placeholder="Ví dụ: 2.0" required>
                    <span class="text-[11px] text-stone-400">Nhiệt độ dưới mức này sẽ coi là quá lạnh / đóng băng.</span>
                </div>

                {{-- Nhiệt độ max --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Nhiệt độ tối đa (°C) <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input text-xs w-full" type="number" step="0.1" name="nhiet_do_max" value="{{ old('nhiet_do_max', $threshold->nhiet_do_max) }}" placeholder="Ví dụ: 8.0" required>
                    <span class="text-[11px] text-stone-400">Nhiệt độ trên mức này sẽ coi là đứt chuỗi lạnh.</span>
                </div>

                {{-- Độ ẩm min --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Độ ẩm tối thiểu (%)
                    </label>
                    <input class="f2f-input text-xs w-full" type="number" step="0.1" name="do_am_min" value="{{ old('do_am_min', $threshold->do_am_min) }}" placeholder="Ví dụ: 85.0">
                    <span class="text-[11px] text-stone-400">Để trống nếu không yêu cầu kiểm soát độ ẩm tối thiểu.</span>
                </div>

                {{-- Độ ẩm max --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Độ ẩm tối đa (%)
                    </label>
                    <input class="f2f-input text-xs w-full" type="number" step="0.1" name="do_am_max" value="{{ old('do_am_max', $threshold->do_am_max) }}" placeholder="Ví dụ: 95.0">
                    <span class="text-[11px] text-stone-400">Để trống nếu không yêu cầu kiểm soát độ ẩm tối đa.</span>
                </div>

                {{-- Thời gian vượt ngưỡng --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Thời gian trễ vượt ngưỡng (phút)
                    </label>
                    <input class="f2f-input text-xs w-full" type="number" min="1" name="thoi_gian_vuot_nguong_phut" value="{{ old('thoi_gian_vuot_nguong_phut', $threshold->thoi_gian_vuot_nguong_phut ?? 15) }}" placeholder="Mặc định: 15">
                    <span class="text-[11px] text-stone-400">Số phút vượt ngưỡng liên tục trước khi hệ thống phát sinh cảnh báo vi phạm.</span>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-stone-100">
                <a href="{{ route('admin.thresholds.index') }}" class="f2f-btn-secondary text-xs">
                    Hủy bỏ
                </a>
                <button type="submit" class="f2f-btn-primary text-xs">
                    Lưu thay đổi cấu hình
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.dashboard', ['title' => 'Chỉnh sửa Kho Lạnh', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('admin.warehouses.index') }}" class="hover:underline">Quản lý kho lạnh</a>
                <span>/</span>
                <span class="text-stone-800">Chỉnh sửa</span>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 mt-1">
                <h1 class="text-2xl font-bold tracking-tight text-stone-900">
                    Chỉnh Sửa Kho Lạnh: {{ $warehouse->ten_kho }}
                </h1>
                <span class="{{ $warehouse->getTrangThaiBadgeClass() }} text-xs">
                    {{ $warehouse->trang_thai_label }}
                </span>
                <span class="font-mono text-xs font-bold text-stone-500 bg-stone-100 px-2 py-0.5 rounded border border-stone-200">
                    {{ $warehouse->ma_kho }}
                </span>
            </div>
            <p class="mt-0.5 text-xs text-stone-600">
                Cập nhật thông tin thông số kỹ thuật, sức chứa, địa chỉ kho và phân công nhân sự quản lý
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.warehouses.index') }}" class="f2f-btn-secondary text-xs">
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
        <form method="POST" action="{{ route('admin.warehouses.update', $warehouse->kho_lanh_id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="border-b border-stone-100 pb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                    Thông Tin Cơ Bản Kho Lạnh
                </h2>
                <p class="text-xs text-stone-500 mt-0.5">Các thông số nhận diện và vị trí đặt kho trong chuỗi cung ứng</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                {{-- Tên kho --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Tên kho lạnh <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="ten_kho" value="{{ old('ten_kho', $warehouse->ten_kho) }}" required class="f2f-input text-xs w-full" placeholder="Ví dụ: Kho lạnh sơ chế Đà Lạt (0-4°C)">
                    <span class="text-[11px] text-stone-400">Tên mô tả rõ dải nhiệt độ hoặc khu vực phục vụ.</span>
                </div>

                {{-- Mã kho --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Mã định danh kho <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="ma_kho" value="{{ old('ma_kho', $warehouse->ma_kho) }}" required class="f2f-input text-xs w-full font-mono uppercase" placeholder="Ví dụ: KL-DL-01">
                    <span class="text-[11px] text-stone-400">Mã kho viết hoa duy nhất, dùng trong truy xuất và mã vạch.</span>
                </div>

                {{-- Sức chứa --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Sức chứa thiết kế <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.001" min="0.001" name="suc_chua" value="{{ old('suc_chua', $warehouse->suc_chua) }}" required class="f2f-input text-xs w-full" placeholder="Ví dụ: 20000">
                    <span class="text-[11px] text-stone-400">Khối lượng hoặc dung tích lưu trữ tối đa cho phép.</span>
                </div>

                {{-- Đơn vị sức chứa --}}
                <div class="space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Đơn vị tính sức chứa <span class="text-rose-500">*</span>
                    </label>
                    <select name="don_vi_suc_chua" required class="f2f-select text-xs w-full">
                        @foreach($unitLabels as $uCode => $uLabel)
                            <option value="{{ $uCode }}" {{ old('don_vi_suc_chua', $warehouse->don_vi_suc_chua) === $uCode ? 'selected' : '' }}>
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
                    <input type="text" name="dia_chi" value="{{ old('dia_chi', $warehouse->dia_chi) }}" required class="f2f-input text-xs w-full" placeholder="Ví dụ: Thôn Trại Mát, TP. Đà Lạt, tỉnh Lâm Đồng">
                </div>

                {{-- Trạng thái vận hành --}}
                <div class="md:col-span-2 space-y-1">
                    <label class="block font-semibold text-stone-700">
                        Trạng thái vận hành kho <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                        @foreach($statusLabels as $sCode => $sLabel)
                            <label class="p-3 rounded-lg border cursor-pointer transition flex items-center gap-2.5 text-xs {{ old('trang_thai', $warehouse->trang_thai) === $sCode ? 'border-emerald-600 bg-emerald-50/60 font-bold text-emerald-900' : 'border-stone-200 hover:bg-stone-50 text-stone-700' }}">
                                <input type="radio" name="trang_thai" value="{{ $sCode }}" {{ old('trang_thai', $warehouse->trang_thai) === $sCode ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
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
                    <p class="text-xs text-stone-500 mt-0.5">Chọn các nhân sự thuộc vai trò Nhân viên kho lạnh phụ trách vận hành và tiếp nhận tại kho này</p>
                </div>

                @php
                    $assignedStaffIds = old('nhan_vien_ids', $warehouse->nhanVien->pluck('tai_khoan_id')->toArray());
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
                    Lưu thay đổi kho lạnh
                </button>
            </div>
        </form>
    </div>

    {{-- SECONDARY PANELS: IOT DEVICES & THRESHOLD CONFIGS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- IoT Sensors in this warehouse --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Cảm Biến IoT Gắn Tại Kho Này
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">{{ $warehouse->thietBiIot->count() }} thiết bị đo đạc nhiệt độ/độ ẩm</p>
                </div>
            </div>

            <div class="space-y-2.5 text-xs">
                @forelse($warehouse->thietBiIot as $sensor)
                    <div class="p-3 rounded-lg border border-stone-200 bg-stone-50 flex items-start justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="font-mono font-bold text-stone-900">{{ $sensor->ma_thiet_bi }}</div>
                            <div class="text-stone-600">{{ $sensor->loai_thiet_bi }}</div>
                            @if($sensor->latestReading)
                                <div class="font-semibold text-emerald-700 mt-1">
                                    Nhiệt độ đo: {{ $sensor->latestReading->nhiet_do }}°C · Độ ẩm: {{ $sensor->latestReading->do_am }}%
                                </div>
                                <div class="text-[11px] text-stone-400">
                                    Cập nhật: {{ $sensor->latestReading->thoi_gian_do ? \Carbon\Carbon::parse($sensor->latestReading->thoi_gian_do)->format('d/m/Y H:i:s') : 'N/A' }}
                                </div>
                            @else
                                <div class="text-stone-400 italic text-[11px] mt-1">Chưa có tín hiệu đo</div>
                            @endif
                        </div>
                        <div>
                            <span class="f2f-badge {{ $sensor->trang_thai === 'HOAT_DONG' ? 'f2f-badge-success' : 'f2f-badge-neutral' }} text-[11px]">
                                {{ $sensor->trang_thai }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-stone-500">
                        Chưa có thiết bị cảm biến IoT nào được gán cho kho này.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Threshold Configs for this warehouse --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800">
                        Cấu Hình Ngưỡng Cảnh Báo Áp Dụng
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Tiêu chuẩn an toàn nhiệt độ và độ ẩm cho kho</p>
                </div>
                <a href="{{ route('admin.thresholds.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">
                    Quản lý ngưỡng
                </a>
            </div>

            <div class="space-y-2.5 text-xs">
                @forelse($thresholds as $t)
                    <div class="p-3 rounded-lg border border-stone-200 bg-stone-50 flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="font-bold text-stone-900">{{ $t->mo_ta ?: 'Ngưỡng bảo quản kho lạnh' }}</div>
                            <div class="text-stone-700">
                                Dải nhiệt độ an toàn: <strong class="text-emerald-700">{{ $t->nhiet_do_min }}°C – {{ $t->nhiet_do_max }}°C</strong>
                            </div>
                            @if($t->do_am_min || $t->do_am_max)
                                <div class="text-stone-600">
                                    Độ ẩm cho phép: {{ $t->do_am_min }}% – {{ $t->do_am_max }}%
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="f2f-badge {{ in_array($t->trang_thai ?? '', ['HIEU_LUC', 'DANG_AP_DUNG']) ? 'f2f-badge-success' : 'f2f-badge-neutral' }} text-[11px]">
                                {{ in_array($t->trang_thai ?? '', ['HIEU_LUC', 'DANG_AP_DUNG']) ? 'Hiệu lực' : 'Tạm ngưng' }}
                            </span>
                            <a href="{{ route('admin.thresholds.edit', $t->cau_hinh_id) }}" class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 font-semibold text-xs hover:underline">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Sửa
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-stone-500">
                        Chưa có quy chuẩn ngưỡng nào được thiết lập cho kho này.
                        <div class="mt-2">
                            <a href="{{ route('admin.thresholds.index') }}" class="text-emerald-700 font-semibold hover:underline">
                                Thiết lập ngưỡng cảnh báo ngay
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

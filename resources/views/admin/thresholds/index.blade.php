@extends('layouts.dashboard', ['title' => 'Cấu hình hệ thống', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
{{-- Flash Message --}}
@if (session('status'))
    <div class="f2f-alert f2f-alert-success" style="margin-bottom: 16px;">
        {{ session('status') }}
    </div>
@endif

{{-- Validation Errors --}}
@if (isset($errors) && $errors->any())
    <div class="f2f-alert f2f-alert-error" style="margin-bottom: 16px;">
        <ul class="list-disc list-inside text-xs">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="f2f-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Thêm ngưỡng cảnh báo kho lạnh</h2>
            <p class="f2f-subtitle">Thiết lập ngưỡng an toàn về nhiệt độ và độ ẩm giám sát bằng cảm biến IoT.</p>
        </div>
        <div>
            <a href="{{ route('admin.warehouses.index') }}" class="f2f-btn-secondary text-xs">
                Quản lý danh mục kho lạnh
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.thresholds.store') }}" class="f2f-form-body" style="margin-top:0">
        @csrf
        <div class="f2f-form-grid">
            <div class="f2f-field">
                <label class="f2f-label">Kho lạnh áp dụng <span class="text-rose-500">*</span></label>
                <select class="f2f-input" name="kho_lanh_id" required>
                    <option value="">-- Chọn kho lạnh --</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->kho_lanh_id }}" {{ old('kho_lanh_id') == $w->kho_lanh_id ? 'selected' : '' }}>
                            {{ $w->ten_kho }} ({{ $w->ma_kho ?? 'KL-' . $w->kho_lanh_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Mô tả quy chuẩn</label>
                <input class="f2f-input" name="mo_ta" value="{{ old('mo_ta') }}" placeholder="Ví dụ: Tiêu chuẩn bảo quản rau quả tươi">
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Nhiệt độ tối thiểu (°C) <span class="text-rose-500">*</span></label>
                <input class="f2f-input" type="number" step="0.1" name="nhiet_do_min" value="{{ old('nhiet_do_min') }}" placeholder="Ví dụ: 2.0" required>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Nhiệt độ tối đa (°C) <span class="text-rose-500">*</span></label>
                <input class="f2f-input" type="number" step="0.1" name="nhiet_do_max" value="{{ old('nhiet_do_max') }}" placeholder="Ví dụ: 8.0" required>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Độ ẩm tối thiểu (%)</label>
                <input class="f2f-input" type="number" step="0.1" name="do_am_min" value="{{ old('do_am_min') }}" placeholder="Ví dụ: 85.0">
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Độ ẩm tối đa (%)</label>
                <input class="f2f-input" type="number" step="0.1" name="do_am_max" value="{{ old('do_am_max') }}" placeholder="Ví dụ: 95.0">
            </div>
        </div>
        <div style="margin-top:8px">
            <button class="f2f-btn-primary" type="submit">Lưu cấu hình ngưỡng</button>
        </div>
    </form>
</div>

<div class="f2f-card" style="margin-top:16px">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách ngưỡng đang thiết lập</h2>
        </div>
    </div>

    <div class="f2f-table-wrap">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Kho lạnh</th>
                    <th>Mô tả</th>
                    <th>Ngưỡng nhiệt độ (°C)</th>
                    <th>Ngưỡng độ ẩm (%)</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($thresholds as $t)
                @php
                    $isActive = in_array($t->trang_thai ?? '', ['HIEU_LUC', 'DANG_AP_DUNG']);
                @endphp
                <tr>
                    <td><strong>{{ $t->ten_kho ?? 'Kho #' . $t->kho_lanh_id }}</strong></td>
                    <td>{{ $t->mo_ta ?? '—' }}</td>
                    <td><strong class="text-emerald-700">{{ $t->nhiet_do_min }}°C – {{ $t->nhiet_do_max }}°C</strong></td>
                    <td>{{ $t->do_am_min ? $t->do_am_min . '% – ' . $t->do_am_max . '%' : '—' }}</td>
                    <td>
                        <span class="f2f-badge {{ $isActive ? 'f2f-badge-success' : 'f2f-badge-neutral' }}">
                            {{ $isActive ? 'Hiệu lực' : 'Tạm ngưng' }}
                        </span>
                    </td>
                    <td style="text-align:right" class="whitespace-nowrap">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.thresholds.edit', $t->cau_hinh_id) }}" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-200 bg-white text-stone-700 hover:text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50 text-xs font-semibold shadow-xs transition whitespace-nowrap">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                <span>Chỉnh sửa</span>
                            </a>
                            <form method="POST" action="{{ route('admin.thresholds.toggle', $t->cau_hinh_id) }}" style="display:inline-block">
                                @csrf
                                <button class="f2f-btn-ghost f2f-btn-sm whitespace-nowrap" type="submit">
                                    {{ $isActive ? 'Ngưng áp dụng' : 'Kích hoạt' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có cấu hình ngưỡng nào.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="f2f-pagination-wrap">{{ $thresholds->links() }}</div>
</div>
@endsection

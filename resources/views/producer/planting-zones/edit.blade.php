@extends('layouts.dashboard', ['title' => 'Cập nhật hồ sơ vùng trồng', 'breadcrumb' => 'Farm2Fork · Vùng trồng · Cập nhật'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <h2 class="f2f-card-title">Cập nhật hồ sơ: {{ $zone->ma_so_vung_trong ?: $zone->ma_noi_bo }}</h2>
                <span class="f2f-badge {{ $zone->dinh_danh_badge['class'] }}">
                    {{ $zone->dinh_danh_badge['label'] }}
                </span>
            </div>
            <p class="f2f-subtitle" style="margin-top:4px">
                Mã định danh nội bộ: <code>{{ $zone->ma_noi_bo }}</code> · Cập nhật mã số vùng trồng chính thức hoặc hồ sơ chứng nhận.
            </p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.planting-zones.show', $zone->vung_trong_id) }}" class="f2f-btn-ghost">Xem chi tiết</a>
            <a href="{{ route('producer.planting-zones.index') }}" class="f2f-btn-ghost">Danh sách</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-3 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            <strong class="font-bold block mb-1">Vui lòng kiểm tra lại các thông tin sau:</strong>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('producer.planting-zones.update', $zone->vung_trong_id) }}" enctype="multipart/form-data" class="f2f-form-body">
        @csrf
        @method('PUT')

        {{-- 1. Định danh Mã số vùng trồng (MSVT) & Tệp chứng nhận minh chứng --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">1. Thông tin mã số vùng trồng (MSVT) & Minh chứng</h3>
            <p class="f2f-subtitle">Cập nhật mã số vùng trồng được cấp hoặc thay đổi tệp tài liệu chứng nhận minh chứng.</p>

            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="ma_so_vung_trong">
                        Mã số vùng trồng (MSVT) được cấp
                    </label>
                    <input class="f2f-input font-mono font-bold text-emerald-900 tracking-wide uppercase" 
                           id="ma_so_vung_trong" 
                           name="ma_so_vung_trong" 
                           value="{{ old('ma_so_vung_trong', $zone->ma_so_vung_trong) }}" 
                           placeholder="Ví dụ: 68-PUC-DAUTAY-00001 hoặc mã được cấp">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="tep_chung_nhan">
                        Tệp chứng nhận / Tài liệu minh chứng MSVT
                    </label>
                    <input class="f2f-input" 
                           type="file" 
                           id="tep_chung_nhan" 
                           name="tep_chung_nhan" 
                           accept=".pdf,image/png,image/jpeg,image/jpg">
                    <span class="text-[11px] text-stone-500 block mt-1">
                        Tải lên tệp mới nếu muốn thay đổi file hiện tại (PDF hoặc Ảnh JPG, PNG - tối đa 5MB).
                    </span>

                    @if($zone->tep_chung_nhan)
                        <div class="mt-2 p-2 bg-stone-50 rounded border border-stone-200 text-xs flex items-center justify-between">
                            <span class="text-stone-700">Tệp hiện tại: <strong>{{ basename($zone->tep_chung_nhan) }}</strong></span>
                            <a href="{{ route('producer.planting-zones.certificate', $zone->vung_trong_id) }}" target="_blank" class="text-emerald-700 font-semibold hover:underline">
                                Tải xuống xem
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Thông tin vùng canh tác --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">2. Thông tin vùng canh tác</h3>
            <p class="f2f-subtitle">Cập nhật quy mô, cây trồng chủ lực và địa chỉ thực tế của vùng trồng.</p>

            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="cay_trong_chinh">
                        Cây trồng chủ lực <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" 
                           id="cay_trong_chinh" 
                           name="cay_trong_chinh" 
                           value="{{ old('cay_trong_chinh', $zone->cay_trong_chinh) }}" 
                           placeholder="Ví dụ: Dâu tây, Xà lách Romaine, Cà chua bi..." 
                           required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="dien_tich_ha">
                        Diện tích canh tác (ha) <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" 
                           type="number" 
                           step="0.001" 
                           min="0.001" 
                           id="dien_tich_ha" 
                           name="dien_tich_ha" 
                           value="{{ old('dien_tich_ha', $zone->dien_tich_ha) }}" 
                           required>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="dia_chi">
                        Địa chỉ chi tiết vùng trồng <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" 
                           id="dia_chi" 
                           name="dia_chi" 
                           value="{{ old('dia_chi', $zone->dia_chi) }}" 
                           required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="trang_thai">
                        Trạng thái hoạt động <span class="text-rose-500">*</span>
                    </label>
                    <select class="f2f-input" id="trang_thai" name="trang_thai" required>
                        <option value="DANG_SU_DUNG" @selected(old('trang_thai', $zone->trang_thai) === 'DANG_SU_DUNG')>Đang canh tác / sẵn sàng sử dụng</option>
                        <option value="TAM_NGUNG" @selected(old('trang_thai', $zone->trang_thai) === 'TAM_NGUNG')>Tạm ngưng hoạt động</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 3. Tiêu chuẩn chất lượng canh tác (Tùy chọn) --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">3. Tiêu chuẩn chất lượng canh tác (Tùy chọn)</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="loai_chung_nhan">Tiêu chuẩn chứng nhận</label>
                    <select class="f2f-input" id="loai_chung_nhan" name="loai_chung_nhan">
                        <option value="">-- Chưa có chứng nhận tiêu chuẩn --</option>
                        <option value="VIETGAP" @selected(old('loai_chung_nhan', $zone->loai_chung_nhan) === 'VIETGAP')>VietGAP Trồng trọt</option>
                        <option value="GLOBALGAP" @selected(old('loai_chung_nhan', $zone->loai_chung_nhan) === 'GLOBALGAP')>GlobalGAP</option>
                        <option value="ORGANIC" @selected(old('loai_chung_nhan', $zone->loai_chung_nhan) === 'ORGANIC')>Nông nghiệp Hữu cơ (Organic)</option>
                        <option value="HACCP" @selected(old('loai_chung_nhan', $zone->loai_chung_nhan) === 'HACCP')>HACCP</option>
                        <option value="KHAC" @selected(old('loai_chung_nhan', $zone->loai_chung_nhan) === 'KHAC')>Tiêu chuẩn an toàn khác</option>
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="so_chung_nhan">Số giấy chứng nhận</label>
                    <input class="f2f-input" id="so_chung_nhan" name="so_chung_nhan" value="{{ old('so_chung_nhan', $zone->so_chung_nhan) }}">
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="don_vi_cap_chung_nhan">Tổ chức đánh giá & cấp chứng nhận</label>
                    <input class="f2f-input" id="don_vi_cap_chung_nhan" name="don_vi_cap_chung_nhan" value="{{ old('don_vi_cap_chung_nhan', $zone->don_vi_cap_chung_nhan) }}">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_cap_chung_nhan">Ngày cấp chứng nhận</label>
                    <input class="f2f-input" type="date" id="ngay_cap_chung_nhan" name="ngay_cap_chung_nhan" value="{{ old('ngay_cap_chung_nhan', $zone->ngay_cap_chung_nhan?->format('Y-m-d')) }}">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_het_han_chung_nhan">Ngày hết hạn chứng nhận</label>
                    <input class="f2f-input" type="date" id="ngay_het_han_chung_nhan" name="ngay_het_han_chung_nhan" value="{{ old('ngay_het_han_chung_nhan', $zone->ngay_het_han_chung_nhan?->format('Y-m-d')) }}">
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('producer.planting-zones.show', $zone->vung_trong_id) }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Cập nhật hồ sơ vùng trồng</button>
        </div>
    </form>
</div>
@endsection

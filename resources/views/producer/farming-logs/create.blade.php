@extends('layouts.dashboard', ['title' => 'Ghi nhật ký canh tác', 'breadcrumb' => 'Farm2Fork · Nhật ký · Thêm mới'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Ghi chép hoạt động đồng ruộng</h2>
            <p class="f2f-subtitle">Nhập chi tiết hoạt động chăm sóc cây trồng, danh sách vật tư sử dụng và thời gian cách ly PHI.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.farming-logs.index') }}" class="f2f-btn-ghost">Danh sách nhật ký</a>
        </div>
    </div>

    <form method="POST" action="{{ route('producer.farming-logs.store') }}" enctype="multipart/form-data" class="f2f-form-body" id="farming-log-form">
        @csrf

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Thông tin mùa vụ & Thời điểm thực hiện</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="mua_vu_id">Chọn mùa vụ đang canh tác <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="mua_vu_id" name="mua_vu_id" required>
                        <option value="">-- Chọn mùa vụ --</option>
                        @foreach($seasons as $s)
                            @php $qInfo = $s->getThongTinCachLy(); @endphp
                            <option value="{{ $s->mua_vu_id }}" 
                                    data-dang-cach-ly="{{ $qInfo['dang_cach_ly'] ? '1' : '0' }}" 
                                    data-ngay-het-cach-ly="{{ $qInfo['ngay_het_cach_ly'] ?? '' }}" 
                                    data-con-lai="{{ $qInfo['so_ngay_con_lai'] ?? 0 }}" 
                                    data-vat-tu="{{ $qInfo['ten_vat_tu'] ?? '' }}" 
                                    @selected(old('mua_vu_id', $selectedSeasonId) == $s->mua_vu_id)>
                                {{ $s->ten_mua_vu }} ({{ $s->sanPham->ten_san_pham ?? '' }} · {{ $s->vungTrong->ten_vung_trong }})
                                @if($qInfo['dang_cach_ly']) [Đang cách ly PHI] @endif
                            </option>
                        @endforeach
                    </select>
                    <div id="season-quarantine-warning" class="f2f-alert f2f-alert-warning" style="display:none;margin-top:10px"></div>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="loai_hoat_dong">Loại hoạt động canh tác <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="loai_hoat_dong" name="loai_hoat_dong" required>
                        @foreach(\App\Models\NhatKyCanhTac::HOAT_DONG_LABELS as $key => $label)
                            <option value="{{ $key }}" @selected(old('loai_hoat_dong', $defaultActivity) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_thuc_hien">Thời điểm thực hiện <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="datetime-local" id="ngay_thuc_hien" name="ngay_thuc_hien" value="{{ old('ngay_thuc_hien', date('Y-m-d\TH:i')) }}" required>
                </div>
            </div>
        </div>

        <!-- Dynamic Supplies Section -->
        <div class="f2f-form-section" id="supplies-section">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px">
                <div>
                    <h3 class="f2f-section-title" style="margin:0">Phân bón & Thuốc bảo vệ thực vật sử dụng</h3>
                    <p class="f2f-subtitle" style="font-size:13px;margin:2px 0 0">Chỉ áp dụng khi có bón phân hoặc phun thuốc phòng trừ sâu bệnh.</p>
                </div>
                <button type="button" class="f2f-btn-ghost f2f-btn-sm" id="btn-add-supply">
                    + Thêm vật tư sử dụng
                </button>
            </div>

            <div id="supplies-wrapper" class="f2f-stack">
                <div class="f2f-empty" id="supplies-empty-placeholder">
                    Chưa có vật tư nào được chọn. Nhấn nút <strong>"+ Thêm vật tư sử dụng"</strong> nếu đợt chăm sóc này có dùng phân bón hoặc thuốc BVTV.
                </div>
            </div>
        </div>

        <!-- Harvest lot creation (shown if activity is THU_HOACH) -->
        <div class="f2f-form-section" id="harvest-section" style="display:none">
            <h3 class="f2f-section-title">Tạo lô nông sản thu hoạch nguyên liệu</h3>
            <div class="f2f-field" style="margin-bottom:12px">
                <label class="f2f-checkbox">
                    <input type="checkbox" id="create_lot" name="create_lot" value="1" @checked(old('create_lot'))>
                    <span><strong>Tạo ngay Lô nông sản nguyên liệu để bàn giao sang sơ chế / đóng gói</strong></span>
                </label>
            </div>
            <div class="f2f-form-grid" id="harvest-fields">
                <div class="f2f-field">
                    <label class="f2f-label" for="so_luong_thu_hoach">Khối lượng thu hoạch (kg)</label>
                    <input class="f2f-input" type="number" step="0.01" min="0.01" id="so_luong_thu_hoach" name="so_luong_thu_hoach" value="{{ old('so_luong_thu_hoach') }}" placeholder="Ví dụ: 350.5">
                </div>
                <div class="f2f-field">
                    <label class="f2f-label" for="ma_lo">Mã lô nông sản</label>
                    <input class="f2f-input" id="ma_lo" name="ma_lo" value="{{ old('ma_lo') }}">
                </div>
            </div>
        </div>

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Minh chứng hiện trường & Ghi chú kỹ thuật</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="tep_minh_chung">
                        Ảnh chụp đồng ruộng / Bao bì vật tư / Hiện trường <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" type="file" id="tep_minh_chung" name="tep_minh_chung" accept="image/jpeg,image/png,image/webp" data-preview-target="log-img-preview" required>
                    <span class="f2f-field-hint" style="font-size:12px;color:#78716c;margin-top:4px;display:block;">
                        Người sản xuất bắt buộc phải chụp ảnh chụp thực tế tại vườn để lưu vết minh chứng minh bạch cho người tiêu dùng truy xuất.
                    </span>
                    <div id="log-img-preview" class="f2f-file-list" style="margin-top:6px"></div>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="ghi_chu">Ghi chú chi tiết kỹ thuật canh tác</label>
                    <textarea class="f2f-input" id="ghi_chu" name="ghi_chu" rows="3" placeholder="Mô tả hiện trạng cây trồng, liều lượng pha chế, thời tiết lúc thực hiện, kết quả quan sát...">{{ old('ghi_chu') }}</textarea>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="trang_thai">Xác nhận nhật ký <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="trang_thai" name="trang_thai" required>
                        <option value="DA_XAC_NHAN" @selected(old('trang_thai', 'DA_XAC_NHAN') === 'DA_XAC_NHAN')>Xác nhận chính thức (Ghi nhận vào sổ kiểm toán VietGAP)</option>
                        <option value="NHAP" @selected(old('trang_thai') === 'NHAP')>Lưu bản nháp (sẽ chỉnh sửa và xác nhận sau)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('producer.farming-logs.index') }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Lưu nhật ký canh tác</button>
        </div>
    </form>
</div>

<!-- Template for adding supplies via JS -->
<template id="supply-item-template">
    <div class="f2f-item supply-item">
        <div class="f2f-item-head">
            <strong>Vật tư #[INDEX]</strong>
            <button type="button" class="f2f-item-remove btn-remove-supply">Xóa</button>
        </div>
        <div class="f2f-form-grid" style="margin-top:10px">
            <div class="f2f-field f2f-col-span-2">
                <label class="f2f-label">Tên vật tư / Phân bón / Thuốc</label>
                <select class="f2f-input supply-select" name="vat_tu[[INDEX]][vat_tu_id]" required>
                    <option value="">-- Chọn danh mục vật tư --</option>
                    @foreach($supplies as $vt)
                        <option value="{{ $vt->vat_tu_id }}" data-unit="{{ $vt->don_vi_tinh }}" data-phi="{{ $vt->thoi_gian_cach_ly_ngay ?? 0 }}">
                            {{ $vt->ten_thuong_mai }} ({{ $vt->loai_vat_tu }} · Cách ly: {{ $vt->thoi_gian_cach_ly_ngay ?? 0 }} ngày)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Liều lượng sử dụng</label>
                <input class="f2f-input" type="number" step="0.001" min="0.001" name="vat_tu[[INDEX]][lieu_luong]" placeholder="Ví dụ: 25.5" required>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Đơn vị tính</label>
                <input class="f2f-input supply-unit" name="vat_tu[[INDEX]][don_vi_tinh]" placeholder="kg, lít, chai..." value="kg" required>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Thời gian cách ly (ngày)</label>
                <input class="f2f-input supply-phi" type="number" min="0" name="vat_tu[[INDEX]][thoi_gian_cach_ly]" value="0">
                <span class="f2f-field-hint supply-phi-hint">Thời gian cách ly tối thiểu: 0 ngày</span>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Ghi chú pha trộn / Nồng độ</label>
                <input class="f2f-input" name="vat_tu[[INDEX]][ghi_chu]" placeholder="Pha bình 20L...">
            </div>
        </div>
    </div>
</template>
@endsection


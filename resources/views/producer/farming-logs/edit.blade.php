@extends('layouts.dashboard', ['title' => 'Điều chỉnh nhật ký canh tác', 'breadcrumb' => 'Farm2Fork · Nhật ký · Điều chỉnh'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Điều chỉnh nhật ký canh tác #{{ $log->nhat_ky_id }}</h2>
            <p class="f2f-subtitle">
                Mùa vụ: <strong>{{ $log->muaVu->ten_mua_vu }}</strong> · Vùng: {{ $log->muaVu->vungTrong->ten_vung_trong }}
            </p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.farming-logs.show', $log->nhat_ky_id) }}" class="f2f-btn-ghost">Xem chi tiết</a>
        </div>
    </div>

    @if($log->trang_thai === 'DA_XAC_NHAN')
        <div class="f2f-alert f2f-alert-warning" style="margin-bottom:16px">
            <div>
                <strong>LƯU Ý TRUY XUẤT MINH BẠCH:</strong><br>
                Bản ghi này đã được <strong>Xác nhận chính thức</strong>. Khi cập nhật, hệ thống sẽ lưu bản ghi gốc và tạo một phiên bản sửa đổi mới có liên kết lịch sử. Bạn cần giải trình rõ <strong>Lý do điều chỉnh</strong> theo chuẩn kiểm toán VietGAP.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('producer.farming-logs.update', $log->nhat_ky_id) }}" enctype="multipart/form-data" class="f2f-form-body" id="farming-log-form">
        @csrf
        @method('PUT')

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Hoạt động & Thời điểm thực hiện</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="loai_hoat_dong">Loại hoạt động canh tác <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="loai_hoat_dong" name="loai_hoat_dong" required>
                        @foreach(\App\Models\NhatKyCanhTac::HOAT_DONG_LABELS as $key => $label)
                            <option value="{{ $key }}" @selected(old('loai_hoat_dong', $log->loai_hoat_dong) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_thuc_hien">Thời điểm thực hiện <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="datetime-local" id="ngay_thuc_hien" name="ngay_thuc_hien" value="{{ old('ngay_thuc_hien', $log->ngay_thuc_hien?->format('Y-m-d\TH:i')) }}" required>
                </div>
            </div>
        </div>

        <!-- Supplies Section -->
        <div class="f2f-form-section" id="supplies-section">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px">
                <div>
                    <h3 class="f2f-section-title" style="margin:0">Phân bón & Thuốc bảo vệ thực vật</h3>
                    <p class="f2f-subtitle" style="font-size:13px;margin:2px 0 0">Danh sách các vật tư nông nghiệp đã sử dụng trong đợt chăm sóc này.</p>
                </div>
                <button type="button" class="f2f-btn-ghost f2f-btn-sm" id="btn-add-supply">
                    + Thêm vật tư
                </button>
            </div>

            <div id="supplies-wrapper" class="f2f-stack">
                @forelse($log->chiTietVatTu as $idx => $ct)
                    <div class="f2f-item supply-item">
                        <div class="f2f-item-head">
                            <strong>Vật tư #{{ $idx + 1 }}</strong>
                            <button type="button" class="f2f-item-remove btn-remove-supply">Xóa</button>
                        </div>
                        <div class="f2f-form-grid" style="margin-top:10px">
                            <div class="f2f-field f2f-col-span-2">
                                <label class="f2f-label">Tên vật tư / Phân bón / Thuốc</label>
                                <select class="f2f-input supply-select" name="vat_tu[{{ $idx }}][vat_tu_id]" required>
                                    <option value="">-- Chọn danh mục vật tư --</option>
                                    @foreach($supplies as $vt)
                                        <option value="{{ $vt->vat_tu_id }}" data-unit="{{ $vt->don_vi_tinh }}" data-phi="{{ $vt->thoi_gian_cach_ly_ngay ?? 0 }}" @selected($ct->vat_tu_id == $vt->vat_tu_id)>
                                            {{ $vt->ten_thuong_mai }} ({{ $vt->loai_vat_tu }} · Cách ly: {{ $vt->thoi_gian_cach_ly_ngay ?? 0 }} ngày)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="f2f-field">
                                <label class="f2f-label">Liều lượng sử dụng</label>
                                <input class="f2f-input" type="number" step="0.001" min="0.001" name="vat_tu[{{ $idx }}][lieu_luong]" value="{{ $ct->lieu_luong }}" required>
                            </div>
                            <div class="f2f-field">
                                <label class="f2f-label">Đơn vị tính</label>
                                <input class="f2f-input supply-unit" name="vat_tu[{{ $idx }}][don_vi_tinh]" value="{{ $ct->don_vi_tinh }}" required>
                            </div>
                            <div class="f2f-field">
                                <label class="f2f-label">Thời gian cách ly (ngày)</label>
                                <input class="f2f-input supply-phi" type="number" min="{{ $ct->vatTu?->thoi_gian_cach_ly_ngay ?? 0 }}" name="vat_tu[{{ $idx }}][thoi_gian_cach_ly]" value="{{ $ct->thoi_gian_cach_ly_ap_dung_ngay ?? 0 }}">
                                <span class="f2f-field-hint supply-phi-hint">Thời gian cách ly tối thiểu: {{ $ct->vatTu?->thoi_gian_cach_ly_ngay ?? 0 }} ngày</span>
                            </div>
                            <div class="f2f-field">
                                <label class="f2f-label">Ghi chú pha trộn</label>
                                <input class="f2f-input" name="vat_tu[{{ $idx }}][ghi_chu]" value="{{ $ct->ghi_chu }}">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="f2f-empty" id="supplies-empty-placeholder">
                        Chưa có vật tư nào. Nhấn nút <strong>"+ Thêm vật tư"</strong> để bổ sung.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Minh chứng & Giải trình điều chỉnh</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="tep_minh_chung">
                        Ảnh chụp đồng ruộng / Bao bì vật tư / Hiện trường
                        @if(empty($log->tep_minh_chung))
                            <span class="text-rose-500">* (Bắt buộc phải tải ảnh)</span>
                        @else
                            <span class="f2f-field-hint" style="display:inline">(Để trống nếu giữ nguyên ảnh hiện tại)</span>
                        @endif
                    </label>
                    @if($log->tep_minh_chung)
                        <div class="f2f-file-existing" style="margin-bottom:8px">
                            <span>Tệp hiện tại: <strong>{{ basename($log->tep_minh_chung) }}</strong></span>
                            <a href="{{ asset('storage/' . $log->tep_minh_chung) }}" target="_blank" class="f2f-link">Xem ảnh</a>
                        </div>
                    @endif
                    <input class="f2f-input" type="file" id="tep_minh_chung" name="tep_minh_chung" accept="image/jpeg,image/png,image/webp" data-preview-target="edit-img-preview" {{ empty($log->tep_minh_chung) ? 'required' : '' }}>
                    <div id="edit-img-preview" class="f2f-file-list" style="margin-top:6px"></div>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="ghi_chu">Ghi chú kỹ thuật</label>
                    <textarea class="f2f-input" id="ghi_chu" name="ghi_chu" rows="3">{{ old('ghi_chu', $log->ghi_chu) }}</textarea>
                </div>

                @if($log->trang_thai === 'DA_XAC_NHAN')
                    <div class="f2f-field f2f-col-span-2">
                        <label class="f2f-label" for="ly_do_sua">Lý do điều chỉnh / sửa đổi <span class="text-rose-500">*</span></label>
                        <input class="f2f-input" id="ly_do_sua" name="ly_do_sua" value="{{ old('ly_do_sua') }}" placeholder="Ví dụ: Đính chính nhầm đơn vị tính kg thành bao; Bổ sung hình ảnh bao bì phân bón..." required>
                        <span class="f2f-field-hint">Thông tin bắt buộc nhằm đảm bảo tính minh bạch theo tiêu chuẩn VietGAP.</span>
                    </div>
                @endif

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="trang_thai">Trạng thái xác nhận <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="trang_thai" name="trang_thai" required>
                        <option value="DA_XAC_NHAN" @selected(old('trang_thai', $log->trang_thai) === 'DA_XAC_NHAN')>Đã xác nhận chính thức</option>
                        <option value="NHAP" @selected(old('trang_thai', $log->trang_thai) === 'NHAP')>Bản nháp</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('producer.farming-logs.show', $log->nhat_ky_id) }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Lưu bản ghi điều chỉnh</button>
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
                <input class="f2f-input supply-unit" name="vat_tu[[INDEX]][don_vi_tinh]" placeholder="kg, lít..." value="kg" required>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Thời gian cách ly (ngày)</label>
                <input class="f2f-input supply-phi" type="number" min="0" name="vat_tu[[INDEX]][thoi_gian_cach_ly]" value="0">
                <span class="f2f-field-hint supply-phi-hint">Thời gian cách ly tối thiểu: 0 ngày</span>
            </div>
            <div class="f2f-field">
                <label class="f2f-label">Ghi chú pha trộn</label>
                <input class="f2f-input" name="vat_tu[[INDEX]][ghi_chu]" placeholder="Ghi chú...">
            </div>
        </div>
    </div>
</template>
@endsection


@extends('layouts.dashboard', ['title' => 'Sơ chế & tạo lô thành phẩm', 'breadcrumb' => 'Farm2Fork · Sơ chế · Tạo lô'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Thiết lập lô thành phẩm sơ chế</h2>
            <p class="f2f-subtitle">Khấu trừ nguyên liệu thô đầu vào, ghi nhận tỷ lệ hao hụt và khởi tạo lô thành phẩm chuẩn GS1.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('processing.batches.index') }}" class="f2f-btn-ghost">Danh sách lô thành phẩm</a>
        </div>
    </div>

    <form method="POST" action="{{ route('processing.batches.store') }}" class="f2f-form-body" id="batch-form">
        @csrf

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Nguyên liệu đầu vào sử dụng</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="lo_nguyen_lieu_id">Chọn lô nguyên liệu <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="lo_nguyen_lieu_id" name="lo_nguyen_lieu_id" required>
                        <option value="">-- Chọn lô nguyên liệu trong xưởng --</option>
                        @foreach($rawLots as $raw)
                            <option value="{{ $raw->lo_nong_san_id }}" 
                                    data-avail="{{ $raw->so_luong }}" 
                                    data-unit="{{ $raw->don_vi_tinh }}" 
                                    data-product="{{ $raw->san_pham_id }}"
                                    @selected(old('lo_nguyen_lieu_id', $selectedRawLotId) == $raw->lo_nong_san_id)>
                                {{ $raw->ma_lo }} — {{ $raw->sanPham->ten_san_pham ?? '' }} (Còn lại: {{ number_format($raw->so_luong, 2) }} {{ $raw->don_vi_tinh }} · Thu hoạch {{ $raw->ngay_thu_hoach ? $raw->ngay_thu_hoach->format('d/m/Y') : '—' }})
                            </option>
                        @endforeach
                    </select>
                    <span class="f2f-field-hint" id="raw-lot-info">Vui lòng chọn lô nguyên liệu để tiếp tục.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="khoi_luong_su_dung">Khối lượng nguyên liệu đưa vào chế biến <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="number" step="0.01" min="0.01" id="khoi_luong_su_dung" name="khoi_luong_su_dung" value="{{ old('khoi_luong_su_dung') }}" placeholder="Ví dụ: 200.5" required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label">Đơn vị tính nguyên liệu</label>
                    <input class="f2f-input" id="raw_unit_display" value="kg" readonly style="background:#f1f5f9;cursor:not-allowed">
                </div>
            </div>
        </div>

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Quy cách thành phẩm sau sơ chế</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="san_pham_id">Danh mục sản phẩm thành phẩm <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="san_pham_id" name="san_pham_id" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->san_pham_id }}" data-shelf-life="{{ $p->han_su_dung_mac_dinh_ngay ?? 10 }}" @selected(old('san_pham_id') == $p->san_pham_id)>
                                {{ $p->ten_san_pham }} · GTIN: {{ $p->gtin ?: 'chưa có' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="f2f-field-hint">Chỉ sản phẩm có GTIN-14 hợp lệ mới tạo được lô thành phẩm và QR theo cấu trúc GS1.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ma_lo">Mã lô thành phẩm</label>
                    <input class="f2f-input" id="ma_lo" name="ma_lo" maxlength="20" pattern="[A-Za-z0-9_-]{1,20}" value="{{ old('ma_lo') }}" placeholder="Để trống để hệ thống tự tạo">
                    <span class="f2f-field-hint">Tối đa 20 ký tự: chữ, số, dấu gạch ngang hoặc gạch dưới. Mã QR sẽ chứa GTIN và mã lô.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="so_luong_thanh_pham">Sản lượng thành phẩm đạt chuẩn <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="number" step="0.01" min="0.01" id="so_luong_thanh_pham" name="so_luong_thanh_pham" value="{{ old('so_luong_thanh_pham') }}" placeholder="Ví dụ: 180" required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="don_vi_tinh">Đơn vị đóng gói thành phẩm <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="don_vi_tinh" name="don_vi_tinh" required>
                        <option value="kg" @selected(old('don_vi_tinh', 'kg') === 'kg')>kg (Kilogram)</option>
                        <option value="hộp" @selected(old('don_vi_tinh') === 'hộp')>Hộp</option>
                        <option value="khay" @selected(old('don_vi_tinh') === 'khay')>Khay</option>
                        <option value="túi" @selected(old('don_vi_tinh') === 'túi')>Túi</option>
                        <option value="gói" @selected(old('don_vi_tinh') === 'gói')>Gói</option>
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_san_xuat">Ngày sơ chế / Đóng gói sơ bộ <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="date" id="ngay_san_xuat" name="ngay_san_xuat" value="{{ old('ngay_san_xuat', date('Y-m-d')) }}" required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="han_su_dung">Hạn sử dụng khuyến nghị</label>
                    <input class="f2f-input" type="date" id="han_su_dung" name="han_su_dung" value="{{ old('han_su_dung') }}">
                    <span class="f2f-field-hint">Để trống sẽ tự động tính theo hạn sử dụng mặc định của sản phẩm.</span>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="kho_lanh_id">Kho bảo quản thành phẩm sau sơ chế</label>
                    <select class="f2f-input" id="kho_lanh_id" name="kho_lanh_id">
                        <option value="">-- Để tại khu vực xưởng chờ đóng gói SSCC --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->kho_lanh_id }}" @selected(old('kho_lanh_id') == $wh->kho_lanh_id)>
                                {{ $wh->ten_kho }} ({{ $wh->dia_chi }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="ghi_chu">Ghi chú kỹ thuật sơ chế & tỷ lệ hao hụt</label>
                    <textarea class="f2f-input" id="ghi_chu" name="ghi_chu" rows="2" placeholder="Ghi chú về phân loại kích cỡ, nhiệt độ phòng sơ chế, tỷ lệ loại bỏ tạp chất...">{{ old('ghi_chu') }}</textarea>
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('processing.batches.index') }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Hoàn tất sơ chế & Tạo lô thành phẩm</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawSelect = document.getElementById('lo_nguyen_lieu_id');
    const rawQtyInput = document.getElementById('khoi_luong_su_dung');
    const rawUnitDisplay = document.getElementById('raw_unit_display');
    const rawInfo = document.getElementById('raw-lot-info');
    const productSelect = document.getElementById('san_pham_id');

    function updateRawState() {
        if (!rawSelect) return;
        const opt = rawSelect.options[rawSelect.selectedIndex];
        if (opt && opt.value) {
            const avail = opt.getAttribute('data-avail');
            const unit = opt.getAttribute('data-unit') || 'kg';
            const prod = opt.getAttribute('data-product');

            if (rawUnitDisplay) rawUnitDisplay.value = unit;
            if (rawQtyInput) {
                rawQtyInput.max = avail;
                if (!rawQtyInput.value) rawQtyInput.value = avail;
            }
            if (rawInfo) {
                rawInfo.textContent = `Số lượng nguyên liệu khả dụng tối đa: ${avail} ${unit}.`;
                rawInfo.className = 'f2f-field-hint text-emerald-700 font-semibold';
            }
            if (productSelect && prod && !productSelect.value) {
                productSelect.value = prod;
            }
        } else {
            if (rawInfo) {
                rawInfo.textContent = 'Vui lòng chọn lô nguyên liệu để tiếp tục.';
                rawInfo.className = 'f2f-field-hint';
            }
        }
    }

    rawSelect?.addEventListener('change', updateRawState);
    updateRawState();
});
</script>
@endsection


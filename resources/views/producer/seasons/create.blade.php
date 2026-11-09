@extends('layouts.dashboard', ['title' => 'Thiết lập mùa vụ mới', 'breadcrumb' => 'Farm2Fork · Mùa vụ · Thêm mới'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Khai báo mùa vụ canh tác</h2>
            <p class="f2f-subtitle">Khởi tạo chu kỳ mùa vụ cho cây trồng, xác định diện tích canh tác và dự kiến sản lượng thu hoạch.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.seasons.index') }}" class="f2f-btn-ghost">Danh sách mùa vụ</a>
        </div>
    </div>

    <form method="POST" action="{{ route('producer.seasons.store') }}" class="f2f-form-body">
        @csrf

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Vùng trồng & Loại nông sản</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="vung_trong_id">Vùng trồng canh tác <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="vung_trong_id" name="vung_trong_id" required>
                        <option value="">-- Chọn vùng trồng --</option>
                        @foreach($plantingZones as $z)
                            <option value="{{ $z->vung_trong_id }}" @selected(old('vung_trong_id', $selectedZoneId) == $z->vung_trong_id)>
                                {{ $z->ma_so_vung_trong ?? $z->ten_vung_trong }} ({{ $z->cay_trong_chinh ?? 'Nông sản' }} · {{ $z->dia_chi }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ten_san_pham">Loại nông sản / Trái cây <span class="text-rose-500">*</span></label>
                    <input type="text"
                           class="f2f-input"
                           id="ten_san_pham"
                           name="ten_san_pham"
                           list="crop-product-list"
                           value="{{ old('ten_san_pham') }}"
                           required
                           autocomplete="off">
                    <datalist id="crop-product-list">
                        @foreach($products as $p)
                            <option value="{{ $p->ten_san_pham }}">{{ $p->nhom_san_pham ?? 'Nông sản' }}</option>
                        @endforeach
                    </datalist>
                    <span class="f2f-field-hint">Điền tự do tên loại nông sản / trái cây canh tác. Có thể chọn từ gợi ý hoặc nhập loại nông sản mới bất kỳ.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ma_mua_vu">Mã mùa vụ</label>
                    <input class="f2f-input" id="ma_mua_vu" name="ma_mua_vu" value="{{ old('ma_mua_vu') }}" placeholder="Để trống hệ thống sẽ tự sinh">
                    <span class="f2f-field-hint">Mã định danh duy nhất dùng để truy xuất nhật ký và lô hàng.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ten_mua_vu">Tên mùa vụ</label>
                    <input class="f2f-input" id="ten_mua_vu" name="ten_mua_vu" value="{{ old('ten_mua_vu') }}">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="giong_cay_trong">Giống cây trồng</label>
                    <input class="f2f-input" id="giong_cay_trong" name="giong_cay_trong" value="{{ old('giong_cay_trong') }}" placeholder="Ví dụ: Dâu tây Albion F1, Xà lách Romaine chịu nhiệt, Cà chua bi Nova...">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="dien_tich_canh_tac_ha">Diện tích canh tác vụ này (ha)</label>
                    <input class="f2f-input" type="number" step="0.001" min="0.001" id="dien_tich_canh_tac_ha" name="dien_tich_canh_tac_ha" value="{{ old('dien_tich_canh_tac_ha') }}" placeholder="Ví dụ: 0.500">
                </div>
            </div>
        </div>

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Tiến độ & Kế hoạch sản lượng</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_gieo">Ngày gieo hạt / Xuống giống</label>
                    <input class="f2f-input" type="date" id="ngay_gieo" name="ngay_gieo" value="{{ old('ngay_gieo', date('Y-m-d')) }}">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_thu_hoach_du_kien">Ngày dự kiến bắt đầu thu hoạch</label>
                    <input class="f2f-input" type="date" id="ngay_thu_hoach_du_kien" name="ngay_thu_hoach_du_kien" value="{{ old('ngay_thu_hoach_du_kien') }}">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="san_luong_du_kien">Sản lượng dự kiến</label>
                    <input class="f2f-input" type="number" step="0.1" min="0" id="san_luong_du_kien" name="san_luong_du_kien" value="{{ old('san_luong_du_kien') }}" placeholder="Ví dụ: 5000">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="don_vi_san_luong">Đơn vị tính sản lượng <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="don_vi_san_luong" name="don_vi_san_luong" required>
                        <option value="kg" @selected(old('don_vi_san_luong', 'kg') === 'kg')>kg (Kilogram)</option>
                        <option value="tấn" @selected(old('don_vi_san_luong') === 'tấn')>Tấn</option>
                        <option value="tạ" @selected(old('don_vi_san_luong') === 'tạ')>Tạ</option>
                    </select>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="trang_thai">Trạng thái khởi tạo <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="trang_thai" name="trang_thai" required>
                        <option value="CHUAN_BI" @selected(old('trang_thai') === 'CHUAN_BI')>Chuẩn bị đất / giống</option>
                        <option value="DANG_CANH_TAC" @selected(old('trang_thai', 'DANG_CANH_TAC') === 'DANG_CANH_TAC')>Đang canh tác (bắt đầu ghi nhật ký)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('producer.seasons.index') }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Tạo mùa vụ canh tác</button>
        </div>
    </form>
</div>
@endsection


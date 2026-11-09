@extends('layouts.dashboard', ['title' => 'Tạo lô nông sản thu hoạch', 'breadcrumb' => 'Farm2Fork · Lô thu hoạch · Thêm mới'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Khai báo lô nông sản nguyên liệu</h2>
            <p class="f2f-subtitle">Đóng lô nông sản sau thu hoạch với mã định danh truy xuất nguồn gốc để chuyển giao sang sơ chế hoặc kiểm định.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.lots.index') }}" class="f2f-btn-ghost">Danh sách lô</a>
        </div>
    </div>

    <form method="POST" action="{{ route('producer.lots.store') }}" class="f2f-form-body">
        @csrf

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Nguồn gốc mùa vụ & Nông sản</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="mua_vu_id">Mùa vụ thu hoạch <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="mua_vu_id" name="mua_vu_id" required>
                        <option value="">-- Chọn mùa vụ --</option>
                        @foreach($seasons as $s)
                            @php $qInfo = $s->getThongTinCachLy(); @endphp
                            <option value="{{ $s->mua_vu_id }}" 
                                    data-sanpham="{{ $s->san_pham_id }}" 
                                    data-sanpham-name="{{ $s->sanPham->ten_san_pham ?? '' }}"
                                    data-dang-cach-ly="{{ $qInfo['dang_cach_ly'] ? '1' : '0' }}" 
                                    data-ngay-het-cach-ly="{{ $qInfo['ngay_het_cach_ly'] ?? '' }}" 
                                    data-con-lai="{{ $qInfo['so_ngay_con_lai'] ?? 0 }}" 
                                    data-vat-tu="{{ $qInfo['ten_vat_tu'] ?? '' }}" 
                                    @selected(old('mua_vu_id', $selectedSeasonId) == $s->mua_vu_id)>
                                {{ $s->ten_mua_vu }} ({{ $s->sanPham->ten_san_pham ?? '' }} · {{ $s->vungTrong->ten_vung_trong }})
                                @if($qInfo['dang_cach_ly']) [Đang cách ly PHI còn {{ $qInfo['so_ngay_con_lai'] }} ngày] @endif
                            </option>
                        @endforeach
                    </select>
                    <div id="lot-quarantine-warning" class="f2f-alert f2f-alert-warning" style="display:none;margin-top:10px"></div>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ten_san_pham">Loại nông sản / Trái cây <span class="text-rose-500">*</span></label>
                    <input type="text"
                           class="f2f-input"
                           id="ten_san_pham"
                           name="ten_san_pham"
                           list="lot-product-list"
                           value="{{ old('ten_san_pham') }}"
                           placeholder="Nhập loại nông sản hoặc trái cây (Ví dụ: Dâu tây, Bơ 034...)"
                           required
                           autocomplete="off">
                    <datalist id="lot-product-list">
                        @foreach($products as $p)
                            <option value="{{ $p->ten_san_pham }}">{{ $p->nhom_san_pham ?? 'Nông sản' }}</option>
                        @endforeach
                    </datalist>
                    <span class="f2f-field-hint">Điền tự do loại nông sản thu hoạch hoặc hệ thống tự động điền theo mùa vụ đã chọn.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ma_lo">Mã lô nông sản</label>
                    <input class="f2f-input" id="ma_lo" name="ma_lo" value="{{ old('ma_lo') }}" placeholder="Để trống hệ thống sẽ tự sinh mã định danh (VD: LO-DAUTAY-20261024-X1)">
                    <span class="f2f-field-hint" style="font-size:12px;color:#78716c;margin-top:4px;display:block;">Mã định danh lô nguyên liệu thu hoạch để bàn giao sang bộ phận sơ chế và đóng gói.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ma_lo_nha_cung_cap">Mã lô nội bộ / nông hộ (nếu có)</label>
                    <input class="f2f-input" id="ma_lo_nha_cung_cap" name="ma_lo_nha_cung_cap" value="{{ old('ma_lo_nha_cung_cap') }}" placeholder="Ví dụ: L1-THAI-PHIEN">
                </div>
            </div>
        </div>

        <div class="f2f-form-section">
            <h3 class="f2f-section-title">Khối lượng & Thời gian sử dụng</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="so_luong">Khối lượng thu hoạch <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="number" step="0.01" min="0.01" id="so_luong" name="so_luong" value="{{ old('so_luong') }}" placeholder="Ví dụ: 500" required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="don_vi_tinh">Đơn vị tính <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="don_vi_tinh" name="don_vi_tinh" required>
                        <option value="kg" @selected(old('don_vi_tinh', 'kg') === 'kg')>kg (Kilogram)</option>
                        <option value="tấn" @selected(old('don_vi_tinh') === 'tấn')>Tấn</option>
                        <option value="hộp" @selected(old('don_vi_tinh') === 'hộp')>Hộp</option>
                        <option value="thùng" @selected(old('don_vi_tinh') === 'thùng')>Thùng</option>
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_thu_hoach">Ngày thu hoạch <span class="text-rose-500">*</span></label>
                    <input class="f2f-input" type="date" id="ngay_thu_hoach" name="ngay_thu_hoach" value="{{ old('ngay_thu_hoach', date('Y-m-d')) }}" required>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="han_su_dung">Hạn sử dụng khuyến nghị</label>
                    <input class="f2f-input" type="date" id="han_su_dung" name="han_su_dung" value="{{ old('han_su_dung') }}">
                    <span class="f2f-field-hint">Để trống hệ thống sẽ tính theo hạn sử dụng mặc định của loại nông sản.</span>
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('producer.lots.index') }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Tạo lô nông sản thu hoạch</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const seasonSelect = document.getElementById('mua_vu_id');
        const productInput = document.getElementById('ten_san_pham');

        function syncProductFromSeason() {
            if (!seasonSelect || !productInput) return;
            const opt = seasonSelect.options[seasonSelect.selectedIndex];
            if (opt && opt.getAttribute('data-sanpham-name')) {
                const prodName = opt.getAttribute('data-sanpham-name');
                if (!productInput.value || productInput.dataset.autoFilled === '1') {
                    productInput.value = prodName;
                    productInput.dataset.autoFilled = '1';
                }
            }
        }

        productInput?.addEventListener('input', function() {
            productInput.dataset.autoFilled = '0';
        });

        seasonSelect?.addEventListener('change', function() {
            productInput.dataset.autoFilled = '1';
            syncProductFromSeason();
        });

        if (productInput && !productInput.value) {
            syncProductFromSeason();
        }
    });
</script>
@endsection


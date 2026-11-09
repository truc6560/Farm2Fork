@extends('layouts.dashboard', ['title' => 'Khai báo mùa vụ mới', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi · Khai báo mùa vụ'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Khai Báo Mùa Vụ Mới</h2>
            <p class="f2f-subtitle">Thiết lập thông tin mùa vụ canh tác, liên kết vùng trồng được cấp mã PUC và kế hoạch sản lượng chuỗi cung ứng.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('chain.seasons.index') }}" class="f2f-btn-ghost text-xs">
                ← Quay lại danh sách
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="f2f-alert f2f-alert-danger" style="margin: 16px 20px 0; border-radius: 8px;">
            <strong class="block mb-1">Vui lòng kiểm tra lại các thông tin bên dưới:</strong>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('chain.seasons.store') }}" class="f2f-form-body">
        @csrf

        {{-- Group 1: Vùng trồng & Nông sản --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">1. Vùng trồng & Mặt hàng nông sản</h3>
            <p class="f2f-subtitle" style="font-size:12px;margin:2px 0 12px">Mỗi mùa vụ phải gắn liền với một vùng trồng cụ thể để đảm bảo mã số định danh nguồn gốc.</p>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="vung_trong_id">Vùng trồng liên kết <span class="text-rose-500">*</span></label>
                    <select class="f2f-select @error('vung_trong_id') border-rose-500 @enderror" id="vung_trong_id" name="vung_trong_id" required>
                        <option value="">-- Chọn vùng trồng đã được cấp phép --</option>
                        @foreach($plantingZones as $zone)
                            <option value="{{ $zone->vung_trong_id }}" @selected(old('vung_trong_id') == $zone->vung_trong_id)>
                                {{ $zone->ten_vung_trong }} (Mã PUC: {{ $zone->ma_vung_trong ?? 'N/A' }} · {{ $zone->toChuc->ten_to_chuc ?? '' }})
                            </option>
                        @endforeach
                    </select>
                    @error('vung_trong_id')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="san_pham_id">Mặt hàng nông sản / Cây trồng <span class="text-rose-500">*</span></label>
                    <select class="f2f-select @error('san_pham_id') border-rose-500 @enderror" id="san_pham_id" name="san_pham_id" required>
                        <option value="">-- Chọn loại nông sản --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->san_pham_id }}" @selected(old('san_pham_id') == $p->san_pham_id)>
                                {{ $p->ten_san_pham }} (Mã: {{ $p->ma_san_pham }})
                            </option>
                        @endforeach
                    </select>
                    @error('san_pham_id')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="giong_cay_trong">Tên giống cây trồng cụ thể</label>
                    <input class="f2f-input @error('giong_cay_trong') border-rose-500 @enderror" type="text" id="giong_cay_trong" name="giong_cay_trong" value="{{ old('giong_cay_trong') }}" placeholder="Ví dụ: Dâu tây giống Nhật Hana F1, Cà chua bi Socola, Xà lách lolo xanh...">
                    <span class="f2f-field-hint" style="font-size:11px;color:#78716c;margin-top:3px;display:block;">
                        Ghi rõ giống cây trồng để thể hiện minh bạch trên tem nhãn truy xuất nguồn gốc QR.
                    </span>
                    @error('giong_cay_trong')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Group 2: Định danh mùa vụ --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">2. Thông tin định danh mùa vụ</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="ten_mua_vu">Tên mùa vụ</label>
                    <input class="f2f-input @error('ten_mua_vu') border-rose-500 @enderror" type="text" id="ten_mua_vu" name="ten_mua_vu" value="{{ old('ten_mua_vu') }}" placeholder="Để trống nếu muốn hệ thống tự đặt tên chuẩn">
                    <span class="f2f-field-hint" style="font-size:11px;color:#78716c;margin-top:3px;display:block;">
                        Nếu để trống, hệ thống sẽ tự đặt dạng: <em>[Tên nông sản] - [Vùng trồng] (Vụ 2026)</em>
                    </span>
                    @error('ten_mua_vu')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ma_mua_vu">Mã mùa vụ</label>
                    <input class="f2f-input @error('ma_mua_vu') border-rose-500 @enderror" type="text" id="ma_mua_vu" name="ma_mua_vu" value="{{ old('ma_mua_vu') }}" placeholder="Để trống để hệ thống tự sinh mã">
                    <span class="f2f-field-hint" style="font-size:11px;color:#78716c;margin-top:3px;display:block;">
                        Mã tự sinh theo quy chuẩn VietGAP: <code>MV-[SP]-VT[ID]-[NĂM]...</code>
                    </span>
                    @error('ma_mua_vu')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Group 3: Kế hoạch thời vụ & Quy mô sản xuất --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">3. Khung thời vụ & Kế hoạch sản lượng</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_gieo">Ngày bắt đầu / Gieo trồng</label>
                    <input class="f2f-input @error('ngay_gieo') border-rose-500 @enderror" type="date" id="ngay_gieo" name="ngay_gieo" value="{{ old('ngay_gieo', date('Y-m-d')) }}">
                    @error('ngay_gieo')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_thu_hoach_du_kien">Ngày dự kiến bắt đầu thu hoạch</label>
                    <input class="f2f-input @error('ngay_thu_hoach_du_kien') border-rose-500 @enderror" type="date" id="ngay_thu_hoach_du_kien" name="ngay_thu_hoach_du_kien" value="{{ old('ngay_thu_hoach_du_kien') }}">
                    <span class="f2f-field-hint" style="font-size:11px;color:#78716c;margin-top:3px;display:block;">
                        Phải sau hoặc bằng ngày bắt đầu gieo trồng.
                    </span>
                    @error('ngay_thu_hoach_du_kien')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="dien_tich_canh_tac_ha">Diện tích canh tác vụ này (ha)</label>
                    <input class="f2f-input @error('dien_tich_canh_tac_ha') border-rose-500 @enderror" type="number" step="0.001" min="0.001" id="dien_tich_canh_tac_ha" name="dien_tich_canh_tac_ha" value="{{ old('dien_tich_canh_tac_ha') }}" placeholder="Ví dụ: 1.5">
                    @error('dien_tich_canh_tac_ha')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="san_luong_du_kien">Sản lượng dự kiến thu hoạch</label>
                    <div style="display:flex;gap:8px">
                        <input class="f2f-input @error('san_luong_du_kien') border-rose-500 @enderror" style="flex:1" type="number" step="0.01" min="0" id="san_luong_du_kien" name="san_luong_du_kien" value="{{ old('san_luong_du_kien') }}" placeholder="Ví dụ: 5000">
                        <select class="f2f-select" style="width:110px" name="don_vi_san_luong" id="don_vi_san_luong" required>
                            <option value="kg" @selected(old('don_vi_san_luong', 'kg') === 'kg')>kg</option>
                            <option value="tấn" @selected(old('don_vi_san_luong') === 'tấn')>tấn</option>
                            <option value="tạ" @selected(old('don_vi_san_luong') === 'tạ')>tạ</option>
                            <option value="hộp" @selected(old('don_vi_san_luong') === 'hộp')>hộp</option>
                        </select>
                    </div>
                    @error('san_luong_du_kien')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="trang_thai">Trạng thái khởi tạo mùa vụ <span class="text-rose-500">*</span></label>
                    <select class="f2f-select @error('trang_thai') border-rose-500 @enderror" id="trang_thai" name="trang_thai" required>
                        <option value="DANG_CANH_TAC" @selected(old('trang_thai', 'DANG_CANH_TAC') === 'DANG_CANH_TAC')>Đang canh tác (Bắt đầu các hoạt động chăm sóc, tưới tiêu, bón phân)</option>
                        <option value="CHUAN_BI" @selected(old('trang_thai') === 'CHUAN_BI')>Chuẩn bị giống / đất (Giai đoạn cải tạo đất và ươm giống)</option>
                        <option value="DANG_THU_HOACH" @selected(old('trang_thai') === 'DANG_THU_HOACH')>Đang thu hoạch (Sẵn sàng tạo các lô nguyên liệu thu hoạch)</option>
                    </select>
                    @error('trang_thai')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('chain.seasons.index') }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Khai báo mùa vụ</button>
        </div>
    </form>
</div>
@endsection

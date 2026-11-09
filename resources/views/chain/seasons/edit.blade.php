@extends('layouts.dashboard', ['title' => 'Chỉnh sửa Mùa vụ: ' . $season->ten_mua_vu, 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi · Chỉnh sửa mùa vụ'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Chỉnh Sửa Thông Tin Mùa Vụ</h2>
            <p class="f2f-subtitle">Cập nhật quy hoạch sản xuất, thông tin giống cây, khung thời vụ và trạng thái vòng đời.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('chain.seasons.show', $season->mua_vu_id) }}" class="f2f-btn-ghost text-xs">
                ← Quay lại chi tiết
            </a>
        </div>
    </div>

    @if($hasLots)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 mx-5 mt-4">
            <div class="flex items-start gap-2.5">
                <span class="text-amber-700 text-lg">⚠️</span>
                <div>
                    <strong class="text-amber-900 text-xs uppercase block">Mùa vụ đã phát sinh lô nông sản trong chuỗi:</strong>
                    <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                        Để đảm bảo tính toàn vẹn và minh bạch của chuỗi truy xuất nguồn gốc (Vùng trồng → Lô nông sản), thông tin <strong>Vùng trồng</strong>, <strong>Loại nông sản</strong> và <strong>Mã mùa vụ</strong> được cố định. Bạn vẫn có thể cập nhật tên mùa vụ, giống cây, khung thời vụ, sản lượng và trạng thái.
                    </p>
                </div>
            </div>
        </div>
    @endif

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

    <form method="POST" action="{{ route('chain.seasons.update', $season->mua_vu_id) }}" class="f2f-form-body">
        @csrf
        @method('PUT')

        {{-- Group 1: Vùng trồng & Nông sản --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">1. Vùng trồng & Mặt hàng nông sản</h3>
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="vung_trong_id">Vùng trồng liên kết <span class="text-rose-500">*</span></label>
                    @if($hasLots)
                        <input class="f2f-input bg-stone-100 text-stone-600 cursor-not-allowed" type="text" readonly value="{{ $season->vungTrong->ten_vung_trong }} (Mã PUC: {{ $season->vungTrong->ma_vung_trong ?? 'N/A' }})">
                        <span class="f2f-field-hint" style="font-size:11px;color:#78716c;margin-top:3px;display:block;">
                            Đã khóa cố định do vụ này đã phát sinh {{ $season->loNongSan->count() }} lô nông sản.
                        </span>
                    @else
                        <select class="f2f-select @error('vung_trong_id') border-rose-500 @enderror" id="vung_trong_id" name="vung_trong_id" required>
                            @foreach($plantingZones as $zone)
                                <option value="{{ $zone->vung_trong_id }}" @selected(old('vung_trong_id', $season->vung_trong_id) == $zone->vung_trong_id)>
                                    {{ $zone->ten_vung_trong }} (Mã PUC: {{ $zone->ma_vung_trong ?? 'N/A' }} · {{ $zone->toChuc->ten_to_chuc ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('vung_trong_id')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="san_pham_id">Mặt hàng nông sản / Cây trồng <span class="text-rose-500">*</span></label>
                    @if($hasLots)
                        <input class="f2f-input bg-stone-100 text-stone-600 cursor-not-allowed" type="text" readonly value="{{ $season->sanPham->ten_san_pham ?? '—' }} ({{ $season->sanPham->ma_san_pham ?? '—' }})">
                        <span class="f2f-field-hint" style="font-size:11px;color:#78716c;margin-top:3px;display:block;">
                            Đã khóa cố định để bảo toàn nguồn gốc lô nông sản.
                        </span>
                    @else
                        <select class="f2f-select @error('san_pham_id') border-rose-500 @enderror" id="san_pham_id" name="san_pham_id" required>
                            @foreach($products as $p)
                                <option value="{{ $p->san_pham_id }}" @selected(old('san_pham_id', $season->san_pham_id) == $p->san_pham_id)>
                                    {{ $p->ten_san_pham }} ({{ $p->ma_san_pham }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('san_pham_id')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="giong_cay_trong">Tên giống cây trồng cụ thể</label>
                    <input class="f2f-input @error('giong_cay_trong') border-rose-500 @enderror" type="text" id="giong_cay_trong" name="giong_cay_trong" value="{{ old('giong_cay_trong', $season->giong_cay_trong) }}" placeholder="Ví dụ: Dâu tây giống Nhật Hana F1...">
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
                    <label class="f2f-label" for="ten_mua_vu">Tên mùa vụ <span class="text-rose-500">*</span></label>
                    <input class="f2f-input @error('ten_mua_vu') border-rose-500 @enderror" type="text" id="ten_mua_vu" name="ten_mua_vu" value="{{ old('ten_mua_vu', $season->ten_mua_vu) }}" required>
                    @error('ten_mua_vu')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ma_mua_vu">Mã mùa vụ</label>
                    @if($hasLots)
                        <input class="f2f-input bg-stone-100 text-stone-600 cursor-not-allowed font-mono" type="text" readonly value="{{ $season->ma_mua_vu }}">
                    @else
                        <input class="f2f-input @error('ma_mua_vu') border-rose-500 @enderror font-mono" type="text" id="ma_mua_vu" name="ma_mua_vu" value="{{ old('ma_mua_vu', $season->ma_mua_vu) }}" required>
                    @endif
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
                    <input class="f2f-input @error('ngay_gieo') border-rose-500 @enderror" type="date" id="ngay_gieo" name="ngay_gieo" value="{{ old('ngay_gieo', $season->ngay_gieo?->format('Y-m-d')) }}">
                    @error('ngay_gieo')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_thu_hoach_du_kien">Ngày dự kiến bắt đầu thu hoạch</label>
                    <input class="f2f-input @error('ngay_thu_hoach_du_kien') border-rose-500 @enderror" type="date" id="ngay_thu_hoach_du_kien" name="ngay_thu_hoach_du_kien" value="{{ old('ngay_thu_hoach_du_kien', $season->ngay_thu_hoach_du_kien?->format('Y-m-d')) }}">
                    @error('ngay_thu_hoach_du_kien')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="dien_tich_canh_tac_ha">Diện tích canh tác vụ này (ha)</label>
                    <input class="f2f-input @error('dien_tich_canh_tac_ha') border-rose-500 @enderror" type="number" step="0.001" min="0.001" id="dien_tich_canh_tac_ha" name="dien_tich_canh_tac_ha" value="{{ old('dien_tich_canh_tac_ha', $season->dien_tich_canh_tac_ha) }}">
                    @error('dien_tich_canh_tac_ha')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="san_luong_du_kien">Sản lượng dự kiến thu hoạch</label>
                    <div style="display:flex;gap:8px">
                        <input class="f2f-input @error('san_luong_du_kien') border-rose-500 @enderror" style="flex:1" type="number" step="0.01" min="0" id="san_luong_du_kien" name="san_luong_du_kien" value="{{ old('san_luong_du_kien', $season->san_luong_du_kien) }}">
                        <select class="f2f-select" style="width:110px" name="don_vi_san_luong" id="don_vi_san_luong" required>
                            <option value="kg" @selected(old('don_vi_san_luong', $season->don_vi_san_luong) === 'kg')>kg</option>
                            <option value="tấn" @selected(old('don_vi_san_luong', $season->don_vi_san_luong) === 'tấn')>tấn</option>
                            <option value="tạ" @selected(old('don_vi_san_luong', $season->don_vi_san_luong) === 'tạ')>tạ</option>
                            <option value="hộp" @selected(old('don_vi_san_luong', $season->don_vi_san_luong) === 'hộp')>hộp</option>
                        </select>
                    </div>
                    @error('san_luong_du_kien')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="trang_thai">Trạng thái vòng đời mùa vụ <span class="text-rose-500">*</span></label>
                    <select class="f2f-select @error('trang_thai') border-rose-500 @enderror" id="trang_thai" name="trang_thai" required>
                        @if(!$hasLots)
                            <option value="CHUAN_BI" @selected(old('trang_thai', $season->trang_thai) === 'CHUAN_BI')>Chuẩn bị giống / đất</option>
                        @endif
                        <option value="DANG_CANH_TAC" @selected(old('trang_thai', $season->trang_thai) === 'DANG_CANH_TAC')>Đang canh tác</option>
                        <option value="DANG_THU_HOACH" @selected(old('trang_thai', $season->trang_thai) === 'DANG_THU_HOACH')>Đang thu hoạch</option>
                        <option value="HOAN_THANH" @selected(old('trang_thai', $season->trang_thai) === 'HOAN_THANH')>Đã hoàn thành</option>
                        <option value="HUY" @selected(old('trang_thai', $season->trang_thai) === 'HUY')>Hủy vụ</option>
                    </select>
                    @error('trang_thai')
                        <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ly_do_thay_doi">Ghi chú thay đổi (Audit trail)</label>
                    <input class="f2f-input" type="text" id="ly_do_thay_doi" name="ly_do_thay_doi" value="{{ old('ly_do_thay_doi') }}" placeholder="Ví dụ: Điều chỉnh ngày thu hoạch dự kiến theo thực tế thời tiết...">
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('chain.seasons.show', $season->mua_vu_id) }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Lưu thay đổi</button>
        </div>
    </form>
</div>
@endsection

{{-- Partial: form fields for partner create/edit --}}

@if($errors->any())
    <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div>
    <label class="f2f-label" for="ten_to_chuc">Tên đối tác <span class="text-rose-500">*</span></label>
    <input type="text" id="ten_to_chuc" name="ten_to_chuc"
        value="{{ old('ten_to_chuc', $partner?->ten_to_chuc) }}"
        placeholder="Tên công ty, siêu thị, đơn vị phân phối..."
        class="f2f-input @error('ten_to_chuc') border-rose-400 @enderror">
    @error('ten_to_chuc')<p class="f2f-error">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="f2f-label" for="ma_to_chuc">Mã tổ chức</label>
        <input type="text" id="ma_to_chuc" name="ma_to_chuc"
            value="{{ old('ma_to_chuc', $partner?->ma_to_chuc) }}"
            placeholder="Để trống để tự động tạo"
            class="f2f-input @error('ma_to_chuc') border-rose-400 @enderror">
        @error('ma_to_chuc')<p class="f2f-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="f2f-label" for="ma_so_thue">Mã số thuế</label>
        <input type="text" id="ma_so_thue" name="ma_so_thue"
            value="{{ old('ma_so_thue', $partner?->ma_so_thue) }}"
            placeholder="0312345678"
            class="f2f-input @error('ma_so_thue') border-rose-400 @enderror">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="f2f-label" for="so_dien_thoai">Số điện thoại</label>
        <input type="text" id="so_dien_thoai" name="so_dien_thoai"
            value="{{ old('so_dien_thoai', $partner?->so_dien_thoai) }}"
            class="f2f-input @error('so_dien_thoai') border-rose-400 @enderror">
    </div>
    <div>
        <label class="f2f-label" for="email">Email</label>
        <input type="email" id="email" name="email"
            value="{{ old('email', $partner?->email) }}"
            class="f2f-input @error('email') border-rose-400 @enderror">
        @error('email')<p class="f2f-error">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="f2f-label" for="dia_chi">Địa chỉ</label>
    <textarea id="dia_chi" name="dia_chi" rows="2"
        class="f2f-input @error('dia_chi') border-rose-400 @enderror"
        placeholder="Địa chỉ đầy đủ của đối tác...">{{ old('dia_chi', $partner?->dia_chi) }}</textarea>
</div>

<div>
    <label class="f2f-label" for="trang_thai">Trạng thái <span class="text-rose-500">*</span></label>
    <select id="trang_thai" name="trang_thai" class="f2f-input @error('trang_thai') border-rose-400 @enderror">
        <option value="HOAT_DONG"       @selected(old('trang_thai', $partner?->trang_thai ?? 'HOAT_DONG') === 'HOAT_DONG')>Đang hoạt động</option>
        <option value="TAM_NGUNG"       @selected(old('trang_thai', $partner?->trang_thai) === 'TAM_NGUNG')>Tạm ngưng</option>
        <option value="NGUNG_HOAT_DONG" @selected(old('trang_thai', $partner?->trang_thai) === 'NGUNG_HOAT_DONG')>Ngưng hoạt động</option>
    </select>
    @error('trang_thai')<p class="f2f-error">{{ $message }}</p>@enderror
</div>


{{-- Partial: form fields for vehicle create/edit --}}

@if($errors->any())
    <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Biển số --}}
<div>
    <label class="f2f-label" for="bien_so">Biển số xe <span class="text-rose-500">*</span></label>
    <input type="text" id="bien_so" name="bien_so"
        value="{{ old('bien_so', $vehicle?->bien_so) }}"
        placeholder="Ví dụ: 51C-28419"
        class="f2f-input @error('bien_so') border-rose-400 @enderror"
        {{ $vehicle && $vehicle->trang_thai === 'DANG_HOAT_DONG' ? 'readonly' : '' }}>
    @error('bien_so')<p class="f2f-error">{{ $message }}</p>@enderror
</div>

{{-- Loại xe --}}
<div>
    <label class="f2f-label" for="loai_xe">Loại xe <span class="text-rose-500">*</span></label>
    <select id="loai_xe" name="loai_xe" class="f2f-input @error('loai_xe') border-rose-400 @enderror">
        <option value="">-- Chọn loại xe --</option>
        @foreach($loaiXeOptions as $val => $label)
            <option value="{{ $val }}" @selected(old('loai_xe', $vehicle?->loai_xe) === $val)>{{ $label }}</option>
        @endforeach
    </select>
    @error('loai_xe')<p class="f2f-error">{{ $message }}</p>@enderror
</div>

{{-- Tải trọng --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="f2f-label" for="tai_trong">Tải trọng <span class="text-rose-500">*</span></label>
        <input type="number" id="tai_trong" name="tai_trong" step="0.01" min="0"
            value="{{ old('tai_trong', $vehicle?->tai_trong) }}"
            class="f2f-input @error('tai_trong') border-rose-400 @enderror">
        @error('tai_trong')<p class="f2f-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="f2f-label" for="don_vi_tai_trong">Đơn vị <span class="text-rose-500">*</span></label>
        <select id="don_vi_tai_trong" name="don_vi_tai_trong" class="f2f-input @error('don_vi_tai_trong') border-rose-400 @enderror">
            <option value="kg"  @selected(old('don_vi_tai_trong', $vehicle?->don_vi_tai_trong) === 'kg')>Kilogram (kg)</option>
            <option value="tan" @selected(old('don_vi_tai_trong', $vehicle?->don_vi_tai_trong) === 'tan')>Tấn</option>
        </select>
    </div>
</div>

{{-- Hệ thống làm lạnh --}}
<div>
    <label class="f2f-label">Hệ thống làm lạnh <span class="text-rose-500">*</span></label>
    <div class="flex items-center gap-6 mt-1">
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input type="radio" name="co_he_thong_lam_lanh" value="1"
                @checked(old('co_he_thong_lam_lanh', $vehicle?->co_he_thong_lam_lanh ? '1' : '0') === '1')>
            Có trang bị hệ thống làm lạnh
        </label>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input type="radio" name="co_he_thong_lam_lanh" value="0"
                @checked(old('co_he_thong_lam_lanh', $vehicle?->co_he_thong_lam_lanh ? '1' : '0') === '0')>
            Không có
        </label>
    </div>
    @error('co_he_thong_lam_lanh')<p class="f2f-error">{{ $message }}</p>@enderror
</div>

{{-- Trạng thái --}}
<div>
    <label class="f2f-label" for="trang_thai">Trạng thái <span class="text-rose-500">*</span></label>
    <select id="trang_thai" name="trang_thai" class="f2f-input @error('trang_thai') border-rose-400 @enderror">
        @foreach($trangThaiLabels as $val => $label)
            <option value="{{ $val }}" @selected(old('trang_thai', $vehicle?->trang_thai) === $val)>{{ $label }}</option>
        @endforeach
    </select>
    @error('trang_thai')<p class="f2f-error">{{ $message }}</p>@enderror
</div>

{{-- Ghi chú --}}
<div>
    <label class="f2f-label" for="ghi_chu">Ghi chú</label>
    <textarea id="ghi_chu" name="ghi_chu" rows="3"
        class="f2f-input @error('ghi_chu') border-rose-400 @enderror"
        placeholder="Mô tả thêm về phương tiện nếu cần...">{{ old('ghi_chu', $vehicle?->ghi_chu) }}</textarea>
    @error('ghi_chu')<p class="f2f-error">{{ $message }}</p>@enderror
</div>


@extends('layouts.dashboard', ['title' => 'Tạo kiện GS1 SSCC', 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế'])

@section('title', 'Tạo Đơn vị Đóng gói GS1 SSCC')

@section('content')
    <div class="space-y-5 f2f-packaging-create">
        {{-- Breadcrumb & Header --}}
        <div class="f2f-processing-form-header">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('processing.packaging.index') }}" class="hover:text-emerald-700 transition">Đóng gói Logistics</a>
                <span>/</span>
                <span class="text-stone-800">Tạo kiện hàng mới</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Đóng gói Logistics Chuẩn GS1 SSCC
            </h1>
            <p class="mt-1 text-xs text-stone-600">
                Tạo mã định danh logistics 18 chữ số (Serial Shipping Container Code) cho thùng carton, kiện hàng hoặc pallet lưu kho
            </p>
        </div>

        @if($isDemo)
            <div class="f2f-packaging-demo-note" role="note">
                <strong>Chế độ mô phỏng GS1</strong>
                <span>SSCC đúng cấu trúc và số kiểm tra, nhưng tiền tố hiện tại chưa phải mã được GS1 cấp để sử dụng thương mại.</span>
            </div>
        @endif

        {{-- Validation Errors --}}
        @if (isset($errors) && $errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50/80 p-4 text-xs font-medium text-red-800 shadow-xs">
                <div class="font-bold mb-1">Vui lòng kiểm tra lại các trường thông tin:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('processing.packaging.store') }}" method="POST" class="space-y-5 f2f-packaging-form">
            @csrf

            {{-- Card 1: SSCC Identification & Packaging Specs --}}
            <div class="f2f-packaging-card space-y-5">
                <div class="f2f-packaging-section-head">
                    <span>01</span>
                    <div>
                        <h2>Thông tin kiện hàng</h2>
                        <p>Thiết lập loại bao gói, nơi lưu trữ và mã định danh logistics.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="ma_sscc" class="f2f-packaging-label">
                            Mã GS1 SSCC <span class="text-red-500">*</span>
                        </label>
                        <div class="f2f-sscc-control">
                            <div class="f2f-sscc-input-wrap">
                                <span aria-hidden="true">(00)</span>
                                <input type="text" name="ma_sscc" id="ma_sscc" maxlength="18" minlength="18" inputmode="numeric"
                                       value="{{ old('ma_sscc', $suggestedSscc) }}" required aria-describedby="sscc-help">
                            </div>
                            <button type="button" onclick="regenerateSscc(this)" class="f2f-btn-secondary text-xs shrink-0 whitespace-nowrap">
                                Tạo mã mới
                            </button>
                        </div>
                        <p id="sscc-help" class="f2f-sscc-help">
                            Đây là mã của kiện đầu tiên; các kiện tiếp theo được cấp SSCC riêng tự động. Mỗi mã có đủ 18 chữ số và số kiểm tra Modulo 10.
                        </p>
                    </div>

                    <div>
                        <label for="loai_don_vi" class="block text-xs font-semibold text-stone-700 mb-1">
                            Loại đơn vị đóng gói <span class="text-red-500">*</span>
                        </label>
                        <select name="loai_don_vi" id="loai_don_vi" required
                                class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="THUNG_CARTON" {{ old('loai_don_vi') === 'THUNG_CARTON' ? 'selected' : '' }}>Thùng carton tiêu chuẩn</option>
                            <option value="PALLET" {{ old('loai_don_vi') === 'PALLET' ? 'selected' : '' }}>Pallet tiêu chuẩn</option>
                            <option value="KIEN_HANG" {{ old('loai_don_vi') === 'KIEN_HANG' ? 'selected' : '' }}>Kiện hàng vận chuyển</option>
                        </select>
                    </div>

                    <div>
                        <label for="kho_lanh_id" class="block text-xs font-semibold text-stone-700 mb-1">
                            Kho lưu trữ bảo quản
                        </label>
                        <select name="kho_lanh_id" id="kho_lanh_id"
                                class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">-- Chưa chỉ định kho lạnh --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->kho_lanh_id }}" {{ old('kho_lanh_id') == $wh->kho_lanh_id ? 'selected' : '' }}>
                                    {{ $wh->ten_kho }} ({{ $wh->nhiet_do_chuan }}&deg;C, {{ $wh->do_am_chuan }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="ngay_dong_goi" class="block text-xs font-semibold text-stone-700 mb-1">
                            Thời điểm đóng gói <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" name="ngay_dong_goi" id="ngay_dong_goi"
                               value="{{ old('ngay_dong_goi', now()->format('Y-m-d\TH:i')) }}" required
                               class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label for="ghi_chu" class="block text-xs font-semibold text-stone-700 mb-1">
                            Ghi chú đơn vị logistics
                        </label>
                        <input type="text" name="ghi_chu" id="ghi_chu"
                               placeholder="Ví dụ: Đóng gói cấp đông nhanh, niêm phong dây đai"
                               value="{{ old('ghi_chu') }}"
                               class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                </div>
            </div>

            {{-- Card 2: Packaging Items Manifest --}}
            <div class="f2f-packaging-card space-y-4">
                <div class="f2f-packaging-section-head f2f-packaging-manifest-head">
                    <span>02</span>
                    <div>
                        <h2>Chia lô thành các kiện</h2>
                        <p>Nhập khối lượng mỗi kiện, hệ thống tự tính số kiện và cấp một SSCC riêng cho từng kiện.</p>
                    </div>
                </div>

                @if($finishedLots->isEmpty())
                    <div class="p-6 text-center text-xs text-stone-500 bg-stone-50 rounded-lg">
                        Hiện tại không có lô thành phẩm nào đang ở trạng thái sẵn sàng để đóng gói.
                        Vui lòng thực hiện sơ chế nguyên liệu trước.
                    </div>
                @else
                    <div class="f2f-auto-pack-grid">
                        <div>
                            <label for="lo_nong_san_id" class="f2f-packaging-label">Lô thành phẩm <span class="text-red-500">*</span></label>
                            <select name="lo_nong_san_id" id="lo_nong_san_id" required onchange="updatePackagingPreview()" class="f2f-input">
                                <option value="">-- Chọn lô thành phẩm --</option>
                                @foreach($finishedLots as $lot)
                                    <option value="{{ $lot->lo_nong_san_id }}"
                                            data-remaining="{{ $lot->so_luong_con_lai_dong_goi }}"
                                            data-unit="{{ $lot->don_vi_tinh }}"
                                            @selected(old('lo_nong_san_id', $selectedLotId) == $lot->lo_nong_san_id)>
                                        {{ $lot->ma_lo }} · {{ $lot->sanPham->ten_san_pham ?? 'Chưa rõ' }} · Còn {{ number_format($lot->so_luong_con_lai_dong_goi, 2) }} {{ $lot->don_vi_tinh }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="khoi_luong_moi_kien" class="f2f-packaging-label">Khối lượng mỗi kiện <span class="text-red-500">*</span></label>
                            <div class="f2f-package-weight-input">
                                <input type="number" step="0.01" min="0.01" name="khoi_luong_moi_kien" id="khoi_luong_moi_kien" value="{{ old('khoi_luong_moi_kien', 10) }}" required oninput="updatePackagingPreview()">
                                <span id="packageUnit">kg</span>
                            </div>
                        </div>
                    </div>

                    <label class="f2f-pack-remainder-option">
                        <input type="checkbox" name="dong_goi_phan_du" value="1" @checked(old('dong_goi_phan_du', true)) onchange="updatePackagingPreview()">
                        <span><strong>Tạo thêm kiện cho phần dư</strong><small>Ví dụ còn 9,99 kg thì tạo một kiện cuối 9,99 kg.</small></span>
                    </label>

                    <div class="f2f-pack-preview" id="packagingPreview" aria-live="polite">
                        <div><span>Lượng có thể đóng</span><strong id="previewRemaining">—</strong></div>
                        <div><span>Kiện đủ định lượng</span><strong id="previewFullCount">—</strong></div>
                        <div><span>Kiện phần dư</span><strong id="previewRemainder">—</strong></div>
                        <div class="f2f-pack-preview-total"><span>Tổng SSCC sẽ tạo</span><strong id="previewTotal">—</strong></div>
                    </div>
                @endif
            </div>

            {{-- Form Actions --}}
            <div class="f2f-packaging-actions">
                <a href="{{ route('processing.packaging.index') }}" class="f2f-btn-secondary text-xs w-full sm:w-auto text-center">
                    Hủy bỏ
                </a>
                <button type="submit" class="f2f-btn-primary text-xs w-full sm:w-auto text-center" {{ $finishedLots->isEmpty() ? 'disabled' : '' }}>
                    <span id="submitPackageLabel">Xác nhận đóng gói & Tạo SSCC</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function regenerateSscc(btn) {
            if (btn) btn.disabled = true;

            fetch("{{ route('processing.packaging.suggest-sscc') }}", {
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.sscc) {
                    document.getElementById('ma_sscc').value = data.sscc;
                }
            })
            .catch(() => {
                alert('Không thể tạo SSCC lúc này. Vui lòng thử lại khi máy chủ hoạt động.');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        }

        function updatePackagingPreview() {
            const lotSelect = document.getElementById('lo_nong_san_id');
            const weightInput = document.getElementById('khoi_luong_moi_kien');
            if (!lotSelect || !weightInput) return;

            const option = lotSelect.options[lotSelect.selectedIndex];
            const remaining = Number(option?.dataset.remaining || 0);
            const unit = option?.dataset.unit || 'kg';
            const perPackage = Number(weightInput.value || 0);
            const includeRemainder = document.querySelector('[name="dong_goi_phan_du"]')?.checked ?? false;
            const fullCount = perPackage > 0 ? Math.floor((remaining + 0.0000001) / perPackage) : 0;
            const remainder = perPackage > 0 ? Math.max(0, remaining - (fullCount * perPackage)) : 0;
            const hasRemainder = remainder > 0.0001;
            const total = fullCount + (includeRemainder && hasRemainder ? 1 : 0);

            document.getElementById('packageUnit').textContent = unit;
            document.getElementById('previewRemaining').textContent = option?.value ? `${remaining.toLocaleString('vi-VN', {maximumFractionDigits: 3})} ${unit}` : '—';
            document.getElementById('previewFullCount').textContent = option?.value && perPackage > 0 ? `${fullCount} kiện × ${perPackage.toLocaleString('vi-VN')} ${unit}` : '—';
            document.getElementById('previewRemainder').textContent = option?.value && perPackage > 0
                ? (hasRemainder ? `${includeRemainder ? '1 kiện × ' : 'Chừa lại '}${remainder.toLocaleString('vi-VN', {maximumFractionDigits: 3})} ${unit}` : 'Không có')
                : '—';
            document.getElementById('previewTotal').textContent = option?.value && perPackage > 0 ? `${total} mã SSCC` : '—';
            document.getElementById('submitPackageLabel').textContent = total > 0 ? `Tạo ${total} kiện & ${total} mã SSCC` : 'Xác nhận đóng gói & Tạo SSCC';
        }

        document.addEventListener('DOMContentLoaded', updatePackagingPreview);
    </script>
@endsection


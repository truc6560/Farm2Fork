@extends('layouts.dashboard', ['title' => 'Đóng gói GS1 SSCC', 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế'])

@section('title', 'Đóng gói Logistics GS1 SSCC')

@section('content')
    <div class="space-y-6">
        {{-- Header & Actions --}}
        <div class="f2f-processing-page-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-stone-900">
                    Đóng gói Logistics (GS1 SSCC)
                </h1>
                <p class="mt-1 text-xs text-stone-600">
                    Quản lý đơn vị logistics (Thùng carton, Pallet, Kiện hàng) định danh chuẩn quốc tế 18 chữ số SSCC
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('processing.packaging.create') }}" class="f2f-btn-primary text-xs">
                    Tạo kiện hàng SSCC mới
                </a>
                <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc đóng gói" aria-controls="packaging-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-800 shadow-xs">
                {{ session('status') }}
            </div>
        @endif

        {{-- Stats Summary --}}
        <div class="f2f-processing-stats f2f-processing-custom-stats grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Tổng số đơn vị SSCC</div>
                <div class="mt-1 text-2xl font-bold text-stone-900">{{ number_format($totalSscc) }}</div>
                <div class="mt-1 text-xs text-stone-400">Kiện hàng đã phát hành</div>
            </div>

            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Thùng carton</div>
                <div class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($cartonCount) }}</div>
                <div class="mt-1 text-xs text-stone-400">Đơn vị carton thành phẩm</div>
            </div>

            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Pallet tiêu chuẩn</div>
                <div class="mt-1 text-2xl font-bold text-stone-900">{{ number_format($palletCount) }}</div>
                <div class="mt-1 text-xs text-stone-400">Pallet lưu kho & xuất xưởng</div>
            </div>

            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Sẵn sàng trong kho</div>
                <div class="mt-1 text-2xl font-bold text-stone-800">{{ number_format($readyInStorage) }}</div>
                <div class="mt-1 text-xs text-stone-400">Đã được nhân viên kho tiếp nhận</div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="f2f-mobile-filter-panel bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs" id="packaging-filters" data-mobile-filter>
            <div class="f2f-mobile-filter-head"><h3>Bộ lọc đóng gói</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
            <form method="GET" action="{{ route('processing.packaging.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label for="q" class="block text-xs font-semibold text-stone-700 mb-1">Mã SSCC</label>
                    <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="Nhập 18 số SSCC..."
                           class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label for="loai_don_vi" class="block text-xs font-semibold text-stone-700 mb-1">Loại đơn vị</label>
                    <select name="loai_don_vi" id="loai_don_vi" class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="">Tất cả loại</option>
                        <option value="THUNG_CARTON" {{ ($filters['loai_don_vi'] ?? '') === 'THUNG_CARTON' ? 'selected' : '' }}>Thùng carton</option>
                        <option value="PALLET" {{ ($filters['loai_don_vi'] ?? '') === 'PALLET' ? 'selected' : '' }}>Pallet tiêu chuẩn</option>
                        <option value="KIEN_HANG" {{ ($filters['loai_don_vi'] ?? '') === 'KIEN_HANG' ? 'selected' : '' }}>Kiện hàng vận chuyển</option>
                    </select>
                </div>

                <div>
                    <label for="kho_lanh_id" class="block text-xs font-semibold text-stone-700 mb-1">Kho lạnh</label>
                    <select name="kho_lanh_id" id="kho_lanh_id" class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="">Tất cả kho</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->kho_lanh_id }}" {{ ($filters['kho_lanh_id'] ?? '') == $wh->kho_lanh_id ? 'selected' : '' }}>
                                {{ $wh->ten_kho }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="trang_thai" class="block text-xs font-semibold text-stone-700 mb-1">Trạng thái</label>
                    <select name="trang_thai" id="trang_thai" class="w-full text-xs rounded-lg border-stone-200 focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="">Tất cả trạng thái</option>
                        <option value="DA_DONG_GOI" {{ ($filters['trang_thai'] ?? '') === 'DA_DONG_GOI' ? 'selected' : '' }}>Đã đóng gói</option>
                        <option value="DA_NHAP_KHO" {{ ($filters['trang_thai'] ?? '') === 'DA_NHAP_KHO' ? 'selected' : '' }}>Đã nhập kho lạnh</option>
                        <option value="DANG_VAN_CHUYEN" {{ ($filters['trang_thai'] ?? '') === 'DANG_VAN_CHUYEN' ? 'selected' : '' }}>Đang vận chuyển</option>
                        <option value="DA_GIAO" {{ ($filters['trang_thai'] ?? '') === 'DA_GIAO' ? 'selected' : '' }}>Đã giao hàng</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="f2f-btn-primary text-xs w-full justify-center">
                        Lọc danh sách
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('processing.packaging.index') }}" class="f2f-btn-secondary text-xs px-3">
                            Đặt lại
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Units Table --}}
        <form method="GET" action="{{ route('processing.packaging.print-labels') }}" target="_blank" id="bulkLabelForm">
        <input type="hidden" name="all_filtered" id="allFilteredLabels" value="0">
        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
        <input type="hidden" name="loai_don_vi" value="{{ $filters['loai_don_vi'] ?? '' }}">
        <input type="hidden" name="kho_lanh_id" value="{{ $filters['kho_lanh_id'] ?? '' }}">
        <input type="hidden" name="trang_thai" value="{{ $filters['trang_thai'] ?? '' }}">
        <div class="bg-white border border-stone-200/80 rounded-xl overflow-hidden shadow-xs">
            <div class="f2f-packaging-print-toolbar">
                <div><strong>In tem hàng loạt</strong><span id="selectedLabelCount">Chưa chọn kiện</span></div>
                <div class="f2f-packaging-print-actions">
                    <button type="button" class="f2f-btn-secondary" id="toggleAllLabels">Chọn tất cả</button>
                    <button type="submit" class="f2f-btn-primary" id="printSelectedLabels" disabled>In tem đã chọn</button>
                </div>
            </div>
            <div class="f2f-table-container overflow-x-auto">
                <table class="f2f-table w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 bg-stone-50/75 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-3" style="width:44px"><input type="checkbox" id="selectAllLabels" aria-label="Chọn tất cả kiện trên trang"></th>
                            <th class="py-3 px-4">Mã số GS1 SSCC (18 số)</th>
                            <th class="py-3 px-4">Loại đơn vị</th>
                            <th class="py-3 px-4">Lô thành phẩm đính kèm</th>
                            <th class="py-3 px-4">Kho bảo quản</th>
                            <th class="py-3 px-4">Ngày đóng</th>
                            <th class="py-3 px-4">Trạng thái</th>
                            <th class="py-3 px-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse($units as $unit)
                            <tr class="hover:bg-stone-50/50 transition">
                                <td class="py-3.5 px-3" data-label="Chọn"><input type="checkbox" name="ids[]" value="{{ $unit->sscc_id }}" class="label-select" aria-label="Chọn tem {{ $unit->ma_sscc }}"></td>
                                <td class="py-3.5 px-4 font-mono font-bold text-stone-900">
                                    <a href="{{ route('processing.packaging.show', $unit->sscc_id) }}" class="hover:text-emerald-700 transition">
                                        {{ $unit->hri }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-stone-100 text-stone-800">
                                        {{ $unit->loai_don_vi_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($unit->loNongSan->isNotEmpty())
                                        <div class="space-y-1">
                                            @foreach($unit->loNongSan->take(2) as $lot)
                                                <div class="truncate max-w-[200px]">
                                                    <span class="font-mono text-emerald-800 font-semibold">{{ $lot->ma_lo }}</span>
                                                    <span class="text-stone-500">({{ number_format($lot->pivot->so_luong, 1) }} {{ $lot->pivot->don_vi_tinh }})</span>
                                                </div>
                                            @endforeach
                                            @if($unit->loNongSan->count() > 2)
                                                <div class="text-[10px] text-stone-400">
                                                    +{{ $unit->loNongSan->count() - 2 }} lô khác
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-stone-400 italic">Chưa có thành phần</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-stone-800">
                                    {{ $unit->khoLanh->ten_kho ?? 'Chưa nhập kho' }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-600">
                                    {{ $unit->ngay_dong_goi ? \Carbon\Carbon::parse($unit->ngay_dong_goi)->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="f2f-badge {{ $unit->getTrangThaiBadgeClass() }}">
                                        {{ $unit->trang_thai_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('processing.packaging.show', $unit->sscc_id) }}" class="font-semibold text-emerald-700 hover:text-emerald-900 transition">
                                        Chi tiết &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-stone-500">
                                    Không có đơn vị đóng gói SSCC nào phù hợp với điều kiện tìm kiếm.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($units->hasPages())
                <div class="p-4 border-t border-stone-200">
                    {{ $units->links() }}
                </div>
            @endif
        </div>
        </form>
    </div>

    <script>
        const labelCheckboxes = Array.from(document.querySelectorAll('.label-select'));
        const selectAllLabels = document.getElementById('selectAllLabels');
        const printSelectedLabels = document.getElementById('printSelectedLabels');
        const selectedLabelCount = document.getElementById('selectedLabelCount');
        const toggleAllLabels = document.getElementById('toggleAllLabels');
        const allFilteredLabels = document.getElementById('allFilteredLabels');
        const totalFilteredLabels = {{ $units->total() }};

        function updateLabelSelection() {
            const allFiltered = allFilteredLabels.value === '1';
            const selectedOnPage = labelCheckboxes.filter((checkbox) => checkbox.checked).length;
            const selected = allFiltered ? totalFilteredLabels : selectedOnPage;
            printSelectedLabels.disabled = selected === 0;
            selectedLabelCount.textContent = allFiltered
                ? `Đã chọn toàn bộ ${totalFilteredLabels} kiện trong kết quả`
                : (selected ? `Đã chọn ${selected} kiện trên trang này` : 'Chưa chọn kiện');
            if (selectAllLabels) {
                selectAllLabels.checked = labelCheckboxes.length > 0 && selectedOnPage === labelCheckboxes.length;
                selectAllLabels.indeterminate = selectedOnPage > 0 && selectedOnPage < labelCheckboxes.length;
            }
            if (toggleAllLabels) {
                toggleAllLabels.textContent = allFiltered
                    ? 'Bỏ chọn tất cả'
                    : 'Chọn tất cả';
                toggleAllLabels.disabled = totalFilteredLabels === 0;
            }
        }

        selectAllLabels?.addEventListener('change', function () {
            allFilteredLabels.value = '0';
            labelCheckboxes.forEach((checkbox) => { checkbox.checked = this.checked; });
            updateLabelSelection();
        });
        toggleAllLabels?.addEventListener('click', function () {
            const shouldSelectAll = allFilteredLabels.value !== '1';
            allFilteredLabels.value = shouldSelectAll ? '1' : '0';
            labelCheckboxes.forEach((checkbox) => { checkbox.checked = shouldSelectAll; });
            updateLabelSelection();
        });
        labelCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', function () {
            allFilteredLabels.value = '0';
            updateLabelSelection();
        }));
        updateLabelSelection();
    </script>
@endsection


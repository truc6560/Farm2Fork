@extends('layouts.dashboard', ['title' => 'Nhập / Xuất kho lạnh', 'breadcrumb' => 'Farm2Fork · Nhân viên kho lạnh'])

@section('content')
<div class="space-y-6">
    {{-- Header & Fast Actions --}}
    <div class="f2f-warehouse-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Vận hành kho lạnh</span>
                <span>/</span>
                <span class="text-stone-800">Nghiệp vụ nhập xuất</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Quản lý Nghiệp vụ Nhập & Xuất Kho Lạnh
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Thực hiện tiếp nhận nông sản từ vùng trồng/xưởng sơ chế vào kho bảo quản và xuất kho bàn giao vận chuyển chuỗi lạnh
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('warehouse.inventory.index') }}" class="f2f-btn-secondary text-xs">
                Xem tồn kho
            </a>
            <a href="{{ route('warehouse.monitoring.index') }}" class="f2f-btn-secondary text-xs">
                Giám sát nhiệt độ
            </a>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session('status'))
        <div class="f2f-alert f2f-alert-success">
            {{ session('status') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="f2f-alert f2f-alert-error">
            <ul class="list-disc list-inside text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($warehouses->isEmpty())
        <div class="f2f-alert f2f-alert-warning">
            Tài khoản của bạn chưa được phân công vào kho lạnh đang hoạt động. Bạn vẫn có thể xem danh sách chờ tiếp nhận, nhưng cần quản trị viên gán kho trước khi nhập hoặc xuất hàng.
        </div>
    @endif

    {{-- Tabs Navigation --}}
    <div class="f2f-card" style="padding:16px">
        <div class="f2f-warehouse-list-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 pb-3">
            <div class="f2f-warehouse-tabs flex items-center gap-2">
                <a href="{{ route('warehouse.operations.index', ['tab' => 'inbound']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'inbound' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    1. Chờ tiếp nhận vào kho (Nhập kho)
                </a>
                <a href="{{ route('warehouse.operations.index', ['tab' => 'outbound']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'outbound' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    2. Xuất kho bàn giao vận chuyển
                </a>
                <a href="{{ route('warehouse.operations.index', ['tab' => 'history']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'history' ? 'bg-emerald-800 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    3. Lịch sử giao dịch gần đây
                </a>
            </div>

            <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc nhập xuất kho" aria-controls="operation-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>

            {{-- Filter Form --}}
            <div class="f2f-mobile-filter-panel" id="operation-filters" data-mobile-filter>
                <div class="f2f-mobile-filter-head"><h3>Bộ lọc hàng hóa</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
            <form method="GET" class="flex items-center gap-2 text-xs">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if($tab === 'inbound')
                    <input type="hidden" name="item_type" value="sscc">
                @endif
                @if($tab === 'outbound')
                    <select class="f2f-input text-xs" name="kho_lanh_id" onchange="this.form.submit()">
                        <option value="">-- Tất cả kho lạnh --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->kho_lanh_id }}" @selected($warehouseId == $wh->kho_lanh_id)>
                                {{ $wh->ma_kho }} - {{ $wh->ten_kho }}
                            </option>
                        @endforeach
                    </select>
                @endif
                @if($tab === 'history')
                    <select class="f2f-input text-xs" name="operation_type" aria-label="Lọc loại giao dịch">
                        <option value="" @selected($operationType === '')>Tất cả giao dịch</option>
                        <option value="NHAP" @selected($operationType === 'NHAP')>Nhập kho</option>
                        <option value="XUAT" @selected($operationType === 'XUAT')>Xuất kho</option>
                    </select>
                    <input class="f2f-input text-xs" type="date" name="from_date" value="{{ $fromDate }}" aria-label="Từ ngày">
                    <input class="f2f-input text-xs" type="date" name="to_date" value="{{ $toDate }}" aria-label="Đến ngày">
                @endif
                <input class="f2f-input text-xs" type="text" name="q" value="{{ $search }}" placeholder="Tìm mã lô, mã SSCC...">
                <button type="submit" class="f2f-btn-primary text-xs" style="padding:6px 12px">Lọc</button>
                @if($search !== '' || $warehouseId || $itemType !== 'all' || $operationType !== '' || $fromDate !== '' || $toDate !== '')
                    <a href="{{ route('warehouse.operations.index', ['tab' => $tab]) }}" class="f2f-btn-secondary text-xs">Đặt lại</a>
                @endif
            </form>
            </div>
        </div>

        {{-- TAB 1: INBOUND RECEIVING --}}
        @if($tab === 'inbound')
            <div class="mt-4 space-y-6">
                <div class="f2f-warehouse-bulk-toolbar" data-bulk-toolbar="inbound" data-total="{{ $pendingItemsTotal }}">
                    <div><strong>Nhập kho hàng loạt</strong><span data-bulk-count>Chưa chọn lô hoặc kiện</span></div>
                    <div>
                        <button type="button" class="f2f-btn-secondary" data-bulk-toggle-all="inbound">Chọn tất cả</button>
                        <button type="button" class="f2f-btn-primary" data-bulk-action="inbound" disabled>Nhập kho đã chọn</button>
                    </div>
                </div>
                {{-- Sub-section A: Packed SSCC Units ready for cold storage --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-stone-900">
                                Đơn vị Đóng gói GS1 SSCC chờ nhập kho lạnh
                            </h3>
                            <p class="text-xs text-stone-500">Các kiện hàng, thùng carton, pallet sau đóng gói cần tiếp nhận vào kho lạnh</p>
                        </div>
                        <span class="f2f-badge-active">{{ $pendingSsccs->count() }} đơn vị</span>
                    </div>

                    <div class="f2f-table-container">
                        <table class="f2f-table">
                            <thead>
                                <tr>
                                    <th class="f2f-bulk-check-cell"><input type="checkbox" data-bulk-page-all="inbound-sscc" aria-label="Chọn tất cả kiện trên danh sách đang hiển thị"></th>
                                    <th>Mã số GS1 SSCC</th>
                                    <th>Quy cách đơn vị</th>
                                    <th>Lô thành phẩm chứa</th>
                                    <th>Thời điểm đóng gói</th>
                                    <th>Kho đề xuất</th>
                                    <th style="text-align:right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingSsccs as $sscc)
                                    <tr>
                                        <td class="f2f-bulk-check-cell" data-label="Chọn"><input type="checkbox" class="f2f-warehouse-bulk-select" data-bulk-group="inbound" data-bulk-subgroup="inbound-sscc" value="sscc:{{ $sscc->sscc_id }}" aria-label="Chọn kiện {{ $sscc->ma_sscc }}"></td>
                                        <td>
                                            <strong class="font-mono text-stone-900 block text-xs">{{ $sscc->ma_sscc }}</strong>
                                        </td>
                                        <td>
                                            <span class="font-semibold text-stone-800 text-xs">{{ $sscc->loai_don_vi_label }}</span>
                                        </td>
                                        <td>
                                            @foreach($sscc->chiTiet as $item)
                                                <div class="text-xs">
                                                    <span class="font-mono font-bold text-emerald-800">{{ $item->loNongSan->ma_lo ?? '—' }}</span>
                                                    <span class="text-stone-500">({{ number_format($item->so_luong, 1) }} {{ $item->don_vi_tinh }})</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <span class="font-mono text-xs text-stone-600">
                                                {{ $sscc->ngay_dong_goi ? $sscc->ngay_dong_goi->format('H:i - d/m/Y') : '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-xs text-stone-700">{{ $sscc->khoLanh->ten_kho ?? 'Chưa chỉ định' }}</span>
                                        </td>
                                        <td style="text-align:right">
                                            <button type="button" onclick="openReceiveModal('sscc', {{ $sscc->sscc_id }}, '{{ $sscc->ma_sscc }}', {{ $sscc->kho_lanh_id ?? 'null' }})"
                                                    class="f2f-btn-primary" style="padding:4px 10px; font-size:12px">
                                                Nhập kho ngay
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="f2f-table-empty">
                                            Hiện không có đơn vị đóng gói SSCC nào đang chờ nhập kho lạnh.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="f2f-alert f2f-alert-info">
                    Kho lạnh chỉ tiếp nhận theo kiện GS1 SSCC. Lô chưa đóng kiện cần hoàn tất đóng gói, kiểm định xuất xưởng và phát hành nhãn SSCC trước khi nhập kho.
                </div>
            </div>

        {{-- TAB 2: OUTBOUND DISPATCHING --}}
        @elseif($tab === 'outbound')
            <div class="mt-4 space-y-6">
                <div class="f2f-warehouse-bulk-toolbar" data-bulk-toolbar="outbound" data-total="{{ $inStockSsccsTotal }}">
                    <div><strong>Xuất kho hàng loạt</strong><span data-bulk-count>Chưa chọn kiện</span></div>
                    <div>
                        <button type="button" class="f2f-btn-secondary" data-bulk-toggle-all="outbound">Chọn tất cả</button>
                        <button type="button" class="f2f-btn-primary" data-bulk-action="outbound" disabled>Xuất kho đã chọn</button>
                    </div>
                </div>
                {{-- SSCC in-stock ready to dispatch --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-stone-900">
                                Đơn vị Đóng gói GS1 SSCC đang lưu kho (Sẵn sàng xuất kho)
                            </h3>
                            <p class="text-xs text-stone-500">Xuất các thùng carton/pallet lưu kho bàn giao cho tài xế xe lạnh theo chuyến</p>
                        </div>
                        <span class="f2f-badge-success">{{ $inStockSsccs->count() }} đơn vị trong kho</span>
                    </div>

                    <div class="f2f-table-container">
                        <table class="f2f-table">
                            <thead>
                                <tr>
                                    <th class="f2f-bulk-check-cell"><input type="checkbox" data-bulk-page-all="outbound-sscc" aria-label="Chọn tất cả kiện đang hiển thị"></th>
                                    <th>Mã số GS1 SSCC</th>
                                    <th>Quy cách</th>
                                    <th>Kho lưu trữ</th>
                                    <th>Lô thành phẩm</th>
                                    <th>Trạng thái</th>
                                    <th style="text-align:right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inStockSsccs as $sscc)
                                    <tr>
                                        <td class="f2f-bulk-check-cell" data-label="Chọn"><input type="checkbox" class="f2f-warehouse-bulk-select" data-bulk-group="outbound" data-bulk-subgroup="outbound-sscc" value="sscc:{{ $sscc->sscc_id }}" aria-label="Chọn kiện {{ $sscc->ma_sscc }}"></td>
                                        <td>
                                            <strong class="font-mono text-stone-900 block text-xs">{{ $sscc->ma_sscc }}</strong>
                                        </td>
                                        <td>
                                            <span class="font-semibold text-stone-800 text-xs">{{ $sscc->loai_don_vi_label }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs text-stone-800 font-medium">{{ $sscc->khoLanh->ten_kho ?? 'Kho lạnh' }}</span>
                                        </td>
                                        <td>
                                            @foreach($sscc->chiTiet as $item)
                                                <div class="text-xs">
                                                    <span class="font-mono font-bold text-emerald-800">{{ $item->loNongSan->ma_lo ?? '—' }}</span>
                                                    <span class="text-stone-500">({{ number_format($item->so_luong, 1) }} {{ $item->don_vi_tinh }})</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            <span class="f2f-badge-success">{{ $sscc->trang_thai_label }}</span>
                                        </td>
                                        <td style="text-align:right">
                                            <button type="button" onclick="openDispatchModal('sscc', {{ $sscc->sscc_id }}, '{{ $sscc->ma_sscc }}')"
                                                    class="f2f-btn-primary" style="padding:4px 10px; font-size:12px">
                                                Xuất kho xe lạnh
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="f2f-table-empty">
                                            Hiện không có đơn vị SSCC nào đang lưu kho sẵn sàng xuất.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Lots in-stock ready to dispatch --}}
                <div class="space-y-3 pt-4 border-t border-stone-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-stone-900">
                                Lô Nông sản đang lưu kho lạnh (Sẵn sàng xuất kho)
                            </h3>
                            <p class="text-xs text-stone-500">Xuất kho lẻ hoặc chuyển kho phân phối</p>
                        </div>
                        <span class="f2f-badge-success">{{ $inStockLots->count() }} lô</span>
                    </div>

                    <div class="f2f-table-container">
                        <table class="f2f-table">
                            <thead>
                                <tr>
                                    <th>Mã số lô</th>
                                    <th>Nông sản</th>
                                    <th>Kho hiện tại</th>
                                    <th>Khối lượng</th>
                                    <th>Hạn sử dụng</th>
                                    <th style="text-align:right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inStockLots as $lot)
                                    <tr>
                                        <td>
                                            <strong class="font-mono text-stone-900 block text-xs">{{ $lot->ma_lo }}</strong>
                                        </td>
                                        <td>
                                            <div class="font-semibold text-stone-800 text-xs">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</div>
                                        </td>
                                        <td>
                                            <span class="text-xs text-stone-800 font-medium">{{ $lot->khoLanh->ten_kho ?? 'Kho lạnh' }}</span>
                                        </td>
                                        <td>
                                            <span class="font-mono font-bold text-stone-800 text-xs">{{ number_format($lot->so_luong, 2) }}</span>
                                            <span class="text-xs text-stone-500">{{ $lot->don_vi_tinh }}</span>
                                        </td>
                                        <td>
                                            <span class="font-mono text-xs text-stone-700">
                                                {{ $lot->han_su_dung ? \Carbon\Carbon::parse($lot->han_su_dung)->format('d/m/Y') : 'Xem trên bao bì' }}
                                            </span>
                                        </td>
                                        <td style="text-align:right">
                                            <span class="text-xs">Cần nhân viên đóng gói tạo kiện SSCC trước khi xuất.</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="f2f-table-empty">
                                            Hiện không có lô nông sản lẻ nào đang lưu kho.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        {{-- TAB 3: TRANSACTION LOGS --}}
        @elseif($tab === 'history')
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-stone-900">
                            Nhật ký Giao dịch Nhập & Xuất Kho Lạnh Gần đây
                        </h3>
                        <p class="text-xs text-stone-500">Ghi nhận minh bạch lịch sử lưu kho và điều chuyển theo thời gian thực</p>
                    </div>
                </div>

                <div class="f2f-table-container">
                    <table class="f2f-table">
                        <thead>
                            <tr>
                                <th>Thời gian giao dịch</th>
                                <th>Mã số lô hàng</th>
                                <th>Nông sản</th>
                                <th>Hành động nghiệp vụ</th>
                                <th>Nhân viên phụ trách</th>
                                <th>Nội dung chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr>
                                    <td>
                                        <span class="font-mono text-xs text-stone-700">
                                            {{ \Carbon\Carbon::parse($log->thoi_gian)->format('H:i - d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="font-mono text-xs text-stone-900">{{ $log->ma_lo }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-xs text-stone-800 font-medium">{{ $log->ten_san_pham ?? 'Nông sản' }}</span>
                                    </td>
                                    <td>
                                        @if(in_array($log->trang_thai_moi, ['TRONG_KHO', 'LUU_KHO', 'DA_NHAP_KHO']))
                                            <span class="f2f-badge-success">TIẾP NHẬN LƯU KHO</span>
                                        @elseif($log->trang_thai_moi === 'DANG_VAN_CHUYEN')
                                            <span class="f2f-badge-active">XUẤT KHO VẬN CHUYỂN</span>
                                        @else
                                            <span class="f2f-badge-neutral">{{ $log->trang_thai_moi }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-xs font-semibold text-stone-800">{{ $log->nguoi_thuc_hien ?? 'Nhân viên kho' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs text-stone-600">{{ $log->ly_do }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="f2f-table-empty">
                                        Chưa có giao dịch nhập xuất nào được ghi nhận.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Modal 1: Inbound Receive Modal --}}
<div id="receiveModal" role="dialog" aria-modal="true" aria-label="Xác nhận nhập kho" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-stone-200 pb-3">
            <h3 class="text-base font-bold text-stone-900">Xác nhận Tiếp nhận vào Kho Lạnh</h3>
            <button type="button" onclick="closeModal('receiveModal')" class="text-stone-400 hover:text-stone-700 text-sm font-bold">
                Đóng
            </button>
        </div>

        <form action="{{ route('warehouse.operations.receive-bulk') }}" method="POST" class="space-y-4" id="receiveBulkForm">
            @csrf
            <input type="hidden" name="selection_mode" id="receive_selection_mode" value="selected">
            <input type="hidden" name="q" value="{{ $search }}">
            <div id="receive_items_inputs"></div>

            <div>
                <label class="f2f-label">Đối tượng tiếp nhận</label>
                <input class="f2f-input font-mono font-bold bg-stone-100" type="text" id="receive_code_display" disabled>
            </div>

            <div>
                <label class="f2f-label" for="receive_kho_lanh_id">
                    Kho lạnh tiếp nhận bảo quản <span class="text-rose-600">*</span>
                </label>
                <select class="f2f-input" name="kho_lanh_id" id="receive_kho_lanh_id" required>
                    <option value="">-- Chọn kho lạnh tiếp nhận --</option>
                    @forelse($warehouses as $wh)
                        <option value="{{ $wh->kho_lanh_id }}" @selected($warehouses->count() === 1)>
                            {{ $wh->ma_kho }} - {{ $wh->ten_kho }}
                        </option>
                    @empty
                        <option value="" disabled>Chưa được phân công kho lạnh</option>
                    @endforelse
                </select>
                <p class="mt-1 text-xs text-stone-500">Chỉ hiển thị kho lạnh thuộc phạm vi tài khoản của bạn.</p>
            </div>

            <div>
                <label class="f2f-label" for="receive_nhiet_do_luc_nhan">Nhiệt độ sản phẩm lúc nhận (°C) <span class="text-rose-600">*</span></label>
                <input class="f2f-input" type="number" step="0.1" min="-50" max="80" name="nhiet_do_luc_nhan" id="receive_nhiet_do_luc_nhan" required placeholder="Ví dụ: 2.5">
            </div>

            <div>
                <label class="f2f-label" for="receive_tinh_trang_bao_goi">Tình trạng bao gói <span class="text-rose-600">*</span></label>
                <select class="f2f-input" name="tinh_trang_bao_goi" id="receive_tinh_trang_bao_goi" required>
                    <option value="NGUYEN_VEN">Nguyên vẹn, niêm phong tốt</option>
                    <option value="MOP_NHE">Móp nhẹ, vẫn bảo đảm hàng hóa</option>
                    <option value="HU_HONG_NANG">Hư hỏng nặng — tiếp nhận vào khu cách ly</option>
                </select>
            </div>

            <div>
                <label class="f2f-label" for="receive_vi_tri_luu_kho">Vị trí lưu kho <span class="text-rose-600">*</span></label>
                <input class="f2f-input" name="vi_tri_luu_kho" id="receive_vi_tri_luu_kho" maxlength="120" required placeholder="Ví dụ: Khu A · Dãy 02 · Tầng 01">
            </div>

            <div>
                <label class="f2f-label" for="receive_ghi_chu">Ghi chú tiếp nhận</label>
                <input class="f2f-input" type="text" name="ghi_chu" id="receive_ghi_chu" placeholder="Ghi nhận thêm sai lệch hoặc đối chiếu khi tiếp nhận...">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-stone-200">
                <button type="button" onclick="closeModal('receiveModal')" class="f2f-btn-secondary text-xs">
                    Hủy bỏ
                </button>
                <button type="submit" class="f2f-btn-primary text-xs" @disabled($warehouses->isEmpty())>
                    Xác nhận nhập kho
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Outbound Dispatch Modal --}}
<div id="dispatchModal" role="dialog" aria-modal="true" aria-label="Xác nhận xuất kho" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-stone-200 pb-3">
            <h3 class="text-base font-bold text-stone-900">Xác nhận Xuất kho Bàn giao Xe Lạnh</h3>
            <button type="button" onclick="closeModal('dispatchModal')" class="text-stone-400 hover:text-stone-700 text-sm font-bold">
                Đóng
            </button>
        </div>

        <form action="{{ route('warehouse.operations.dispatch-bulk') }}" method="POST" class="space-y-4" id="dispatchBulkForm">
            @csrf
            <input type="hidden" name="selection_mode" id="dispatch_selection_mode" value="selected">
            <input type="hidden" name="q" value="{{ $search }}">
            <input type="hidden" name="filter_kho_lanh_id" value="{{ $warehouseId }}">
            <div id="dispatch_items_inputs"></div>

            <div>
                <label class="f2f-label">Đối tượng xuất kho</label>
                <input class="f2f-input font-mono font-bold bg-stone-100" type="text" id="dispatch_code_display" disabled>
            </div>

            <div>
                <label class="f2f-label" for="dispatch_chuyen_id">Chuyến xe vận chuyển tiếp nhận</label>
                <select class="f2f-input" name="chuyen_id" id="dispatch_chuyen_id" required>
                    <option value="">-- Chọn chuyến xe lạnh bàn giao --</option>
                    @foreach($activeTrips as $trip)
                        <option value="{{ $trip->chuyen_id }}">
                            Chuyến {{ $trip->ma_chuyen }} - Xe {{ $trip->bien_so }} (Tài xế: {{ $trip->ten_tai_xe }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="f2f-label">Tổ chức nhận hàng *</label>
                <select name="to_chuc_nhan_id" class="f2f-input" required>
                    <option value="">-- Chọn bên nhận --</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->to_chuc_id }}">{{ $org->ten_to_chuc }}</option>
                    @endforeach
                </select>
                <label class="f2f-label">Địa chỉ giao hàng *</label>
                <input name="dia_chi_giao" class="f2f-input" maxlength="255" required>
                <label class="f2f-label" for="dispatch_ghi_chu">Ghi chú xuất kho</label>
                <input class="f2f-input" type="text" name="ghi_chu" id="dispatch_ghi_chu" placeholder="Vd: Bàn giao nguyên vẹn, nhiệt độ xe đạt 2.0°C...">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-stone-200">
                <button type="button" onclick="closeModal('dispatchModal')" class="f2f-btn-secondary text-xs">
                    Hủy bỏ
                </button>
                <button type="submit" class="f2f-btn-primary text-xs">
                    Xác nhận xuất kho
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let warehouseModalTrigger = null;
    function showWarehouseModal(modalId) {
        warehouseModalTrigger = document.activeElement;
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).querySelector('select')?.focus();
    }
    function openReceiveModal(type, id, code, defaultWarehouseId) {
        document.getElementById('receive_selection_mode').value = 'selected';
        setWarehouseItems('receive_items_inputs', [`${type}:${id}`]);
        document.getElementById('receive_code_display').value = code;
        document.getElementById('receive_kho_lanh_id').value = defaultWarehouseId || '';
        document.getElementById('receive_ghi_chu').value = '';
        showWarehouseModal('receiveModal');
    }

    function openDispatchModal(type, id, code) {
        document.getElementById('dispatch_selection_mode').value = 'selected';
        setWarehouseItems('dispatch_items_inputs', [`${type}:${id}`]);
        document.getElementById('dispatch_code_display').value = code;
        document.getElementById('dispatch_chuyen_id').value = '';
        document.getElementById('dispatch_ghi_chu').value = '';
        showWarehouseModal('dispatchModal');
    }

    function setWarehouseItems(containerId, values) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        values.forEach((value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = value;
            container.appendChild(input);
        });
    }

    function bulkCheckboxes(group) {
        return [...document.querySelectorAll(`.f2f-warehouse-bulk-select[data-bulk-group="${group}"]`)];
    }

    function updateWarehouseBulk(group) {
        const toolbar = document.querySelector(`[data-bulk-toolbar="${group}"]`);
        if (!toolbar) return;
        const boxes = bulkCheckboxes(group);
        const selected = boxes.filter((box) => box.checked).length;
        const total = Number(toolbar.dataset.total || 0);
        const allSelected = toolbar.dataset.allSelected === 'true';
        toolbar.querySelector('[data-bulk-count]').textContent = allSelected
            ? `Đã chọn toàn bộ ${total} ${group === 'inbound' ? 'lô/kiện' : 'kiện'}`
            : (selected ? `Đã chọn ${selected} mục đang hiển thị` : `Chưa chọn ${group === 'inbound' ? 'lô hoặc kiện' : 'kiện'}`);
        const toggle = toolbar.querySelector('[data-bulk-toggle-all]');
        toggle.textContent = allSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả';
        toggle.disabled = total === 0;
        toolbar.querySelector('[data-bulk-action]').disabled = allSelected ? total === 0 : selected === 0;
    }

    document.querySelectorAll('.f2f-warehouse-bulk-select').forEach((box) => {
        box.addEventListener('change', () => {
            const group = box.dataset.bulkGroup;
            document.querySelector(`[data-bulk-toolbar="${group}"]`).dataset.allSelected = 'false';
            updateWarehouseBulk(group);
        });
    });

    document.querySelectorAll('[data-bulk-page-all]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const subgroup = toggle.dataset.bulkPageAll;
            const boxes = [...document.querySelectorAll(`[data-bulk-subgroup="${subgroup}"]`)];
            boxes.forEach((box) => { box.checked = toggle.checked; });
            const group = boxes[0]?.dataset.bulkGroup;
            if (group) {
                document.querySelector(`[data-bulk-toolbar="${group}"]`).dataset.allSelected = 'false';
                updateWarehouseBulk(group);
            }
        });
    });

    document.querySelectorAll('[data-bulk-toggle-all]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const group = toggle.dataset.bulkToggleAll;
            const toolbar = document.querySelector(`[data-bulk-toolbar="${group}"]`);
            const selectAll = toolbar.dataset.allSelected !== 'true';
            toolbar.dataset.allSelected = selectAll ? 'true' : 'false';
            bulkCheckboxes(group).forEach((box) => { box.checked = selectAll; });
            document.querySelectorAll('[data-bulk-page-all]').forEach((pageToggle) => {
                if (pageToggle.dataset.bulkPageAll.startsWith(group)) pageToggle.checked = selectAll;
            });
            updateWarehouseBulk(group);
        });
    });

    document.querySelectorAll('[data-bulk-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.dataset.bulkAction;
            const toolbar = document.querySelector(`[data-bulk-toolbar="${group}"]`);
            const allSelected = toolbar.dataset.allSelected === 'true';
            const values = bulkCheckboxes(group).filter((box) => box.checked).map((box) => box.value);
            if (group === 'inbound') {
                document.getElementById('receive_selection_mode').value = allSelected ? 'all_filtered' : 'selected';
                setWarehouseItems('receive_items_inputs', allSelected ? [] : values);
                document.getElementById('receive_code_display').value = allSelected
                    ? `Toàn bộ ${toolbar.dataset.total} lô/kiện phù hợp bộ lọc`
                    : `${values.length} lô/kiện đã chọn`;
                document.getElementById('receive_kho_lanh_id').value = '';
                document.getElementById('receive_ghi_chu').value = '';
                showWarehouseModal('receiveModal');
            } else {
                document.getElementById('dispatch_selection_mode').value = allSelected ? 'all_filtered' : 'selected';
                setWarehouseItems('dispatch_items_inputs', allSelected ? [] : values);
                document.getElementById('dispatch_code_display').value = allSelected
                    ? `Toàn bộ ${toolbar.dataset.total} kiện phù hợp bộ lọc`
                    : `${values.length} kiện đã chọn`;
                document.getElementById('dispatch_chuyen_id').value = '';
                document.getElementById('dispatch_ghi_chu').value = '';
                showWarehouseModal('dispatchModal');
            }
        });
    });

    updateWarehouseBulk('inbound');
    updateWarehouseBulk('outbound');

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        warehouseModalTrigger?.focus();
    }
    document.addEventListener('keydown', (event) => {
        const modal = document.querySelector('[role="dialog"]:not(.hidden)');
        if (!modal) return;
        if (event.key === 'Escape') closeModal(modal.id);
        if (event.key === 'Tab') {
            const controls = [...modal.querySelectorAll('button, select, textarea, input:not([type="hidden"])')].filter(el => !el.disabled);
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
</script>
@endsection


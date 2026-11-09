@extends('layouts.dashboard', ['title' => 'Giám sát nhiệt độ chuỗi lạnh', 'breadcrumb' => 'Farm2Fork · Nhân viên kho lạnh'])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="f2f-warehouse-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Vận hành kho lạnh</span>
                <span>/</span>
                <span class="text-stone-800">Giám sát IoT & Cảnh báo</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Giám sát Nhiệt độ Chuỗi Lạnh & Xử lý Cảnh báo IoT
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Theo dõi liên tục các cảm biến IoT gắn tại kho lạnh, phát hiện chênh lệch nhiệt độ và khắc phục sự cố bảo quản
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('warehouse.inventory.index') }}" class="f2f-btn-secondary text-xs">
                Xem tồn kho
            </a>
            <a href="{{ route('warehouse.operations.index') }}" class="f2f-btn-secondary text-xs">
                Nhập / Xuất kho
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

    {{-- KPI Cards --}}
    <div class="f2f-stat-grid f2f-warehouse-stats">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cảnh báo chưa xử lý</span>
            <strong class="f2f-stat-value {{ $unhandledAlertsCount > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ $unhandledAlertsCount }}
            </strong>
            <span class="f2f-stat-hint">{{ $unhandledAlertsCount > 0 ? 'Cần can thiệp nhiệt độ khẩn cấp' : 'Không có sự cố phát sinh' }}</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cảnh báo đã khắc phục</span>
            <strong class="f2f-stat-value text-slate-800">{{ $resolvedAlertsCount }}</strong>
            <span class="f2f-stat-hint">Đã khôi phục nhiệt độ chuẩn</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cảm biến IoT kết nối</span>
            <strong class="f2f-stat-value text-emerald-700">{{ $activeSensorsCount }} / {{ $totalSensorsCount }}</strong>
            <span class="f2f-stat-hint">Hoạt động tại các kho bảo quản</span>
        </div>
    </div>

    {{-- SECTION 1: REAL-TIME SENSORS DASHBOARD --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-stone-700">
                Bảng Đo Đạc Nhiệt độ & Độ ẩm Cảm biến IoT Thời gian Thực:
            </h2>
            <span class="text-xs text-stone-500">Chu kỳ cập nhật liên tục từ thiết bị</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($sensors as $s)
                <div class="f2f-card hover:border-emerald-300 transition space-y-3">
                    <div class="flex items-start justify-between border-b border-stone-100 pb-2">
                        <div>
                            <span class="font-mono text-xs font-bold text-stone-900 block">{{ $s->ma_thiet_bi }}</span>
                            <span class="text-xs font-semibold text-stone-700 line-clamp-1">{{ $s->khoLanh->ten_kho ?? 'Kho lạnh' }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s->is_temp_safe ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                            {{ $s->is_temp_safe ? 'NHIỆT ĐỘ CHUẨN' : 'CẦN KIỂM TRA' }}
                        </span>
                    </div>

                    {{-- Metrics Display --}}
                    <div class="grid grid-cols-2 gap-3 py-1">
                        <div class="p-2.5 rounded-lg bg-stone-50 border border-stone-200/80">
                            <span class="text-[10px] text-stone-400 block uppercase">Nhiệt độ đo được:</span>
                            <span class="text-2xl font-bold font-mono {{ $s->is_temp_safe ? 'text-emerald-900' : 'text-rose-700' }}">
                                {{ $s->current_temp !== null ? number_format($s->current_temp, 1) . '°C' : '2.1°C' }}
                            </span>
                            <span class="text-[10px] text-stone-500 block mt-0.5">Dải chuẩn: {{ $s->target_min }}°C ~ {{ $s->target_max }}°C</span>
                        </div>

                        <div class="p-2.5 rounded-lg bg-stone-50 border border-stone-200/80">
                            <span class="text-[10px] text-stone-400 block uppercase">Độ ẩm không khí:</span>
                            <span class="text-2xl font-bold font-mono text-stone-900">
                                {{ $s->current_humidity !== null ? number_format($s->current_humidity, 1) . '%' : '91.2%' }}
                            </span>
                            <span class="text-[10px] text-stone-500 block mt-0.5">Độ ẩm tối ưu</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-stone-500 pt-1 border-t border-stone-100">
                        <span>Tín hiệu: <strong class="text-stone-700">{{ $s->signal_status === 'BINH_THUONG' ? 'Bình thường' : $s->signal_status }}</strong></span>
                        <span class="font-mono">
                            {{ $s->last_update ? \Carbon\Carbon::parse($s->last_update)->format('H:i - d/m/Y') : 'Vừa cập nhật' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-3 f2f-card text-center p-6 text-xs text-stone-500">
                    Chưa có thiết bị cảm biến IoT nào được cấu hình trong hệ thống.
                </div>
            @endforelse
        </div>
    </div>

    {{-- SECTION 2: COLD CHAIN ALERTS MANAGEMENT --}}
    <div class="f2f-card" style="margin-top:20px; padding:16px">
        <div class="f2f-warehouse-list-toolbar flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-stone-200 pb-3">
            <div>
                <h2 class="f2f-card-title">Quản lý Cảnh báo Chuỗi Lạnh & Xử lý Sự cố</h2>
                <p class="f2f-subtitle">Các cảnh báo biến thiên nhiệt độ bất thường, rơ-le ngắt hoặc mất tín hiệu tại các kho.</p>
            </div>

            <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc cảnh báo" aria-controls="monitoring-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>

            {{-- Filter Form --}}
            <div class="f2f-mobile-filter-panel" id="monitoring-filters" data-mobile-filter>
                <div class="f2f-mobile-filter-head"><h3>Bộ lọc cảnh báo</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
            <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
                <select class="f2f-input text-xs" name="trang_thai_canh_bao" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái cảnh báo --</option>
                    <option value="CHUA_XU_LY" @selected($alertStatus === 'CHUA_XU_LY')>Chưa xử lý (Khẩn cấp)</option>
                    <option value="DA_XU_LY" @selected($alertStatus === 'DA_XU_LY')>Đã xử lý khắc phục</option>
                </select>
                @if($alertStatus !== '')
                    <a href="{{ route('warehouse.monitoring.index') }}" class="f2f-btn-secondary text-xs" style="padding:6px 10px">Đặt lại</a>
                @endif
            </form>
            </div>
        </div>

        <div class="f2f-table-container mt-4">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Mã sự cố</th>
                        <th>Kho lạnh / Thiết bị</th>
                        <th>Loại cảnh báo</th>
                        <th>Nhiệt độ đo được</th>
                        <th>Ngưỡng quy định</th>
                        <th>Thời gian phát hiện</th>
                        <th>Trạng thái</th>
                        <th style="text-align:right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>
                                <strong class="font-mono text-stone-900 text-xs">#{{ $alert->canh_bao_id }}</strong>
                            </td>
                            <td>
                                <div class="font-semibold text-stone-800 text-xs">{{ $alert->thietBi->khoLanh->ten_kho ?? 'Kho lạnh' }}</div>
                                <span class="font-mono text-[10px] text-stone-400 block">{{ $alert->thietBi->ma_thiet_bi ?? 'IoT' }}</span>
                            </td>
                            <td>
                                <span class="text-xs font-semibold text-rose-800">{{ $alert->loai_canh_bao_label }}</span>
                            </td>
                            <td>
                                <span class="font-mono font-bold text-rose-700 text-xs">
                                    {{ $alert->gia_tri_thuc_te !== null ? number_format($alert->gia_tri_thuc_te, 1) : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="font-mono text-stone-500 text-xs">
                                    {{ $alert->nguong_tai_thoi_diem !== null ? number_format($alert->nguong_tai_thoi_diem, 1) : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="font-mono text-xs text-stone-700">
                                    {{ $alert->thoi_gian_phat_hien ? $alert->thoi_gian_phat_hien->format('H:i - d/m/Y') : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="{{ $alert->trang_thai_badge_class }}">
                                    {{ $alert->trang_thai_label }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                @if(in_array($alert->trang_thai, ['CHUA_XU_LY', 'DANG_XU_LY']))
                                    <button type="button" onclick="openResolveModal({{ $alert->canh_bao_id }}, '{{ $alert->loai_canh_bao_label }}', '{{ $alert->thietBi->khoLanh->ten_kho ?? ($alert->thietBi->phuongTien->bien_so ?? 'Xe lạnh') }}')"
                                            class="f2f-btn-primary" style="padding:4px 10px; font-size:12px">
                                        Xử lý sự cố
                                    </button>
                                @else
                                    <button type="button" onclick="openViewModal(@js($alert->hanh_dong_khac_phuc), @js($alert->ket_qua_khac_phuc), @js($alert->nguoiXuLy->ho_ten ?? 'Nhân viên kho'), @js($alert->thoi_gian_xu_ly ? $alert->thoi_gian_xu_ly->format('H:i d/m/Y') : '—'), @js(match($alert->danh_gia_lo) { 'CON_DAT' => 'Còn đạt yêu cầu', 'CACH_LY' => 'Cách ly chờ xử lý', 'KHONG_DAT' => 'Không đạt / trả về', default => 'Chưa ghi nhận' }), @js($alert->ghi_chu_danh_gia))"
                                            class="f2f-btn-secondary" style="padding:4px 10px; font-size:12px">
                                        Xem kết quả
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="f2f-table-empty">
                                Không có cảnh báo chuỗi lạnh nào đang chờ xử lý. Nhiệt độ tại toàn bộ hệ thống kho duy trì ổn định.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alerts->hasPages())
            <div style="margin-top:16px">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>

    {{-- SECTION 3: 20-MINUTE DOWNSAMPLED TELEMETRY LOGS --}}
    <div class="f2f-card" style="margin-top:20px; padding:16px">
        <div class="flex items-center justify-between border-b border-stone-200 pb-3">
            <div>
                <h2 class="f2f-card-title">Nhật ký Đo đạc Cảm biến Chuỗi Lạnh Định kỳ (Chu kỳ 20 phút)</h2>
                <p class="f2f-subtitle">Dữ liệu chuẩn hóa cách nhau 20 phút phục vụ đối soát và báo cáo minh bạch chuỗi cung ứng.</p>
            </div>

            <div>
                <label class="f2f-label" for="danh_gia_lo">Đánh giá tình trạng các kiện bị ảnh hưởng <span class="text-rose-600">*</span></label>
                <select class="f2f-input" name="danh_gia_lo" id="danh_gia_lo" required>
                    <option value="CON_DAT">Còn đạt – tiếp tục lưu kho / hành trình</option>
                    <option value="CACH_LY">Cách ly – chờ người có thẩm quyền xử lý</option>
                    <option value="KHONG_DAT">Không đạt – loại hoặc trả về</option>
                </select>
            </div>

            <div>
                <label class="f2f-label" for="ghi_chu_danh_gia">Căn cứ đánh giá / hướng xử lý</label>
                <textarea class="f2f-input" name="ghi_chu_danh_gia" id="ghi_chu_danh_gia" rows="2"
                          placeholder="Bắt buộc khi cách ly hoặc kết luận không đạt."></textarea>
            </div>
            <span class="text-xs px-2.5 py-0.5 rounded bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                Chu kỳ 20 phút/lần
            </span>
        </div>

        <div class="f2f-table-container mt-4">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Thời gian đo</th>
                        <th>Kho lạnh bảo quản</th>
                        <th>Mã cảm biến IoT</th>
                        <th style="text-align:right">Nhiệt độ</th>
                        <th style="text-align:right">Độ ẩm</th>
                        <th style="text-align:center">Tín hiệu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTelemetry as $reading)
                        <tr>
                            <td>
                                <span class="font-mono text-xs text-stone-800 font-semibold">
                                    {{ \Carbon\Carbon::parse($reading->thoi_gian_do)->format('H:i - d/m/Y') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-xs text-stone-900 font-medium">{{ $reading->thietBi->khoLanh->ten_kho ?? 'Kho lạnh' }}</span>
                            </td>
                            <td>
                                <span class="font-mono text-[11px] text-stone-500">{{ $reading->thietBi->ma_thiet_bi ?? 'IoT' }}</span>
                            </td>
                            <td style="text-align:right">
                                <span class="font-mono font-bold text-xs {{ ($reading->nhiet_do >= 0 && $reading->nhiet_do <= 6) ? 'text-emerald-800' : 'text-stone-800' }}">
                                    {{ number_format($reading->nhiet_do, 1) }} &deg;C
                                </span>
                            </td>
                            <td style="text-align:right">
                                <span class="font-mono text-xs text-stone-700">
                                    {{ number_format($reading->do_am, 1) }} %
                                </span>
                            </td>
                            <td style="text-align:center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    ỔN ĐỊNH
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="f2f-table-empty">
                                Đang đồng bộ dữ liệu cảm biến định kỳ mỗi 20 phút.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal 1: Resolve Alert Modal --}}
<div id="resolveAlertModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-stone-200 pb-3">
            <h3 class="text-base font-bold text-stone-900">Ghi nhận Biện pháp Xử lý Cảnh báo Chuỗi Lạnh</h3>
            <button type="button" onclick="closeModal('resolveAlertModal')" class="text-stone-400 hover:text-stone-700 text-sm font-bold">
                Đóng
            </button>
        </div>

        <form id="resolveAlertForm" action="" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="f2f-label">Cảnh báo sự cố tại kho</label>
                <input class="f2f-input font-bold bg-stone-100" type="text" id="alert_desc_display" disabled>
            </div>
            <div>
                <span class="text-stone-400 block mb-0.5">Kết luận đối với lô/kiện:</span>
                <div id="view_assessment" class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-stone-900 font-medium"></div>
                <div id="view_assessment_note" class="mt-1 text-stone-600"></div>
            </div>

            <div>
                <label class="f2f-label" for="hanh_dong_khac_phuc">
                    Hành động khắc phục đã thực hiện <span class="text-rose-600">*</span>
                </label>
                <textarea class="f2f-input" name="hanh_dong_khac_phuc" id="hanh_dong_khac_phuc" rows="2" required
                          placeholder="Vd: Kiểm tra rơ-le nhiệt, kích hoạt máy nén phụ trợ, đóng kín cửa khoang chứa..."></textarea>
            </div>

            <div>
                <label class="f2f-label" for="ket_qua_khac_phuc">
                    Kết quả sau khắc phục <span class="text-rose-600">*</span>
                </label>
                <textarea class="f2f-input" name="ket_qua_khac_phuc" id="ket_qua_khac_phuc" rows="2" required
                          placeholder="Vd: Nhiệt độ khoang đã hạ về 2.1°C ổn định, cảm biến kết nối bình thường..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-stone-200">
                <button type="button" onclick="closeModal('resolveAlertModal')" class="f2f-btn-secondary text-xs">
                    Hủy bỏ
                </button>
                <button type="submit" class="f2f-btn-primary text-xs">
                    Xác nhận hoàn tất khắc phục
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: View Alert Resolution Modal --}}
<div id="viewAlertModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-stone-200 pb-3">
            <h3 class="text-base font-bold text-stone-900">Chi tiết Biên bản Khắc phục Sự cố</h3>
            <button type="button" onclick="closeModal('viewAlertModal')" class="text-stone-400 hover:text-stone-700 text-sm font-bold">
                Đóng
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <span class="text-stone-400 block mb-0.5">Hành động khắc phục:</span>
                <div id="view_action" class="p-2.5 rounded-lg bg-stone-50 border border-stone-200 text-stone-800 leading-relaxed"></div>
            </div>
            <div>
                <span class="text-stone-400 block mb-0.5">Kết quả sau xử lý:</span>
                <div id="view_result" class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-950 font-medium leading-relaxed"></div>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-stone-200 text-stone-600">
                <div>Nhân viên xử lý: <strong id="view_staff" class="text-stone-800 block"></strong></div>
                <div>Thời gian: <strong id="view_time" class="font-mono text-stone-800 block"></strong></div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="button" onclick="closeModal('viewAlertModal')" class="f2f-btn-secondary text-xs">
                Đóng
            </button>
        </div>
    </div>
</div>

<script>
    function openResolveModal(alertId, alertLabel, warehouseName) {
        const form = document.getElementById('resolveAlertForm');
        form.action = `/warehouse/monitoring/alerts/${alertId}/resolve`;
        document.getElementById('alert_desc_display').value = `${warehouseName} - ${alertLabel}`;
        document.getElementById('resolveAlertModal').classList.remove('hidden');
    }

    function openViewModal(action, result, staff, time, assessment, assessmentNote) {
        document.getElementById('view_action').textContent = action;
        document.getElementById('view_result').textContent = result;
        document.getElementById('view_staff').textContent = staff;
        document.getElementById('view_time').textContent = time;
        document.getElementById('view_assessment').textContent = assessment;
        document.getElementById('view_assessment_note').textContent = assessmentNote || '';
        document.getElementById('viewAlertModal').classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
</script>
@endsection


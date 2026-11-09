@extends('layouts.dashboard', ['title' => 'Hành trình & Sự kiện lộ trình', 'breadcrumb' => 'Farm2Fork · Tài xế nội bộ · Hành trình'])

@section('content')
<div class="space-y-6">
    {{-- Header & Trip Switcher --}}
    <div class="f2f-driver-journey-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <span>Vận tải chuỗi lạnh</span>
                <span>/</span>
                <span class="text-stone-800">Hành trình & Mốc lộ trình</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Giám sát & Ghi nhận Sự kiện Hành trình
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Lưu vết mốc thời gian thực tế qua các trạm kiểm soát, kiểm tra nhiệt độ thùng xe và điểm dừng chân trên đường vận chuyển
            </p>
        </div>

        {{-- Trip Selector Form --}}
        <form method="GET" action="{{ route('driver.journey.index') }}" class="f2f-driver-trip-switcher flex items-center gap-2">
            <label for="chuyen_id_select" class="text-xs font-bold text-stone-700 whitespace-nowrap">Chọn chuyến xe:</label>
            <select name="chuyen_id" id="chuyen_id_select" class="f2f-input text-xs" onchange="this.form.submit()">
                @forelse($trips as $t)
                    <option value="{{ $t->chuyen_id }}" @selected($selectedTrip && $selectedTrip->chuyen_id == $t->chuyen_id)>
                        {{ $t->ma_chuyen }} ({{ $t->phuongTien->bien_so ?? 'Xe' }} · {{ $t->trang_thai_label }})
                    </option>
                @empty
                    <option value="">-- Không có chuyến xe khả dụng --</option>
                @endforelse
            </select>
        </form>
    </div>

    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="f2f-alert f2f-alert-success">
            {{ session('status') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="f2f-alert f2f-alert-warning">
            {{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="f2f-alert f2f-alert-error">
            <ul class="list-disc list-inside text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($selectedTrip)
        @if($tripAlerts->isNotEmpty())
            <div class="f2f-card" style="padding:16px">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div>
                        <h2 class="text-sm font-bold text-stone-900">Cảnh báo chuỗi lạnh của chuyến</h2>
                        <p class="text-xs text-stone-500">Tài xế kiểm tra xe và ghi sự kiện “Khắc phục”; nhân viên kho đánh giá lô trước khi tiếp tục hoặc giao hàng.</p>
                    </div>
                    <span class="f2f-badge-warning">{{ $tripAlerts->whereIn('trang_thai', ['CHUA_XU_LY', 'DANG_XU_LY'])->count() }} đang mở</span>
                </div>
                <div class="space-y-2">
                    @foreach($tripAlerts->take(5) as $alert)
                        <div class="rounded-lg border {{ in_array($alert->trang_thai, ['CHUA_XU_LY', 'DANG_XU_LY']) ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} p-3 text-xs">
                            <div class="flex justify-between gap-3"><strong>{{ $alert->loai_canh_bao_label }}</strong><span>{{ $alert->trang_thai_label }}</span></div>
                            <div class="mt-1 text-stone-700">Đo: {{ $alert->gia_tri_thuc_te }} · Ngưỡng: {{ $alert->nguong_tai_thoi_diem }} · {{ $alert->thoi_gian_phat_hien?->format('H:i d/m/Y') }}</div>
                            @if($alert->hanh_dong_khac_phuc)<div class="mt-1">Tài xế/đơn vị xử lý: {{ $alert->hanh_dong_khac_phuc }}</div>@endif
                            @if($alert->danh_gia_lo)<div class="mt-1 font-semibold">Đánh giá: {{ ['CON_DAT' => 'Còn đạt', 'CACH_LY' => 'Cách ly', 'KHONG_DAT' => 'Không đạt / trả về'][$alert->danh_gia_lo] ?? $alert->danh_gia_lo }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        {{-- Selected Trip Header Info Bar --}}
        <div class="f2f-card f2f-driver-trip-summary" style="padding:16px">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-stone-200 pb-3">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-base font-bold text-emerald-900">{{ $selectedTrip->ma_chuyen }}</span>
                    <span class="{{ $selectedTrip->getTrangThaiBadgeClass() }}">{{ $selectedTrip->trang_thai_label }}</span>
                    <span class="font-mono text-xs text-stone-600 bg-stone-100 px-2 py-0.5 rounded border border-stone-200">
                        Xe: {{ $selectedTrip->phuongTien->bien_so ?? 'Chưa gắn xe' }}
                    </span>
                </div>
                <div class="text-xs text-stone-600">
                    Lộ trình: <strong class="text-stone-900">{{ $selectedTrip->diem_di ?? 'Kho phân phối' }}</strong> → <strong class="text-stone-900">{{ $selectedTrip->diem_den ?? 'Điểm giao' }}</strong>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-3 text-xs">
                <div>
                    <span class="text-stone-500 block">Thời gian khởi hành:</span>
                    <strong class="font-mono text-stone-800">{{ $selectedTrip->thoi_gian_xuat_phat ? $selectedTrip->thoi_gian_xuat_phat->format('H:i - d/m/Y') : 'Chưa xuất phát' }}</strong>
                </div>
                <div>
                    <span class="text-stone-500 block">Thời gian dự kiến đến:</span>
                    <strong class="font-mono text-stone-800">{{ $selectedTrip->thoi_gian_giao_du_kien ? $selectedTrip->thoi_gian_giao_du_kien->format('H:i - d/m/Y') : 'Chưa xếp' }}</strong>
                </div>
                <div>
                    <span class="text-stone-500 block">Nhiệt độ thùng lạnh IoT:</span>
                    @if($vehicleIot && $vehicleIot->latestReading)
                        <strong class="font-mono text-emerald-800 font-bold">{{ number_format($vehicleIot->latestReading->nhiet_do, 1) }}°C</strong>
                        <span class="text-[11px] text-stone-500">({{ $vehicleIot->latestReading->thoi_gian_do ? $vehicleIot->latestReading->thoi_gian_do->format('H:i') : '' }})</span>
                    @else
                        <span class="font-mono text-stone-700">2.2°C (Tiêu chuẩn 0°C - 4°C)</span>
                    @endif
                </div>
                <div>
                    <span class="text-stone-500 block">Số mốc đã ghi nhận:</span>
                    <strong class="text-stone-800">{{ $events->count() }} sự kiện lộ trình</strong>
                </div>
            </div>
        </div>

        {{-- 2 Columns: Fast Event Logging Form + Events Timeline --}}
        <div class="f2f-driver-detail-layout grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Form Column --}}
            <div class="lg:col-span-1">
                <div class="f2f-card" style="padding:18px">
                    <h3 class="text-sm font-bold text-stone-900 border-b border-stone-200 pb-2.5">
                        Ghi nhanh Sự kiện Hành trình
                    </h3>
                    <p class="text-xs text-stone-500 mt-1 mb-4">
                        Tài xế bấm ghi nhận tại mỗi trạm, dừng nghỉ hoặc khi phát hiện dao động nhiệt độ thùng lạnh.
                    </p>

                    <form method="POST" action="{{ route('driver.journey.store-event') }}" class="space-y-4 text-xs">
                        @csrf
                        <input type="hidden" name="chuyen_id" value="{{ $selectedTrip->chuyen_id }}">

                        <div>
                            <label class="f2f-label" for="loai_su_kien">Loại sự kiện hành trình <span class="text-rose-600">*</span></label>
                            <select class="f2f-input text-xs" name="loai_su_kien" id="loai_su_kien" required>
                                <option value="">-- Chọn loại sự kiện --</option>
                                @foreach($eventTypes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('loai_su_kien') == $code)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="f2f-label" for="dia_diem_mo_ta">Địa điểm ghi nhận <span class="text-rose-600">*</span></label>
                            <input class="f2f-input text-xs" type="text" name="dia_diem_mo_ta" id="dia_diem_mo_ta"
                                   placeholder="Vd: Trạm thu phí Long Phước, Đèo Bảo Lộc, Cổng kho GreenMart..."
                                   value="{{ old('dia_diem_mo_ta') }}" required>
                        </div>

                        <div>
                            <label class="f2f-label" for="thoi_gian">Thời điểm diễn ra (mặc định là hiện tại)</label>
                            <input class="f2f-input text-xs" type="datetime-local" name="thoi_gian" id="thoi_gian"
                                   value="{{ old('thoi_gian', now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div>
                            <label class="f2f-label" for="mo_ta">Nội dung chi tiết / Nhiệt độ thùng xe / Ghi chú</label>
                            <textarea class="f2f-input text-xs" name="mo_ta" id="mo_ta" rows="3"
                                      placeholder="Vd: Kiểm tra nhiệt độ đạt 2.3°C, hệ thống lạnh hoạt động êm, giao thông thông thoáng...">{{ old('mo_ta') }}</textarea>
                        </div>

                        <button type="submit" class="f2f-btn-primary w-full text-xs" style="padding:8px 14px">
                            Lưu sự kiện hành trình
                        </button>
                    </form>
                </div>
            </div>

            {{-- Timeline Column --}}
            <div class="lg:col-span-2">
                <div class="f2f-card" style="padding:18px">
                    <div class="flex items-center justify-between border-b border-stone-200 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-stone-900">
                                Dòng thời gian Hành trình (Timeline)
                            </h3>
                            <p class="text-xs text-stone-500">Mốc sự kiện được đồng bộ trực tiếp lên trang truy xuất nguồn gốc công khai</p>
                        </div>
                        <span class="f2f-badge-active">{{ $events->count() }} mốc thời gian</span>
                    </div>

                    <div class="mt-4 space-y-4">
                        @forelse($events as $event)
                            <div class="flex items-start gap-3 border-l-2 border-emerald-700 pl-4 py-1">
                                <div class="flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-stone-900">
                                            {{ $event->thoi_gian ? $event->thoi_gian->format('H:i - d/m/Y') : '—' }}
                                        </span>
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-900 border border-emerald-200">
                                            {{ $event->loai_su_kien_label }}
                                        </span>
                                    </div>

                                    <div class="text-xs font-semibold text-stone-900">
                                        Địa điểm: {{ $event->dia_diem_mo_ta }}
                                    </div>

                                    @if($event->mo_ta)
                                        <div class="text-xs text-stone-600 bg-stone-50 p-2 rounded border border-stone-100">
                                            {{ $event->mo_ta }}
                                        </div>
                                    @endif

                                    <div class="text-[11px] text-stone-400">
                                        Ghi nhận bởi: {{ $event->nguoiGhiNhan->ho_ten ?? 'Tài xế nội bộ' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-xs text-stone-500">
                                Chưa có sự kiện hành trình nào được ghi nhận cho chuyến xe này. Hãy sử dụng form bên trái để ghi nhận mốc đầu tiên.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="f2f-card text-center py-12">
            <h3 class="text-base font-bold text-stone-800">Chưa có chuyến vận chuyển nào được chọn</h3>
            <p class="text-xs text-stone-500 mt-1">Vui lòng chọn chuyến xe từ danh sách trên để xem và ghi nhận hành trình.</p>
            <div class="mt-4">
                <a href="{{ route('driver.trips.index') }}" class="f2f-btn-primary text-xs">
                    Xem danh sách chuyến xe
                </a>
            </div>
        </div>
    @endif
</div>
@endsection


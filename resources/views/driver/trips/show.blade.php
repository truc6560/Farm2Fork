@extends('layouts.dashboard', ['title' => 'Chuyến xe ' . $trip->ma_chuyen, 'breadcrumb' => 'Farm2Fork · Tài xế nội bộ · Chi tiết chuyến'])

@section('content')
<div class="space-y-6">
    {{-- Header & Quick Controls --}}
    <div class="f2f-driver-detail-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('driver.trips.index') }}" class="hover:underline">Chuyến vận chuyển</a>
                <span>/</span>
                <span class="text-stone-800">{{ $trip->ma_chuyen }}</span>
            </div>
            <div class="mt-1 flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-stone-900 font-mono">
                    {{ $trip->ma_chuyen }}
                </h1>
                <span class="{{ $trip->getTrangThaiBadgeClass() }}">
                    {{ $trip->trang_thai_label }}
                </span>
            </div>
            <p class="mt-0.5 text-xs text-stone-600">
                Hành trình từ: <strong class="text-stone-800">{{ $trip->diem_di ?? 'Kho phân phối' }}</strong> đến <strong class="text-stone-800">{{ $trip->diem_den ?? 'Điểm giao' }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('driver.trips.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại danh sách
            </a>

            <a href="{{ route('driver.journey.index', ['chuyen_id' => $trip->chuyen_id]) }}" class="f2f-btn-secondary text-xs">
                Ghi nhận mốc hành trình
            </a>

            @if(in_array($trip->trang_thai, ['SAN_SANG', 'CHUAN_BI']))
                <form method="POST" action="{{ route('driver.trips.start', $trip->chuyen_id) }}" onsubmit="return confirm('Bắt đầu xuất phát chuyến xe này?')">
                    @csrf
                    <button type="submit" class="f2f-btn-primary text-xs">
                        Bắt đầu xuất phát
                    </button>
                </form>
            @elseif($trip->trang_thai === 'DANG_VAN_CHUYEN')
                <form method="POST" action="{{ route('driver.trips.complete', $trip->chuyen_id) }}" onsubmit="return confirm('Xác nhận hoàn tất toàn bộ chuyến xe và bàn giao?')">
                    @csrf
                    <button type="submit" class="f2f-btn-primary text-xs" style="background-color:#047857">
                        Xác nhận hoàn thành chuyến
                    </button>
                </form>
            @endif
        </div>
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

    {{-- Metrics Summary Grid --}}
    <div class="f2f-driver-detail-stats grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="f2f-card" style="padding:14px">
            <div class="text-[11px] font-semibold text-stone-500 uppercase">Phương tiện xe lạnh</div>
            <div class="mt-1 text-base font-bold text-stone-900 font-mono">
                {{ $trip->phuongTien->bien_so ?? 'Chưa gắn' }}
            </div>
            <div class="text-[11px] text-stone-500">{{ $trip->phuongTien->loai_xe ?? '—' }}</div>
        </div>

        <div class="f2f-card" style="padding:14px">
            <div class="text-[11px] font-semibold text-stone-500 uppercase">Nhiệt độ thùng xe (IoT)</div>
            @if($vehicleIot && $vehicleIot->latestReading)
                <div class="mt-1 text-base font-bold text-emerald-800 font-mono">
                    {{ number_format($vehicleIot->latestReading->nhiet_do, 1) }}°C
                </div>
                <div class="text-[11px] text-stone-500">Độ ẩm: {{ number_format($vehicleIot->latestReading->do_am, 0) }}% (Cập nhật {{ $vehicleIot->latestReading->thoi_gian_do ? $vehicleIot->latestReading->thoi_gian_do->format('H:i') : 'vừa xong' }})</div>
            @else
                <div class="mt-1 text-base font-bold text-stone-700 font-mono">2.2°C</div>
                <div class="text-[11px] text-stone-500">Dải bảo quản tiêu chuẩn 0°C - 4°C</div>
            @endif
        </div>

        <div class="f2f-card" style="padding:14px">
            <div class="text-[11px] font-semibold text-stone-500 uppercase">Đơn hàng cần giao</div>
            <div class="mt-1 text-base font-bold text-stone-900">
                {{ $completedDeliveries }} / {{ $totalDeliveries }} <span class="text-xs font-normal text-stone-600">điểm giao</span>
            </div>
            <div class="text-[11px] text-stone-500">Đã giao {{ $completedItems }}/{{ $totalItems }} kiện SSCC</div>
        </div>

        <div class="f2f-card" style="padding:14px">
            <div class="text-[11px] font-semibold text-stone-500 uppercase">Thời gian xuất phát</div>
            <div class="mt-1 text-sm font-bold text-stone-900 font-mono">
                {{ $trip->thoi_gian_xuat_phat ? $trip->thoi_gian_xuat_phat->format('H:i - d/m/Y') : 'Chưa lăn bánh' }}
            </div>
            <div class="text-[11px] text-stone-500">
                @if($trip->thoi_gian_giao_thuc_te)
                    Đã hoàn thành lúc {{ $trip->thoi_gian_giao_thuc_te->format('H:i d/m') }}
                @else
                    Dự kiến đến: {{ $trip->thoi_gian_giao_du_kien ? $trip->thoi_gian_giao_du_kien->format('H:i d/m') : 'Đang cập nhật' }}
                @endif
            </div>
        </div>
    </div>

    {{-- Main 2 Columns Layout --}}
    <div class="f2f-driver-detail-layout grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left 2 cols: Delivery Orders & Packaging Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Delivery Orders Section --}}
            <div class="f2f-card" style="padding:18px">
                <div class="flex items-center justify-between border-b border-stone-200 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-stone-900">
                            Danh sách Phiếu Giao hàng & Điểm trả hàng
                        </h3>
                        <p class="text-xs text-stone-500">Các điểm nhận hàng và số lượng kiện hàng GS1 SSCC cần bàn giao trên chuyến xe này</p>
                    </div>
                    <span class="f2f-badge-active">{{ $totalDeliveries }} phiếu</span>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse($trip->phieuGiaoHang as $pgh)
                        <div class="border border-stone-200 rounded-lg p-4 bg-stone-50/50 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-200 pb-2.5">
                                <div>
                                    <span class="font-mono font-bold text-xs text-emerald-900">{{ $pgh->ma_phieu_giao_hang }}</span>
                                    <span class="mx-1.5 text-stone-300">·</span>
                                    <strong class="text-xs text-stone-900">{{ $pgh->toChucNhan->ten_to_chuc ?? $pgh->ten_nguoi_nhan }}</strong>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="{{ $pgh->getTrangThaiBadgeClass() }}">
                                        {{ $pgh->trang_thai_label }}
                                    </span>
                                    <a href="{{ route('driver.delivery.show', $pgh->phieu_giao_hang_id) }}" class="f2f-btn-primary" style="padding:3px 8px;font-size:11px">
                                        Ký nhận & Bàn giao
                                    </a>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-stone-500">Người nhận:</span>
                                    <strong class="text-stone-800">{{ $pgh->ten_nguoi_nhan }}</strong>
                                    @if($pgh->sdt_nguoi_nhan)
                                        <span class="font-mono text-stone-600">({{ $pgh->sdt_nguoi_nhan }})</span>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-stone-500">Yêu cầu nhiệt độ:</span>
                                    <span class="font-mono font-semibold text-emerald-800">
                                        {{ $pgh->yeu_cau_nhiet_do_min !== null ? $pgh->yeu_cau_nhiet_do_min . '°C' : '0°C' }}
                                        -
                                        {{ $pgh->yeu_cau_nhiet_do_max !== null ? $pgh->yeu_cau_nhiet_do_max . '°C' : '4°C' }}
                                    </span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-stone-500">Địa chỉ giao:</span>
                                    <span class="text-stone-800 font-medium">{{ $pgh->dia_chi_giao }}</span>
                                </div>
                            </div>

                            {{-- SSCC Line Items inside this delivery note --}}
                            <div class="pt-2 border-t border-stone-200">
                                <div class="text-[11px] font-semibold text-stone-600 uppercase mb-1.5">
                                    Kiện hàng GS1 SSCC bàn giao:
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($pgh->chiTietGiaoNhan as $item)
                                        <div class="bg-white border border-stone-200 rounded p-2 text-xs">
                                            <div class="flex items-center justify-between">
                                                <span class="font-mono font-bold text-stone-900">{{ $item->donViDongGoi->ma_sscc ?? 'SSCC #' . $item->sscc_id }}</span>
                                                <span class="text-[10px] font-semibold {{ $item->trang_thai_giao_nhan === 'DA_GIAO' ? 'text-emerald-700' : 'text-amber-700' }}">
                                                    {{ $item->trang_thai_label }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-stone-600 mt-1">
                                                @if($item->donViDongGoi && $item->donViDongGoi->chiTiet)
                                                    @foreach($item->donViDongGoi->chiTiet as $detail)
                                                        <div>{{ $detail->loNongSan->sanPham->ten_san_pham ?? 'Nông sản' }} - Lô: <strong class="font-mono">{{ $detail->loNongSan->ma_lo ?? '—' }}</strong> ({{ number_format($detail->so_luong, 1) }} {{ $detail->don_vi_tinh }})</div>
                                                    @endforeach
                                                @else
                                                    <span>Kiện hàng nông sản chuỗi lạnh</span>
                                                @endif
                                            </div>
                                            @if($item->tinh_trang_bao_goi)
                                                <div class="text-[10px] text-stone-500 mt-1">
                                                    Tình trạng: {{ $item->tinh_trang_bao_goi_label }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-stone-500">
                            Chưa có phiếu giao hàng nào được gán vào chuyến xe này.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Journey Events Timeline Section --}}
            <div class="f2f-card" style="padding:18px">
                <div class="flex items-center justify-between border-b border-stone-200 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-stone-900">
                            Nhật ký Mốc Lộ trình & Sự kiện Hành trình
                        </h3>
                        <p class="text-xs text-stone-500">Lịch sử các trạm kiểm soát, kiểm tra nhiệt độ, dừng nghỉ và bàn giao</p>
                    </div>
                    <a href="{{ route('driver.journey.index', ['chuyen_id' => $trip->chuyen_id]) }}" class="f2f-btn-primary text-xs" style="padding:4px 10px">
                        Ghi thêm sự kiện
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($trip->suKienHanhTrinh as $sk)
                        <div class="flex items-start gap-3 border-l-2 border-emerald-700 pl-3.5 py-1">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-stone-900 font-mono">
                                        {{ $sk->thoi_gian ? $sk->thoi_gian->format('H:i - d/m/Y') : '—' }}
                                    </span>
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-stone-100 text-stone-800 border border-stone-200">
                                        {{ $sk->loai_su_kien_label }}
                                    </span>
                                </div>
                                <div class="text-xs font-semibold text-emerald-900 mt-0.5">
                                    Địa điểm: {{ $sk->dia_diem_mo_ta ?? 'Dọc đường vận chuyển' }}
                                </div>
                                @if($sk->mo_ta)
                                    <div class="text-xs text-stone-600 mt-0.5">
                                        {{ $sk->mo_ta }}
                                    </div>
                                @endif
                                <div class="text-[11px] text-stone-400 mt-0.5">
                                    Ghi nhận bởi: {{ $sk->nguoiGhiNhan->ho_ten ?? 'Tài xế' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-stone-500">
                            Chưa có sự kiện hành trình nào được ghi nhận cho chuyến xe này.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Col: Vehicle Specs & Driver Info --}}
        <div class="space-y-6">
            {{-- Vehicle Card --}}
            <div class="f2f-card" style="padding:18px">
                <h3 class="text-sm font-bold text-stone-900 border-b border-stone-200 pb-2">
                    Thông số Phương tiện Vận chuyển
                </h3>
                <div class="mt-3 space-y-2.5 text-xs">
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Biển số kiểm soát:</span>
                        <strong class="font-mono text-stone-900">{{ $trip->phuongTien->bien_so ?? 'Chưa gắn' }}</strong>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Phân loại phương tiện:</span>
                        <span class="text-stone-800 font-medium">{{ $trip->phuongTien->loai_xe ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Tải trọng thiết kế:</span>
                        <span class="font-mono font-semibold text-stone-800">
                            {{ $trip->phuongTien->tai_trong ? number_format($trip->phuongTien->tai_trong, 0) . ' ' . $trip->phuongTien->don_vi_tai_trong : '—' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Hệ thống làm lạnh:</span>
                        <span class="font-semibold {{ $trip->phuongTien?->co_he_thong_lam_lanh ? 'text-emerald-700' : 'text-stone-600' }}">
                            {{ $trip->phuongTien?->co_he_thong_lam_lanh ? 'Có thùng lạnh đạt chuẩn' : 'Xe tải thường' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-stone-500">Trạng thái xe:</span>
                        <span class="text-stone-800">{{ $trip->phuongTien->trang_thai_label ?? '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Driver & Contact Card --}}
            <div class="f2f-card" style="padding:18px">
                <h3 class="text-sm font-bold text-stone-900 border-b border-stone-200 pb-2">
                    Tài xế & Điều phối
                </h3>
                <div class="mt-3 space-y-2.5 text-xs">
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Tài xế phụ trách:</span>
                        <strong class="text-stone-900">{{ $trip->taiXe->ho_ten ?? 'Tài xế nội bộ' }}</strong>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Số điện thoại:</span>
                        <span class="font-mono text-stone-800">{{ $trip->taiXe->so_dien_thoai ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Email liên lạc:</span>
                        <span class="text-stone-700">{{ $trip->taiXe->email ?? '—' }}</span>
                    </div>
                    @if($trip->ghi_chu)
                        <div class="pt-1">
                            <span class="text-stone-500 block mb-0.5">Ghi chú điều phối:</span>
                            <div class="p-2 bg-stone-50 rounded text-stone-700 text-[11px] leading-relaxed border border-stone-200">
                                {{ $trip->ghi_chu }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


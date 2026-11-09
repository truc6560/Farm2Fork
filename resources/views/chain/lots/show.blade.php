@extends('layouts.dashboard', ['title' => 'Chi tiết Lô Nông sản ' . $lot->ma_lo, 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi'])

@section('content')
@foreach($traceGaps as $gap)<div class="f2f-alert f2f-alert-warning">{{ $gap }}</div>@endforeach
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('chain.lots.index') }}" class="hover:underline">Lô nông sản</a>
                <span>/</span>
                <span class="text-stone-800">Hồ sơ chi tiết</span>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 mt-1">
                <h1 class="text-2xl font-bold tracking-tight text-stone-900 font-mono">
                    {{ $lot->ma_lo }}
                </h1>
                <span class="{{ $lot->getTrangThaiBadgeClass() }} text-xs">
                    {{ $statusLabels[$lot->trang_thai] ?? $lot->trang_thai }}
                </span>
                <span class="f2f-badge-neutral text-xs">
                    {{ $lot->loai_lo === 'NGUYEN_LIEU' ? 'Lô nguyên liệu' : 'Lô thành phẩm' }}
                </span>
            </div>
            <p class="mt-0.5 text-xs text-stone-600">
                Sản phẩm: <strong class="text-stone-800">{{ $lot->sanPham?->ten_san_pham ?? 'Chưa phân loại' }}</strong>
                · Nguồn: <strong class="text-stone-800">{{ $lot->toChucNguon?->ten_to_chuc ?? 'N/A' }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ $lot->digital_link_url }}" target="_blank" class="f2f-btn-primary text-xs">
                Mở trang truy xuất công khai
            </a>
            <a href="{{ route('chain.lots.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại danh sách
            </a>
        </div>
    </div>

    {{-- Top Overview Grid: GS1 Digital Link & Continuity Check --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- GS1 QR Code & Digital Link Info --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-stone-700">
                Mã QR & Chuẩn GS1 Digital Link
            </h2>

            <div class="flex flex-col items-center justify-center p-3 bg-stone-50 rounded-lg border border-stone-200">
                <div class="bg-white p-2.5 rounded border border-stone-200 shadow-xs">
                    {!! $lot->qr_svg !!}
                </div>
                <div class="text-[11px] text-stone-500 mt-2 text-center">
                    Quét bằng camera điện thoại để truy xuất thông tin nguồn gốc
                </div>
            </div>

            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between py-1 border-b border-stone-100">
                    <span class="text-stone-500">Mã GTIN:</span>
                    <span class="font-mono font-bold text-stone-800">{{ $lot->sanPham?->gtin ?? 'Chưa cấp' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-stone-100">
                    <span class="text-stone-500">Khối lượng:</span>
                    <span class="font-bold text-stone-800">{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-stone-100">
                    <span class="text-stone-500">Ngày thu hoạch:</span>
                    <span class="font-medium text-stone-800">{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-stone-100">
                    <span class="text-stone-500">Hạn sử dụng:</span>
                    <span class="font-medium text-stone-800">{{ $lot->han_su_dung ? $lot->han_su_dung->format('d/m/Y') : 'N/A' }}</span>
                </div>
            </div>
        </div>

        {{-- Traceability Continuity & Integrity Verification (2 cols) --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-stone-700">
                        Đánh Giá Tính Toàn Vẹn Chuỗi Dữ Liệu Truy Xuất (Continuity Audit)
                    </h2>
                    <p class="text-xs text-stone-500 mt-0.5">Kiểm tra tính liên tục của mắt xích thông tin giữa các công đoạn</p>
                </div>
                <div>
                    @if($isCompleteTrace)
                        <span class="f2f-badge-success text-xs">Đủ hồ sơ theo giai đoạn hiện tại</span>
                    @else
                        <span class="f2f-badge-warning text-xs">Thiếu dữ liệu mắt xích</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div class="p-3 rounded-lg border {{ $checks['has_production'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-amber-200 bg-amber-50/40' }} text-xs">
                    <div class="font-bold {{ $checks['has_production'] ? 'text-emerald-900' : 'text-amber-900' }}">
                        1. Mùa vụ & Vùng trồng
                    </div>
                    <div class="mt-1 text-stone-600">
                        {{ $checks['has_production'] ? 'Đã liên kết mùa vụ canh tác và tọa độ vùng trồng' : 'Chưa có thông tin mùa vụ gốc' }}
                    </div>
                </div>

                <div class="p-3 rounded-lg border {{ $checks['has_farming_logs'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-stone-200 bg-stone-50/40' }} text-xs">
                    <div class="font-bold {{ $checks['has_farming_logs'] ? 'text-emerald-900' : 'text-stone-700' }}">
                        2. Nhật ký canh tác & Minh chứng
                    </div>
                    <div class="mt-1 text-stone-600">
                        {{ $checks['has_farming_logs'] ? 'Có ghi nhận nhật ký bón phân, tưới nước, phun thuốc' : 'Chưa có nhật ký hoạt động vườn' }}
                    </div>
                </div>

                <div class="p-3 rounded-lg border {{ $checks['has_material_trace'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }} text-xs">
                    <div class="font-bold {{ $checks['has_material_trace'] ? 'text-emerald-900' : 'text-rose-900' }}">
                        3. Sơ chế & Nguyên liệu cấu thành
                    </div>
                    <div class="mt-1 text-stone-600">
                        @if($lot->loai_lo === 'THANH_PHAM')
                            {{ $checks['has_material_trace'] ? 'Đã liên kết các lô nguyên liệu đầu vào cấu thành' : 'CẢNH BÁO: Thành phẩm chưa liên kết lô nguyên liệu' }}
                        @else
                            Lô nguyên liệu thu hoạch trực tiếp
                        @endif
                    </div>
                </div>

                <div class="p-3 rounded-lg border {{ $checks['qc_passed'] ? 'border-emerald-200 bg-emerald-50/40' : ($checks['has_qc'] ? 'border-rose-200 bg-rose-50/40' : 'border-amber-200 bg-amber-50/40') }} text-xs">
                    <div class="font-bold {{ $checks['qc_passed'] ? 'text-emerald-900' : 'text-stone-900' }}">
                        4. Kiểm định chất lượng (QC)
                    </div>
                    <div class="mt-1 text-stone-600">
                        @if($checks['qc_passed'])
                            Đã kiểm định và ĐẠT tiêu chuẩn an toàn
                        @elseif($checks['has_qc'])
                            Không đạt tiêu chuẩn kiểm định - Cần khắc phục
                        @else
                            Chưa có phiếu kiểm định chất lượng
                        @endif
                    </div>
                </div>

                <div class="p-3 rounded-lg border {{ $checks['has_storage'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-stone-200 bg-stone-50/40' }} text-xs">
                    <div class="font-bold {{ $checks['has_storage'] ? 'text-emerald-900' : 'text-stone-700' }}">
                        5. Lưu kho bảo quản lạnh
                    </div>
                    <div class="mt-1 text-stone-600">
                        {{ $checks['has_storage'] ? 'Được bảo quản tại ' . $lot->khoLanh?->ten_kho : 'Chưa nhập kho lạnh chính thức' }}
                    </div>
                </div>

                <div class="p-3 rounded-lg border {{ $checks['has_packaging'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-stone-200 bg-stone-50/40' }} text-xs">
                    <div class="font-bold {{ $checks['has_packaging'] ? 'text-emerald-900' : 'text-stone-700' }}">
                        6. Đóng gói SSCC & Vận chuyển
                    </div>
                    <div class="mt-1 text-stone-600">
                        {{ $checks['has_packaging'] ? 'Đã đóng gói ' . $ssccUnits->count() . ' kiện SSCC chuẩn GS1' : 'Chưa tạo kiện đóng gói SSCC' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DETAILED 5-STAGE TIMELINE --}}
    <div class="space-y-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-stone-800 border-b border-stone-200 pb-2">
            Hành Trình Xuyên Suốt Chuỗi Cung Ứng
        </h2>

        {{-- GIAI ĐOẠN 1: SẢN XUẤT & CANH TÁC --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">1</span>
                    <h3 class="text-sm font-bold text-stone-900">Giai Đoạn 1: Sản Xuất & Canh Tác Tại Vườn</h3>
                </div>
                <span class="text-xs text-stone-500">Đơn vị: {{ $lot->toChucNguon?->ten_to_chuc ?? 'N/A' }}</span>
            </div>

            @if($lot->muaVu)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-stone-50 p-3.5 rounded-lg border border-stone-200">
                    <div>
                        <span class="text-stone-500 block">Mùa vụ canh tác:</span>
                        <strong class="text-stone-800">{{ $lot->muaVu->ten_mua_vu }} ({{ $lot->muaVu->ma_mua_vu }})</strong>
                    </div>
                    <div>
                        <span class="text-stone-500 block">Vùng trồng:</span>
                        <strong class="text-stone-800">{{ $lot->muaVu->vungTrong?->ten_vung_trong ?? 'N/A' }}</strong>
                        <div class="text-[11px] text-stone-500">{{ $lot->muaVu->vungTrong?->dia_chi ?? '' }}</div>
                    </div>
                    <div>
                        <span class="text-stone-500 block">Thời gian canh tác:</span>
                        <span class="text-stone-800">
                            {{ $lot->muaVu->ngay_gieo ? $lot->muaVu->ngay_gieo->format('d/m/Y') : '' }}
                            → {{ $lot->muaVu->ngay_thu_hoach_du_kien ? $lot->muaVu->ngay_thu_hoach_du_kien->format('d/m/Y') : '' }}
                        </span>
                    </div>
                </div>

                {{-- Farming logs summary --}}
                @if($lot->muaVu->nhatKyCanhTac->isNotEmpty())
                    <div class="space-y-2">
                        <span class="text-xs font-semibold text-stone-700 block">Nhật ký canh tác gần nhất:</span>
                        <div class="space-y-2">
                            @foreach($lot->muaVu->nhatKyCanhTac->take(4) as $log)
                                <div class="p-2.5 rounded border border-stone-100 bg-stone-50/50 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <span class="font-bold text-stone-800">{{ $log->loai_hoat_dong }}</span>
                                        <span class="text-stone-500"> lúc {{ $log->ngay_thuc_hien ? $log->ngay_thuc_hien->format('d/m/Y H:i') : '' }}</span>
                                        @if($log->ghi_chu)
                                            <span class="text-stone-600">· {{ $log->ghi_chu }}</span>
                                        @endif
                                    </div>
                                    @if($log->tep_minh_chung)
                                        <a href="{{ asset('storage/' . $log->tep_minh_chung) }}" target="_blank" class="text-emerald-700 font-semibold text-[11px] hover:underline">
                                            Xem ảnh minh chứng
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-800">
                    Lô này chưa được gắn với mùa vụ canh tác nào trong cơ sở dữ liệu.
                </div>
            @endif
        </div>

        {{-- GIAI ĐOẠN 2: SƠ CHẾ & PHÂN LOẠI --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center font-bold text-xs">2</span>
                    <h3 class="text-sm font-bold text-stone-900">Giai Đoạn 2: Tiếp Nhận Sơ Chế & Tạo Lô Thành Phẩm</h3>
                </div>
                <span class="text-xs text-stone-500">{{ $lot->loai_lo === 'THANH_PHAM' ? 'Chế biến thành phẩm' : 'Nguyên liệu đầu vào' }}</span>
            </div>

            @if($lot->loai_lo === 'THANH_PHAM')
                @if($lot->nguyenLieuDauVao->isNotEmpty())
                    <div class="space-y-2">
                        <span class="text-xs font-semibold text-stone-700 block">Các lô nguyên liệu đầu vào cấu thành:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($lot->nguyenLieuDauVao as $raw)
                                <div class="p-3 rounded-lg border border-stone-200 bg-stone-50 text-xs flex justify-between items-center">
                                    <div>
                                        <div class="font-bold text-stone-800 font-mono">{{ $raw->ma_lo }}</div>
                                        <div class="text-stone-500">{{ $raw->sanPham?->ten_san_pham ?? '' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-stone-800">{{ number_format($raw->pivot->khoi_luong_su_dung ?? 0, 1) }} {{ $raw->pivot->don_vi_tinh ?? 'kg' }}</span>
                                        <a href="{{ route('chain.lots.show', $raw->lo_nong_san_id) }}" class="block text-emerald-700 hover:underline text-[11px] font-semibold mt-0.5">
                                            Xem lô nguyên liệu
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-3 bg-rose-50 rounded-lg border border-rose-200 text-xs text-rose-800">
                        CẢNH BÁO: Lô thành phẩm này chưa ghi nhận nguyên liệu đầu vào trong bảng thanh_phan_lo_nong_san!
                    </div>
                @endif
            @else
                <div class="text-xs text-stone-600 bg-stone-50 p-3 rounded-lg border border-stone-200">
                    Lô nông sản nguyên liệu được tạo sau thu hoạch ngày <strong>{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : 'N/A' }}</strong>.
                    @if($lot->thanhPhamDauRa->isNotEmpty())
                        <div class="mt-2 font-semibold text-stone-800">Đã được sơ chế để tạo các lô thành phẩm sau:</div>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            @foreach($lot->thanhPhamDauRa as $tp)
                                <li>
                                    <a href="{{ route('chain.lots.show', $tp->lo_nong_san_id) }}" class="text-emerald-700 hover:underline font-mono font-bold">
                                        {{ $tp->ma_lo }}
                                    </a>
                                    ({{ $tp->sanPham?->ten_san_pham }}) - {{ number_format($tp->pivot->khoi_luong_su_dung ?? 0, 1) }} kg
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mt-1 text-stone-500">Chưa chuyển sang công đoạn chế biến thành phẩm.</div>
                    @endif
                </div>
            @endif
        </div>

        {{-- GIAI ĐOẠN 3: KIỂM ĐỊNH CHẤT LƯỢNG (QC) --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">3</span>
                    <h3 class="text-sm font-bold text-stone-900">Giai Đoạn 3: Kiểm Định Chất Lượng & Cấp Chứng Nhận QC</h3>
                </div>
                <span class="text-xs text-stone-500">{{ $lot->phieuKiemDinh->count() }} phiếu kiểm tra</span>
            </div>

            @forelse($lot->phieuKiemDinh as $ticket)
                <div class="p-4 rounded-lg border {{ $ticket->ket_luan === 'DAT' ? 'border-emerald-200 bg-emerald-50/20' : 'border-rose-200 bg-rose-50/20' }} space-y-3 text-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-200/60 pb-2">
                        <div>
                            <span class="font-bold font-mono text-stone-900 text-sm">{{ $ticket->ma_phieu }}</span>
                            <span class="text-stone-500 ml-2">Loại: {{ $ticket->loai_kiem_dinh_label }}</span>
                            <span class="text-stone-500 ml-2">Ngày: {{ $ticket->ngay_kiem_dinh ? $ticket->ngay_kiem_dinh->format('d/m/Y H:i') : 'N/A' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="{{ $ticket->getKetLuanBadgeClass() }}">
                                {{ $ticket->ket_luan_label }}
                            </span>
                            <span class="text-stone-600">Bởi: {{ $ticket->nguoiKiemDinh?->ho_ten ?? 'KTV' }}</span>
                        </div>
                    </div>

                    {{-- Criteria results --}}
                    @if($ticket->chiTietKetQua->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-[11px]">
                                <thead>
                                    <tr class="text-stone-500 border-b border-stone-200">
                                        <th class="py-1">Tiêu chí kiểm nghiệm</th>
                                        <th class="py-1">Giá trị tham chiếu</th>
                                        <th class="py-1">Giá trị thực tế</th>
                                        <th class="py-1">Kết quả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ticket->chiTietKetQua as $ct)
                                        <tr class="border-b border-stone-100">
                                            <td class="py-1 font-semibold text-stone-800">{{ $ct->ten_tieu_chi }}</td>
                                            <td class="py-1 text-stone-600">{{ $ct->gia_tri_tham_chieu }} {{ $ct->don_vi_do }}</td>
                                            <td class="py-1 font-bold {{ $ct->ket_qua === 'DAT' ? 'text-emerald-700' : 'text-rose-600' }}">{{ $ct->gia_tri_thuc_te }} {{ $ct->don_vi_do }}</td>
                                            <td class="py-1">
                                                <span class="{{ $ct->ket_qua === 'DAT' ? 'text-emerald-700' : 'text-rose-600' }} font-bold">
                                                    {{ $ct->ket_qua }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($ticket->ket_luan === 'KHONG_DAT')
                        <div class="p-2.5 rounded bg-rose-100/70 text-rose-900 space-y-1">
                            <div class="font-bold">Hướng xử lý vi phạm: {{ $ticket->huong_xu_ly_label }}</div>
                            @if($ticket->ghi_chu_loi)
                                <div>Mô tả lỗi: {{ $ticket->ghi_chu_loi }}</div>
                            @endif
                            @if($ticket->thoi_gian_khac_phuc)
                                <div class="text-stone-700 font-medium">
                                    Đã khắc phục: {{ $ticket->ket_qua_khac_phuc }} (bởi {{ $ticket->nguoiKhacPhuc?->ho_ten ?? 'KTV' }} lúc {{ $ticket->thoi_gian_khac_phuc->format('d/m/Y H:i') }})
                                </div>
                            @else
                                <div class="font-semibold text-rose-700">Chưa ghi nhận kết quả khắc phục!</div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-3 bg-stone-50 rounded-lg border border-stone-200 text-xs text-stone-500">
                    Lô này chưa thực hiện kiểm tra chất lượng tại xưởng.
                </div>
            @endforelse
        </div>

        {{-- GIAI ĐOẠN 4: LƯU KHO LẠNH & IOT --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-xs">4</span>
                    <h3 class="text-sm font-bold text-stone-900">Giai Đoạn 4: Lưu Kho Lạnh & Giám Sát Nhiệt Độ</h3>
                </div>
                <span class="text-xs text-stone-500">{{ $lot->khoLanh?->ten_kho ?? 'Chưa nhập kho' }}</span>
            </div>

            @if($lot->khoLanh)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-stone-50 p-3.5 rounded-lg border border-stone-200">
                    <div>
                        <span class="text-stone-500 block">Kho lạnh bảo quản:</span>
                        <strong class="text-stone-800">{{ $lot->khoLanh->ten_kho }} ({{ $lot->khoLanh->ma_kho }})</strong>
                        <div class="text-[11px] text-stone-500">{{ $lot->khoLanh->dia_chi }}</div>
                    </div>
                    <div>
                        <span class="text-stone-500 block">Sức chứa thiết kế:</span>
                        <span class="font-bold text-stone-800">{{ number_format($lot->khoLanh->suc_chua) }} {{ $lot->khoLanh->don_vi_suc_chua }}</span>
                    </div>
                    <div>
                        <span class="text-stone-500 block">Trạng thái kho:</span>
                        <span class="f2f-badge-success text-[11px]">{{ $lot->khoLanh->trang_thai }}</span>
                    </div>
                </div>

                {{-- Warehouse sensors --}}
                @if($lot->khoLanh->thietBiIot->isNotEmpty())
                    <div class="space-y-1.5">
                        <span class="text-xs font-semibold text-stone-700 block">Cảm biến IoT gắn tại kho:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            @foreach($lot->khoLanh->thietBiIot as $sensor)
                                <div class="p-2.5 rounded border border-stone-200 bg-white">
                                    <div class="font-bold text-stone-800">{{ $sensor->ma_thiet_bi }}</div>
                                    <div class="text-stone-500 text-[11px]">{{ $sensor->loai_thiet_bi }}</div>
                                    @if($sensor->latestReading)
                                        <div class="mt-1 font-semibold text-emerald-700">
                                            Nhiệt độ hiện thời: {{ $sensor->latestReading->nhiet_do }}°C · Độ ẩm: {{ $sensor->latestReading->do_am }}%
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="p-3 bg-stone-50 rounded-lg border border-stone-200 text-xs text-stone-500">
                    Lô hàng chưa được bàn giao nhập kho lạnh.
                </div>
            @endif
        </div>

        {{-- GIAI ĐOẠN 5: ĐÓNG GÓI SSCC, VẬN CHUYỂN & GIAO HÀNG --}}
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs">5</span>
                    <h3 class="text-sm font-bold text-stone-900">Giai Đoạn 5: Đóng Gói SSCC & Vận Chuyển Giao Nhận</h3>
                </div>
                <span class="text-xs text-stone-500">{{ $ssccUnits->count() }} kiện SSCC</span>
            </div>

            @if($ssccUnits->isNotEmpty())
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-stone-700 block">Danh sách kiện đóng gói SSCC:</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($ssccUnits as $sscc)
                            <div class="p-3 rounded-lg border border-stone-200 bg-stone-50 text-xs space-y-1">
                                <div class="font-bold font-mono text-stone-900">{{ $sscc->ma_sscc }}</div>
                                <div class="text-stone-500">Loại đơn vị: {{ $sscc->loai_don_vi }}</div>
                                <div class="text-stone-500">Trạng thái: <span class="font-semibold text-stone-800">{{ $sscc->trang_thai }}</span></div>
                                <div class="text-[11px] text-stone-500">Ngày đóng gói: {{ $sscc->ngay_dong_goi ? \Carbon\Carbon::parse($sscc->ngay_dong_goi)->format('d/m/Y') : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Trips linked --}}
                @if($trips->isNotEmpty())
                    <div class="space-y-2 pt-2 border-t border-stone-100">
                        <span class="text-xs font-semibold text-stone-700 block">Chuyến vận chuyển liên kết:</span>
                        <div class="space-y-3">
                            @foreach($trips as $trip)
                                <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50 text-xs space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <span class="font-bold font-mono text-stone-900 text-sm">{{ $trip->ma_chuyen }}</span>
                                            <span class="text-stone-600 ml-2">Lộ trình: {{ $trip->diem_di }} → {{ $trip->diem_den }}</span>
                                        </div>
                                        <div>
                                            <span class="f2f-badge-active text-[11px]">{{ $trip->trang_thai }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-stone-600">
                                        <div>Phương tiện: <strong>{{ $trip->phuongTien?->bien_so ?? 'N/A' }}</strong></div>
                                        <div>Tài xế: <strong>{{ $trip->taiXe?->ho_ten ?? 'N/A' }}</strong></div>
                                        <div>Xuất phát: {{ $trip->thoi_gian_xuat_phat ? $trip->thoi_gian_xuat_phat->format('d/m/Y H:i') : 'Chưa' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="p-3 bg-stone-50 rounded-lg border border-stone-200 text-xs text-stone-500">
                    Lô này chưa được chia vào các kiện đóng gói SSCC hoặc tạo phiếu giao hàng.
                </div>
            @endif
        </div>
    </div>

    {{-- AUDIT TRAIL / LỊCH SỬ THAY ĐỔI TRẠNG THÁI --}}
    @if($lot->lichSuTrangThai->isNotEmpty())
        <div class="bg-white rounded-xl border border-stone-200 p-5 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-stone-700">
                Nhật Ký Lưu Vết Trạng Thái Lô (Audit Trail)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-600 font-semibold">
                            <th class="py-2 px-3">Thời gian</th>
                            <th class="py-2 px-3">Trạng thái cũ</th>
                            <th class="py-2 px-3">Trạng thái mới</th>
                            <th class="py-2 px-3">Người cập nhật</th>
                            <th class="py-2 px-3">Lý do / Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach($lot->lichSuTrangThai as $history)
                            <tr>
                                <td class="py-2 px-3 text-stone-500 font-mono">{{ $history->thoi_gian ? $history->thoi_gian->format('d/m/Y H:i:s') : '' }}</td>
                                <td class="py-2 px-3">{{ $history->trang_thai_cu }}</td>
                                <td class="py-2 px-3 font-bold text-stone-800">{{ $history->trang_thai_moi }}</td>
                                <td class="py-2 px-3">{{ $history->taiKhoan?->ho_ten ?? 'Hệ thống' }}</td>
                                <td class="py-2 px-3 text-stone-600">{{ $history->ly_do }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection


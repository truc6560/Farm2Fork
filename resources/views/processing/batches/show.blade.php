@extends('layouts.dashboard', ['title' => 'Chi tiết lô ' . $lot->ma_lo, 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế'])

@section('title', 'Chi tiết Lô Thành phẩm - ' . $lot->ma_lo)

@section('content')
@include('trace.handoffs', ['traceLot' => $lot])
    <div class="space-y-6">
        {{-- Breadcrumb & Header --}}
        <div class="f2f-processing-page-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                    <a href="{{ route('processing.batches.index') }}" class="hover:text-emerald-700 transition">Quản lý Sơ chế</a>
                    <span>/</span>
                    <span class="text-stone-800">Chi tiết Lô Thành phẩm</span>
                </div>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                    Lô: {{ $lot->ma_lo }}
                </h1>
                <p class="mt-0.5 text-xs text-stone-600">
                    Sản phẩm: <span class="font-semibold text-stone-900">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</span>
                    &bull; Mã SP: {{ $lot->sanPham->ma_san_pham ?? '—' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('processing.batches.index') }}" class="f2f-btn-secondary text-xs">
                    Quay lại danh sách
                </a>
                @if(in_array($lot->trang_thai, ['DA_SO_CHE', 'DANG_DONG_GOI']))
                    <a href="{{ route('processing.packaging.create', ['lo_id' => $lot->lo_nong_san_id]) }}" class="f2f-btn-primary text-xs">
                        Đóng gói SSCC
                    </a>
                @endif
            </div>
        </div>

        {{-- Overview Cards --}}
        <div class="f2f-processing-detail-stats grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Khối lượng thành phẩm</div>
                <div class="mt-1 text-xl font-bold text-stone-900">
                    {{ number_format($lot->so_luong, 2) }}
                    <span class="text-xs font-normal text-stone-500">{{ $lot->don_vi_tinh }}</span>
                </div>
                <div class="mt-1 text-xs text-stone-500">Sản lượng sau phân loại</div>
            </div>

            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Trạng thái xử lý</div>
                <div class="mt-1.5">
                    <span class="f2f-badge {{ $lot->getTrangThaiBadgeClass() }}">
                        {{ $lot->trang_thai }}
                    </span>
                </div>
                <div class="mt-1 text-xs text-stone-500">Tiến trình vận hành</div>
            </div>

            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Kho bảo quản</div>
                <div class="mt-1 text-base font-semibold text-stone-900 truncate">
                    {{ $lot->khoLanh->ten_kho ?? 'Chưa phân kho' }}
                </div>
                <div class="mt-1 text-xs text-stone-500">
                    @if($lot->khoLanh)
                        {{ $lot->khoLanh->nhiet_do_chuan }}&deg;C &bull; {{ $lot->khoLanh->do_am_chuan }}%
                    @else
                        Chưa chỉ định
                    @endif
                </div>
            </div>

            <div class="bg-white border border-stone-200/80 rounded-xl p-4 shadow-xs">
                <div class="text-xs font-medium text-stone-500">Hạn sử dụng</div>
                <div class="mt-1 text-base font-semibold text-stone-900">
                    {{ $lot->han_su_dung ? \Carbon\Carbon::parse($lot->han_su_dung)->format('d/m/Y') : 'Không quy định' }}
                </div>
                <div class="mt-1 text-xs text-stone-500">
                    Tạo: {{ $lot->ngay_thu_hoach_hoac_tao_lo ? \Carbon\Carbon::parse($lot->ngay_thu_hoach_hoac_tao_lo)->format('d/m/Y') : '—' }}
                </div>
            </div>
        </div>

        {{-- GS1 Identification & Consumer QR Code Card --}}
        <div class="bg-white border border-stone-200/90 rounded-xl p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row items-center gap-6">
                {{-- Scannable QR Code Canvas / SVG --}}
                <div class="p-4 bg-stone-50 border border-stone-200 rounded-xl shrink-0 text-center shadow-2xs">
                    <div class="w-52 h-52 flex items-center justify-center bg-white p-2 rounded-lg border border-stone-200/80">
                        {!! $lot->qr_svg !!}
                    </div>
                    <div class="mt-2 font-mono text-[11px] font-bold text-stone-800">
                        {{ $lot->ma_lo }}
                    </div>
                    <div class="text-[10px] text-stone-500">Mã QR quét được bằng điện thoại</div>
                </div>

                {{-- GS1 Digital Link Info & Instructions --}}
                <div class="flex-1 space-y-3 text-center lg:text-left">
                    <div>
                        <span class="inline-block text-[10px] font-bold tracking-wider uppercase px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                            GS1 Digital Link & Mã QR Truy xuất Người tiêu dùng
                        </span>
                        <h2 class="mt-1.5 text-lg font-bold text-stone-900">
                            Mã QR Truy xuất Nguồn gốc Sản phẩm
                        </h2>
                        <p class="mt-1 text-xs text-stone-600 leading-relaxed">
                            Mã QR này được in trực tiếp lên nhãn bao bì sản phẩm hoặc tem dán thùng. Người tiêu dùng sử dụng camera điện thoại hoặc Zalo để quét và xem ngay toàn bộ thông tin nguồn gốc từ nông trại, kiểm định an toàn VietGAP đến quy trình sơ chế.
                        </p>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-lg border border-stone-200/80 space-y-1">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-stone-500">Đường dẫn truy xuất (GS1 URI):</div>
                        <div class="font-mono text-xs font-semibold text-emerald-900 break-all select-all">
                            {{ $lot->digital_link_url }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5 pt-1">
                        <a href="{{ $lot->digital_link_url }}" target="_blank" rel="noopener noreferrer" class="f2f-btn-primary text-xs">
                            Mở trang truy xuất người tiêu dùng &rarr;
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $lot->digital_link_url }}'); alert('Đã sao chép liên kết GS1 Digital Link vào clipboard!');" class="f2f-btn-secondary text-xs">
                            Sao chép liên kết
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2 Columns Section: Traceability One-step-back & Packaging Units --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Column 1: One-Step-Back Traceability --}}
            <div class="bg-white border border-stone-200/80 rounded-xl p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Truy xuất 1 bước lùi (Nguyên liệu đầu vào)</h2>
                        <p class="text-xs text-stone-500 mt-0.5">Lô nông sản nguyên liệu từ vùng trồng được sử dụng để sơ chế</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-stone-100 text-stone-700">
                        {{ $lot->nguyenLieuDauVao->count() }} lô nguồn
                    </span>
                </div>

                @if($lot->nguyenLieuDauVao->count() > 0)
                    <div class="space-y-3">
                        @foreach($lot->nguyenLieuDauVao as $raw)
                            <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50/60 hover:bg-stone-50 transition space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-mono text-xs font-bold text-emerald-900">
                                            {{ $raw->ma_lo }}
                                        </div>
                                        <div class="text-xs font-semibold text-stone-900 mt-0.5">
                                            {{ $raw->sanPham->ten_san_pham ?? 'Chưa rõ' }}
                                        </div>
                                    </div>
                                    <a href="{{ route('processing.inbound.show', $raw->lo_nong_san_id) }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 transition">
                                        Xem chi tiết &rarr;
                                    </a>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs text-stone-600 pt-1 border-t border-stone-200/60">
                                    <div>
                                        <span class="text-stone-400">Khối lượng dùng:</span>
                                        <span class="font-bold text-stone-800">
                                            {{ number_format($raw->pivot->khoi_luong_su_dung ?? 0, 2) }} {{ $raw->pivot->don_vi_tinh ?? 'kg' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-stone-400">Cơ sở sản xuất:</span>
                                        <span class="font-medium text-stone-800 truncate block">
                                            {{ $raw->toChucNguon->ten_to_chuc ?? $raw->muaVu->vungTrong->toChuc->ten_to_chuc ?? 'Hợp tác xã đối tác' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-stone-400">Vùng trồng:</span>
                                        <span class="text-stone-800">
                                            {{ $raw->muaVu->vungTrong->ten_vung_trong ?? 'Theo hợp đồng' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-stone-400">Mã VietGAP:</span>
                                        <span class="font-mono text-stone-800">
                                            {{ $raw->muaVu->vungTrong->ma_vietgap ?? 'Đang thẩm định' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-stone-500 text-xs bg-stone-50 rounded-lg">
                        Không có dữ liệu nguyên liệu đầu vào liên kết với lô thành phẩm này.
                    </div>
                @endif
            </div>

            {{-- Column 2: Packaging Units (SSCC) --}}
            <div class="bg-white border border-stone-200/80 rounded-xl p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Đơn vị đóng gói Logistics (SSCC)</h2>
                        <p class="text-xs text-stone-500 mt-0.5">Kiện hàng, thùng carton hoặc pallet đã đóng gói từ lô này</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-stone-100 text-stone-700">
                        {{ $lot->donViSscc->count() }} đơn vị
                    </span>
                </div>

                @if($lot->donViSscc->count() > 0)
                    <div class="space-y-3">
                        @foreach($lot->donViSscc as $unit)
                            <div class="p-3.5 rounded-lg border border-stone-200 bg-stone-50/60 hover:bg-stone-50 transition space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-mono text-xs font-bold text-stone-900">
                                            (00) {{ $unit->ma_sscc }}
                                        </div>
                                        <div class="text-xs font-medium text-stone-600 mt-0.5">
                                            {{ $unit->loai_don_vi_label }} &bull; Đóng gói: {{ number_format($unit->pivot->so_luong ?? 0, 2) }} {{ $unit->pivot->don_vi_tinh ?? 'kg' }}
                                        </div>
                                    </div>
                                    <span class="f2f-badge {{ $unit->getTrangThaiBadgeClass() }}">
                                        {{ $unit->trang_thai_label }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs text-stone-500 pt-1 border-t border-stone-200/60">
                                    <div>
                                        Kho: <span class="font-medium text-stone-800">{{ $unit->khoLanh->ten_kho ?? 'Chưa nhập kho' }}</span>
                                    </div>
                                    <a href="{{ route('processing.packaging.show', $unit->sscc_id) }}" class="font-semibold text-emerald-700 hover:text-emerald-900 transition">
                                        Chi tiết kiện SSCC &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-stone-500 text-xs bg-stone-50 rounded-lg space-y-3">
                        <p>Lô thành phẩm này chưa được đưa vào đơn vị đóng gói logistics nào.</p>
                        @if(in_array($lot->trang_thai, ['DA_SO_CHE', 'DANG_DONG_GOI']))
                            <a href="{{ route('processing.packaging.create', ['lo_id' => $lot->lo_nong_san_id]) }}" class="inline-flex items-center text-xs font-semibold text-emerald-700 hover:text-emerald-900">
                                Tạo kiện hàng SSCC mới cho lô này &rarr;
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection


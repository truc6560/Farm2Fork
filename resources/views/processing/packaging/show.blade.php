@extends('layouts.dashboard', ['title' => 'Chi tiết kiện SSCC', 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế'])

@section('title', 'Đơn vị Logistics GS1 SSCC - ' . $unit->ma_sscc)

@section('content')
    <div class="space-y-6">
        {{-- Breadcrumb & Header --}}
        <div class="f2f-processing-page-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                    <a href="{{ route('processing.packaging.index') }}" class="hover:text-emerald-700 transition">Đóng gói Logistics</a>
                    <span>/</span>
                    <span class="text-stone-800">Chi tiết kiện hàng</span>
                </div>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                    Đơn vị GS1 SSCC: (00) {{ $unit->ma_sscc }}
                </h1>
                <p class="mt-0.5 text-xs text-stone-600">
                    Loại: <span class="font-semibold text-stone-900">{{ $unit->loai_don_vi_label }}</span>
                    &bull; Ngày đóng: {{ $unit->ngay_dong_goi ? \Carbon\Carbon::parse($unit->ngay_dong_goi)->format('d/m/Y H:i') : '—' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('processing.packaging.index') }}" class="f2f-btn-secondary text-xs">
                    Quay lại danh sách
                </a>
                <a href="{{ route('processing.packaging.print-labels', ['ids' => [$unit->sscc_id]]) }}" target="_blank" class="f2f-btn-primary text-xs">
                    In nhãn logistics GS1
                </a>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs font-medium text-emerald-800 shadow-xs print:hidden">
                {{ session('status') }}
            </div>
        @endif

        {{-- Printable GS1 Logistics Label (Standard Layout) --}}
        <div class="bg-white border-2 border-stone-800 rounded-xl p-6 shadow-sm max-w-2xl mx-auto space-y-6">
            {{-- Top Header of Label --}}
            <div class="border-b-2 border-stone-800 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-stone-500">Nhãn logistics theo cấu trúc GS1</div>
                        @if($unit->is_demo)
                            <div class="text-xs font-bold text-amber-800">MÃ MÔ PHỎNG · KHÔNG DÙNG THƯƠNG MẠI</div>
                        @endif
                        <div class="text-xl font-bold text-stone-900">FARM2FORK SUPPLY CHAIN</div>
                        <div class="text-xs text-stone-600 mt-0.5">Trung tâm Sơ chế, Chế biến & Đóng gói Nông sản Farm2Fork</div>
                        <div class="mt-2 text-left">
                            <span class="inline-block px-2.5 py-1 text-xs font-bold rounded bg-stone-900 text-white uppercase">
                                {{ $unit->loai_don_vi_label }}
                            </span>
                            <span class="ml-2 text-[11px] text-stone-500">
                                Kho: {{ $unit->khoLanh->ten_kho ?? 'Chưa chỉ định' }}
                            </span>
                        </div>
                    </div>
                    <div class="shrink-0 text-center bg-stone-50 p-2 border border-stone-300 rounded">
                        <div class="w-28 h-28 flex items-center justify-center bg-white p-1">
                            {!! \App\Services\QrCodeService::svg(app(\App\Services\Gs1DigitalLinkService::class)->ssccUrl($unit->ma_sscc), 104, 4) !!}
                        </div>
                        <div class="text-[9px] font-mono text-stone-600 mt-1 font-semibold">Quét để xem kiện SSCC</div>
                    </div>
                </div>
            </div>

            {{-- Label Content Details --}}
            <div class="grid grid-cols-2 gap-4 border-b-2 border-stone-800 pb-4 text-xs">
                <div>
                    <div class="text-stone-400 font-semibold uppercase text-[10px]">Đơn vị gửi hàng (Shipper)</div>
                    <div class="font-bold text-stone-900 mt-0.5">Hệ thống Nông sản Farm2Fork</div>
                    <div class="text-stone-600">Khu công nghiệp Nông nghiệp Công nghệ cao</div>
                    <div class="text-stone-600">Việt Nam &bull; SSCC: {{ $unit->ma_sscc }}</div>
                </div>
                <div>
                    <div class="text-stone-400 font-semibold uppercase text-[10px]">Thông số bảo quản & Vận hành</div>
                    <div class="font-bold text-stone-900 mt-0.5">
                        Trạng thái: {{ $unit->trang_thai_label }}
                    </div>
                    <div class="text-stone-600">
                        @if($unit->khoLanh)
                            Bảo quản: {{ $unit->khoLanh->nhiet_do_chuan }}&deg;C &bull; Độ ẩm: {{ $unit->khoLanh->do_am_chuan }}%
                        @else
                            Bảo quản: Nhiệt độ phòng tiêu chuẩn
                        @endif
                    </div>
                    <div class="text-stone-600">
                        Ngày tạo nhãn: {{ $unit->ngay_dong_goi ? \Carbon\Carbon::parse($unit->ngay_dong_goi)->format('d/m/Y') : date('d/m/Y') }}
                    </div>
                </div>
            </div>

            {{-- Contained Products Summary --}}
            <div class="border-b-2 border-stone-800 pb-4">
                <div class="text-stone-400 font-semibold uppercase text-[10px] mb-2">Thành phần hàng hóa bên trong (Content Manifest)</div>
                <div class="space-y-1.5 text-xs">
                    @foreach($unit->chiTiet as $item)
                        <div class="flex items-center justify-between border-b border-stone-100 py-1">
                            <div>
                                <span class="font-semibold text-stone-900">{{ $item->loNongSan->sanPham->ten_san_pham ?? 'Chưa xác định' }}</span>
                                <span class="font-mono text-stone-500 text-[11px] ml-1.5">Lô: {{ $item->loNongSan->ma_lo ?? '—' }}</span>
                            </div>
                            <div class="font-bold text-stone-900 font-mono">
                                {{ number_format($item->so_luong, 2) }} {{ $item->don_vi_tinh }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- GS1 SSCC Barcode Representation --}}
            <div class="pt-2 text-center space-y-3">
                <div class="text-[11px] font-bold uppercase tracking-widest text-stone-500">
                    SERIAL SHIPPING CONTAINER CODE (SSCC) — GS1-128
                </div>

                {{-- Authentic GS1-128 (Subset C) SVG Barcode --}}
                <div class="bg-white p-3 border border-stone-200 rounded max-w-lg mx-auto overflow-hidden">
                    {!! $unit->barcode_svg !!}
                </div>

                {{-- Human Readable Interpretation (HRI) --}}
                <div class="font-mono text-lg sm:text-xl font-bold tracking-widest text-stone-900">
                    {{ $unit->hri }}
                </div>
                <p class="text-[10px] text-stone-500">
                    Định danh logistics 18 chữ số kèm số kiểm tra Modulo 10 theo tiêu chuẩn GS1 Quốc tế
                </p>
            </div>
        </div>

        {{-- Detailed Manifest Table --}}
        <div class="bg-white border border-stone-200/80 rounded-xl p-5 shadow-xs space-y-4 print:hidden">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-stone-900">Bảng kê chi tiết lô hàng đóng gói</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Liên kết truy xuất nguồn gốc ngược về lô thành phẩm và vùng trồng</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-stone-100 text-stone-700">
                    {{ $unit->chiTiet->count() }} mặt hàng
                </span>
            </div>

            <div class="f2f-table-container overflow-x-auto">
                <table class="f2f-table w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 bg-stone-50/75 text-stone-600 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Mã lô thành phẩm</th>
                            <th class="py-2.5 px-3">Tên sản phẩm</th>
                            <th class="py-2.5 px-3">Số lượng đóng</th>
                            <th class="py-2.5 px-3">Nguồn nguyên liệu ban đầu</th>
                            <th class="py-2.5 px-3">Hạn sử dụng</th>
                            <th class="py-2.5 px-3 text-right">Chi tiết lô</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @foreach($unit->chiTiet as $item)
                            <tr class="hover:bg-stone-50/50 transition">
                                <td class="py-3 px-3 font-mono font-bold text-emerald-900">
                                    {{ $item->loNongSan->ma_lo ?? '—' }}
                                </td>
                                <td class="py-3 px-3 font-medium text-stone-900">
                                    {{ $item->loNongSan->sanPham->ten_san_pham ?? '—' }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-stone-900">
                                    {{ number_format($item->so_luong, 2) }} {{ $item->don_vi_tinh }}
                                </td>
                                <td class="py-3 px-3 text-stone-600">
                                    @if($item->loNongSan && $item->loNongSan->nguyenLieuDauVao->isNotEmpty())
                                        @foreach($item->loNongSan->nguyenLieuDauVao as $raw)
                                            <div>
                                                {{ $raw->toChucNguon->ten_to_chuc ?? 'Hợp tác xã sản xuất' }}
                                                <span class="text-stone-400 font-mono text-[11px]">({{ $raw->ma_lo }})</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-stone-400 italic">Theo quy trình sản xuất</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-stone-600">
                                    {{ ($item->loNongSan && $item->loNongSan->han_su_dung) ? \Carbon\Carbon::parse($item->loNongSan->han_su_dung)->format('d/m/Y') : 'Không quy định' }}
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($item->loNongSan)
                                        <a href="{{ route('processing.batches.show', $item->loNongSan->lo_nong_san_id) }}" class="font-semibold text-emerald-700 hover:text-emerald-900 transition">
                                            Xem lô &rarr;
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($unit->ghi_chu)
                <div class="pt-3 border-t border-stone-100 text-xs text-stone-600">
                    <span class="font-semibold text-stone-800">Ghi chú kiện hàng:</span> {{ $unit->ghi_chu }}
                </div>
            @endif
        </div>
    </div>
@endsection


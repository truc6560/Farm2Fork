@extends('layouts.dashboard', ['title' => 'Phiếu giao hàng ' . $delivery->ma_phieu_giao_hang, 'breadcrumb' => 'Farm2Fork · Tài xế nội bộ · Chi tiết giao nhận'])

@section('content')
<div class="space-y-6">
    {{-- Header & Quick Controls --}}
    <div class="f2f-driver-detail-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('driver.delivery.index') }}" class="hover:underline">Giao nhận hàng hóa</a>
                <span>/</span>
                <span class="text-stone-800">{{ $delivery->ma_phieu_giao_hang }}</span>
            </div>
            <div class="mt-1 flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-stone-900 font-mono">
                    {{ $delivery->ma_phieu_giao_hang }}
                </h1>
                <span class="{{ $delivery->getTrangThaiBadgeClass() }}">
                    {{ $delivery->trang_thai_label }}
                </span>
            </div>
            <p class="mt-0.5 text-xs text-stone-600">
                Khách hàng nhận: <strong class="text-stone-800">{{ $delivery->toChucNhan->ten_to_chuc ?? $delivery->ten_nguoi_nhan }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('driver.delivery.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại danh sách
            </a>
            @if($delivery->chuyenVanChuyen)
                <a href="{{ route('driver.trips.show', $delivery->chuyenVanChuyen->chuyen_id) }}" class="f2f-btn-secondary text-xs">
                    Xem chuyến xe
                </a>
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

    {{-- 2 Columns Layout: Details & Proof Form --}}
    <div class="f2f-driver-detail-layout grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left 2 cols: Delivery Specs & SSCC Packaging units --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order Specs Card --}}
            <div class="f2f-card" style="padding:18px">
                <h3 class="text-sm font-bold text-stone-900 border-b border-stone-200 pb-2.5">
                    Thông tin Đơn hàng & Địa chỉ Giao nhận
                </h3>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Đơn vị nhận hàng:</span>
                        <strong class="text-stone-900">{{ $delivery->toChucNhan->ten_to_chuc ?? '—' }}</strong>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Người liên hệ nhận:</span>
                        <span class="text-stone-900 font-semibold">{{ $delivery->ten_nguoi_nhan }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Số điện thoại liên hệ:</span>
                        <span class="font-mono text-stone-900 font-bold">{{ $delivery->sdt_nguoi_nhan ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-stone-100">
                        <span class="text-stone-500">Yêu cầu nhiệt độ chuỗi lạnh:</span>
                        <span class="font-mono text-emerald-800 font-bold">
                            {{ $delivery->yeu_cau_nhiet_do_min !== null ? $delivery->yeu_cau_nhiet_do_min . '°C' : '0°C' }}
                            đến
                            {{ $delivery->yeu_cau_nhiet_do_max !== null ? $delivery->yeu_cau_nhiet_do_max . '°C' : '4°C' }}
                        </span>
                    </div>
                    <div class="sm:col-span-2 py-1 border-b border-stone-100">
                        <span class="text-stone-500 block mb-0.5">Địa chỉ giao hàng thực tế:</span>
                        <strong class="text-stone-900 text-xs">{{ $delivery->dia_chi_giao }}</strong>
                    </div>
                    @if($delivery->ghi_chu)
                        <div class="sm:col-span-2 py-1">
                            <span class="text-stone-500 block mb-0.5">Ghi chú phiếu giao:</span>
                            <div class="p-2 bg-stone-50 rounded border border-stone-200 text-stone-700 text-[11px]">
                                {{ $delivery->ghi_chu }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- SSCC Packaging Units List --}}
            <div class="f2f-card" style="padding:18px">
                <div class="flex items-center justify-between border-b border-stone-200 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-stone-900">
                            Danh sách Đơn vị Đóng gói GS1 SSCC Bàn giao
                        </h3>
                        <p class="text-xs text-stone-500">Kiểm tra tem mã vạch SSCC, số lượng và niêm phong trước khi bàn giao</p>
                    </div>
                    <span class="f2f-badge-active">{{ $delivery->chiTietGiaoNhan->count() }} kiện hàng</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($delivery->chiTietGiaoNhan as $item)
                        <div class="border border-stone-200 rounded-lg p-3 bg-stone-50/50 text-xs space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border-b border-stone-200 pb-2">
                                <div>
                                    <span class="font-mono font-bold text-stone-900 text-xs">{{ $item->donViDongGoi->ma_sscc ?? 'Mã SSCC #' . $item->sscc_id }}</span>
                                    <span class="text-stone-500 font-medium ml-2">({{ $item->donViDongGoi->loai_don_vi_label ?? 'Kiện hàng' }})</span>
                                </div>
                                <span class="font-semibold {{ $item->trang_thai_giao_nhan === 'DA_GIAO' ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $item->trang_thai_label }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                @if($item->donViDongGoi && $item->donViDongGoi->chiTiet)
                                    @foreach($item->donViDongGoi->chiTiet as $dt)
                                        <div class="flex items-center justify-between text-[11px] text-stone-700">
                                            <span>
                                                {{ $dt->loNongSan->sanPham->ten_san_pham ?? 'Nông sản' }} ·
                                                Lô: <strong class="font-mono text-emerald-900">{{ $dt->loNongSan->ma_lo ?? '—' }}</strong>
                                            </span>
                                            <span class="font-mono font-semibold">{{ number_format($dt->so_luong, 1) }} {{ $dt->don_vi_tinh }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            @if($item->trang_thai_giao_nhan === 'DA_GIAO')
                                <div class="pt-2 border-t border-stone-200 grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                                    <div>
                                        <span class="text-stone-500">Người nhận thực tế:</span>
                                        <strong class="text-stone-800">{{ $item->ten_nguoi_nhan_thuc_te ?? '—' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-stone-500">Thời gian nhận:</span>
                                        <span class="font-mono text-stone-800">{{ $item->thoi_gian_giao_nhan ? $item->thoi_gian_giao_nhan->format('H:i - d/m/Y') : '—' }}</span>
                                    </div>
                                    @if($item->tinh_trang_bao_goi)
                                        <div class="sm:col-span-2">
                                            <span class="text-stone-500">Tình trạng:</span>
                                            <span class="text-stone-800">{{ $item->tinh_trang_bao_goi_label }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-stone-500">
                            Không có kiện hàng nào trong phiếu giao này.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Col: Delivery Confirmation Form & Proof --}}
        <div class="space-y-6">
            {{-- Delivery Confirmation Form --}}
            <div class="f2f-card" style="padding:18px">
                <h3 class="text-sm font-bold text-stone-900 border-b border-stone-200 pb-2.5">
                    Xác nhận Bàn giao & Ký nhận
                </h3>
                <p class="text-xs text-stone-500 mt-1 mb-4">
                    Nhập họ tên người nhận thực tế, tình trạng bao bì và tải ảnh biên bản bàn giao để lưu trữ vào chuỗi truy xuất nguồn gốc.
                </p>

                @if(in_array($delivery->trang_thai, ['CHO_GIAO', 'DANG_GIAO']))
                <form method="POST" action="{{ route('driver.delivery.confirm', $delivery->phieu_giao_hang_id) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                        <label class="f2f-label" for="sscc_codes">Quét SSCC tại điểm nhận <span class="text-rose-600">*</span></label>
                        <textarea class="f2f-input font-mono text-xs" name="sscc_codes" id="sscc_codes" rows="3" required
                                  placeholder="Dùng máy quét/camera; mỗi mã một dòng">{{ old('sscc_codes') }}</textarea>
                        <span class="text-[11px] text-stone-600 mt-1 block">Phải quét đúng và đủ {{ $delivery->chiTietGiaoNhan->count() }} kiện của phiếu trước khi ký nhận.</span>
                    </div>
                    @foreach($quantities as $quantity)
                        <div class="border rounded p-2">
                            <strong>{{ $quantity->ma_lo }}</strong>
                            <p>Đã giao: {{ $quantity->so_luong_giao }} {{ $quantity->don_vi_tinh }}</p>
                            <label>Thực nhận (0 nếu từ chối)</label>
                            <input class="f2f-input" type="number" min="0" step="0.001" max="{{ $quantity->so_luong_giao }}" name="quantities[{{ $quantity->id }}][received]" value="{{ old('quantities.'.$quantity->id.'.received', $quantity->so_luong_nhan ?? $quantity->so_luong_giao) }}" required>
                            <label>Lý do nhận thiếu / từ chối</label>
                            <input class="f2f-input" name="quantities[{{ $quantity->id }}][reason]" value="{{ old('quantities.'.$quantity->id.'.reason', $quantity->ly_do) }}" maxlength="500">
                        </div>
                    @endforeach

                    <div>
                        <label class="f2f-label" for="ten_nguoi_nhan_thuc_te">Họ tên người nhận thực tế <span class="text-rose-600">*</span></label>
                        <input class="f2f-input text-xs" type="text" name="ten_nguoi_nhan_thuc_te" id="ten_nguoi_nhan_thuc_te"
                               placeholder="Vd: Nguyễn Văn A"
                               value="{{ old('ten_nguoi_nhan_thuc_te', $delivery->chiTietGiaoNhan->first()?->ten_nguoi_nhan_thuc_te ?? $delivery->ten_nguoi_nhan) }}" required>
                    </div>

                    <div>
                        <label class="f2f-label" for="tinh_trang_bao_goi">Tình trạng bao gói & niêm phong <span class="text-rose-600">*</span></label>
                        <select class="f2f-input text-xs" name="tinh_trang_bao_goi" id="tinh_trang_bao_goi" required>
                            @foreach($packingConditions as $code => $label)
                                <option value="{{ $code }}" @selected(old('tinh_trang_bao_goi', $delivery->chiTietGiaoNhan->first()?->tinh_trang_bao_goi ?? 'NGUYEN_VEN') == $code)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="f2f-label" for="mo_ta_hu_hong">Mô tả khiếm khuyết / Hư hỏng (nếu có)</label>
                        <textarea class="f2f-input text-xs" name="mo_ta_hu_hong" id="mo_ta_hu_hong" rows="2"
                                  placeholder="Ghi rõ chi tiết nếu có thùng carton bị móp, rách bao bì...">{{ old('mo_ta_hu_hong', $delivery->chiTietGiaoNhan->first()?->mo_ta_hu_hong) }}</textarea>
                    </div>

                    <div>
                        <label class="f2f-label" for="anh_minh_chung">Ảnh chụp biên bản ký nhận / Hiện trường bàn giao</label>
                        <input class="f2f-input text-xs" type="file" name="anh_minh_chung" id="anh_minh_chung" accept="image/jpeg,image/png,image/webp">
                        <span class="text-[11px] text-stone-500 mt-1 block">Hỗ trợ tệp ảnh JPG, PNG, WEBP (tối đa 10MB)</span>
                    </div>

                    <div>
                        <label class="f2f-label" for="ghi_chu">Ghi chú của tài xế</label>
                        <textarea class="f2f-input text-xs" name="ghi_chu" id="ghi_chu" rows="2"
                                  placeholder="Ghi chú thêm về thời điểm bàn giao, kiểm đếm...">{{ old('ghi_chu', $delivery->chiTietGiaoNhan->first()?->ghi_chu) }}</textarea>
                    </div>

                    <button type="submit" class="f2f-btn-primary w-full text-xs" style="padding:9px 14px">
                        {{ $delivery->trang_thai === 'DA_GIAO' ? 'Cập nhật lại biên bản giao nhận' : 'Xác nhận đã giao hàng thành công' }}
                    </button>
                </form>
                @else
                    <p>Biên bản đã chốt. Kết quả: {{ $delivery->trang_thai_label }}</p>
                    @foreach($quantities as $q)<p>{{ $q->ma_lo }}: nhận {{ $q->so_luong_nhan }} / {{ $q->so_luong_giao }} {{ $q->don_vi_tinh }}. {{ $q->ly_do }}</p>@endforeach
                @endif
            </div>

            {{-- Proof Image Display (if exists) --}}
            @php
                $firstProofUrl = $delivery->chiTietGiaoNhan->firstWhere('anh_minh_chung_url')?->anh_minh_chung_url;
            @endphp
            @if($firstProofUrl)
                <div class="f2f-card" style="padding:18px">
                    <h3 class="text-sm font-bold text-stone-900 border-b border-stone-200 pb-2">
                        Ảnh Minh chứng Biên bản Giao hàng
                    </h3>
                    <div class="mt-3">
                        <a href="{{ $firstProofUrl }}" target="_blank" title="Xem ảnh kích thước lớn">
                            <img src="{{ $firstProofUrl }}" alt="Biên bản giao hàng" class="w-full h-auto rounded border border-stone-200 object-cover max-h-64">
                        </a>
                        <div class="mt-2 text-center">
                            <a href="{{ $firstProofUrl }}" target="_blank" class="f2f-link text-xs">
                                Mở xem ảnh gốc đầy đủ
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


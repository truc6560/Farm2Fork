@extends('layouts.dashboard', ['title' => 'Phiếu kiểm định ' . $ticket->ma_phieu, 'breadcrumb' => 'Farm2Fork · Kiểm định chất lượng (QC)'])

@section('content')
<div class="space-y-6 f2f-qc f2f-qc-detail">
    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('qc.inspections.index') }}" class="hover:text-emerald-700 transition">Sổ kiểm định</a>
                <span>/</span>
                <span class="text-stone-800">Chi tiết phiếu</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Phiếu Kiểm nghiệm: {{ $ticket->ma_phieu }}
            </h1>
            <p class="mt-0.5 text-xs text-stone-600">
                Lô nông sản: <span class="font-mono font-bold text-stone-900">{{ $lot->ma_lo ?? '—' }}</span>
                &bull; Sản phẩm: <span class="font-semibold text-stone-900">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('qc.inspections.index') }}" class="f2f-btn-secondary text-xs">
                Quay lại sổ kiểm định
            </a>
            <button type="button" onclick="window.print()" class="f2f-btn-primary text-xs">
                In phiếu kiểm nghiệm
            </button>
            @if($ticket->ket_luan === 'KHONG_DAT')
                <a href="{{ route('qc.inspections.create', ['lo_id' => $lot->lo_nong_san_id]) }}" class="f2f-btn-secondary text-xs">
                    Tạo phiếu tái kiểm định
                </a>
            @endif
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session('status'))
        <div class="f2f-alert f2f-alert-success print:hidden">
            {{ session('status') }}
        </div>
    @endif

    {{-- Printable Formal Quality Inspection Certificate --}}
    <div class="bg-white border-2 border-stone-800 rounded-2xl p-6 sm:p-8 shadow-sm max-w-4xl mx-auto space-y-6 print:border-none print:shadow-none print:p-0">
        {{-- Header of Certificate --}}
        <div class="border-b-2 border-stone-800 pb-5 text-center space-y-1.5">
            <div class="text-[11px] font-bold uppercase tracking-widest text-stone-600">
                HỆ THỐNG QUẢN LÝ CHUỖI CUNG ỨNG & TRUY XUẤT NÔNG SẢN FARM2FORK
            </div>
            <div class="text-xs font-semibold text-stone-700">
                BỘ PHẬN KIỂM ĐỊNH CHẤT LƯỢNG & AN TOÀN THỰC PHẨM
            </div>
            <h2 class="text-xl sm:text-2xl font-bold uppercase tracking-tight text-stone-900 pt-2">
                PHIẾU KẾT QUẢ KIỂM NGHIỆM CHẤT LƯỢNG NÔNG SẢN
            </h2>
            <div class="flex flex-wrap items-center justify-center gap-3 text-xs text-stone-600 pt-1">
                <span>Mã số phiếu: <strong class="font-mono text-stone-900">{{ $ticket->ma_phieu }}</strong></span>
                <span>&bull;</span>
                <span>Ngày kiểm nghiệm: <strong class="font-mono text-stone-900">{{ $ticket->ngay_kiem_dinh ? $ticket->ngay_kiem_dinh->format('H:i - d/m/Y') : '—' }}</strong></span>
                <span>&bull;</span>
                <span>Hình thức: <strong class="text-stone-900">{{ $ticket->loai_kiem_dinh_label }}</strong></span>
            </div>
        </div>

        {{-- Lot Profile Summary Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-stone-50 border border-stone-200 text-xs">
            <div class="space-y-1.5">
                <div>
                    <span class="text-stone-500">Tên nông sản:</span>
                    <strong class="text-stone-900 ml-1 text-sm">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</strong>
                </div>
                <div>
                    <span class="text-stone-500">Mã định danh lô (Lot Code):</span>
                    <strong class="font-mono text-stone-900 ml-1">{{ $lot->ma_lo }}</strong>
                </div>
                <div>
                    <span class="text-stone-500">Phân loại lô hàng:</span>
                    <span class="font-semibold text-stone-800 ml-1">
                        {{ $lot->loai_lo === 'NGUYEN_LIEU' ? 'Nông sản nguyên liệu thu hoạch' : 'Nông sản thành phẩm sơ chế đóng gói' }}
                    </span>
                </div>
                <div>
                    <span class="text-stone-500">Khối lượng kiểm nghiệm:</span>
                    <strong class="font-mono text-stone-900 ml-1">{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</strong>
                </div>
            </div>

            <div class="space-y-1.5">
                <div>
                    <span class="text-stone-500">Cơ sở sản xuất / Nguồn gốc:</span>
                    <strong class="text-stone-900 ml-1">
                        {{ $lot->toChucNguon->ten_to_chuc ?? $lot->muaVu?->vungTrong?->toChuc?->ten_to_chuc ?? 'Hợp tác xã đối tác' }}
                    </strong>
                </div>
                <div>
                    <span class="text-stone-500">Vùng trồng / Chứng nhận:</span>
                    <span class="text-stone-800 ml-1">
                        {{ $lot->muaVu?->vungTrong?->ten_vung_trong ?? 'Vùng chuyên canh' }}
                        @if($lot->muaVu?->vungTrong?->so_chung_nhan)
                            ({{ $lot->muaVu->vungTrong->loai_chung_nhan ?? 'Chứng nhận' }}:
                            <strong class="font-mono">{{ $lot->muaVu->vungTrong->so_chung_nhan }}</strong>
                            @if($lot->muaVu->vungTrong->ngay_het_han_chung_nhan)
                                · {{ $lot->muaVu->vungTrong->ngay_het_han_chung_nhan->isPast() ? 'đã hết hạn' : 'còn hiệu lực đến ' . $lot->muaVu->vungTrong->ngay_het_han_chung_nhan->format('d/m/Y') }}
                            @endif)
                        @else
                            (Chưa có dữ liệu chứng nhận)
                        @endif
                    </span>
                </div>
                <div>
                    <span class="text-stone-500">Ngày thu hoạch / Đóng gói:</span>
                    <span class="font-mono text-stone-800 ml-1">
                        {{ $lot->ngay_thu_hoach ? \Carbon\Carbon::parse($lot->ngay_thu_hoach)->format('d/m/Y') : ($lot->ngay_san_xuat ? \Carbon\Carbon::parse($lot->ngay_san_xuat)->format('d/m/Y') : '—') }}
                    </span>
                </div>
                <div>
                    <span class="text-stone-500">Kho lưu trữ / Bảo quản:</span>
                    <span class="text-stone-800 ml-1">{{ $lot->khoLanh->ten_kho ?? 'Kho lạnh tiêu chuẩn' }}</span>
                </div>
            </div>
        </div>

        {{-- Criteria Test Results Table --}}
        <div class="space-y-2">
            <div class="font-bold uppercase tracking-wider text-xs text-stone-800">
                BẢNG KẾT QUẢ ĐO ĐẠC & PHÂN TÍCH CHỈ TIÊU KỸ THUẬT:
            </div>

            <div class="overflow-x-auto border border-stone-300 rounded-lg">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-stone-100 text-stone-700 uppercase text-[10px] tracking-wider border-b border-stone-300">
                        <tr>
                            <th class="p-2.5 text-center" style="width:40px">STT</th>
                            <th class="p-2.5">Tiêu chí kiểm nghiệm</th>
                            <th class="p-2.5">Tiêu chuẩn chuẩn hóa</th>
                            <th class="p-2.5">Giá trị thực tế đo được</th>
                            <th class="p-2.5 text-center" style="width:110px">Kết luận</th>
                            <th class="p-2.5">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        @forelse($ticket->chiTietKetQua as $index => $item)
                            <tr class="hover:bg-stone-50/70 transition">
                                <td class="p-2.5 text-center font-mono text-stone-500">{{ $index + 1 }}</td>
                                <td class="p-2.5 font-semibold text-stone-900">{{ $item->ten_tieu_chi }}</td>
                                <td class="p-2.5 text-stone-600 font-mono">{{ $item->gia_tri_tham_chieu ?: '—' }}</td>
                                <td class="p-2.5 font-bold font-mono text-stone-900">
                                    {{ $item->gia_tri_thuc_te ?: '—' }} {{ $item->don_vi_do }}
                                </td>
                                <td class="p-2.5 text-center">
                                    @if($item->ket_qua === 'DAT')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            ĐẠT
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                            KHÔNG ĐẠT
                                        </span>
                                    @endif
                                </td>
                                <td class="p-2.5 text-stone-500">{{ $item->ghi_chu ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-4 text-center text-stone-500">
                                    Chưa có dữ liệu chỉ tiêu kiểm nghiệm.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Overall Conclusion Box --}}
        <div class="p-4 rounded-xl border {{ $ticket->ket_luan === 'DAT' ? 'border-emerald-300 bg-emerald-50/60' : 'border-rose-300 bg-rose-50/60' }} space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider {{ $ticket->ket_luan === 'DAT' ? 'text-emerald-800' : 'text-rose-800' }}">
                        KẾT LUẬN CHUNG CỦA CHUYÊN VIÊN KIỂM ĐỊNH:
                    </span>
                    <div class="text-base font-bold {{ $ticket->ket_luan === 'DAT' ? 'text-emerald-950' : 'text-rose-950' }} mt-0.5">
                        {{ $ticket->ket_luan_label }}
                    </div>
                </div>
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $ticket->ket_luan === 'DAT' ? 'bg-emerald-200 text-emerald-900 border border-emerald-400' : 'bg-rose-200 text-rose-900 border border-rose-400' }}">
                        {{ $ticket->ket_luan === 'DAT' ? 'ĐỦ ĐIỀU KIỆN CHUYỂN CÔNG ĐOẠN' : ($ticket->ket_luan === 'KHONG_DAT' ? 'CẦN XỬ LÝ THEO QUY TRÌNH' : 'CHỜ HOÀN TẤT KIỂM ĐỊNH') }}
                    </span>
                </div>
            </div>

            @if($ticket->huong_xu_ly)
                <div class="pt-2 border-t border-rose-200 text-xs text-rose-900">
                    <span class="font-bold">Hướng xử lý tiếp theo:</span> {{ $ticket->huong_xu_ly_label }}
                    @if($ticket->ghi_chu_loi)
                        <span class="block mt-0.5 text-rose-800">Nguyên nhân: {{ $ticket->ghi_chu_loi }}</span>
                    @endif
                </div>
            @endif

            @if($ticket->ghi_chu)
                <div class="pt-1 text-xs text-stone-700">
                    <span class="font-semibold">Nhận xét:</span> {{ $ticket->ghi_chu }}
                </div>
            @endif
        </div>

        {{-- Signatures Section --}}
        <div class="grid grid-cols-2 gap-6 pt-4 text-xs text-center border-t border-stone-200">
            <div>
                <div class="font-semibold text-stone-600 uppercase text-[11px]">ĐẠI DIỆN PHÒNG KCS / KIỂM NGHIỆM</div>
                <div class="text-stone-400 text-[10px] mt-0.5">(Ký và ghi rõ họ tên)</div>
                <div class="h-16 flex items-center justify-center">
                    <span class="font-semibold text-stone-900 text-sm">
                        {{ $ticket->nguoiKiemDinh->ho_ten ?? 'Bùi Thanh Tâm' }}
                    </span>
                </div>
                <div class="text-[11px] text-stone-500">Chuyên viên kiểm định chất lượng</div>
            </div>

            <div>
                <div class="font-semibold text-stone-600 uppercase text-[11px]">TRẠNG THÁI XỬ LÝ LÔ</div>
                <div class="text-stone-400 text-[10px] mt-0.5">(Cập nhật tự động theo kết quả QC)</div>
                <div class="h-16 flex items-center justify-center">
                    <span class="text-xs font-semibold px-3 py-1 rounded border {{ $ticket->ket_luan === 'DAT' ? 'text-emerald-800 border-emerald-300 bg-emerald-50' : ($ticket->ket_luan === 'KHONG_DAT' ? 'text-rose-800 border-rose-300 bg-rose-50' : 'text-amber-800 border-amber-300 bg-amber-50') }}">
                        @if($ticket->ket_luan === 'DAT')
                            ĐỦ ĐIỀU KIỆN CHUYỂN CÔNG ĐOẠN
                        @elseif($ticket->ket_luan === 'KHONG_DAT' && $ticket->thoi_gian_khac_phuc)
                            ĐÃ GHI NHẬN KHẮC PHỤC
                        @elseif($ticket->ket_luan === 'KHONG_DAT')
                            CHỜ XỬ LÝ / TÁI KIỂM ĐỊNH
                        @else
                            CHỜ HOÀN TẤT KIỂM ĐỊNH
                        @endif
                    </span>
                </div>
                <div class="text-[11px] text-stone-500">Trạng thái nội bộ hệ thống Farm2Fork</div>
            </div>
        </div>
    </div>

    {{-- Remediation / Corrective Action Section (Hidden when printing) --}}
    @if($ticket->ket_luan === 'KHONG_DAT')
        <div class="f2f-card print:hidden">
            <h2 class="f2f-card-title">Xử lý Khắc phục & Theo dõi Tái kiểm định</h2>
            <p class="f2f-subtitle">Ghi nhận biên bản phân loại lại, xử lý trả hàng hoặc các biện pháp khắc phục kỹ thuật.</p>

            @if($ticket->thoi_gian_khac_phuc)
                <div class="mt-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-emerald-900">ĐÃ GHI NHẬN KẾT QUẢ KHẮC PHỤC</span>
                        <span class="font-mono text-stone-600">{{ $ticket->thoi_gian_khac_phuc->format('H:i - d/m/Y') }}</span>
                    </div>
                    <div class="text-stone-800">
                        <span class="font-semibold">Nội dung khắc phục:</span> {{ $ticket->ket_qua_khac_phuc }}
                    </div>
                    <div class="text-[11px] text-stone-500 pt-1 border-t border-emerald-200">
                        Người thực hiện: <strong class="text-stone-800">{{ $ticket->nguoiKhacPhuc->ho_ten ?? 'Chuyên viên kỹ thuật' }}</strong>
                    </div>
                </div>
            @else
                <form action="{{ route('qc.inspections.remedy', $ticket->phieu_id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="f2f-label" for="ket_qua_khac_phuc">
                            Nội dung biện pháp khắc phục đã thực hiện <span class="text-rose-600">*</span>
                        </label>
                        <textarea class="f2f-input" name="ket_qua_khac_phuc" id="ket_qua_khac_phuc" rows="3" required
                                  placeholder="Mô tả cụ thể: Đã loại bỏ 15kg quả dập, xử lý thông gió hạ nhiệt khoang mát, lấy mẫu ngẫu nhiên kiểm tra lại..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="f2f-label" for="chuyen_trang_thai">
                                Cập nhật trạng thái lô sau khi khắc phục <span class="text-rose-600">*</span>
                            </label>
                            <select class="f2f-input" name="chuyen_trang_thai" id="chuyen_trang_thai" required>
                                @if($lot->loai_lo === 'NGUYEN_LIEU')
                                    <option value="CHO_KIEM_DINH">Chuyển sang Chờ tái kiểm nguyên liệu</option>
                                @else
                                    <option value="CHO_KIEM_DINH_XUAT_XUONG">Chuyển sang Chờ tái kiểm xuất xưởng</option>
                                @endif
                                <option value="GIU_NGUYEN">Giữ nguyên trạng thái hiện tại</option>
                            </select>
                            <p class="mt-1 text-xs text-stone-500">Khắc phục không tự cấp trạng thái đạt; QC phải lập phiếu tái kiểm riêng.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="submit" class="f2f-btn-primary">
                            Xác nhận ghi nhận kết quả khắc phục
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection


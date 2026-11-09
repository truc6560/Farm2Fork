@extends('layouts.dashboard', ['title' => 'Chi tiết Mùa vụ: ' . $season->ten_mua_vu, 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi · Chi tiết mùa vụ'])

@section('content')
<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('chain.seasons.index') }}" class="hover:text-emerald-700 transition">Quản lý mùa vụ</a>
                <span>/</span>
                <span class="text-stone-800">Hồ sơ mùa vụ</span>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-1.5">
                <h1 class="text-2xl font-bold text-stone-900">{{ $season->ten_mua_vu }}</h1>
                <span class="f2f-badge {{ $season->getTrangThaiBadgeClass() }} text-xs">
                    {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$season->trang_thai] ?? $season->trang_thai }}
                </span>
                <code class="px-2 py-0.5 rounded bg-stone-100 text-stone-700 font-mono text-xs border border-stone-200">
                    {{ $season->ma_mua_vu }}
                </code>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('chain.seasons.index') }}" class="f2f-btn-ghost text-xs">
                ← Danh sách mùa vụ
            </a>

            <a href="{{ route('chain.seasons.edit', $season->mua_vu_id) }}" class="f2f-btn-ghost text-xs" title="Chỉnh sửa thông tin mùa vụ">
                Sửa mùa vụ
            </a>

            @if($season->loNongSan->isNotEmpty())
                <a href="{{ route('chain.lots.index', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary text-xs">
                    Xem {{ $season->loNongSan->count() }} lô thu hoạch
                </a>
            @endif

            {{-- Lifecycle Actions in Header --}}
            @if($season->trang_thai === 'DANG_CANH_TAC')
                <form method="POST" action="{{ route('chain.seasons.start-harvest', $season->mua_vu_id) }}" onsubmit="return confirm('Xác nhận chuyển mùa vụ sang giai đoạn ĐANG THU HOẠCH?')">
                    @csrf
                    <button type="submit" class="f2f-btn-secondary text-xs text-emerald-700 font-bold">
                        Bắt đầu đợt thu hoạch
                    </button>
                </form>
            @elseif($season->trang_thai === 'DANG_THU_HOACH')
                <details class="relative inline-block text-left">
                    <summary class="f2f-btn-primary text-xs list-none cursor-pointer">
                        Nghiệm thu kết thúc vụ
                    </summary>
                    <div class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-stone-200 p-4 z-20">
                        <form method="POST" action="{{ route('chain.seasons.complete', $season->mua_vu_id) }}">
                            @csrf
                            <div class="font-bold text-sm text-stone-800 mb-1">Nghiệm thu & Kết thúc mùa vụ</div>
                            <p class="text-xs text-stone-500 mb-3">Xác nhận đã thu hoạch xong. Sau khi kết thúc, hồ sơ chuyển sang lưu trữ kiểm toán.</p>
                            <label class="block text-xs font-semibold text-stone-700 mb-1">Ghi chú nghiệm thu *</label>
                            <textarea name="ly_do" rows="3" class="f2f-input text-xs w-full" required placeholder="Nêu lý do kết thúc (VD: Đã thu hoạch đủ 3 đợt, sản lượng đạt kế hoạch)..."></textarea>
                            <button type="submit" class="f2f-btn-primary text-xs w-full mt-3">Xác nhận kết thúc vụ</button>
                        </form>
                    </div>
                </details>
            @elseif($season->trang_thai === 'HOAN_THANH')
                <details class="relative inline-block text-left">
                    <summary class="f2f-btn-secondary text-xs list-none cursor-pointer">
                        Mở lại mùa vụ
                    </summary>
                    <div class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-stone-200 p-4 z-20">
                        <form method="POST" action="{{ route('chain.seasons.reopen', $season->mua_vu_id) }}">
                            @csrf
                            <div class="font-bold text-sm text-stone-800 mb-1">Mở lại mùa vụ</div>
                            <p class="text-xs text-stone-500 mb-3">Chỉ mở lại khi người sản xuất cần bổ sung đợt thu hoạch.</p>
                            <label class="block text-xs font-semibold text-stone-700 mb-1">Lý do mở lại *</label>
                            <textarea name="ly_do" rows="3" class="f2f-input text-xs w-full" required placeholder="Nêu rõ đợt thu hoạch cần bổ sung..."></textarea>
                            <button type="submit" class="f2f-btn-primary text-xs w-full mt-3">Xác nhận mở lại</button>
                        </form>
                    </div>
                </details>
            @endif

            {{-- Delete Button (Protected) --}}
            @if($canDelete)
                <form method="POST" action="{{ route('chain.seasons.destroy', $season->mua_vu_id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mùa vụ này? Hành động không thể hoàn tác.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="f2f-btn-ghost text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50" title="Xóa mùa vụ chưa phát sinh dữ liệu">
                        Xóa vụ
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('status'))
        <div class="f2f-alert f2f-alert-success" style="border-radius:8px;">
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="f2f-alert f2f-alert-danger" style="border-radius:8px;">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- PHI Quarantine Warning Alert --}}
    @if(!empty($quarantineInfo['dang_cach_ly']))
        <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="text-2xl">⚠️</div>
                <div class="space-y-1">
                    <h3 class="font-bold text-amber-900 text-sm">CẢNH BÁO KIỂM SOÁT THỜI GIAN CÁCH LY THUỐC BVTV (PHI):</h3>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        Mùa vụ đang trong thời gian cách ly thuốc bảo vệ thực vật <strong>{{ $quarantineInfo['ten_vat_tu'] ?? 'Thuốc BVTV' }}</strong>.
                        @if(!empty($quarantineInfo['ngay_su_dung']))
                            Đợt phun gần nhất: <strong>{{ $quarantineInfo['ngay_su_dung']->format('d/m/Y H:i') }}</strong>.
                        @endif
                        Thời gian cách ly an toàn: <strong>{{ $quarantineInfo['so_ngay_cach_ly'] ?? 0 }} ngày</strong>.
                        Thời điểm sớm nhất được phép thu hoạch: <strong>{{ data_get($quarantineInfo, 'ngay_het_cach_ly')?->format('d/m/Y') }}</strong>
                        (Còn lại <strong>{{ $quarantineInfo['so_ngay_con_lai'] ?? 0 }} ngày</strong>).
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="text-emerald-700 font-bold text-base">✓</span>
                <span class="text-xs font-semibold text-emerald-900">
                    An toàn dư lượng bảo vệ thực vật (PHI): Không có hoạt chất thuốc BVTV nào đang trong thời gian cách ly. Mùa vụ đủ điều kiện thu hoạch an toàn VietGAP.
                </span>
            </div>
        </div>
    @endif

    {{-- Season Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- Zone Info --}}
        <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 block">Vùng trồng nông sản</span>
            <strong class="text-base text-stone-900 mt-1 block">{{ $season->vungTrong->ten_vung_trong ?? '—' }}</strong>
            <div class="text-xs text-stone-500 mt-1 space-y-0.5">
                <div>Mã PUC: <code class="font-mono text-stone-700">{{ $season->vungTrong->ma_vung_trong ?? 'N/A' }}</code></div>
                <div>Địa chỉ: {{ $season->vungTrong->dia_chi ?? 'Đang cập nhật' }}</div>
                @if($season->vungTrong?->toChuc)
                    <div class="text-emerald-700 font-medium">{{ $season->vungTrong->toChuc->ten_to_chuc }}</div>
                @endif
            </div>
        </div>

        {{-- Product Info --}}
        <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 block">Mặt hàng & Giống cây</span>
            <strong class="text-base text-stone-900 mt-1 block">{{ $season->sanPham->ten_san_pham ?? '—' }}</strong>
            <div class="text-xs text-stone-500 mt-1 space-y-0.5">
                <div>Giống: <span class="font-semibold text-stone-700">{{ $season->giong_cay_trong ?? 'Chưa ghi nhận' }}</span></div>
                <div>Mã sản phẩm: <code class="font-mono text-stone-700">{{ $season->sanPham->ma_san_pham ?? '—' }}</code></div>
                <div>Diện tích canh tác: <strong>{{ $season->dien_tich_canh_tac_ha ? number_format($season->dien_tich_canh_tac_ha, 2) . ' ha' : '—' }}</strong></div>
            </div>
        </div>

        {{-- Schedule Info --}}
        <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 block">Khung thời vụ</span>
            <div class="mt-2 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-stone-500">Ngày gieo / cấy:</span>
                    <strong class="text-stone-800">{{ $season->ngay_gieo?->format('d/m/Y') ?? 'Chưa ghi' }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">Dự kiến thu hoạch:</span>
                    <strong class="text-stone-800">{{ $season->ngay_thu_hoach_du_kien?->format('d/m/Y') ?? 'Chưa ghi' }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">Đợt thu thực tế:</span>
                    <strong class="text-emerald-700">{{ $season->loNongSan->count() }} đợt thu hoạch</strong>
                </div>
            </div>
        </div>

        {{-- Yield Info --}}
        <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 block">Sản lượng dự kiến vs Thực thu</span>
            <div class="mt-2 text-xs space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-stone-500">Kế hoạch:</span>
                    <strong class="text-stone-800">{{ number_format($season->san_luong_du_kien ?? 0, 0) }} {{ $season->don_vi_san_luong ?? 'kg' }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">Đã thu thực tế:</span>
                    <strong class="text-emerald-700 font-bold">{{ number_format($totalHarvestedQuantity, 1) }} {{ $season->don_vi_san_luong ?? 'kg' }}</strong>
                </div>
                @if($season->san_luong_du_kien > 0)
                    @php $yieldPct = round(($totalHarvestedQuantity / $season->san_luong_du_kien) * 100, 1); @endphp
                    <div class="w-full bg-stone-100 rounded-full h-1.5 mt-2">
                        <div class="h-1.5 rounded-full {{ $yieldPct >= 100 ? 'bg-emerald-600' : 'bg-sky-600' }}" style="width: {{ min(100, $yieldPct) }}%"></div>
                    </div>
                    <div class="text-[11px] text-right text-stone-500 mt-0.5">Đạt {{ $yieldPct }}% kế hoạch</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Harvest Lots Section --}}
    <div class="f2f-card">
        <div class="f2f-card-toolbar">
            <div>
                <h2 class="f2f-card-title">Các Lô Nông Sản Thu Hoạch Từ Vụ Này ({{ $season->loNongSan->count() }})</h2>
                <p class="f2f-subtitle">Danh sách các lô nguyên liệu thô đã thu hoạch và đưa vào lưu chuyển chuỗi cung ứng.</p>
            </div>
            @if($season->loNongSan->isNotEmpty())
                <div class="f2f-toolbar-actions">
                    <a href="{{ route('chain.lots.index', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-ghost f2f-btn-sm">
                        Quản lý trên Giám sát lô hàng →
                    </a>
                </div>
            @endif
        </div>

        <div class="f2f-table-wrap">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Mã lô nguyên liệu</th>
                        <th>Khối lượng</th>
                        <th>Ngày thu hoạch</th>
                        <th>Kho lạnh lưu trữ</th>
                        <th>Kiểm định QC</th>
                        <th>Trạng thái lô</th>
                        <th style="text-align:right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($season->loNongSan as $lot)
                    <tr>
                        <td>
                            <a href="{{ route('chain.lots.show', $lot->lo_nong_san_id) }}" class="font-bold text-stone-900 hover:text-emerald-700">
                                {{ $lot->ma_lo }}
                            </a>
                        </td>
                        <td>
                            <strong class="text-emerald-700">{{ number_format($lot->so_luong, 2) }}</strong> {{ $lot->don_vi_tinh }}
                        </td>
                        <td>
                            {{ $lot->ngay_thu_hoach?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td>
                            {{ $lot->khoLanh?->ten_kho ?? 'Chưa đưa vào kho' }}
                        </td>
                        <td>
                            @php $latestQc = $lot->phieuKiemDinh->last(); @endphp
                            @if($latestQc)
                                @if($latestQc->ket_luan === 'DAT')
                                    <span class="f2f-badge-success text-[11px]">Đạt tiêu chuẩn</span>
                                @elseif($latestQc->ket_luan === 'KHONG_DAT')
                                    <span class="f2f-badge-danger text-[11px]">Không đạt</span>
                                @else
                                    <span class="f2f-badge-warning text-[11px]">{{ $latestQc->ket_luan }}</span>
                                @endif
                            @else
                                <span class="text-xs text-stone-400 italic">Chờ kiểm định</span>
                            @endif
                        </td>
                        <td>
                            <span class="f2f-badge {{ $lot->getTrangThaiBadgeClass() }}">
                                {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('chain.lots.show', $lot->lo_nong_san_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                Chi tiết lô
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="f2f-table-empty">Mùa vụ này chưa có lô nông sản thu hoạch nào được tạo.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Farming Logs Section --}}
    <div class="f2f-card">
        <div class="f2f-card-toolbar">
            <div>
                <h2 class="f2f-card-title">Nhật Ký Canh Tác & Chăm Sóc Đồng Ruộng ({{ $season->nhatKyCanhTac->count() }})</h2>
                <p class="f2f-subtitle">Dữ liệu ghi chép nhật ký phân bón, tưới tiêu, thuốc BVTV theo tiêu chuẩn VietGAP.</p>
            </div>
        </div>

        <div class="f2f-table-wrap">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Thời điểm thực hiện</th>
                        <th>Loại hoạt động</th>
                        <th>Người ghi chép</th>
                        <th>Vật tư / Thuốc / Phân bón sử dụng</th>
                        <th>Ghi chú kỹ thuật</th>
                        <th>Trạng thái xác nhận</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($season->nhatKyCanhTac->sortByDesc('ngay_thuc_hien') as $log)
                    <tr>
                        <td>
                            <strong>{{ $log->ngay_thuc_hien?->format('d/m/Y H:i') ?? '—' }}</strong>
                        </td>
                        <td>
                            <span class="font-medium text-stone-800">
                                {{ \App\Models\NhatKyCanhTac::HOAT_DONG_LABELS[$log->loai_hoat_dong] ?? $log->loai_hoat_dong }}
                            </span>
                        </td>
                        <td>
                            {{ $log->nguoiThucHien->ho_ten ?? 'N/A' }}
                        </td>
                        <td>
                            @if($log->chiTietVatTu->isNotEmpty())
                                <ul class="text-xs space-y-1">
                                    @foreach($log->chiTietVatTu as $vt)
                                        <li class="text-stone-700">
                                            • <strong>{{ $vt->vatTu->ten_thuong_mai ?? 'Vật tư' }}</strong>: 
                                            {{ $vt->lieu_luong }} {{ $vt->don_vi_tinh }}
                                            @if($vt->thoi_gian_cach_ly_ap_dung_ngay > 0)
                                                <span class="text-amber-700">(Cách ly: {{ $vt->thoi_gian_cach_ly_ap_dung_ngay }} ngày)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-xs text-stone-400 italic">Không sử dụng vật tư</span>
                            @endif
                        </td>
                        <td class="text-xs text-stone-600 max-w-xs truncate">
                            {{ $log->ghi_chu ?: '—' }}
                        </td>
                        <td>
                            @if($log->trang_thai === 'DA_XAC_NHAN')
                                <span class="f2f-badge-success text-[11px]">Đã xác nhận</span>
                            @elseif($log->trang_thai === 'NHAP')
                                <span class="f2f-badge-warning text-[11px]">Bản nháp</span>
                            @else
                                <span class="f2f-badge-danger text-[11px]">{{ $log->trang_thai }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="f2f-table-empty">Chưa có nhật ký canh tác nào được ghi nhận cho mùa vụ này.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Lifecycle Audit History --}}
    <div class="f2f-card">
        <div class="f2f-card-toolbar">
            <div>
                <h2 class="f2f-card-title">Lịch Sử Vòng Đời Mùa Vụ (Audit Trail)</h2>
                <p class="f2f-subtitle">Nhật ký truy vết các lần chuyển đổi trạng thái và người thực hiện.</p>
            </div>
        </div>

        <div class="f2f-table-wrap">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Trạng thái cũ</th>
                        <th>Trạng thái mới</th>
                        <th>Lý do / Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($season->lichSuTrangThai->sortByDesc('thoi_gian') as $history)
                    <tr>
                        <td>{{ $history->thoi_gian?->format('d/m/Y H:i:s') ?? '—' }}</td>
                        <td><strong>{{ $history->nguoiThucHien->ho_ten ?? 'Hệ thống tự động' }}</strong></td>
                        <td>
                            <span class="f2f-badge {{ $season->getTrangThaiBadgeClass($history->trang_thai_cu) }}">
                                {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$history->trang_thai_cu] ?? $history->trang_thai_cu }}
                            </span>
                        </td>
                        <td>
                            <span class="f2f-badge {{ $season->getTrangThaiBadgeClass($history->trang_thai_moi) }}">
                                {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$history->trang_thai_moi] ?? $history->trang_thai_moi }}
                            </span>
                        </td>
                        <td class="text-xs text-stone-700">{{ $history->ly_do ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="f2f-table-empty">Chưa có lịch sử chuyển đổi trạng thái vòng đời.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

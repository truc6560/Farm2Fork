@extends('layouts.dashboard', ['title' => 'Quản lý mùa vụ', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi · Mùa vụ'])

@section('content')
<div class="space-y-5">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Quản Lý & Giám Sát Mùa Vụ</h1>
            <p class="text-xs text-stone-500 mt-1">
                Theo dõi tiến độ sinh trưởng, quy hoạch vùng trồng, kiểm soát an toàn PHI và điều phối vòng đời mùa vụ toàn chuỗi.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('chain.seasons.create') }}" class="f2f-btn-primary text-xs font-semibold shadow-sm inline-flex items-center gap-1.5">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Khai báo mùa vụ mới</span>
            </a>
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

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Tổng số mùa vụ</span>
            <strong class="f2f-stat-value text-stone-900">{{ number_format($totalSeasons) }}</strong>
            <span class="f2f-stat-hint">Toàn bộ kế hoạch canh tác</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang canh tác</span>
            <strong class="f2f-stat-value text-emerald-700">{{ number_format($activeSeasons) }}</strong>
            <span class="f2f-stat-hint">Gieo trồng & chăm sóc</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đang thu hoạch</span>
            <strong class="f2f-stat-value text-amber-700">{{ number_format($harvestingSeasons) }}</strong>
            <span class="f2f-stat-hint">Mở đợt thu hoạch nguyên liệu</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Đã hoàn thành</span>
            <strong class="f2f-stat-value text-sky-700">{{ number_format($completedSeasons) }}</strong>
            <span class="f2f-stat-hint">Đã nghiệm thu kết thúc vụ</span>
        </div>
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Cách ly PHI</span>
            <strong class="f2f-stat-value {{ $quarantineCount > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                {{ number_format($quarantineCount) }}
            </strong>
            <span class="f2f-stat-hint">{{ $quarantineCount > 0 ? 'Vụ đang cách ly thuốc' : 'Tất cả an toàn' }}</span>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-xl border border-stone-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('chain.seasons.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
                {{-- Keyword --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Mã / Tên mùa vụ / Giống</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nhập tên mùa vụ, giống..." class="f2f-input text-xs w-full">
                </div>

                {{-- Status --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Trạng thái vòng đời</label>
                    <select name="trang_thai" class="f2f-select text-xs w-full">
                        <option value="">-- Tất cả trạng thái --</option>
                        @foreach(\App\Models\MuaVu::TRANG_THAI_LABELS as $code => $label)
                            <option value="{{ $code }}" @selected(($filters['trang_thai'] ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Planting Zone --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Vùng trồng liên kết</label>
                    <select name="vung_trong_id" class="f2f-select text-xs w-full">
                        <option value="">-- Tất cả vùng trồng --</option>
                        @foreach($plantingZones as $zone)
                            <option value="{{ $zone->vung_trong_id }}" @selected(($filters['vung_trong_id'] ?? '') == $zone->vung_trong_id)>
                                {{ $zone->ten_vung_trong }} ({{ $zone->ma_vung_trong ?? 'PUC' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Product --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Mặt hàng nông sản</label>
                    <select name="san_pham_id" class="f2f-select text-xs w-full">
                        <option value="">-- Tất cả nông sản --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->san_pham_id }}" @selected(($filters['san_pham_id'] ?? '') == $p->san_pham_id)>
                                {{ $p->ten_san_pham }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Year --}}
                <div>
                    <label class="block font-semibold text-stone-700 mb-1">Năm mùa vụ</label>
                    <select name="nam" class="f2f-select text-xs w-full">
                        <option value="">-- Tất cả năm --</option>
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" @selected(($filters['nam'] ?? '') == $yr)>Năm {{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-stone-100">
                <span class="text-xs text-stone-500">
                    Tìm thấy <strong>{{ $seasons->total() }}</strong> mùa vụ phù hợp
                </span>
                <div class="flex items-center gap-2">
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('chain.seasons.index') }}" class="f2f-btn-ghost text-xs">Xóa bộ lọc</a>
                    @endif
                    <button type="submit" class="f2f-btn-primary text-xs">Lọc danh sách</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Main Seasons Table --}}
    <div class="f2f-card">
        <div class="f2f-table-wrap">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Mùa vụ & Nông sản</th>
                        <th>Vùng trồng & Đơn vị</th>
                        <th>Hồ sơ canh tác</th>
                        <th>Thời vụ & Trạng thái</th>
                        <th>Lần đổi gần nhất</th>
                        <th style="text-align:right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($seasons as $season)
                    @php
                        $latest = $season->lichSuTrangThai->first();
                        $qInfo = $season->getThongTinCachLy();
                        $canDeleteSeason = ($season->lo_nong_san_count == 0 && $season->nhat_ky_canh_tac_count == 0);
                    @endphp
                    <tr>
                        {{-- Season & Product --}}
                        <td>
                            <a href="{{ route('chain.seasons.show', $season->mua_vu_id) }}" class="font-bold text-stone-900 hover:text-emerald-700 text-sm block">
                                {{ $season->ten_mua_vu }}
                            </a>
                            <div class="f2f-table-sub">
                                <code class="text-stone-600">{{ $season->ma_mua_vu }}</code> · {{ $season->sanPham?->ten_san_pham ?? 'Chưa xác định' }}
                                @if($season->giong_cay_trong)
                                    · <span class="italic text-stone-500">Giống: {{ $season->giong_cay_trong }}</span>
                                @endif
                            </div>
                        </td>

                        {{-- Planting Zone --}}
                        <td>
                            <strong>{{ $season->vungTrong?->ten_vung_trong ?? '—' }}</strong>
                            <div class="f2f-table-sub">
                                Mã PUC: <code>{{ $season->vungTrong?->ma_vung_trong ?? 'N/A' }}</code>
                                @if($season->vungTrong?->toChuc)
                                    · {{ $season->vungTrong->toChuc->ten_to_chuc }}
                                @endif
                            </div>
                        </td>

                        {{-- Profile / Counts --}}
                        <td>
                            <div class="flex items-center gap-2">
                                <div>
                                    <span class="font-semibold text-stone-800">{{ $season->nhat_ky_canh_tac_count }}</span>
                                    <span class="text-xs text-stone-500">nhật ký</span>
                                </div>
                                <span>·</span>
                                <div>
                                    <span class="font-semibold text-stone-800">{{ $season->lo_nong_san_count }}</span>
                                    <span class="text-xs text-stone-500">lô thu hoạch</span>
                                </div>
                            </div>
                            @if($season->san_luong_du_kien)
                                <div class="f2f-table-sub">
                                    Dự kiến: {{ number_format($season->san_luong_du_kien, 0) }} {{ $season->don_vi_san_luong ?? 'kg' }}
                                </div>
                            @endif
                        </td>

                        {{-- Schedule & Status --}}
                        <td>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="f2f-badge {{ $season->getTrangThaiBadgeClass() }}">
                                    {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$season->trang_thai] ?? $season->trang_thai }}
                                </span>
                                @if(!empty($qInfo['dang_cach_ly']))
                                    <span class="f2f-badge-danger text-[10px]" title="Đang cách ly thuốc: {{ $qInfo['ten_vat_tu'] ?? '' }}">
                                        ⚠️ Cách ly PHI
                                    </span>
                                @else
                                    <span class="f2f-badge-success text-[10px]">
                                        ✓ An toàn PHI
                                    </span>
                                @endif
                            </div>
                            <div class="f2f-table-sub" style="margin-top:3px">
                                Bắt đầu: {{ $season->ngay_gieo?->format('d/m/Y') ?? '—' }} → Thu dự kiến: {{ $season->ngay_thu_hoach_du_kien?->format('d/m/Y') ?? '—' }}
                            </div>
                        </td>

                        {{-- Last Transition --}}
                        <td>
                            @if($latest)
                                <div class="text-xs text-stone-800">{{ $latest->thoi_gian?->format('d/m/Y H:i') }}</div>
                                <div class="f2f-table-sub">{{ $latest->nguoiThucHien?->ho_ten ?? 'Hệ thống' }} · {{ $latest->ly_do }}</div>
                            @else
                                <span class="f2f-table-sub">Chưa có thay đổi</span>
                            @endif
                        </td>

                        {{-- Action Buttons --}}
                        <td>
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                {{-- View Detail Button --}}
                                <a href="{{ route('chain.seasons.show', $season->mua_vu_id) }}" class="f2f-btn-ghost f2f-btn-sm" title="Xem chi tiết mùa vụ">
                                    Chi tiết
                                </a>

                                {{-- Edit Button --}}
                                <a href="{{ route('chain.seasons.edit', $season->mua_vu_id) }}" class="f2f-btn-ghost f2f-btn-sm" title="Chỉnh sửa thông tin mùa vụ">
                                    Sửa
                                </a>

                                {{-- View Lots Button --}}
                                @if($season->lo_nong_san_count > 0)
                                    <a href="{{ route('chain.lots.index', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-ghost f2f-btn-sm" title="Xem các lô hàng thu hoạch từ vụ này">
                                        {{ $season->lo_nong_san_count }} lô
                                    </a>
                                @endif

                                {{-- Lifecycle Actions --}}
                                @if($season->trang_thai === 'DANG_CANH_TAC')
                                    <form method="POST" action="{{ route('chain.seasons.start-harvest', $season->mua_vu_id) }}" onsubmit="return confirm('Xác nhận chuyển mùa vụ sang giai đoạn ĐANG THU HOẠCH?')">
                                        @csrf
                                        <button type="submit" class="f2f-btn-secondary f2f-btn-sm text-emerald-700 font-medium" title="Chuyển sang giai đoạn thu hoạch">
                                            Thu hoạch
                                        </button>
                                    </form>
                                @elseif($season->trang_thai === 'DANG_THU_HOACH')
                                    <details class="relative inline-block text-left">
                                        <summary class="f2f-btn-primary f2f-btn-sm list-none cursor-pointer" title="Nghiệm thu & Kết thúc mùa vụ">
                                            Kết thúc
                                        </summary>
                                        <div class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-stone-200 p-3 z-20">
                                            <form method="POST" action="{{ route('chain.seasons.complete', $season->mua_vu_id) }}">
                                                @csrf
                                                <div class="font-semibold text-xs text-stone-800 mb-1">Nghiệm thu & Kết thúc vụ</div>
                                                <p class="text-[11px] text-stone-500 mb-2">Sau khi kết thúc, hồ sơ chuyển sang lưu trữ kiểm toán.</p>
                                                <textarea name="ly_do" rows="2" class="f2f-input text-xs w-full" required placeholder="Ghi chú kết thúc (VD: Đã thu hoạch xong sản lượng)..."></textarea>
                                                <button type="submit" class="f2f-btn-primary f2f-btn-sm w-full mt-2">Xác nhận hoàn thành</button>
                                            </form>
                                        </div>
                                    </details>
                                @elseif($season->trang_thai === 'HOAN_THANH')
                                    <details class="relative inline-block text-left">
                                        <summary class="f2f-btn-secondary f2f-btn-sm list-none cursor-pointer" title="Mở lại mùa vụ để bổ sung đợt thu hoạch">
                                            Mở lại
                                        </summary>
                                        <div class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-stone-200 p-3 z-20">
                                            <form method="POST" action="{{ route('chain.seasons.reopen', $season->mua_vu_id) }}">
                                                @csrf
                                                <div class="font-semibold text-xs text-stone-800 mb-1">Mở lại để thu hoạch thêm</div>
                                                <p class="text-[11px] text-stone-500 mb-2">Chỉ mở lại khi người sản xuất cần bổ sung đợt thu hoạch.</p>
                                                <textarea name="ly_do" rows="2" class="f2f-input text-xs w-full" required placeholder="Lý do mở lại (VD: Cần bổ sung đợt thu hoạch vét)..."></textarea>
                                                <button type="submit" class="f2f-btn-primary f2f-btn-sm w-full mt-2">Xác nhận mở lại</button>
                                            </form>
                                        </div>
                                    </details>
                                @endif

                                {{-- Delete Button (Protected) --}}
                                @if($canDeleteSeason)
                                    <form method="POST" action="{{ route('chain.seasons.destroy', $season->mua_vu_id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn mùa vụ này? Hành động này không thể hoàn tác.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="f2f-btn-ghost f2f-btn-sm text-rose-600 hover:text-rose-800 hover:bg-rose-50" title="Xóa mùa vụ chưa phát sinh dữ liệu">
                                            Xóa
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="f2f-btn-ghost f2f-btn-sm text-stone-400 cursor-not-allowed opacity-60" title="Không thể xóa vì mùa vụ đã phát sinh lô nông sản hoặc nhật ký canh tác trong chuỗi truy xuất">
                                        Xóa
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="f2f-table-empty">
                            <div class="py-6 text-center">
                                <p class="text-sm text-stone-600 font-medium">Không tìm thấy mùa vụ nào phù hợp với điều kiện tìm kiếm.</p>
                                <p class="text-xs text-stone-400 mt-1">Hãy thử xóa bộ lọc hoặc khai báo thêm mùa vụ mới.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($seasons->hasPages())
            <div class="f2f-pagination-wrap">
                {{ $seasons->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

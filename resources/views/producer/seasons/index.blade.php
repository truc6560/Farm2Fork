@extends('layouts.dashboard', ['title' => 'Quản lý mùa vụ', 'breadcrumb' => 'Farm2Fork · Người sản xuất'])

@section('content')
<div class="f2f-stat-grid f2f-producer-summary">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng số mùa vụ</span>
        <strong class="f2f-stat-value text-emerald-800">{{ $totalSeasons }}</strong>
        <span class="f2f-stat-hint">Toàn bộ các vụ mùa</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Đang canh tác</span>
        <strong class="f2f-stat-value text-emerald-600">{{ $activeSeasons }}</strong>
        <span class="f2f-stat-hint">Đang chăm sóc & ghi chép</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Đang thu hoạch</span>
        <strong class="f2f-stat-value text-amber-600">{{ $harvestingSeasons }}</strong>
        <span class="f2f-stat-hint">Đang thu gom & đóng lô</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Đã hoàn thành</span>
        <strong class="f2f-stat-value text-slate-600">{{ $completedSeasons }}</strong>
        <span class="f2f-stat-hint">Đã kết thúc vụ</span>
    </div>
</div>

<div class="f2f-card">
    <div class="f2f-card-toolbar f2f-producer-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách mùa vụ canh tác</h2>
            <p class="f2f-subtitle">Theo dõi chu kỳ sinh trưởng của cây trồng, ghi chép nhật ký phân thuốc và sản lượng thu hoạch.</p>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc mùa vụ" aria-controls="season-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.seasons.create') }}" class="f2f-btn-primary">
                + Thiết lập mùa vụ mới
            </a>
        </div>
    </div>

    <!-- Responsive Filter Form -->
    <div class="f2f-mobile-filter-panel" id="season-filters" data-mobile-filter>
        <div class="f2f-mobile-filter-head"><h3>Bộ lọc mùa vụ</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm mã vụ, tên vụ, loại nông sản, giống cây...">
            </div>
            <div>
                <select class="f2f-input" name="vung_trong_id">
                    <option value="">-- Tất cả vùng trồng --</option>
                    @foreach($plantingZones as $zone)
                        <option value="{{ $zone->vung_trong_id }}" @selected(($filters['vung_trong_id'] ?? '') == $zone->vung_trong_id)>
                            {{ $zone->ten_vung_trong }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="san_pham_id">
                    <option value="">-- Tất cả loại nông sản --</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->san_pham_id }}" @selected(($filters['san_pham_id'] ?? '') == $prod->san_pham_id)>
                            {{ $prod->ten_san_pham }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="trang_thai">
                    <option value="">-- Trạng thái vụ --</option>
                    <option value="CHUAN_BI" @selected(($filters['trang_thai'] ?? '') === 'CHUAN_BI')>Chuẩn bị</option>
                    <option value="DANG_CANH_TAC" @selected(($filters['trang_thai'] ?? '') === 'DANG_CANH_TAC')>Đang canh tác</option>
                    <option value="DANG_THU_HOACH" @selected(($filters['trang_thai'] ?? '') === 'DANG_THU_HOACH')>Đang thu hoạch</option>
                    <option value="HOAN_THANH" @selected(($filters['trang_thai'] ?? '') === 'HOAN_THANH')>Hoàn thành</option>
                    <option value="HUY" @selected(($filters['trang_thai'] ?? '') === 'HUY')>Hủy</option>
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters)))
                    <a href="{{ route('producer.seasons.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <div class="f2f-mobile-season-section">
        @include('producer.partials.mobile-season-list', ['mobileSeasons' => $seasons])
    </div>

    <!-- Table on Desktop / Card on Mobile -->
    <div class="f2f-table-wrap f2f-desktop-season-list">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã & Tên mùa vụ</th>
                    <th>Vùng trồng</th>
                    <th>Nông sản & Giống</th>
                    <th>Thời gian vụ</th>
                    <th>Sản lượng dự kiến</th>
                    <th>Nhật ký</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($seasons as $s)
                <tr>
                    <td>
                        <a href="{{ route('producer.seasons.show', $s->mua_vu_id) }}" class="f2f-table-link">
                            <strong>{{ $s->ten_mua_vu }}</strong>
                        </a>
                        <div class="f2f-table-sub">
                            <code>{{ $s->ma_mua_vu }}</code>
                            @if($s->dien_tich_canh_tac_ha)
                                · {{ number_format($s->dien_tich_canh_tac_ha, 2) }} ha
                            @endif
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('producer.planting-zones.show', $s->vung_trong_id) }}" class="f2f-link">
                            {{ $s->vungTrong->ten_vung_trong ?? '—' }}
                        </a>
                        <div class="f2f-table-sub">{{ $s->vungTrong->loai_chung_nhan ?? 'Chưa CN' }}</div>
                    </td>
                    <td>
                        <strong>{{ $s->sanPham->ten_san_pham ?? '—' }}</strong>
                        <div class="f2f-table-sub">{{ $s->giong_cay_trong ? 'Giống: ' . $s->giong_cay_trong : '' }}</div>
                    </td>
                    <td>
                        <div>Gieo: {{ $s->ngay_gieo ? $s->ngay_gieo->format('d/m/Y') : '—' }}</div>
                        <div class="f2f-table-sub">Dự kiến thu: {{ $s->ngay_thu_hoach_du_kien ? $s->ngay_thu_hoach_du_kien->format('d/m/Y') : '—' }}</div>
                    </td>
                    <td>
                        <strong>{{ $s->san_luong_du_kien ? number_format($s->san_luong_du_kien, 0) . ' ' . $s->don_vi_san_luong : '—' }}</strong>
                    </td>
                    <td>
                        <span class="f2f-tag">{{ $s->nhatKyCanhTac->count() }} ghi chép</span>
                    </td>
                    <td>
                        <span class="f2f-badge {{ $s->getTrangThaiBadgeClass() }}">
                            {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$s->trang_thai] ?? $s->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div class="f2f-actions-cell">
                            <a href="{{ route('producer.farming-logs.create', ['mua_vu_id' => $s->mua_vu_id]) }}" class="f2f-btn-primary f2f-btn-sm" title="Ghi nhật ký">
                                + Nhật ký
                            </a>
                            <a href="{{ route('producer.seasons.show', $s->mua_vu_id) }}" class="f2f-btn-ghost f2f-btn-sm" title="Chi tiết vụ">
                                Chi tiết
                            </a>
                            <a href="{{ route('producer.seasons.edit', $s->mua_vu_id) }}" class="f2f-btn-ghost f2f-btn-sm" title="Sửa">
                                Sửa
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Không có mùa vụ nào thỏa mãn điều kiện lọc.</p>
                            <a href="{{ route('producer.seasons.create') }}" class="f2f-btn-primary" style="margin-top:8px">Thiết lập mùa vụ mới</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="f2f-pagination-wrap f2f-season-pagination">
        {{ $seasons->links() }}
    </div>
</div>
@endsection


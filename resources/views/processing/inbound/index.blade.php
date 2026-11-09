@extends('layouts.dashboard', ['title' => 'Tiếp nhận nguyên liệu', 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế'])

@section('content')
<div class="f2f-stat-grid f2f-processing-stats">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng lô nguyên liệu</span>
        <strong class="f2f-stat-value text-slate-800">{{ $totalRaw }}</strong>
        <span class="f2f-stat-hint">Toàn bộ lô chuyển giao</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Chờ tiếp nhận</span>
        <strong class="f2f-stat-value text-amber-600">{{ $pendingReceipt }}</strong>
        <span class="f2f-stat-hint">Lô mới từ vùng trồng</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Chờ / Đang sơ chế</span>
        <strong class="f2f-stat-value text-emerald-600">{{ $readyToProcess }}</strong>
        <span class="f2f-stat-hint">Đã nhận vào xưởng</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Đã sơ chế xong</span>
        <strong class="f2f-stat-value text-blue-600">{{ $processed }}</strong>
        <span class="f2f-stat-hint">Đã tạo thành phẩm</span>
    </div>
</div>

<div class="f2f-card" style="margin-top:16px">
    <div class="f2f-card-toolbar f2f-processing-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách nguyên liệu đầu vào</h2>
            <p class="f2f-subtitle">Tiếp nhận và kiểm tra các lô nông sản thu hoạch từ người sản xuất trước khi đưa vào dây chuyền sơ chế.</p>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc nguyên liệu" aria-controls="inbound-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
    </div>

    <!-- Filter form -->
    <div class="f2f-mobile-filter-panel" id="inbound-filters" data-mobile-filter>
        <div class="f2f-mobile-filter-head"><h3>Bộ lọc nguyên liệu</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm mã lô, mã nhà cung cấp...">
            </div>
            <div>
                <select class="f2f-input" name="san_pham_id">
                    <option value="">-- Tất cả nông sản --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->san_pham_id }}" @selected(($filters['san_pham_id'] ?? '') == $p->san_pham_id)>
                            {{ $p->ten_san_pham }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="trang_thai">
                    <option value="">-- Trạng thái tiếp nhận --</option>
                    <option value="MOI_TAO" @selected(($filters['trang_thai'] ?? '') === 'MOI_TAO')>Chờ tiếp nhận (Mới tạo)</option>
                    <option value="CHO_SO_CHE" @selected(($filters['trang_thai'] ?? '') === 'CHO_SO_CHE')>Đã tiếp nhận (Chờ sơ chế)</option>
                    <option value="DA_SO_CHE" @selected(($filters['trang_thai'] ?? '') === 'DA_SO_CHE')>Đã hoàn thành sơ chế</option>
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters ?? [])))
                    <a href="{{ route('processing.inbound.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <div class="f2f-mobile-inbound-list" aria-label="Danh sách nguyên liệu đầu vào">
        @forelse($lots as $lot)
            <a class="f2f-mobile-inbound-row" href="{{ route('processing.inbound.show', $lot->lo_nong_san_id) }}">
                <span class="f2f-mobile-inbound-copy">
                    <strong>{{ $lot->muaVu?->ma_mua_vu ?: $lot->ma_lo }}</strong>
                    <small>{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</small>
                </span>
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
            </a>
        @empty
            <div class="f2f-mobile-inbound-empty">Không có nguyên liệu đầu vào phù hợp với bộ lọc.</div>
        @endforelse
    </div>

    <!-- Table of Raw Material Lots -->
    <div class="f2f-table-wrap f2f-desktop-inbound-list">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã lô nguyên liệu</th>
                    <th>Nông sản</th>
                    <th>Nguồn gốc nông hộ / Mùa vụ</th>
                    <th>Khối lượng hiện có</th>
                    <th>Ngày thu hoạch</th>
                    <th>Kho lưu trữ</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($lots as $lot)
                <tr>
                    <td>
                        <a href="{{ route('processing.inbound.show', $lot->lo_nong_san_id) }}" class="f2f-table-link">
                            <code>{{ $lot->ma_lo }}</code>
                        </a>
                        @if($lot->ma_lo_nha_cung_cap)
                            <div class="f2f-table-sub">NCC: {{ $lot->ma_lo_nha_cung_cap }}</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $lot->sanPham->ten_san_pham ?? '—' }}</strong>
                        <div class="f2f-table-sub">{{ $lot->sanPham->nhom_san_pham ?? '' }}</div>
                    </td>
                    <td>
                        @if($lot->muaVu)
                            <div>{{ $lot->muaVu->ten_mua_vu }}</div>
                            <div class="f2f-table-sub">{{ $lot->muaVu->vungTrong->ten_vung_trong ?? '' }} ({{ $lot->muaVu->vungTrong->loai_chung_nhan ?? 'Chưa CN' }})</div>
                        @elseif($lot->toChucNguon)
                            <div>{{ $lot->toChucNguon->ten_to_chuc }}</div>
                            <div class="f2f-table-sub">Cung ứng ngoài</div>
                        @else
                            <span class="f2f-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <strong class="{{ (float)$lot->so_luong > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                            {{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}
                        </strong>
                    </td>
                    <td>{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : '—' }}</td>
                    <td>
                        {{ $lot->khoLanh->ten_kho ?? 'Chưa nhập kho' }}
                    </td>
                    <td>
                        <span class="f2f-badge {{ $lot->getTrangThaiBadgeClass() }}">
                            {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div class="f2f-actions-cell">
                            @if($lot->trang_thai === 'MOI_TAO')
                                <a href="{{ route('processing.inbound.show', $lot->lo_nong_san_id) }}" class="f2f-btn-primary f2f-btn-sm">
                                    Tiếp nhận
                                </a>
                            @elseif($lot->trang_thai === 'CHO_SO_CHE' && (float)$lot->so_luong > 0)
                                <a href="{{ route('processing.batches.create', ['nguyen_lieu_id' => $lot->lo_nong_san_id]) }}" class="f2f-btn-primary f2f-btn-sm">
                                    Sơ chế
                                </a>
                            @endif
                            <a href="{{ route('processing.inbound.show', $lot->lo_nong_san_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                Chi tiết
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có lô nguyên liệu nào thỏa mãn điều kiện lọc.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="f2f-pagination-wrap">
        {{ $lots->links() }}
    </div>
</div>
@endsection


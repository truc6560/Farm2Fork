@extends('layouts.dashboard', ['title' => 'Lô nông sản thu hoạch', 'breadcrumb' => 'Farm2Fork · Người sản xuất'])

@section('content')
<div class="f2f-card">
    <div class="f2f-card-toolbar f2f-producer-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách lô nông sản thu hoạch</h2>
            <p class="f2f-subtitle">Các lô nông sản nguyên liệu thu hoạch từ đồng ruộng sẵn sàng bàn giao sang sơ chế, kiểm định và đóng gói.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.lots.create') }}" class="f2f-btn-primary">
                + Tạo lô thu hoạch mới
            </a>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc lô thu hoạch" aria-controls="producer-lot-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
    </div>

    <!-- Filter form -->
    <div class="f2f-mobile-filter-panel" id="producer-lot-filters" data-mobile-filter>
        <div class="f2f-mobile-filter-head"><h3>Bộ lọc lô thu hoạch</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm mã lô, mã NCC, loại nông sản...">
            </div>
            <div>
                <select class="f2f-input" name="san_pham_id">
                    <option value="">-- Tất cả loại nông sản --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->san_pham_id }}" @selected(($filters['san_pham_id'] ?? '') == $p->san_pham_id)>
                            {{ $p->ten_san_pham }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="trang_thai">
                    <option value="">-- Tất cả trạng thái --</option>
                    @foreach(\App\Models\LoNongSan::TRANG_THAI_LABELS as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['trang_thai'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters)))
                    <a href="{{ route('producer.lots.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <!-- Table of Lots -->
    <div class="f2f-table-wrap">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã lô nông sản</th>
                    <th>Nông sản</th>
                    <th>Mùa vụ & Vùng trồng</th>
                    <th>Khối lượng thu hoạch</th>
                    <th>Ngày thu hoạch</th>
                    <th>Hạn sử dụng</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($lots as $lot)
                <tr>
                    <td>
                        <a href="{{ route('producer.lots.show', $lot->lo_nong_san_id) }}" class="f2f-table-link">
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
                            <a href="{{ route('producer.seasons.show', $lot->mua_vu_id) }}" class="f2f-link">
                                {{ $lot->muaVu->ten_mua_vu }}
                            </a>
                            <div class="f2f-table-sub">{{ $lot->muaVu->vungTrong->ten_vung_trong ?? '' }}</div>
                        @else
                            <span class="f2f-muted">Thu mua ngoài</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</strong>
                    </td>
                    <td>{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : '—' }}</td>
                    <td>{{ $lot->han_su_dung ? $lot->han_su_dung->format('d/m/Y') : '—' }}</td>
                    <td>
                        <span class="f2f-badge f2f-badge-active">
                            {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('producer.lots.show', $lot->lo_nong_san_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                            Chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có lô thu hoạch nào.</p>
                            <a href="{{ route('producer.lots.create') }}" class="f2f-btn-primary" style="margin-top:8px">Tạo lô thu hoạch mới</a>
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


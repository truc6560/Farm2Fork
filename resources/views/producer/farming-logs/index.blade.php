@extends('layouts.dashboard', ['title' => 'Nhật ký canh tác', 'breadcrumb' => 'Farm2Fork · Người sản xuất'])

@section('content')
<div class="f2f-stat-grid f2f-producer-summary">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng lượt ghi nhật ký</span>
        <strong class="f2f-stat-value text-emerald-800">{{ $totalLogs }}</strong>
        <span class="f2f-stat-hint">Hồ sơ nhật ký đồng ruộng</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Bón phân</span>
        <strong class="f2f-stat-value text-emerald-600">{{ $fertilizeCount }}</strong>
        <span class="f2f-stat-hint">Bón lót & bón thúc</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Phun thuốc BVTV</span>
        <strong class="f2f-stat-value text-amber-600">{{ $pesticideCount }}</strong>
        <span class="f2f-stat-hint">Kiểm soát cách ly PHI</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Thu hoạch</span>
        <strong class="f2f-stat-value text-purple-700">{{ $harvestCount }}</strong>
        <span class="f2f-stat-hint">Thu gom nông sản</span>
    </div>
</div>

<div class="f2f-card">
    <div class="f2f-card-toolbar f2f-producer-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Sổ nhật ký canh tác điện tử</h2>
            <p class="f2f-subtitle">Ghi chép minh bạch mọi thao tác ngoài đồng ruộng phục vụ truy xuất nguồn gốc và kiểm toán VietGAP.</p>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc mùa vụ" aria-controls="log-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.farming-logs.create') }}" class="f2f-btn-primary">
                + Ghi nhật ký hoạt động
            </a>
        </div>
    </div>

    <!-- Filter form -->
    <div class="f2f-mobile-filter-panel" id="log-filters" data-mobile-filter>
        <div class="f2f-mobile-filter-head"><h3>Bộ lọc nhật ký</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <select class="f2f-input" name="mua_vu_id">
                    <option value="">-- Tất cả mùa vụ --</option>
                    @foreach($seasons as $s)
                        <option value="{{ $s->mua_vu_id }}" @selected(($filters['mua_vu_id'] ?? '') == $s->mua_vu_id)>
                            {{ $s->ten_mua_vu }} ({{ $s->vungTrong->ten_vung_trong }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="loai_hoat_dong">
                    <option value="">-- Tất cả loại hoạt động --</option>
                    @foreach(\App\Models\NhatKyCanhTac::HOAT_DONG_LABELS as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['loai_hoat_dong'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="trang_thai">
                    <option value="">-- Trạng thái --</option>
                    <option value="DA_XAC_NHAN" @selected(($filters['trang_thai'] ?? '') === 'DA_XAC_NHAN')>Đã xác nhận</option>
                    <option value="NHAP" @selected(($filters['trang_thai'] ?? '') === 'NHAP')>Bản nháp</option>
                    <option value="DA_THAY_THE" @selected(($filters['trang_thai'] ?? '') === 'DA_THAY_THE')>Đã thay thế</option>
                </select>
            </div>
            <div>
                <input class="f2f-input" type="date" name="tu_ngay" value="{{ $filters['tu_ngay'] ?? '' }}" aria-label="Từ ngày thực hiện">
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters)))
                    <a href="{{ route('producer.farming-logs.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <div class="f2f-mobile-season-section">
        <h3>Chọn mùa vụ để ghi nhật ký</h3>
        @include('producer.partials.mobile-season-list', ['mobileSeasons' => $mobileSeasons])
    </div>

    <!-- Table of Logs -->
    <div class="f2f-table-wrap f2f-desktop-log-list">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Thời gian thực hiện</th>
                    <th>Hoạt động</th>
                    <th>Mùa vụ & Vùng trồng</th>
                    <th>Vật tư sử dụng</th>
                    <th>Người thực hiện</th>
                    <th>Minh chứng</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>
                        <a href="{{ route('producer.farming-logs.show', $log->nhat_ky_id) }}" class="f2f-table-link">
                            <strong>{{ $log->ngay_thuc_hien ? $log->ngay_thuc_hien->format('d/m/Y H:i') : '—' }}</strong>
                        </a>
                    </td>
                    <td>
                        <span class="f2f-tag {{ $log->getHoatDongBadgeColor() }}">
                            {{ \App\Models\NhatKyCanhTac::HOAT_DONG_LABELS[$log->loai_hoat_dong] ?? $log->loai_hoat_dong }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('producer.seasons.show', $log->mua_vu_id) }}" class="f2f-link">
                            {{ $log->muaVu->ten_mua_vu }}
                        </a>
                        <div class="f2f-table-sub">{{ $log->muaVu->vungTrong->ten_vung_trong ?? '' }}</div>
                    </td>
                    <td>
                        @if($log->chiTietVatTu->isNotEmpty())
                            @foreach($log->chiTietVatTu as $item)
                                <div style="font-size:13px">
                                    • {{ $item->vatTu->ten_thuong_mai ?? 'Vật tư' }}:
                                    <strong>{{ number_format($item->lieu_luong, 1) }} {{ $item->don_vi_tinh }}</strong>
                                    @if($item->thoi_gian_cach_ly_ap_dung_ngay > 0)
                                        <small class="text-amber-700">({{ $item->thoi_gian_cach_ly_ap_dung_ngay }}d PHI)</small>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <span class="f2f-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $log->nguoiThucHien->ho_ten ?? '—' }}</td>
                    <td>
                        @if($log->tep_minh_chung)
                            <a href="{{ asset('storage/' . $log->tep_minh_chung) }}" target="_blank" class="f2f-link" title="Xem ảnh">
                                Xem ảnh
                            </a>
                        @else
                            <span class="f2f-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="f2f-badge {{ $log->trang_thai === 'DA_XAC_NHAN' ? 'f2f-badge-success' : ($log->trang_thai === 'DA_THAY_THE' ? 'f2f-badge-neutral' : 'f2f-badge-warning') }}">
                            {{ \App\Models\NhatKyCanhTac::TRANG_THAI_LABELS[$log->trang_thai] ?? $log->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div class="f2f-actions-cell">
                            <a href="{{ route('producer.farming-logs.show', $log->nhat_ky_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                Chi tiết
                            </a>
                            @if($log->trang_thai !== 'DA_THAY_THE')
                                <a href="{{ route('producer.farming-logs.edit', $log->nhat_ky_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                    {{ $log->trang_thai === 'DA_XAC_NHAN' ? 'Điều chỉnh' : 'Sửa' }}
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có ghi chép nhật ký nào phù hợp.</p>
                            <a href="{{ route('producer.farming-logs.create') }}" class="f2f-btn-primary" style="margin-top:8px">Ghi chép hoạt động mới</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="f2f-pagination-wrap f2f-desktop-log-list">
        {{ $logs->links() }}
    </div>
</div>
@endsection


@extends('layouts.dashboard', ['title' => 'Quản lý vùng trồng', 'breadcrumb' => 'Farm2Fork · Người sản xuất'])

@section('content')
<div class="f2f-stat-grid f2f-producer-summary">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng số vùng trồng</span>
        <strong class="f2f-stat-value text-emerald-700">{{ $totalZones }}</strong>
        <span class="f2f-stat-hint">Được quản lý trong tổ chức</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng diện tích</span>
        <strong class="f2f-stat-value text-amber-600">{{ number_format($totalArea, 2) }} <span style="font-size:16px">ha</span></strong>
        <span class="f2f-stat-hint">Quy mô diện tích đất canh tác</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Chứng nhận sắp hết hạn (30 ngày)</span>
        <strong class="f2f-stat-value {{ $expiringCerts > 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $expiringCerts }}</strong>
        <span class="f2f-stat-hint">{{ $expiringCerts > 0 ? 'Cần gia hạn gấp' : 'Tất cả còn hiệu lực' }}</span>
    </div>
</div>

<div class="f2f-card">
    <div class="f2f-card-toolbar f2f-producer-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách vùng trồng canh tác</h2>
            <p class="f2f-subtitle">Lưu trữ và theo dõi hồ sơ vùng trồng, mã số vùng trồng (MSVT) và tài liệu chứng nhận tiêu chuẩn.</p>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc vùng trồng" aria-controls="zone-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.planting-zones.create') }}" class="f2f-btn-primary">
                + Khai báo vùng trồng mới
            </a>
        </div>
    </div>

    <!-- Responsive Filter Form -->
    <div class="f2f-mobile-filter-panel" id="zone-filters" data-mobile-filter>
        <div class="f2f-mobile-filter-head"><h3>Bộ lọc vùng trồng</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo mã số PUC, địa chỉ, cây trồng...">
            </div>
            <div>
                <select class="f2f-input" name="loai_chung_nhan">
                    <option value="">-- Tất cả loại chứng nhận --</option>
                    <option value="VIETGAP" @selected(($filters['loai_chung_nhan'] ?? '') === 'VIETGAP')>VietGAP</option>
                    <option value="GLOBALGAP" @selected(($filters['loai_chung_nhan'] ?? '') === 'GLOBALGAP')>GlobalGAP</option>
                    <option value="ORGANIC" @selected(($filters['loai_chung_nhan'] ?? '') === 'ORGANIC')>Hữu cơ (Organic)</option>
                    <option value="KHAC" @selected(($filters['loai_chung_nhan'] ?? '') === 'KHAC')>Khác</option>
                </select>
            </div>
            <div>
                <select class="f2f-input" name="trang_thai">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="DANG_SU_DUNG" @selected(($filters['trang_thai'] ?? '') === 'DANG_SU_DUNG')>Đang sử dụng</option>
                    <option value="TAM_NGUNG" @selected(($filters['trang_thai'] ?? '') === 'TAM_NGUNG')>Tạm ngưng</option>
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters)))
                    <a href="{{ route('producer.planting-zones.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <div class="f2f-mobile-zone-section">
        @include('producer.partials.mobile-zone-list', ['mobileZones' => $zones])
    </div>

    <!-- Table view on Desktop / Card view on Mobile -->
    <div class="f2f-table-wrap f2f-desktop-zone-list">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã nội bộ & MSVT</th>
                    <th>Cây trồng chủ lực</th>
                    <th>Diện tích</th>
                    <th>Địa chỉ vùng trồng</th>
                    <th>Chứng nhận & Minh chứng</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($zones as $z)
                <tr>
                    <td>
                        <a href="{{ route('producer.planting-zones.show', $z->vung_trong_id) }}" class="f2f-table-link">
                            <strong class="font-mono text-stone-900">{{ $z->ma_noi_bo }}</strong>
                        </a>
                        <div class="f2f-table-sub">
                            @if($z->ma_so_vung_trong)
                                <code class="font-mono font-bold text-emerald-800">{{ $z->ma_so_vung_trong }}</code>
                            @else
                                <span class="text-amber-700 italic">Chưa có MSVT</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <strong>{{ $z->cay_trong_chinh ?? 'Chưa xác định' }}</strong>
                    </td>
                    <td>
                        <strong>{{ $z->dien_tich_ha ? number_format($z->dien_tich_ha, 3) . ' ha' : '—' }}</strong>
                    </td>
                    <td>
                        <div class="text-xs text-stone-700">{{ $z->dia_chi }}</div>
                    </td>
                    <td>
                        @if($z->tep_chung_nhan)
                            <div class="mb-1">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Có tệp minh chứng
                                </span>
                            </div>
                        @endif
                        @if($z->loai_chung_nhan)
                            <div class="f2f-cert-badge">
                                <strong>{{ $z->loai_chung_nhan }}</strong>
                                @if($z->so_chung_nhan)
                                    <small>({{ $z->so_chung_nhan }})</small>
                                @endif
                            </div>
                            @if($z->ngay_het_han_chung_nhan)
                                @if($z->isCertificateExpired())
                                    <div class="f2f-cert-expired">Đã hết hạn ({{ $z->ngay_het_han_chung_nhan->format('d/m/Y') }})</div>
                                @elseif($z->isCertificateExpiringSoon(30))
                                    <div class="f2f-cert-warning">Sắp hết hạn ({{ $z->ngay_het_han_chung_nhan->format('d/m/Y') }})</div>
                                @else
                                    <div class="f2f-cert-ok">HSD: {{ $z->ngay_het_han_chung_nhan->format('d/m/Y') }}</div>
                                @endif
                            @endif
                        @elseif(!$z->tep_chung_nhan)
                            <span class="f2f-muted">Chưa đính kèm</span>
                        @endif
                    </td>
                    <td>
                        <span class="f2f-badge {{ $z->trang_thai === 'DANG_SU_DUNG' ? 'f2f-badge-success' : 'f2f-badge-danger' }}">
                            {{ \App\Models\VungTrong::TRANG_THAI_LABELS[$z->trang_thai] ?? $z->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div class="f2f-actions-cell">
                            <a href="{{ route('producer.planting-zones.show', $z->vung_trong_id) }}" class="f2f-btn-ghost f2f-btn-sm" title="Xem chi tiết">
                                Chi tiết
                            </a>
                            <a href="{{ route('producer.planting-zones.edit', $z->vung_trong_id) }}" class="f2f-btn-ghost f2f-btn-sm" title="Chỉnh sửa">
                                Sửa
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Không tìm thấy vùng trồng nào phù hợp với bộ lọc.</p>
                            <a href="{{ route('producer.planting-zones.create') }}" class="f2f-btn-primary" style="margin-top:8px">Thêm vùng trồng mới</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="f2f-pagination-wrap f2f-zone-pagination">
        {{ $zones->links() }}
    </div>
</div>
@endsection


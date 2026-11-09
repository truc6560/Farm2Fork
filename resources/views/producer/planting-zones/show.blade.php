@extends('layouts.dashboard', ['title' => 'Vùng trồng ' . ($zone->ma_so_vung_trong ?: $zone->ma_noi_bo), 'breadcrumb' => 'Farm2Fork · Vùng trồng · Chi tiết'])

@section('content')
<section class="f2f-mobile-zone-detail">
    <a class="f2f-mobile-back" href="{{ route('producer.planting-zones.index') }}">← Danh sách vùng trồng</a>
    <div class="f2f-mobile-zone-detail-title">
        <div>
            <h2>{{ $zone->ten_vung_trong }}</h2>
            <code>{{ $zone->ma_so_vung_trong ?: $zone->ma_noi_bo }}</code>
        </div>
        <span class="f2f-badge {{ $zone->trang_thai === 'DANG_SU_DUNG' ? 'f2f-badge-success' : 'f2f-badge-danger' }}">
            {{ \App\Models\VungTrong::TRANG_THAI_LABELS[$zone->trang_thai] ?? $zone->trang_thai }}
        </span>
    </div>
    <p class="f2f-mobile-zone-summary">{{ $zone->cay_trong_chinh ?: 'Chưa xác định cây trồng' }} · {{ $zone->dien_tich_ha ? number_format($zone->dien_tich_ha, 3) . ' ha' : 'Chưa nhập diện tích' }}</p>
    <a href="{{ route('producer.seasons.create', ['vung_trong_id' => $zone->vung_trong_id]) }}" class="f2f-btn-primary f2f-mobile-zone-action">+ Tạo mùa vụ cho vùng này</a>

    <details class="f2f-mobile-zone-more">
        <summary>Xem thông tin vùng trồng</summary>
        <dl>
            <div><dt>Mã nội bộ</dt><dd>{{ $zone->ma_noi_bo }}</dd></div>
            <div><dt>MSVT</dt><dd>{{ $zone->ma_so_vung_trong ?: 'Chưa có' }}</dd></div>
            <div><dt>Địa chỉ</dt><dd>{{ $zone->dia_chi }}</dd></div>
            <div><dt>Chứng nhận</dt><dd>{{ $zone->loai_chung_nhan ?: 'Chưa khai báo' }}</dd></div>
            @if($zone->so_chung_nhan)<div><dt>Số chứng nhận</dt><dd>{{ $zone->so_chung_nhan }}</dd></div>@endif
            @if($zone->ngay_het_han_chung_nhan)<div><dt>Hạn chứng nhận</dt><dd>{{ $zone->ngay_het_han_chung_nhan->format('d/m/Y') }}</dd></div>@endif
        </dl>
        <div class="f2f-mobile-zone-more-actions">
            <a href="{{ route('producer.planting-zones.edit', $zone->vung_trong_id) }}" class="f2f-btn-ghost">Sửa thông tin</a>
            @if($zone->tep_chung_nhan)
                <a href="{{ route('producer.planting-zones.certificate', $zone->vung_trong_id) }}" target="_blank" class="f2f-btn-ghost">Xem chứng nhận</a>
            @endif
        </div>
    </details>
</section>

<section class="f2f-mobile-zone-seasons">
    <div class="f2f-mobile-section-head">
        <h3>Mùa vụ thuộc vùng ({{ $seasons->count() }})</h3>
    </div>
    @include('producer.partials.mobile-season-list', ['mobileSeasons' => $seasons])
</section>

<div class="f2f-detail-layout">
    <div class="f2f-detail-main">
        <div class="f2f-card">
            <div class="f2f-card-toolbar">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <h2 class="f2f-card-title font-mono" style="margin:0">{{ $zone->ten_vung_trong }}</h2>
                        <span class="f2f-badge {{ $zone->dinh_danh_badge['class'] }}">
                            {{ $zone->dinh_danh_badge['label'] }}
                        </span>
                        <span class="f2f-badge {{ $zone->trang_thai === 'DANG_SU_DUNG' ? 'f2f-badge-success' : 'f2f-badge-danger' }}">
                            {{ \App\Models\VungTrong::TRANG_THAI_LABELS[$zone->trang_thai] ?? $zone->trang_thai }}
                        </span>
                    </div>
                    <p class="f2f-subtitle" style="margin-top:4px">
                        Mã định danh nội bộ: <code class="font-bold text-stone-700">{{ $zone->ma_noi_bo }}</code>
                        @if($zone->ma_so_vung_trong)
                            · Mã số vùng trồng (MSVT): <code class="font-mono font-bold text-emerald-800">{{ $zone->ma_so_vung_trong }}</code>
                        @else
                            · <em>(Chưa có mã số vùng trồng chính thức, đã đính kèm tài liệu minh chứng)</em>
                        @endif
                    </p>
                </div>
                <div class="f2f-toolbar-actions">
                    <a href="{{ route('producer.seasons.create', ['vung_trong_id' => $zone->vung_trong_id]) }}" class="f2f-btn-primary">
                        + Tạo mùa vụ mới cho vùng này
                    </a>
                    <a href="{{ route('producer.planting-zones.edit', $zone->vung_trong_id) }}" class="f2f-btn-ghost">
                        Sửa thông tin
                    </a>
                    <a href="{{ route('producer.planting-zones.index') }}" class="f2f-btn-ghost">
                        Danh sách
                    </a>
                </div>
            </div>

            <div class="f2f-info-grid">
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Mã định danh nội bộ</span>
                    <strong class="f2f-info-val font-mono text-stone-800">{{ $zone->ma_noi_bo }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Tên vùng trồng</span>
                    <strong class="f2f-info-val text-stone-900">{{ $zone->ten_vung_trong }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Mã số vùng trồng (MSVT)</span>
                    <strong class="f2f-info-val font-mono text-emerald-800">{{ $zone->ma_so_vung_trong ?? 'Chưa khai báo mã chính thức' }}</strong>
                </div>
                @if($zone->ten_tinh_thanh)
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Tỉnh / Thành phố (theo mã MSVT)</span>
                    <strong class="f2f-info-val text-stone-900">{{ $zone->ten_tinh_thanh }}</strong>
                </div>
                @endif
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Cây trồng chủ lực</span>
                    <strong class="f2f-info-val text-stone-900">{{ $zone->cay_trong_chinh ?? 'Chưa xác định' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Quy mô diện tích</span>
                    <strong class="f2f-info-val text-emerald-700">{{ $zone->dien_tich_ha ? number_format($zone->dien_tich_ha, 3) . ' ha' : 'Chưa nhập' }}</strong>
                </div>
                <div class="f2f-info-item f2f-col-span-2">
                    <span class="f2f-info-label">Địa chỉ chi tiết vùng trồng</span>
                    <strong class="f2f-info-val">{{ $zone->dia_chi }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Tổ chức sở hữu</span>
                    <strong class="f2f-info-val">{{ $zone->toChuc->ten_to_chuc ?? '—' }} ({{ $zone->toChuc->ma_to_chuc ?? '' }})</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Ngày tạo hồ sơ</span>
                    <strong class="f2f-info-val">{{ $zone->created_at->format('d/m/Y H:i') }}</strong>
                </div>
            </div>
        </div>

        <!-- Seasons in this zone -->
        <div class="f2f-card" style="margin-top:16px">
            <div class="f2f-card-toolbar">
                <div>
                    <h3 class="f2f-card-title">Các mùa vụ đã và đang canh tác ({{ $seasons->count() }})</h3>
                    <p class="f2f-subtitle">Lịch sử và tiến độ canh tác các vụ mùa thuộc vùng trồng này.</p>
                </div>
                <div class="f2f-toolbar-actions">
                    <a href="{{ route('producer.seasons.create', ['vung_trong_id' => $zone->vung_trong_id]) }}" class="f2f-btn-primary f2f-btn-sm">
                        + Mùa vụ mới
                    </a>
                </div>
            </div>

            <div class="f2f-table-wrap">
                <table class="f2f-table">
                    <thead>
                        <tr>
                            <th>Mã & Tên mùa vụ</th>
                            <th>Nông sản</th>
                            <th>Thời gian vụ</th>
                            <th>Sản lượng dự kiến</th>
                            <th>Trạng thái vụ</th>
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
                                    @if($s->giong_cay_trong) · Giống: {{ $s->giong_cay_trong }} @endif
                                </div>
                            </td>
                            <td>
                                <strong>{{ $s->sanPham->ten_san_pham ?? '—' }}</strong>
                                <div class="f2f-table-sub">{{ $s->sanPham->nhom_san_pham ?? '' }}</div>
                            </td>
                            <td>
                                <div>Gieo: {{ $s->ngay_gieo ? $s->ngay_gieo->format('d/m/Y') : '—' }}</div>
                                <div class="f2f-table-sub">Thu hoạch: {{ $s->ngay_thu_hoach_du_kien ? $s->ngay_thu_hoach_du_kien->format('d/m/Y') : '—' }}</div>
                            </td>
                            <td>
                                <strong>{{ $s->san_luong_du_kien ? number_format($s->san_luong_du_kien, 0) . ' ' . $s->don_vi_san_luong : '—' }}</strong>
                            </td>
                            <td>
                                <span class="f2f-badge {{ $s->getTrangThaiBadgeClass() }}">
                                    {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$s->trang_thai] ?? $s->trang_thai }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <a href="{{ route('producer.seasons.show', $s->mua_vu_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="f2f-empty-cell">
                                <div class="f2f-empty-state">
                                    <p>Vùng trồng này chưa thiết lập mùa vụ nào.</p>
                                    <a href="{{ route('producer.seasons.create', ['vung_trong_id' => $zone->vung_trong_id]) }}" class="f2f-btn-primary f2f-btn-sm" style="margin-top:6px">
                                        Tạo mùa vụ đầu tiên ngay
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar Info: Certification & Stats -->
    <div class="f2f-detail-side">
        <div class="f2f-card">
            <h3 class="f2f-card-title" style="margin-top:0">Tài liệu & Chứng nhận</h3>

            @if($zone->tep_chung_nhan)
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-lg text-xs mb-3">
                    <span class="font-bold text-emerald-900 block mb-1">Tệp tài liệu minh chứng đã lưu:</span>
                    <span class="text-stone-600 block mb-2">{{ basename($zone->tep_chung_nhan) }}</span>
                    <a href="{{ route('producer.planting-zones.certificate', $zone->vung_trong_id) }}" target="_blank" class="f2f-btn-primary f2f-btn-sm f2f-btn-block" style="text-align:center">
                        Tải tệp minh chứng gốc
                    </a>
                </div>
            @endif

            @if($zone->loai_chung_nhan)
                <div class="f2f-cert-box">
                    <div class="f2f-cert-title">{{ $zone->loai_chung_nhan }}</div>
                    <div class="f2f-cert-row">
                        <span>Số hiệu:</span>
                        <strong>{{ $zone->so_chung_nhan ?? 'Đang cập nhật' }}</strong>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Đơn vị cấp:</span>
                        <span>{{ $zone->don_vi_cap_chung_nhan ?? '—' }}</span>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Ngày cấp:</span>
                        <span>{{ $zone->ngay_cap_chung_nhan ? $zone->ngay_cap_chung_nhan->format('d/m/Y') : '—' }}</span>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Hạn hiệu lực:</span>
                        <span>{{ $zone->ngay_het_han_chung_nhan ? $zone->ngay_het_han_chung_nhan->format('d/m/Y') : 'Không thời hạn' }}</span>
                    </div>

                    @if($zone->ngay_het_han_chung_nhan)
                        @if($zone->isCertificateExpired())
                            <div class="f2f-alert f2f-alert-error" style="margin-top:10px;padding:8px">
                                Chứng nhận đã hết hạn! Vui lòng làm thủ tục gia hạn.
                            </div>
                        @elseif($zone->isCertificateExpiringSoon(30))
                            <div class="f2f-alert f2f-alert-warning" style="margin-top:10px;padding:8px">
                                Sắp hết hạn trong {{ now()->diffInDays($zone->ngay_het_han_chung_nhan) }} ngày!
                            </div>
                        @else
                            <div class="f2f-alert f2f-alert-success" style="margin-top:10px;padding:8px">
                                Chứng nhận đang còn hiệu lực
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div class="f2f-empty-state" style="padding:16px 8px">
                    <p style="margin:4px 0">Chưa khai báo tiêu chuẩn an toàn thực phẩm (VietGAP/GlobalGAP...).</p>
                    <a href="{{ route('producer.planting-zones.edit', $zone->vung_trong_id) }}" class="f2f-link">Cập nhật ngay</a>
                </div>
            @endif

            <p class="text-[11px] text-stone-500 mt-3 pt-3 border-t border-stone-100">
                Lưu ý: Hồ sơ vùng trồng được lưu trữ theo khai báo của người sản xuất, phục vụ quản lý nội bộ chuỗi cung ứng và truy xuất nguồn gốc.
            </p>
        </div>
    </div>
</div>
@endsection

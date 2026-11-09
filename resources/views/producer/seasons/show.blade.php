@extends('layouts.dashboard', ['title' => $season->ten_mua_vu, 'breadcrumb' => 'Farm2Fork · Mùa vụ · Chi tiết'])

@section('content')
@php $seasonLocked = in_array($season->trang_thai, ['HOAN_THANH', 'HUY'], true); @endphp
<!-- Quarantine PHI Safety Alert if active -->
@if(!empty($quarantineInfo['dang_cach_ly']) || !empty($quarantineAlerts))
    <div class="f2f-alert f2f-alert-warning" style="border-width:2px;border-color:#f59e0b;margin-bottom:16px">
        <div>
            <strong>CẢNH BÁO THỜI GIAN CÁCH LY (PHI):</strong><br>
            @if(!empty($quarantineInfo['ten_vat_tu']))
                Vật tư <strong>{{ $quarantineInfo['ten_vat_tu'] }}</strong> được sử dụng vào ngày
                <strong>{{ data_get($quarantineInfo, 'ngay_su_dung')?->format('d/m/Y H:i') ?? 'Chưa xác định' }}</strong>
                có thời gian cách ly là <strong>{{ data_get($quarantineInfo, 'so_ngay_cach_ly', 0) }} ngày</strong>.<br>
                <span>Thời điểm thu hoạch an toàn sớm nhất:
                    <strong>{{ data_get($quarantineInfo, 'ngay_het_cach_ly')?->format('d/m/Y H:i') ?? 'Chưa xác định' }}</strong>
                    (Còn lại <strong>{{ data_get($quarantineInfo, 'so_ngay_con_lai', 0) }} ngày</strong>).
                    Nghiêm cấm thu hoạch trước thời điểm này để đảm bảo tiêu chuẩn an toàn thực phẩm.
                </span>
            @elseif(!empty($quarantineAlerts))
                @foreach($quarantineAlerts as $alert)
                    <div>
                        Vật tư <strong>{{ $alert['vat_tu'] }}</strong> được phun vào ngày <strong>{{ $alert['ngay_phun'] }}</strong> có thời gian cách ly là <strong>{{ $alert['so_ngay'] }} ngày</strong>.
                        Thời điểm thu hoạch an toàn: <strong>{{ $alert['ngay_an_toan'] }}</strong> (Còn lại <strong>{{ $alert['con_lai'] }}</strong>).
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endif

@if($season->trang_thai === 'DANG_THU_HOACH')
    <div class="f2f-card" style="margin-bottom:16px;border:1px solid #b8d4bf">
        <h2 class="f2f-card-title" style="margin-top:0">Kết thúc mùa vụ</h2>
        <p class="f2f-subtitle">Chỉ thực hiện khi đã thu hoạch xong toàn bộ. Sau khi kết thúc, nhật ký và lô thu hoạch của vụ này sẽ chuyển sang chế độ chỉ xem.</p>
        <form method="POST" action="{{ route('producer.seasons.complete', $season->mua_vu_id) }}" class="f2f-form-body" onsubmit="return confirm('Xác nhận đã thu hoạch xong toàn bộ và kết thúc mùa vụ này?')">
            @csrf
            <label class="f2f-label" for="season-complete-reason">Ghi chú kết thúc <span class="text-rose-600">*</span></label>
            <textarea class="f2f-input" id="season-complete-reason" name="ly_do" rows="3" maxlength="1000" required placeholder="Ví dụ: Đã thu hoạch đủ 3 đợt, sản lượng thực tế đã được ghi nhận.">{{ old('ly_do') }}</textarea>
            <button class="f2f-btn-primary" type="submit" style="margin-top:10px">Kết thúc mùa vụ</button>
        </form>
    </div>
@elseif($season->trang_thai === 'HOAN_THANH')
    <div class="f2f-alert f2f-alert-success" style="margin-bottom:16px">
        <div><strong>Mùa vụ đã hoàn thành.</strong><br>Hồ sơ đang ở chế độ chỉ xem. Nếu cần ghi nhận thêm đợt thu hoạch, quản lý chuỗi phải mở lại và ghi rõ lý do.</div>
    </div>
@endif

<section class="f2f-mobile-season-detail">
    <a class="f2f-mobile-back" href="{{ route('producer.farming-logs.index') }}">← Danh sách mùa vụ</a>
    <div class="f2f-mobile-season-detail-title">
        <div><h2>{{ $season->ten_mua_vu }}</h2><code>{{ $season->ma_mua_vu }}</code></div>
        <span class="f2f-badge {{ $season->getTrangThaiBadgeClass() }}">{{ \App\Models\MuaVu::TRANG_THAI_LABELS[$season->trang_thai] ?? $season->trang_thai }}</span>
    </div>
    <p class="f2f-mobile-season-dates">{{ $season->ngay_gieo?->format('d/m/Y') ?? 'Chưa có ngày gieo' }} → {{ $season->ngay_thu_hoach_du_kien?->format('d/m/Y') ?? 'Chưa có ngày thu dự kiến' }}</p>
    @if(!$seasonLocked)<a href="{{ route('producer.farming-logs.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary f2f-mobile-log-action">+ Ghi nhật ký canh tác</a>@endif
    <details class="f2f-mobile-season-more">
        <summary>Xem thông tin mùa vụ</summary>
        <dl>
            <div><dt>Vùng trồng</dt><dd>{{ $season->vungTrong->ten_vung_trong }}</dd></div>
            <div><dt>Nông sản</dt><dd>{{ $season->sanPham->ten_san_pham ?? '—' }}</dd></div>
            <div><dt>Giống cây</dt><dd>{{ $season->giong_cay_trong ?? '—' }}</dd></div>
            <div><dt>Diện tích</dt><dd>{{ $season->dien_tich_canh_tac_ha ? number_format($season->dien_tich_canh_tac_ha, 2) . ' ha' : '—' }}</dd></div>
            <div><dt>Sản lượng dự kiến</dt><dd>{{ $season->san_luong_du_kien ? number_format($season->san_luong_du_kien, 0) . ' ' . $season->don_vi_san_luong : '—' }}</dd></div>
        </dl>
        <div class="f2f-mobile-season-more-actions">
            @if(!$seasonLocked)<a href="{{ route('producer.seasons.edit', $season->mua_vu_id) }}" class="f2f-btn-ghost">Sửa mùa vụ</a>@endif
            <a href="{{ route('producer.planting-zones.show', $season->vung_trong_id) }}" class="f2f-btn-ghost">Xem vùng trồng</a>
            @if(!$seasonLocked && empty($quarantineInfo['dang_cach_ly']))
                <a href="{{ route('producer.lots.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-ghost">Tạo lô thu hoạch</a>
            @endif
        </div>
    </details>
</section>

<div class="f2f-detail-layout">
    <div class="f2f-detail-main">
        <div class="f2f-card">
            <div class="f2f-card-toolbar">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <h2 class="f2f-card-title" style="margin:0">{{ $season->ten_mua_vu }}</h2>
                        <span class="f2f-badge {{ $season->getTrangThaiBadgeClass() }}">
                            {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$season->trang_thai] ?? $season->trang_thai }}
                        </span>
                    </div>
                    <p class="f2f-subtitle" style="margin-top:4px">
                        Mã mùa vụ: <code>{{ $season->ma_mua_vu }}</code> · Vùng trồng: 
                        <a href="{{ route('producer.planting-zones.show', $season->vung_trong_id) }}" class="f2f-link">
                            {{ $season->vungTrong->ten_vung_trong }}
                        </a>
                    </p>
                </div>
                <div class="f2f-toolbar-actions">
                    @if(!$seasonLocked)
                    <a href="{{ route('producer.farming-logs.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary">
                        + Ghi nhật ký canh tác
                    </a>
                    @if(!empty($quarantineInfo['dang_cach_ly']))
                        <button type="button" class="f2f-btn-ghost" disabled title="Mùa vụ đang trong thời gian cách ly phân thuốc" style="opacity:0.6;cursor:not-allowed">
                            Chặn thu hoạch (Đang cách ly)
                        </button>
                    @else
                        <a href="{{ route('producer.lots.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary" style="background:#8b5cf6">
                            + Tạo lô thu hoạch
                        </a>
                    @endif
                    <a href="{{ route('producer.seasons.edit', $season->mua_vu_id) }}" class="f2f-btn-ghost">
                        Sửa mùa vụ
                    </a>
                    @endif
                </div>
            </div>

            <div class="f2f-info-grid">
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Nông sản canh tác</span>
                    <strong class="f2f-info-val">{{ $season->sanPham->ten_san_pham ?? '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Giống cây trồng</span>
                    <strong class="f2f-info-val">{{ $season->giong_cay_trong ?? 'Chưa khai báo giống' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Diện tích vụ</span>
                    <strong class="f2f-info-val text-emerald-700">{{ $season->dien_tich_canh_tac_ha ? number_format($season->dien_tich_canh_tac_ha, 2) . ' ha' : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Ngày gieo hạt</span>
                    <strong class="f2f-info-val">{{ $season->ngay_gieo ? $season->ngay_gieo->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Thu hoạch dự kiến</span>
                    <strong class="f2f-info-val">{{ $season->ngay_thu_hoach_du_kien ? $season->ngay_thu_hoach_du_kien->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Kế hoạch sản lượng</span>
                    <strong class="f2f-info-val text-amber-600">{{ $season->san_luong_du_kien ? number_format($season->san_luong_du_kien, 0) . ' ' . $season->don_vi_san_luong : '—' }}</strong>
                </div>
            </div>
        </div>

        <!-- Farming Log Activity Feed / Timeline -->
        <div class="f2f-card" style="margin-top:16px">
            <div class="f2f-card-toolbar">
                <div>
                    <h3 class="f2f-card-title">Dòng thời gian nhật ký canh tác ({{ $season->nhatKyCanhTac->count() }})</h3>
                    <p class="f2f-subtitle">Các hoạt động làm đất, bón phân, tưới nước, phòng trừ sâu bệnh và kiểm tra chất lượng theo chuẩn VietGAP.</p>
                </div>
                <div class="f2f-toolbar-actions">
                    @if(!$seasonLocked)
                    <a href="{{ route('producer.farming-logs.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary f2f-btn-sm">
                        + Ghi hoạt động mới
                    </a>
                    @endif
                </div>
            </div>

            @if($season->nhatKyCanhTac->isNotEmpty())
                <div class="f2f-timeline">
                    @foreach($season->nhatKyCanhTac as $log)
                        <div class="f2f-timeline-item">
                            <div class="f2f-timeline-marker"></div>
                            <div class="f2f-timeline-content">
                                <div class="f2f-timeline-head">
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                        <span class="f2f-tag {{ $log->getHoatDongBadgeColor() }}">
                                            {{ \App\Models\NhatKyCanhTac::HOAT_DONG_LABELS[$log->loai_hoat_dong] ?? $log->loai_hoat_dong }}
                                        </span>
                                        <strong>{{ $log->ngay_thuc_hien ? $log->ngay_thuc_hien->format('d/m/Y H:i') : '' }}</strong>
                                        <span class="f2f-badge {{ $log->trang_thai === 'DA_XAC_NHAN' ? 'f2f-badge-success' : ($log->trang_thai === 'DA_THAY_THE' ? 'f2f-badge-neutral' : 'f2f-badge-warning') }}">
                                            {{ \App\Models\NhatKyCanhTac::TRANG_THAI_LABELS[$log->trang_thai] ?? $log->trang_thai }}
                                        </span>
                                    </div>
                                    <div class="f2f-timeline-actions">
                                        <a href="{{ route('producer.farming-logs.show', $log->nhat_ky_id) }}" class="f2f-btn-ghost f2f-btn-xs">Chi tiết</a>
                                        @if(!$seasonLocked && $log->trang_thai !== 'DA_THAY_THE')
                                            <a href="{{ route('producer.farming-logs.edit', $log->nhat_ky_id) }}" class="f2f-btn-ghost f2f-btn-xs">Sửa</a>
                                        @endif
                                    </div>
                                </div>

                                <div class="f2f-timeline-body">
                                    <p class="f2f-timeline-note">{{ $log->ghi_chu ?? 'Không có ghi chú kỹ thuật.' }}</p>
                                    
                                    @if($log->chiTietVatTu->isNotEmpty())
                                        <div class="f2f-supplies-used">
                                            <strong>Vật tư sử dụng:</strong>
                                            <ul>
                                                @foreach($log->chiTietVatTu as $item)
                                                    <li>
                                                        <span>{{ $item->vatTu->ten_thuong_mai ?? 'Vật tư' }}</span>:
                                                        <strong>{{ number_format($item->lieu_luong, 2) }} {{ $item->don_vi_tinh }}</strong>
                                                        @if($item->thoi_gian_cach_ly_ap_dung_ngay > 0)
                                                            <span class="f2f-tag text-amber-700 bg-amber-50">Cách ly: {{ $item->thoi_gian_cach_ly_ap_dung_ngay }} ngày</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if($log->tep_minh_chung)
                                        <div class="f2f-timeline-media">
                                            <a href="{{ asset('storage/' . $log->tep_minh_chung) }}" target="_blank" class="f2f-media-link">
                                                Xem ảnh / tệp minh chứng thực địa
                                            </a>
                                        </div>
                                    @endif

                                    <div class="f2f-timeline-foot">
                                        <span>Ghi nhận bởi: <strong>{{ $log->nguoiThucHien->ho_ten ?? 'Nhân sự' }}</strong></span>
                                        @if($log->ly_do_sua)
                                            <span class="text-rose-600">· Lý do sửa: {{ $log->ly_do_sua }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="f2f-empty-state">
                    <p>Mùa vụ này chưa ghi chép nhật ký canh tác nào.</p>
                    <a href="{{ route('producer.farming-logs.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary f2f-btn-sm" style="margin-top:6px">
                        Ghi chép hoạt động đầu tiên
                    </a>
                </div>
            @endif
        </div>

        <!-- Harvest Lots from this season -->
        <div class="f2f-card" style="margin-top:16px">
            <div class="f2f-card-toolbar">
                <div>
                    <h3 class="f2f-card-title">Các lô nông sản thu hoạch ({{ $season->loNongSan->count() }})</h3>
                    <p class="f2f-subtitle">Các lô nông sản nguyên liệu thu hoạch từ mùa vụ này sẵn sàng chuyển giao sang khâu sơ chế & đóng gói.</p>
                </div>
                <div class="f2f-toolbar-actions">
                    @if(!$seasonLocked)
                    @if(!empty($quarantineInfo['dang_cach_ly']))
                        <button type="button" class="f2f-btn-ghost f2f-btn-sm" disabled style="opacity:0.6;cursor:not-allowed">
                            Chặn tạo lô (Đang cách ly)
                        </button>
                    @else
                        <a href="{{ route('producer.lots.create', ['mua_vu_id' => $season->mua_vu_id]) }}" class="f2f-btn-primary f2f-btn-sm">
                            + Tạo lô thu hoạch
                        </a>
                    @endif
                    @endif
                </div>
            </div>

            <div class="f2f-table-wrap">
                <table class="f2f-table">
                    <thead>
                        <tr>
                            <th>Mã lô</th>
                            <th>Khối lượng</th>
                            <th>Ngày thu hoạch</th>
                            <th>Hạn sử dụng</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($season->loNongSan as $lot)
                        <tr>
                            <td>
                                <a href="{{ route('producer.lots.show', $lot->lo_nong_san_id) }}" class="f2f-table-link">
                                    <code>{{ $lot->ma_lo }}</code>
                                </a>
                                @if($lot->ma_lo_nha_cung_cap)
                                    <div class="f2f-table-sub">Mã NCC: {{ $lot->ma_lo_nha_cung_cap }}</div>
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
                            <td colspan="6" class="f2f-empty-cell">
                                <div class="f2f-empty-state">
                                    <p>Chưa có lô thu hoạch nào từ mùa vụ này.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar summary -->
    <div class="f2f-detail-side">
        <div class="f2f-card">
            <h3 class="f2f-card-title" style="margin-top:0">Vùng trồng trực thuộc</h3>
            <div class="f2f-cert-box">
                <div class="f2f-cert-title">{{ $season->vungTrong->ten_vung_trong }}</div>
                <div class="f2f-cert-row">
                    <span>MSVT:</span>
                    <strong>{{ $season->vungTrong->ma_so_vung_trong ?? 'Chưa cấp' }}</strong>
                </div>
                <div class="f2f-cert-row">
                    <span>Tiêu chuẩn:</span>
                    <strong class="text-emerald-700">{{ $season->vungTrong->loai_chung_nhan ?? 'Chưa chứng nhận' }}</strong>
                </div>
                <div class="f2f-cert-row">
                    <span>Địa chỉ:</span>
                    <span>{{ $season->vungTrong->dia_chi }}</span>
                </div>
                <div style="margin-top:12px">
                    <a href="{{ route('producer.planting-zones.show', $season->vung_trong_id) }}" class="f2f-btn-ghost f2f-btn-block" style="text-align:center">
                        Xem chi tiết vùng trồng
                    </a>
                </div>
            </div>
        </div>

        <div class="f2f-card" style="margin-top:16px">
            <h3 class="f2f-card-title" style="margin-top:0">Thống kê hoạt động</h3>
            <div class="f2f-mini-stat-list">
                <div class="f2f-mini-stat">
                    <span>Lượt bón phân:</span>
                    <strong>{{ $season->nhatKyCanhTac->whereIn('loai_hoat_dong', ['BON_LOT', 'BON_THUC'])->count() }}</strong>
                </div>
                <div class="f2f-mini-stat">
                    <span>Lượt phun thuốc BVTV:</span>
                    <strong>{{ $season->nhatKyCanhTac->where('loai_hoat_dong', 'PHUN_THUOC')->count() }}</strong>
                </div>
                <div class="f2f-mini-stat">
                    <span>Lượt kiểm tra dịch hại:</span>
                    <strong>{{ $season->nhatKyCanhTac->where('loai_hoat_dong', 'KIEM_TRA')->count() }}</strong>
                </div>
                <div class="f2f-mini-stat">
                    <span>Lô thu hoạch đã tạo:</span>
                    <strong>{{ $season->loNongSan->count() }}</strong>
                </div>
            </div>
        </div>

        @if($season->lichSuTrangThai->isNotEmpty())
        <div class="f2f-card" style="margin-top:16px">
            <h3 class="f2f-card-title" style="margin-top:0">Lịch sử vòng đời</h3>
            <div class="f2f-stack">
                @foreach($season->lichSuTrangThai as $history)
                    <div style="padding:10px 0;border-bottom:1px solid #e7eee9">
                        <strong>{{ \App\Models\MuaVu::TRANG_THAI_LABELS[$history->trang_thai_cu] ?? $history->trang_thai_cu }} → {{ \App\Models\MuaVu::TRANG_THAI_LABELS[$history->trang_thai_moi] ?? $history->trang_thai_moi }}</strong>
                        <div class="f2f-table-sub">{{ $history->thoi_gian?->format('d/m/Y H:i') }} · {{ $history->nguoiThucHien?->ho_ten ?? 'Hệ thống' }}</div>
                        <div style="margin-top:4px">{{ $history->ly_do }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection


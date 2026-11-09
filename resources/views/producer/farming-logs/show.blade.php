@extends('layouts.dashboard', ['title' => 'Chi tiết nhật ký canh tác #' . $log->nhat_ky_id, 'breadcrumb' => 'Farm2Fork · Nhật ký · Chi tiết'])

@section('content')
<div class="f2f-detail-layout">
    <div class="f2f-detail-main">
        <div class="f2f-card">
            <div class="f2f-card-toolbar">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <span class="f2f-tag {{ $log->getHoatDongBadgeColor() }}" style="font-size:14px;padding:6px 14px">
                            {{ \App\Models\NhatKyCanhTac::HOAT_DONG_LABELS[$log->loai_hoat_dong] ?? $log->loai_hoat_dong }}
                        </span>
                        <span class="f2f-badge {{ $log->trang_thai === 'DA_XAC_NHAN' ? 'f2f-badge-success' : ($log->trang_thai === 'DA_THAY_THE' ? 'f2f-badge-neutral' : 'f2f-badge-warning') }}">
                            {{ \App\Models\NhatKyCanhTac::TRANG_THAI_LABELS[$log->trang_thai] ?? $log->trang_thai }}
                        </span>
                    </div>
                    <p class="f2f-subtitle" style="margin-top:6px">
                        Thời điểm thực hiện: <strong>{{ $log->ngay_thuc_hien ? $log->ngay_thuc_hien->format('d/m/Y H:i') : '—' }}</strong> · 
                        Ghi nhận bởi: <strong>{{ $log->nguoiThucHien->ho_ten ?? 'Nhân sự' }}</strong>
                    </p>
                </div>
                <div class="f2f-toolbar-actions">
                    @if($log->trang_thai !== 'DA_THAY_THE' && !in_array($log->muaVu->trang_thai, ['HOAN_THANH', 'HUY'], true))
                        <a href="{{ route('producer.farming-logs.edit', $log->nhat_ky_id) }}" class="f2f-btn-primary">
                            {{ $log->trang_thai === 'DA_XAC_NHAN' ? 'Điều chỉnh bản ghi' : 'Sửa nhật ký' }}
                        </a>
                    @endif
                    <a href="{{ route('producer.farming-logs.index', ['mua_vu_id' => $log->mua_vu_id]) }}" class="f2f-btn-ghost">
                        Danh sách nhật ký
                    </a>
                </div>
            </div>

            <div class="f2f-info-grid">
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Mùa vụ canh tác</span>
                    <strong class="f2f-info-val">
                        <a href="{{ route('producer.seasons.show', $log->mua_vu_id) }}" class="f2f-link">
                            {{ $log->muaVu->ten_mua_vu }}
                        </a>
                    </strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Vùng trồng</span>
                    <strong class="f2f-info-val">
                        <a href="{{ route('producer.planting-zones.show', $log->muaVu->vung_trong_id) }}" class="f2f-link">
                            {{ $log->muaVu->vungTrong->ten_vung_trong }}
                        </a>
                    </strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Cây trồng / Sản phẩm</span>
                    <strong class="f2f-info-val">{{ $log->muaVu->sanPham->ten_san_pham ?? '—' }} ({{ $log->muaVu->giong_cay_trong ?? 'Giống phổ thông' }})</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Tổ chức sở hữu</span>
                    <strong class="f2f-info-val">{{ $log->muaVu->vungTrong->toChuc->ten_to_chuc ?? '—' }}</strong>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="f2f-section">
                <h3 class="f2f-section-title">Nội dung ghi chép kỹ thuật</h3>
                <div class="f2f-note-content">
                    {{ $log->ghi_chu ?: 'Không có ghi chú thêm.' }}
                </div>
            </div>

            @if($log->ly_do_sua)
                <div class="f2f-alert f2f-alert-warning" style="margin-top:14px">
                    <span><strong>Lý do điều chỉnh bản ghi:</strong> {{ $log->ly_do_sua }}</span>
                </div>
            @endif
        </div>

        <!-- Supplies Details Table -->
        <div class="f2f-card" style="margin-top:16px">
            <h3 class="f2f-card-title">Danh mục vật tư nông nghiệp đã sử dụng ({{ $log->chiTietVatTu->count() }})</h3>
            <div class="f2f-table-wrap">
                <table class="f2f-table">
                    <thead>
                        <tr>
                            <th>Tên thương mại vật tư</th>
                            <th>Phân loại</th>
                            <th>Liều lượng sử dụng</th>
                            <th>Thời gian cách ly (PHI)</th>
                            <th>Ghi chú pha trộn</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($log->chiTietVatTu as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->vatTu->ten_thuong_mai ?? 'Vật tư' }}</strong>
                                <div class="f2f-table-sub">Mã: {{ $item->vatTu->ma_vat_tu ?? '' }} · Hoạt chất: {{ $item->vatTu->hoat_chat ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="f2f-tag">
                                    {{ \App\Models\DanhMucVatTu::LOAI_VAT_TU_LABELS[$item->vatTu->loai_vat_tu ?? ''] ?? ($item->vatTu->loai_vat_tu ?? '—') }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ number_format($item->lieu_luong, 3) }} {{ $item->don_vi_tinh }}</strong>
                            </td>
                            <td>
                                @if($item->thoi_gian_cach_ly_ap_dung_ngay > 0)
                                    <span class="f2f-tag text-amber-700 bg-amber-50">
                                        {{ $item->thoi_gian_cach_ly_ap_dung_ngay }} ngày cách ly
                                    </span>
                                @else
                                    <span class="text-emerald-700 font-semibold">0 ngày (An toàn)</span>
                                @endif
                            </td>
                            <td>{{ $item->ghi_chu ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="f2f-empty-cell">
                                <div class="f2f-empty-state">
                                    <p>Hoạt động này không sử dụng phân bón hoặc thuốc BVTV.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Versioning Audit Chain -->
        @if($log->banGhiGoc || $log->banGhiSuaDoi->isNotEmpty())
            <div class="f2f-card" style="margin-top:16px">
                <h3 class="f2f-card-title">Lịch sử chỉnh sửa & Phiên bản liên quan</h3>
                <p class="f2f-subtitle">Chuỗi kiểm toán minh bạch ghi nhận các lần cập nhật và lý do sửa đổi.</p>
                <ul class="f2f-audit-chain">
                    @if($log->banGhiGoc)
                        <li>
                            <span>Phiên bản gốc #{{ $log->banGhiGoc->nhat_ky_id }}:</span>
                            <a href="{{ route('producer.farming-logs.show', $log->banGhiGoc->nhat_ky_id) }}" class="f2f-link">
                                Ghi nhận lúc {{ $log->banGhiGoc->created_at->format('d/m/Y H:i') }} bởi {{ $log->banGhiGoc->nguoiThucHien->ho_ten ?? '' }}
                            </a>
                        </li>
                    @endif
                    <li class="active">
                        <span>Phiên bản hiện tại #{{ $log->nhat_ky_id }}:</span>
                        <strong>Lưu lúc {{ $log->created_at->format('d/m/Y H:i') }}</strong>
                        @if($log->ly_do_sua) — Lý do: <em>"{{ $log->ly_do_sua }}"</em> @endif
                    </li>
                    @foreach($log->banGhiSuaDoi as $rev)
                        <li>
                            <span>Phiên bản thay thế #{{ $rev->nhat_ky_id }}:</span>
                            <a href="{{ route('producer.farming-logs.show', $rev->nhat_ky_id) }}" class="f2f-link">
                                Tạo lúc {{ $rev->created_at->format('d/m/Y H:i') }}
                            </a>
                            @if($rev->ly_do_sua) — Lý do: <em>"{{ $rev->ly_do_sua }}"</em> @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Sidebar Media & Quick Actions -->
    <div class="f2f-detail-side">
        <div class="f2f-card">
            <h3 class="f2f-card-title" style="margin-top:0">Ảnh minh chứng thực địa</h3>
            @if($log->tep_minh_chung)
                <div class="f2f-photo-preview">
                    @if(preg_match('/\.(jpg|jpeg|png|webp)$/i', $log->tep_minh_chung))
                        <a href="{{ asset('storage/' . $log->tep_minh_chung) }}" target="_blank">
                            <img src="{{ asset('storage/' . $log->tep_minh_chung) }}" alt="Minh chứng" class="f2f-responsive-img">
                        </a>
                    @else
                        <div class="f2f-empty" style="padding:20px">
                            <strong>Tệp tài liệu đính kèm (PDF)</strong>
                        </div>
                    @endif
                    <div style="margin-top:10px">
                        <a href="{{ asset('storage/' . $log->tep_minh_chung) }}" target="_blank" class="f2f-btn-ghost f2f-btn-block" style="text-align:center">
                            Mở xem ảnh gốc kích thước lớn
                        </a>
                    </div>
                </div>
            @else
                <div class="f2f-empty-state" style="padding:18px 8px">
                    <p style="margin:4px 0">Chưa đính kèm ảnh chụp hiện trường.</p>
                </div>
            @endif
        </div>

        <div class="f2f-card" style="margin-top:16px">
            <h3 class="f2f-card-title" style="margin-top:0">Thao tác nhanh</h3>
            <div class="f2f-stack">
                <a href="{{ route('producer.farming-logs.create', ['mua_vu_id' => $log->mua_vu_id]) }}" class="f2f-btn-primary f2f-btn-block" style="text-align:center">
                    + Ghi hoạt động tiếp theo
                </a>
                <a href="{{ route('producer.seasons.show', $log->mua_vu_id) }}" class="f2f-btn-ghost f2f-btn-block" style="text-align:center">
                    Xem toàn bộ mùa vụ này
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


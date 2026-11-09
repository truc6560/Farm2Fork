@extends('layouts.dashboard', ['title' => 'Lô nông sản ' . $lot->ma_lo, 'breadcrumb' => 'Farm2Fork · Lô thu hoạch · Chi tiết'])

@section('content')
<div class="f2f-detail-layout">
    <div class="f2f-detail-main">
        <div class="f2f-card">
            <div class="f2f-card-toolbar">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <h2 class="f2f-card-title" style="margin:0">Lô: <code>{{ $lot->ma_lo }}</code></h2>
                        <span class="f2f-badge f2f-badge-active">
                            {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                        </span>
                        <span class="f2f-tag">Nguyên liệu thô</span>
                    </div>
                    <p class="f2f-subtitle" style="margin-top:4px">
                        Sản phẩm: <strong>{{ $lot->sanPham->ten_san_pham ?? '—' }}</strong> · Khối lượng: 
                        <strong class="text-emerald-700">{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</strong>
                    </p>
                </div>
                <div class="f2f-toolbar-actions">
                    <a href="{{ route('producer.lots.index') }}" class="f2f-btn-ghost">
                        Danh sách lô
                    </a>
                </div>
            </div>

            <div class="f2f-info-grid">
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Nông sản</span>
                    <strong class="f2f-info-val">{{ $lot->sanPham->ten_san_pham ?? '—' }} ({{ $lot->sanPham->ma_san_pham ?? '' }})</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Khối lượng thu hoạch</span>
                    <strong class="f2f-info-val text-emerald-800">{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Ngày thu hoạch</span>
                    <strong class="f2f-info-val">{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Hạn sử dụng khuyến nghị</span>
                    <strong class="f2f-info-val text-amber-600">{{ $lot->han_su_dung ? $lot->han_su_dung->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Mã NCC nội bộ</span>
                    <strong class="f2f-info-val">{{ $lot->ma_lo_nha_cung_cap ?? 'Không áp dụng' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Đơn vị sản xuất</span>
                    <strong class="f2f-info-val">{{ $lot->toChucNguon->ten_to_chuc ?? '—' }}</strong>
                </div>
            </div>
        </div>

        <!-- Origin Season Details -->
        @if($lot->muaVu)
            <div class="f2f-card" style="margin-top:16px">
                <div class="f2f-card-toolbar">
                    <div>
                        <h3 class="f2f-card-title">Nguồn gốc mùa vụ & Vùng trồng</h3>
                        <p class="f2f-subtitle">Thông tin về chu kỳ canh tác và địa điểm trồng trọt thu hoạch ra lô nông sản này.</p>
                    </div>
                    <div class="f2f-toolbar-actions">
                        <a href="{{ route('producer.seasons.show', $lot->mua_vu_id) }}" class="f2f-btn-primary f2f-btn-sm">
                            Xem chi tiết mùa vụ
                        </a>
                    </div>
                </div>

                <div class="f2f-info-grid">
                    <div class="f2f-info-item">
                        <span class="f2f-info-label">Mùa vụ</span>
                        <strong class="f2f-info-val">{{ $lot->muaVu->ten_mua_vu }} ({{ $lot->muaVu->ma_mua_vu }})</strong>
                    </div>
                    <div class="f2f-info-item">
                        <span class="f2f-info-label">Giống cây trồng</span>
                        <strong class="f2f-info-val">{{ $lot->muaVu->giong_cay_trong ?? 'Giống phổ thông' }}</strong>
                    </div>
                    <div class="f2f-info-item">
                        <span class="f2f-info-label">Vùng trồng</span>
                        <strong class="f2f-info-val">{{ $lot->muaVu->vungTrong->ten_vung_trong }}</strong>
                    </div>
                    <div class="f2f-info-item">
                        <span class="f2f-info-label">MSVT & Chứng nhận</span>
                        <strong class="f2f-info-val text-emerald-700">
                            {{ $lot->muaVu->vungTrong->ma_so_vung_trong ?? 'Chưa MSVT' }} · {{ $lot->muaVu->vungTrong->loai_chung_nhan ?? 'Chưa CN' }}
                        </strong>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Sidebar summary -->
    <div class="f2f-detail-side">
        <div class="f2f-card">
            <h3 class="f2f-card-title" style="margin-top:0">Tiến trình chuỗi cung ứng</h3>
            <div class="f2f-timeline f2f-timeline-compact">
                <div class="f2f-timeline-item">
                    <div class="f2f-timeline-marker"></div>
                    <div class="f2f-timeline-content">
                        <strong>Thu hoạch tại đồng ruộng</strong>
                        <div class="f2f-table-sub">{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : '' }}</div>
                    </div>
                </div>
                <div class="f2f-timeline-item">
                    <div class="f2f-timeline-marker" style="background:#e2e8f0;border-color:#cbd5e1"></div>
                    <div class="f2f-timeline-content">
                        <span>Tiếp nhận & Sơ chế</span>
                        <div class="f2f-table-sub">Phân hệ nhân viên sơ chế</div>
                    </div>
                </div>
                <div class="f2f-timeline-item">
                    <div class="f2f-timeline-marker" style="background:#e2e8f0;border-color:#cbd5e1"></div>
                    <div class="f2f-timeline-content">
                        <span>Kiểm định chất lượng (QC)</span>
                        <div class="f2f-table-sub">Phân hệ QC</div>
                    </div>
                </div>
                <div class="f2f-timeline-item">
                    <div class="f2f-timeline-marker" style="background:#e2e8f0;border-color:#cbd5e1"></div>
                    <div class="f2f-timeline-content">
                        <span>Đóng gói SSCC & Lưu kho lạnh</span>
                        <div class="f2f-table-sub">Kho lạnh & Vận chuyển</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


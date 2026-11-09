@extends('layouts.dashboard', ['title' => 'Sơ chế & tạo lô', 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế'])

@section('content')
<div class="f2f-stat-grid f2f-processing-stats">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng lô thành phẩm</span>
        <strong class="f2f-stat-value text-slate-800">{{ $totalBatches }}</strong>
        <span class="f2f-stat-hint">Lô đã qua xử lý xưởng</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Chờ đóng gói SSCC</span>
        <strong class="f2f-stat-value text-amber-600">{{ $readyToPack }}</strong>
        <span class="f2f-stat-hint">Sơ chế xong, chờ thùng/pallet</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Đã đóng gói SSCC</span>
        <strong class="f2f-stat-value text-emerald-600">{{ $packed }}</strong>
        <span class="f2f-stat-hint">Đã gán mã chuẩn logistics</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Lưu kho lạnh</span>
        <strong class="f2f-stat-value text-blue-600">{{ $stored }}</strong>
        <span class="f2f-stat-hint">Sẵn sàng xuất kho</span>
    </div>
</div>

<div class="f2f-card" style="margin-top:16px">
    <div class="f2f-card-toolbar f2f-processing-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Quản lý lô thành phẩm sơ chế</h2>
            <p class="f2f-subtitle">Ghi nhận quy trình chế biến, khấu trừ nguyên liệu đầu vào và khởi tạo lô thành phẩm chuẩn GS1.</p>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc lô sơ chế" aria-controls="batch-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('processing.batches.create') }}" class="f2f-btn-primary">
                + Sơ chế tạo lô mới
            </a>
        </div>
    </div>

    <!-- Filter form -->
    <div class="f2f-mobile-filter-panel" id="batch-filters" data-mobile-filter>
        <div class="f2f-mobile-filter-head"><h3>Bộ lọc lô sơ chế</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm mã lô thành phẩm...">
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
                    <option value="">-- Trạng thái lô --</option>
                    <option value="DA_SO_CHE" @selected(($filters['trang_thai'] ?? '') === 'DA_SO_CHE')>Đã sơ chế (Chờ đóng gói)</option>
                    <option value="DANG_DONG_GOI" @selected(($filters['trang_thai'] ?? '') === 'DANG_DONG_GOI')>Đang đóng gói</option>
                    <option value="CHO_KIEM_DINH_XUAT_XUONG" @selected(($filters['trang_thai'] ?? '') === 'CHO_KIEM_DINH_XUAT_XUONG')>Chờ kiểm định xuất xưởng</option>
                    <option value="DAT_KIEM_DINH" @selected(($filters['trang_thai'] ?? '') === 'DAT_KIEM_DINH')>Đạt kiểm định xuất xưởng</option>
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters ?? [])))
                    <a href="{{ route('processing.batches.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <!-- Table of Finished Batches -->
    <div class="f2f-table-wrap">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã lô thành phẩm</th>
                    <th>Nông sản</th>
                    <th>Nguồn nguyên liệu đầu vào</th>
                    <th>Sản lượng thành phẩm</th>
                    <th>Ngày sơ chế / HSD</th>
                    <th>Vị trí kho</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
                <tr>
                    <td>
                        <a href="{{ route('processing.batches.show', $batch->lo_nong_san_id) }}" class="f2f-table-link">
                            <code>{{ $batch->ma_lo }}</code>
                        </a>
                    </td>
                    <td>
                        <strong>{{ $batch->sanPham->ten_san_pham ?? '—' }}</strong>
                        <div class="f2f-table-sub">{{ $batch->sanPham->nhom_san_pham ?? '' }}</div>
                    </td>
                    <td>
                        @forelse($batch->nguyenLieuDauVao as $raw)
                            <div>
                                <a href="{{ route('processing.inbound.show', $raw->lo_nong_san_id) }}" class="f2f-link">
                                    <code>{{ $raw->ma_lo }}</code>
                                </a>
                                ({{ number_format($raw->pivot->khoi_luong_su_dung, 1) }} {{ $raw->pivot->don_vi_tinh }})
                            </div>
                            <div class="f2f-table-sub">
                                {{ $raw->toChucNguon->ten_to_chuc ?? ($raw->muaVu->vungTrong->ten_vung_trong ?? 'Nguồn nội bộ') }}
                            </div>
                        @empty
                            <span class="f2f-muted">—</span>
                        @endforelse
                    </td>
                    <td>
                        <strong class="text-emerald-700">
                            {{ number_format($batch->so_luong, 2) }} {{ $batch->don_vi_tinh }}
                        </strong>
                    </td>
                    <td>
                        <div>SC: {{ $batch->ngay_san_xuat ? $batch->ngay_san_xuat->format('d/m/Y') : '—' }}</div>
                        <div class="f2f-table-sub">HSD: {{ $batch->han_su_dung ? $batch->han_su_dung->format('d/m/Y') : '—' }}</div>
                    </td>
                    <td>
                        {{ $batch->khoLanh->ten_kho ?? 'Xưởng sơ chế' }}
                    </td>
                    <td>
                        <span class="f2f-badge {{ $batch->getTrangThaiBadgeClass() }}">
                            {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$batch->trang_thai] ?? $batch->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div class="f2f-actions-cell">
                            @if(in_array($batch->trang_thai, ['DA_SO_CHE', 'DANG_DONG_GOI']))
                                <a href="{{ route('processing.packaging.create', ['lo_id' => $batch->lo_nong_san_id]) }}" class="f2f-btn-primary f2f-btn-sm">
                                    Đóng gói SSCC
                                </a>
                            @endif
                            <a href="{{ route('processing.batches.show', $batch->lo_nong_san_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                Chi tiết
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có lô thành phẩm sơ chế nào.</p>
                            <a href="{{ route('processing.batches.create') }}" class="f2f-btn-primary" style="margin-top:8px">
                                Sơ chế tạo lô đầu tiên
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="f2f-pagination-wrap">
        {{ $batches->links() }}
    </div>
</div>
@endsection


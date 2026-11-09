@extends('layouts.dashboard', ['title' => 'Lô chờ kiểm định', 'breadcrumb' => 'Farm2Fork · Kiểm định chất lượng (QC)'])

@section('content')
<div class="f2f-qc f2f-qc-pending">
<section class="f2f-qc-mobile-workflow" aria-labelledby="qc-workflow-title">
    <div class="f2f-qc-workflow-head">
        <div><span>Quy trình kiểm định</span><h2 id="qc-workflow-title">Bắt đầu công việc QC</h2></div>
        <strong>{{ $totalPending }} lô chờ</strong>
    </div>
    <div class="f2f-qc-progress" aria-hidden="true"><span style="width:{{ $totalPending > 0 ? '35' : '100' }}%"></span></div>
    <ol class="f2f-qc-steps">
        <li><span>1</span><div><strong>Chọn lô cần kiểm</strong><small>Ưu tiên lô đang chờ kiểm định.</small></div><a href="#qc-pending-list">Xem lô</a></li>
        <li><span>2</span><div><strong>Đo và ghi chỉ tiêu</strong><small>Dùng mẫu nhập kho hoặc xuất xưởng.</small></div><a href="{{ route('qc.inspections.create') }}">Lập phiếu</a></li>
        <li><span>3</span><div><strong>Kết luận và chuyển tiếp</strong><small>Đạt để đi tiếp; không đạt phải có hướng xử lý.</small></div></li>
        <li><span>4</span><div><strong>Lưu hồ sơ kiểm định</strong><small>Tra cứu lại phiếu và kết quả khắc phục.</small></div><a href="{{ route('qc.inspections.index') }}">Lịch sử</a></li>
    </ol>
</section>

<section class="f2f-qc-mobile-actions" aria-label="Thao tác nhanh">
    <button type="button" data-open-qr-scanner>@include('layouts.partials.mobile-icon', ['icon' => 'scan'])<span>Quét mã lô</span></button>
    <a href="{{ route('qc.inspections.create') }}">@include('layouts.partials.mobile-icon', ['icon' => 'add'])<span>Lập phiếu</span></a>
    <a href="{{ route('qc.inspections.index') }}">@include('layouts.partials.mobile-icon', ['icon' => 'list'])<span>Lịch sử</span></a>
</section>

<div class="f2f-stat-grid f2f-qc-stats">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng lô chờ kiểm định</span>
        <strong class="f2f-stat-value text-amber-600">{{ $totalPending }}</strong>
        <span class="f2f-stat-hint">Cần lấy mẫu và đánh giá</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Nguyên liệu nhập kho</span>
        <strong class="f2f-stat-value text-emerald-700">{{ $totalRawPending }}</strong>
        <span class="f2f-stat-hint">Từ các vùng trồng chuyển đến</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Thành phẩm xuất xưởng</span>
        <strong class="f2f-stat-value text-blue-700">{{ $totalFinishedPending }}</strong>
        <span class="f2f-stat-hint">Sau sơ chế & phân loại</span>
    </div>
</div>

<div class="f2f-card" id="qc-pending-list" style="margin-top:16px">
    <div class="f2f-card-toolbar f2f-qc-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách lô nông sản cần kiểm tra chất lượng</h2>
            <p class="f2f-subtitle">Các lô nông sản nguyên liệu vừa tiếp nhận hoặc lô thành phẩm hoàn thiện cần chuyên viên QC kiểm định đạt chuẩn trước khi chuyển tiếp.</p>
        </div>
        <div>
            <a href="{{ route('qc.inspections.create') }}" class="f2f-btn-primary">
                Tạo phiếu kiểm định mới
            </a>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc lô chờ kiểm" aria-controls="qc-pending-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
    </div>

    <!-- Filter form -->
    <div class="f2f-mobile-filter-panel" id="qc-pending-filters" data-mobile-filter>
    <div class="f2f-mobile-filter-head"><h3>Bộ lọc lô chờ kiểm</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $search }}" placeholder="Tìm kiếm mã lô, tên nông sản...">
            </div>
            <div>
                <select class="f2f-input" name="type">
                    <option value="all" @selected($type === 'all')>-- Tất cả loại lô --</option>
                    <option value="NGUYEN_LIEU" @selected($type === 'NGUYEN_LIEU')>Lô nguyên liệu vùng trồng</option>
                    <option value="THANH_PHAM" @selected($type === 'THANH_PHAM')>Lô thành phẩm sơ chế</option>
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc dữ liệu</button>
                @if($search !== '' || $type !== 'all')
                    <a href="{{ route('qc.pending.index') }}" class="f2f-btn-secondary">Đặt lại</a>
                @endif
            </div>
        </div>
    </form>
    </div>

    <!-- Table -->
    <div class="f2f-table-container">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã số lô</th>
                    <th>Nông sản</th>
                    <th>Phân loại</th>
                    <th>Nguồn gốc xuất xứ</th>
                    <th>Khối lượng</th>
                    <th>Ngày thu hoạch / Tạo</th>
                    <th>Trạng thái hiện tại</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lots as $lot)
                    <tr onclick="window.location='{{ route('qc.inspections.create', ['lo_id' => $lot->lo_nong_san_id]) }}'" tabindex="0" aria-label="Kiểm định lô {{ $lot->ma_lo }}">
                        <td>
                            <strong class="font-mono text-stone-900">{{ $lot->ma_lo }}</strong>
                        </td>
                        <td>
                            <div class="font-semibold text-stone-800">{{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}</div>
                            <span class="text-xs text-stone-500 font-mono">{{ $lot->sanPham->ma_san_pham ?? '—' }}</span>
                        </td>
                        <td>
                            @if($lot->loai_lo === 'NGUYEN_LIEU')
                                <span class="f2f-badge-active">Nguyên liệu</span>
                            @else
                                <span class="f2f-badge-success">Thành phẩm</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-xs text-stone-700 font-medium">
                                {{ $lot->toChucNguon->ten_to_chuc ?? $lot->muaVu?->vungTrong?->toChuc?->ten_to_chuc ?? 'Hợp tác xã đối tác' }}
                            </div>
                            @if($lot->muaVu?->vungTrong)
                                <span class="text-[11px] text-stone-400 block">{{ $lot->muaVu->vungTrong->ten_vung_trong }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="font-semibold font-mono text-stone-800">{{ number_format($lot->so_luong, 2) }}</span>
                            <span class="text-xs text-stone-500">{{ $lot->don_vi_tinh }}</span>
                        </td>
                        <td>
                            <span class="font-mono text-xs text-stone-700">
                                {{ $lot->ngay_thu_hoach ? \Carbon\Carbon::parse($lot->ngay_thu_hoach)->format('d/m/Y') : ($lot->ngay_tao ? \Carbon\Carbon::parse($lot->ngay_tao)->format('d/m/Y') : '—') }}
                            </span>
                        </td>
                        <td>
                            <span class="{{ $lot->trang_thai_badge_class }}">
                                {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                            </span>
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('qc.inspections.create', ['lo_id' => $lot->lo_nong_san_id]) }}" class="f2f-btn-primary" style="padding:4px 10px; font-size:12px">
                                Kiểm định ngay
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="f2f-table-empty">
                            Hiện không có lô nông sản nào đang ở trạng thái chờ kiểm định chất lượng.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($lots->hasPages())
        <div style="margin-top:16px">
            {{ $lots->links() }}
        </div>
    @endif
</div>
</div>
@endsection


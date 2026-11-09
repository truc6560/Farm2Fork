@extends('layouts.dashboard', ['title' => 'Sổ kiểm định chất lượng', 'breadcrumb' => 'Farm2Fork · Kiểm định chất lượng (QC)'])

@section('content')
<div class="f2f-qc f2f-qc-history">
<div class="f2f-stat-grid f2f-qc-stats">
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Tổng số phiếu kiểm định</span>
        <strong class="f2f-stat-value text-slate-800">{{ $totalTickets }}</strong>
        <span class="f2f-stat-hint">Hồ sơ đã lập và lưu trữ</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Kết luận đạt chuẩn</span>
        <strong class="f2f-stat-value text-emerald-600">{{ $totalPass }}</strong>
        <span class="f2f-stat-hint">Đủ điều kiện sơ chế / xuất xưởng</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Kết luận không đạt</span>
        <strong class="f2f-stat-value text-rose-600">{{ $totalFail }}</strong>
        <span class="f2f-stat-hint">Cần xử lý khắc phục</span>
    </div>
    <div class="f2f-stat-card">
        <span class="f2f-stat-label">Chờ khắc phục</span>
        <strong class="f2f-stat-value text-amber-600">{{ $totalPendingRemedy }}</strong>
        <span class="f2f-stat-hint">Chưa ghi nhận kết quả xử lý</span>
    </div>
</div>

<div class="f2f-card" style="margin-top:16px">
    <div class="f2f-card-toolbar f2f-qc-list-toolbar">
        <div>
            <h2 class="f2f-card-title">Sổ theo dõi & Lịch sử kiểm định chất lượng</h2>
            <p class="f2f-subtitle">Tra cứu toàn bộ hồ sơ kiểm định nhập kho, kiểm định trước xuất xưởng và kết quả phân tích phòng lab.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('qc.pending.index') }}" class="f2f-btn-secondary">
                Lô chờ kiểm định
            </a>
            <a href="{{ route('qc.inspections.create') }}" class="f2f-btn-primary">
                Tạo phiếu kiểm định mới
            </a>
        </div>
        <button type="button" class="f2f-mobile-filter-trigger" data-mobile-filter-open aria-label="Mở bộ lọc lịch sử kiểm định" aria-controls="qc-history-filters" aria-expanded="false">@include('layouts.partials.mobile-icon', ['icon' => 'search'])</button>
    </div>

    <!-- Filter form -->
    <div class="f2f-mobile-filter-panel" id="qc-history-filters" data-mobile-filter>
    <div class="f2f-mobile-filter-head"><h3>Lọc lịch sử kiểm định</h3><button type="button" data-mobile-filter-close aria-label="Đóng bộ lọc">×</button></div>
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $search }}" placeholder="Tìm mã phiếu, mã lô, tên sản phẩm, kiểm nghiệm viên...">
            </div>
            <div>
                <select class="f2f-input" name="loai_kiem_dinh">
                    <option value="">-- Tất cả loại kiểm định --</option>
                    <option value="NHAP_KHO" @selected($type === 'NHAP_KHO')>Kiểm định nhập kho sơ chế</option>
                    <option value="TRUOC_XUAT_XUONG" @selected($type === 'TRUOC_XUAT_XUONG')>Kiểm định trước xuất xưởng</option>
                    <option value="KIEM_DINH_LAI" @selected($type === 'KIEM_DINH_LAI')>Tái kiểm định chất lượng</option>
                    <option value="SAU_PHAN_PHOI" @selected($type === 'SAU_PHAN_PHOI')>Kiểm định giám sát lưu thông</option>
                </select>
            </div>
            <div>
                <select class="f2f-input" name="ket_luan">
                    <option value="">-- Tất cả kết luận --</option>
                    <option value="DAT" @selected($conclusion === 'DAT')>Đạt tiêu chuẩn</option>
                    <option value="KHONG_DAT" @selected($conclusion === 'KHONG_DAT')>Không đạt</option>
                    <option value="CHO_KIEM_DINH" @selected($conclusion === 'CHO_KIEM_DINH')>Chờ kiểm định</option>
                </select>
            </div>
            <div>
                <input class="f2f-input" type="date" name="from_date" value="{{ $fromDate }}" title="Từ ngày">
            </div>
            <div>
                <input class="f2f-input" type="date" name="to_date" value="{{ $toDate }}" title="Đến ngày">
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if($search !== '' || $type !== '' || $conclusion !== '' || $fromDate !== '' || $toDate !== '')
                    <a href="{{ route('qc.inspections.index') }}" class="f2f-btn-secondary">Đặt lại</a>
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
                    <th>Mã phiếu</th>
                    <th>Lô nông sản</th>
                    <th>Loại kiểm định</th>
                    <th>Thời gian kiểm</th>
                    <th>Kiểm nghiệm viên</th>
                    <th style="text-align:center">Chỉ tiêu</th>
                    <th style="text-align:center">Kết luận</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr onclick="window.location='{{ route('qc.inspections.show', $ticket->phieu_id) }}'" tabindex="0" aria-label="Xem phiếu {{ $ticket->ma_phieu }}">
                        <td>
                            <a href="{{ route('qc.inspections.show', $ticket->phieu_id) }}" class="font-mono font-bold text-emerald-800 hover:underline">
                                {{ $ticket->ma_phieu }}
                            </a>
                        </td>
                        <td>
                            <strong class="font-mono text-stone-900 block text-xs">{{ $ticket->loNongSan->ma_lo ?? '—' }}</strong>
                            <span class="text-xs text-stone-600 font-medium">
                                {{ $ticket->loNongSan->sanPham->ten_san_pham ?? 'Chưa xác định' }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-stone-100 text-stone-700 border border-stone-200">
                                {{ $ticket->loai_kiem_dinh_label }}
                            </span>
                        </td>
                        <td>
                            <span class="font-mono text-xs text-stone-700">
                                {{ $ticket->ngay_kiem_dinh ? $ticket->ngay_kiem_dinh->format('H:i - d/m/Y') : '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="text-xs font-semibold text-stone-800">
                                {{ $ticket->nguoiKiemDinh->ho_ten ?? 'Chuyên viên KCS' }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            <span class="font-mono text-xs font-semibold text-stone-600">
                                {{ $ticket->chiTietKetQua->count() }} chỉ tiêu
                            </span>
                        </td>
                        <td style="text-align:center">
                            @if($ticket->ket_luan === 'DAT')
                                <span class="f2f-badge-success">ĐẠT CHUẨN</span>
                            @elseif($ticket->ket_luan === 'KHONG_DAT')
                                <span class="f2f-badge-danger">KHÔNG ĐẠT</span>
                                @if($ticket->huong_xu_ly)
                                    <span class="text-[10px] text-rose-700 block mt-0.5 font-medium">
                                        {{ $ticket->huong_xu_ly_label }}
                                    </span>
                                @endif
                            @else
                                <span class="f2f-badge-warning">CHỜ KIỂM ĐỊNH</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('qc.inspections.show', $ticket->phieu_id) }}" class="f2f-btn-secondary" style="padding:4px 10px; font-size:12px">
                                Chi tiết
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="f2f-table-empty">
                            Không tìm thấy phiếu kiểm định chất lượng nào phù hợp với bộ lọc.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tickets->hasPages())
        <div style="margin-top:16px">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
</div>
@endsection


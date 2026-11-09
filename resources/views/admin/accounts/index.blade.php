@extends('layouts.dashboard', ['title' => 'Quản lý tài khoản', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="f2f-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách tài khoản hệ thống</h2>
            <p class="f2f-subtitle">Quản lý phân quyền vai trò, khóa hoặc mở khóa tài khoản người dùng trong toàn chuỗi.</p>
        </div>
    </div>

    <!-- Filter form -->
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo tên, email...">
            </div>
            <div>
                <select class="f2f-input" name="vai_tro_id">
                    <option value="">Tất cả vai trò</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->vai_tro_id }}" @selected((string)($filters['vai_tro_id'] ?? '') === (string)$r->vai_tro_id)>{{ $r->ten_vai_tro }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="trang_thai">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $code => $label)
                        <option value="{{ $code }}" @selected(($filters['trang_thai'] ?? '') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters ?? [])))
                    <a href="{{ route('admin.accounts.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>

    <div class="f2f-table-wrap">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Tổ chức</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($accounts as $acc)
                <tr>
                    <td><strong>{{ $acc->ho_ten }}</strong></td>
                    <td>{{ $acc->email }}</td>
                    <td><span class="f2f-tag">{{ $acc->vaiTro->ten_vai_tro ?? '—' }}</span></td>
                    <td>{{ $acc->toChuc->ten_to_chuc ?? '—' }}</td>
                    <td>
                        <span class="f2f-badge {{ $acc->trang_thai === 'DANG_HOAT_DONG' ? 'f2f-badge-success' : 'f2f-badge-danger' }}">
                            {{ $statuses[$acc->trang_thai] ?? $acc->trang_thai }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <div class="f2f-actions-cell">
                            @if($acc->trang_thai === 'DANG_HOAT_DONG')
                                <form method="POST" action="{{ route('admin.accounts.lock', $acc->tai_khoan_id) }}">
                                    @csrf
                                    <button class="f2f-btn-danger f2f-btn-sm" type="submit" onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?')">Khóa</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.accounts.unlock', $acc->tai_khoan_id) }}">
                                    @csrf
                                    <button class="f2f-btn-ghost f2f-btn-sm" type="submit">Mở khóa</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.accounts.change-role', $acc->tai_khoan_id) }}" style="display:inline-flex;gap:4px">
                                @csrf
                                <select class="f2f-input" name="vai_tro_id" style="padding:4px 8px;font-size:12.5px;min-width:140px" required>
                                    @foreach ($roles->where('ma_vai_tro', '!=', 'ADMIN') as $r)
                                        <option value="{{ $r->vai_tro_id }}" @selected($acc->vai_tro_id == $r->vai_tro_id)>{{ $r->ten_vai_tro }}</option>
                                    @endforeach
                                </select>
                                <button class="f2f-btn-ghost f2f-btn-sm" type="submit">Đổi</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có tài khoản nào phù hợp.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="f2f-pagination-wrap">{{ $accounts->links() }}</div>
</div>
@endsection

@extends('layouts.dashboard', ['title' => 'Duyệt hồ sơ đăng ký', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="f2f-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Danh sách hồ sơ đăng ký tham gia</h2>
            <p class="f2f-subtitle">Xét duyệt hồ sơ đăng ký từ các cơ sở, hợp tác xã và doanh nghiệp nông nghiệp.</p>
        </div>
    </div>

    <!-- Filter box -->
    <form method="GET" class="f2f-filter-box">
        <div class="f2f-filter-grid">
            <div>
                <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo tên cơ sở, mã hồ sơ, loại hình...">
            </div>
            <div>
                <select class="f2f-input" name="status">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statusOptions as $code => $label)
                        <option value="{{ $code }}" @selected(($filters['status'] ?? '') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="f2f-input" name="type">
                    <option value="">Tất cả loại hình</option>
                    <option value="NONG_HO" @selected(($filters['type'] ?? '') === 'NONG_HO')>Nông hộ</option>
                    <option value="HTX" @selected(($filters['type'] ?? '') === 'HTX')>HTX</option>
                    <option value="DOANH_NGHIEP_NONG_NGHIEP" @selected(($filters['type'] ?? '') === 'DOANH_NGHIEP_NONG_NGHIEP')>Doanh nghiệp nông nghiệp</option>
                </select>
            </div>
            <div class="f2f-filter-actions">
                <button class="f2f-btn-primary" type="submit">Lọc</button>
                @if(!empty(array_filter($filters ?? [])))
                    <a href="{{ route('admin.registrations.index') }}" class="f2f-btn-ghost">Xóa lọc</a>
                @endif
            </div>
        </div>
    </form>

    <div class="f2f-table-wrap">
        <table class="f2f-table">
            <thead>
                <tr>
                    <th>Mã hồ sơ</th>
                    <th>Tên cơ sở</th>
                    <th>Loại hình</th>
                    <th>Trạng thái</th>
                    <th>Thời gian cập nhật</th>
                    <th style="text-align:right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            @forelse($registrations as $item)
                <tr>
                    <td><code>{{ $item['application_code'] }}</code></td>
                    <td><strong>{{ $item['organization']['name'] }}</strong></td>
                    <td><span class="f2f-tag">{{ $item['organization']['type'] }}</span></td>
                    <td>
                        <span class="f2f-badge {{ in_array($item['status'], ['APPROVED', 'DA_PHE_DUYET']) ? 'f2f-badge-success' : (in_array($item['status'], ['REJECTED', 'TU_CHOI']) ? 'f2f-badge-danger' : 'f2f-badge-warning') }}">
                            {{ $statusOptions[$item['status']] ?? $item['status'] }}
                        </span>
                    </td>
                    <td>{{ $item['updated_at'] }}</td>
                    <td style="text-align:right">
                        <a class="f2f-btn-ghost f2f-btn-sm" href="{{ route('admin.registrations.show', $item['application_code']) }}">
                            Xem chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="f2f-empty-cell">
                        <div class="f2f-empty-state">
                            <p>Chưa có hồ sơ nào phù hợp với bộ lọc.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

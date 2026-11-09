@extends('layouts.dashboard', ['title' => 'Tổng quan', 'breadcrumb' => 'Farm2Fork · ' . ($user->vaiTro->ten_vai_tro ?? 'Tổng quan')])

@section('content')
@php
    $guides = [
        'NGUOI_SAN_XUAT' => ['Công việc tại vùng trồng', 'Bắt đầu từ vùng trồng và mùa vụ. Ghi nhật ký sau mỗi lần chăm sóc; kiểm tra ngày hết cách ly trước khi tạo lô thu hoạch.', ['Vùng trồng', 'Mùa vụ', 'Nhật ký canh tác', 'Lô thu hoạch']],
        'NV_SO_CHE_DONG_GOI' => ['Tiếp nhận và đóng gói nông sản', 'Đối chiếu lô nguyên liệu và kết quả kiểm định trước khi sơ chế. Mã SSCC dùng để nhận diện từng thùng hoặc pallet khi bàn giao.', ['Tiếp nhận', 'Kiểm tra kết quả QC', 'Sơ chế', 'Đóng gói']],
        'QC' => ['Kiểm tra chất lượng lô hàng', 'Mở danh sách lô chờ kiểm định, kiểm tra hồ sơ rồi nhập kết quả thực tế. Nếu lô không đạt, ghi rõ lý do và hướng xử lý.', ['Chọn lô', 'Xem hồ sơ', 'Nhập kết quả', 'Kết luận']],
        'NV_KHO_LANH' => ['Theo dõi hàng trong kho', 'Kiểm tra mã lô hoặc mã kiện và kho tiếp nhận. Trước khi bàn giao, đối chiếu kết quả kiểm định và thông tin vận chuyển.', ['Nhập kho', 'Theo dõi tồn kho', 'Kiểm tra nhiệt độ', 'Bàn giao']],
        'TAI_XE' => ['Công việc trong chuyến giao hàng', 'Mở chuyến được phân công, kiểm tra hàng và điểm giao. Ghi nhận hành trình, xác nhận từng phiếu giao trước khi kết thúc chuyến.', ['Xem chuyến', 'Xuất phát', 'Xác nhận giao hàng', 'Kết thúc chuyến']],
        'ADMIN' => ['Quản lý hệ thống', 'Kiểm tra hồ sơ đăng ký, cấp quyền đúng vai trò và quản lý kho lạnh. Các chức năng bên dưới mở trực tiếp màn hình làm việc.', ['Duyệt hồ sơ', 'Tài khoản', 'Kho lạnh', 'Ngưỡng cảnh báo']],
    ];
    $guide = $guides[$user->vaiTro?->ma_vai_tro ?? ''] ?? null;
@endphp
@if($guide)
    <section class="f2f-work-guide" aria-label="Hướng dẫn công việc">
        <div><p class="f2f-eyebrow">Hướng dẫn thao tác</p><h2>{{ $guide[0] }}</h2><p>{{ $guide[1] }}</p></div>
        <ol class="f2f-work-steps">
            @foreach($guide[2] as $step)
                <li><span aria-hidden="true">{{ $loop->iteration }}</span>{{ $step }}</li>
            @endforeach
        </ol>
    </section>
@endif
@if(!empty($alerts))
    @foreach($alerts as $alert)
        <div class="f2f-alert f2f-alert-warning">{{ $alert }}</div>
    @endforeach
@endif

<div class="f2f-stat-grid {{ ($user->vaiTro?->ma_vai_tro ?? '') === 'NV_KHO_LANH' ? 'f2f-role-warehouse-stats' : '' }} {{ ($user->vaiTro?->ma_vai_tro ?? '') === 'NV_SO_CHE_DONG_GOI' ? 'f2f-role-processing-stats' : '' }} {{ ($user->vaiTro?->ma_vai_tro ?? '') === 'TAI_XE' ? 'f2f-role-driver-stats' : '' }}">
    @forelse($stats as $stat)
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">{{ $stat['label'] }}</span>
            <strong class="f2f-stat-value">{{ $stat['value'] }}</strong>
            @if(!empty($stat['hint']))
                <span class="f2f-stat-hint">{{ $stat['hint'] }}</span>
            @endif
        </div>
    @empty
        <div class="f2f-stat-card">
            <span class="f2f-stat-label">Dữ liệu</span>
            <strong class="f2f-stat-value">0</strong>
            <span class="f2f-stat-hint">Chưa có dữ liệu hiển thị.</span>
        </div>
    @endforelse
</div>

<div class="f2f-panel">
    <h2>Chức năng của bạn</h2>
    <p class="f2f-subtitle">Chọn công việc cần thực hiện. Bạn cũng có thể mở các chức năng này từ menu.</p>
    <div class="f2f-feature-list">
        @foreach($navItems as $item)
            @if(!empty($item['route']))
                <a class="f2f-feature-link" href="{{ route($item['route']) }}">{{ $item['label'] }} →</a>
            @else
                <span class="f2f-feature-pending">{{ $item['label'] }} · Đang phát triển</span>
            @endif
        @endforeach
    </div>
</div>
@endsection

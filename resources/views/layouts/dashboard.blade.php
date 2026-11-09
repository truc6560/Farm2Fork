<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Farm2Fork' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="f2f-app {{ request()->routeIs('producer.*') ? 'f2f-producer f2f-field-role' : '' }} {{ request()->routeIs('warehouse.*') ? 'f2f-warehouse f2f-field-role' : '' }} {{ request()->routeIs('processing.*') ? 'f2f-processing f2f-field-role' : '' }} {{ request()->routeIs('driver.*') ? 'f2f-driver f2f-field-role' : '' }} {{ request()->routeIs('*.index') || request()->routeIs('*.show') ? 'f2f-mobile-card-list' : '' }} {{ request()->routeIs('*.create') || request()->routeIs('*.edit') ? 'f2f-mobile-form-page' : '' }} {{ request()->routeIs('producer.seasons.show') ? 'f2f-producer-season-show' : '' }} {{ request()->routeIs('producer.farming-logs.index') ? 'f2f-producer-log-index' : '' }} {{ request()->routeIs('producer.seasons.index') ? 'f2f-producer-season-index' : '' }} {{ request()->routeIs('producer.planting-zones.index') ? 'f2f-producer-zone-index' : '' }} {{ request()->routeIs('producer.planting-zones.show') ? 'f2f-producer-zone-show' : '' }} {{ request()->routeIs('warehouse.operations.*') ? 'f2f-warehouse-operations' : '' }} {{ request()->routeIs('warehouse.inventory.*') ? 'f2f-warehouse-inventory' : '' }} {{ request()->routeIs('warehouse.monitoring.*') ? 'f2f-warehouse-monitoring' : '' }} {{ request()->routeIs('processing.inbound.*') ? 'f2f-processing-inbound' : '' }} {{ request()->routeIs('processing.inbound.index') ? 'f2f-processing-inbound-index' : '' }} {{ request()->routeIs('processing.batches.*') ? 'f2f-processing-batches' : '' }} {{ request()->routeIs('processing.packaging.*') ? 'f2f-processing-packaging' : '' }} {{ request()->routeIs('driver.trips.*') ? 'f2f-driver-trips' : '' }} {{ request()->routeIs('driver.journey.*') ? 'f2f-driver-journey' : '' }} {{ request()->routeIs('driver.delivery.*') ? 'f2f-driver-delivery' : '' }}">
<a class="f2f-skip-link" href="#main-content">Đến nội dung chính</a>
@php
    $authUser = auth()->user();
    if ($authUser && !$authUser->relationLoaded('vaiTro')) {
        $authUser->load('vaiTro');
    }
    if ($authUser && !$authUser->relationLoaded('toChuc')) {
        $authUser->load('toChuc');
    }
    $authUserRole = $authUser?->vaiTro?->ma_vai_tro;
    $sidebarNavItems = config('role_menu.' . ($authUserRole ?? ''), []);
    $mobileNavigation = [
        'ADMIN' => [
            'tabs' => [['Tài khoản', 'admin.accounts.index', 'admin.accounts.*', 'users'], ['Thành viên', 'admin.members.index', 'admin.members.*', 'people']],
            'action' => ['Thêm thành viên', 'admin.members.index', 'add'],
        ],
        'NGUOI_SAN_XUAT' => [
            'tabs' => [['Vùng trồng', 'producer.planting-zones.index', 'producer.planting-zones.*', 'field'], ['Mùa vụ', 'producer.seasons.index', 'producer.seasons.*', 'season']],
            'action' => ['Nhật ký', 'producer.farming-logs.index', 'journal'],
        ],
        'NV_SO_CHE_DONG_GOI' => [
            'tabs' => [['Tiếp nhận', 'processing.inbound.index', 'processing.inbound.*', 'inbox'], ['Đóng gói', 'processing.packaging.index', 'processing.packaging.*', 'package']],
            'action' => ['Quét QR', null, 'scan'],
        ],
        'QC' => [
            'tabs' => [['Chờ kiểm', 'qc.pending.index', 'qc.pending.*', 'inbox'], ['Lịch sử', 'qc.inspections.index', 'qc.inspections.*', 'list']],
            'action' => ['Quét QR', null, 'scan'],
        ],
        'NV_KHO_LANH' => [
            'tabs' => [['Nhập xuất', 'warehouse.operations.index', 'warehouse.operations.*', 'inbox'], ['Tồn kho', 'warehouse.inventory.index', 'warehouse.inventory.*', 'package']],
            'action' => ['Quét QR', null, 'scan'],
        ],
        'TAI_XE' => [
            'tabs' => [['Chuyến đi', 'driver.trips.index', 'driver.trips.*', 'truck'], ['Giao nhận', 'driver.delivery.index', 'driver.delivery.*', 'package']],
            'action' => ['Quét QR', null, 'scan'],
        ],
        'QUAN_LY_CHUOI' => [
            'tabs' => [['Lô hàng', 'chain.lots.index', 'chain.lots.*', 'package'], ['Vận chuyển', 'chain.transport.index', 'chain.transport.*', 'truck']],
            'action' => ['Cảnh báo', 'chain.alerts.index', 'alert'],
        ],
    ];
    $mobileNav = $mobileNavigation[$authUserRole] ?? null;
@endphp

<div class="f2f-app-shell" id="app-shell">
    <!-- Backdrop for mobile drawer -->
    <div class="f2f-sidebar-backdrop" id="sidebar-backdrop"></div>

    <aside class="f2f-sidebar" id="app-sidebar">
        <div class="f2f-sidebar-head-row">
            <a href="{{ route('dashboard') }}" class="f2f-side-brand" title="Trang tổng quan Farm2Fork" style="text-decoration:none;display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:52px;width:auto;max-width:210px;object-fit:contain;">
            </a>
            <button class="f2f-sidebar-close-btn" type="button" id="sidebar-close" aria-label="Đóng menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="f2f-side-role-row">
            <span class="f2f-side-role">{{ $authUser?->vaiTro?->ten_vai_tro ?? $authUserRole ?? 'Thành viên' }}</span>
            <form method="POST" action="{{ route('auth.logout') }}" class="f2f-logout-inline">
                @csrf
                <button class="f2f-logout-btn" type="submit" title="Đăng xuất khỏi hệ thống">Đăng xuất</button>
            </form>
        </div>
        <nav class="f2f-side-nav" aria-label="Chức năng theo vai trò">
            <a href="{{ route('dashboard') }}" class="f2f-side-link {{ request()->routeIs('dashboard') || request()->routeIs('chain.dashboard') ? 'active' : '' }}">
                Tổng quan
            </a>
            @foreach(($sidebarNavItems ?? []) as $item)
                @if(!empty($item['route']))
                    <a href="{{ route($item['route']) }}" class="f2f-side-link {{ request()->routeIs($item['active'] ?? $item['route']) ? 'active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="f2f-side-link disabled" title="Đang phát triển">
                        {{ $item['label'] }} <em>· Sắp có</em>
                    </span>
                @endif
            @endforeach
        </nav>
        <div class="f2f-side-foot">
            <div class="f2f-side-user">
                <strong>{{ $authUser?->ho_ten }}</strong>
                <span>{{ $authUser?->toChuc?->ten_to_chuc ?? $authUser?->email }}</span>
            </div>
        </div>
    </aside>

    <div class="f2f-main">
        <!-- Top bar visible only on mobile screens -->
        <div class="f2f-mobile-topbar">
            <button class="f2f-hamburger-btn" type="button" id="sidebar-toggle" aria-label="Mở menu chức năng" aria-controls="app-sidebar" aria-expanded="false">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <strong class="f2f-mobile-page-title">{{ $title ?? 'Tổng quan' }}</strong>
            <button class="f2f-mobile-account" type="button" id="mobile-profile-trigger" aria-label="Xem hồ sơ tài khoản của {{ $authUser?->ho_ten }}">
                {{ mb_substr($authUser?->ho_ten ?? 'F', 0, 1) }}
            </button>
        </div>

        <header class="f2f-header">
            <div>
                <p class="f2f-eyebrow">{{ $breadcrumb ?? 'Farm2Fork' }}</p>
                <h1 class="f2f-page-title">{{ $title ?? 'Tổng quan' }}</h1>
            </div>

            {{-- Thẻ người dùng góc trên bên phải có menu chức năng --}}
            <div class="f2f-user-menu-container" id="user-menu-container">
                <button type="button" class="f2f-header-user" id="user-menu-trigger" aria-haspopup="true" aria-expanded="false" title="Menu tài khoản">
                    <div class="f2f-avatar">{{ mb_substr($authUser?->ho_ten ?? 'F', 0, 1) }}</div>
                    <div>
                        <strong>{{ $authUser?->ho_ten }}</strong>
                        <span>{{ $authUser?->toChuc?->ten_to_chuc ?? 'Farm2Fork' }}</span>
                    </div>
                    <svg class="f2f-user-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <div class="f2f-user-dropdown" id="user-menu-dropdown" style="display:none;" role="menu" aria-label="Menu tài khoản">
                    <div class="f2f-dropdown-head">
                        <span class="f2f-dropdown-name">{{ $authUser?->ho_ten }}</span>
                        <span class="f2f-dropdown-email">{{ $authUser?->email }}</span>
                        <span class="f2f-dropdown-role-badge">{{ $authUser?->vaiTro?->ten_vai_tro ?? $authUserRole ?? 'Thành viên' }}</span>
                    </div>

                    <button type="button" class="f2f-dropdown-item" id="btn-open-profile" role="menuitem">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Hồ sơ tài khoản</span>
                    </button>

                    <a href="{{ route('auth.change-password.form') }}" class="f2f-dropdown-item" role="menuitem">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Đổi mật khẩu</span>
                    </a>

                    <div class="f2f-dropdown-divider"></div>

                    <form method="POST" action="{{ route('auth.logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="f2f-dropdown-item f2f-dropdown-item-danger" role="menuitem">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span>Đăng xuất</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="f2f-alert f2f-alert-success" role="status">
                <div>{{ session('status') }}</div>
            </div>
        @endif
        @if (session('warning'))
            <div class="f2f-alert f2f-alert-warning" role="alert">
                <div>{{ session('warning') }}</div>
            </div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="f2f-alert f2f-alert-error" role="alert" tabindex="-1" data-error-summary>
                <strong>Chưa lưu được. Vui lòng kiểm tra các thông tin sau:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main class="f2f-main-body" id="main-content" tabindex="-1">
            @yield('content')
        </main>
    </div>
</div>

@if($mobileNav)
    <nav class="f2f-mobile-bottom-nav" aria-label="Điều hướng nhanh">
        <a href="{{ route('dashboard') }}" class="f2f-mobile-tab {{ request()->routeIs('dashboard') || request()->routeIs('chain.dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard') || request()->routeIs('chain.dashboard')) aria-current="page" @endif>
            @include('layouts.partials.mobile-icon', ['icon' => 'home'])<span>Tổng quan</span>
        </a>
        @php $firstTab = $mobileNav['tabs'][0]; @endphp
        <a href="{{ route($firstTab[1]) }}" class="f2f-mobile-tab {{ request()->routeIs($firstTab[2]) ? 'active' : '' }}" @if(request()->routeIs($firstTab[2])) aria-current="page" @endif>
            @include('layouts.partials.mobile-icon', ['icon' => $firstTab[3]])<span>{{ $firstTab[0] }}</span>
        </a>
        @if($mobileNav['action'][2] === 'scan')
            <button class="f2f-mobile-tab f2f-mobile-primary" type="button" data-open-qr-scanner aria-label="Quét mã QR truy xuất">
                <span class="f2f-mobile-primary-icon">@include('layouts.partials.mobile-icon', ['icon' => 'scan'])</span><span>Quét QR</span>
            </button>
        @else
            @php $mobileActionActive = $authUserRole === 'NGUOI_SAN_XUAT' ? request()->routeIs('producer.farming-logs.*') : request()->routeIs($mobileNav['action'][1]); @endphp
            <a href="{{ route($mobileNav['action'][1]) }}" class="f2f-mobile-tab f2f-mobile-primary {{ $mobileActionActive ? 'active' : '' }}" aria-label="{{ $authUserRole === 'NGUOI_SAN_XUAT' ? 'Nhật ký canh tác' : $mobileNav['action'][0] }}" @if($mobileActionActive) aria-current="page" @endif>
                <span class="f2f-mobile-primary-icon">@include('layouts.partials.mobile-icon', ['icon' => $mobileNav['action'][2]])</span><span>{{ $mobileNav['action'][0] }}</span>
            </a>
        @endif
        @php $secondTab = $mobileNav['tabs'][1]; @endphp
        <a href="{{ route($secondTab[1]) }}" class="f2f-mobile-tab {{ request()->routeIs($secondTab[2]) ? 'active' : '' }}" @if(request()->routeIs($secondTab[2])) aria-current="page" @endif>
            @include('layouts.partials.mobile-icon', ['icon' => $secondTab[3]])<span>{{ $secondTab[0] }}</span>
        </a>
        <button class="f2f-mobile-tab" type="button" id="mobile-menu-toggle" aria-label="Mở tất cả chức năng" aria-controls="app-sidebar" aria-expanded="false">
            @include('layouts.partials.mobile-icon', ['icon' => 'menu'])<span>Menu</span>
        </button>
    </nav>
@endif

@if($mobileNav && $mobileNav['action'][2] === 'scan')
    <div class="f2f-qr-overlay" id="mobile-qr-scanner" hidden role="dialog" aria-modal="true" aria-labelledby="mobile-qr-title">
        <div class="f2f-qr-card">
            <div class="f2f-qr-head"><h2 id="mobile-qr-title">Quét mã QR</h2><button type="button" data-close-qr-scanner aria-label="Đóng trình quét">×</button></div>
            <p>Hướng camera vào mã QR trên lô hàng hoặc chọn ảnh mã QR để xem thông tin truy xuất.</p>
            <video id="mobile-qr-video" playsinline muted hidden></video>
            <p id="mobile-qr-status" role="status">Chọn cách quét mã.</p>
            <div class="f2f-qr-actions">
                <button type="button" class="f2f-btn-primary" id="mobile-qr-camera">Mở camera</button>
                <label class="f2f-btn-secondary" for="mobile-qr-file">Chọn ảnh QR</label>
                <input id="mobile-qr-file" type="file" accept="image/*" class="f2f-visually-hidden">
            </div>
            <form id="mobile-qr-manual" class="f2f-qr-manual">
                <label for="mobile-qr-link">Hoặc dán đường dẫn truy xuất</label>
                <div><input id="mobile-qr-link" class="f2f-input" type="url" inputmode="url" placeholder="https://.../01/.../10/..."><button class="f2f-btn-secondary" type="submit">Mở</button></div>
            </form>
        </div>
    </div>
@endif

{{-- Modal Xem Hồ Sơ Tài Khoản --}}
<div id="profile-modal" class="f2f-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
    <div class="f2f-modal-card">
        <div class="f2f-modal-header">
            <h3 id="profile-modal-title">Hồ sơ người dùng</h3>
            <button type="button" class="f2f-modal-close-btn" id="btn-close-profile-modal" aria-label="Đóng">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="f2f-modal-body">
            <div style="display:flex;align-items:center;gap:14px;padding:10px 0 16px;border-bottom:1px solid #f1f5f9;">
                <div class="f2f-avatar" style="width:48px;height:48px;font-size:18px;">
                    {{ mb_substr($authUser?->ho_ten ?? 'F', 0, 1) }}
                </div>
                <div>
                    <h4 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;">{{ $authUser?->ho_ten }}</h4>
                    <span style="font-size:13px;color:#64748b;">{{ $authUser?->email }}</span>
                </div>
            </div>

            <div class="f2f-profile-row">
                <span class="f2f-profile-label">Mã tài khoản:</span>
                <span class="f2f-profile-value">#{{ $authUser?->tai_khoan_id }}</span>
            </div>

            <div class="f2f-profile-row">
                <span class="f2f-profile-label">Vai trò:</span>
                <span class="f2f-profile-value">
                    <span class="f2f-dropdown-role-badge" style="margin:0;">
                        {{ $authUser?->vaiTro?->ten_vai_tro ?? $authUserRole ?? 'Chưa phân vai' }}
                    </span>
                </span>
            </div>

            <div class="f2f-profile-row">
                <span class="f2f-profile-label">Đơn vị / Cơ sở:</span>
                <span class="f2f-profile-value">{{ $authUser?->toChuc?->ten_to_chuc ?? 'Farm2Fork Việt Nam' }}</span>
            </div>

            <div class="f2f-profile-row">
                <span class="f2f-profile-label">Số điện thoại:</span>
                <span class="f2f-profile-value">{{ $authUser?->so_dien_thoai ?: 'Chưa cập nhật' }}</span>
            </div>

            <div class="f2f-profile-row">
                <span class="f2f-profile-label">Trạng thái:</span>
                <span class="f2f-profile-value">
                    @if($authUser?->trang_thai === 'DANG_HOAT_DONG')
                        <span style="color:#059669;font-weight:600;">● Đang hoạt động</span>
                    @else
                        <span style="color:#dc2626;font-weight:600;">{{ $authUser?->trang_thai }}</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="f2f-modal-footer">
            <a href="{{ route('auth.change-password.form') }}" class="f2f-btn-primary text-xs" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span>Đổi mật khẩu</span>
            </a>
            <button type="button" class="f2f-btn-secondary text-xs" id="btn-modal-cancel">Đóng</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dropdown user menu
    const trigger = document.getElementById('user-menu-trigger');
    const dropdown = document.getElementById('user-menu-dropdown');

    if (trigger && dropdown) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dropdown.style.display !== 'none';
            dropdown.style.display = isOpen ? 'none' : 'block';
            trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });

        document.addEventListener('click', function (e) {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && dropdown.style.display !== 'none') {
                dropdown.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });
    }

    // Profile modal
    const openProfileBtn = document.getElementById('btn-open-profile');
    const profileModal = document.getElementById('profile-modal');
    const closeProfileBtn = document.getElementById('btn-close-profile-modal');
    const cancelModalBtn = document.getElementById('btn-modal-cancel');

    function openModal() {
        if (dropdown) dropdown.style.display = 'none';
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
        if (profileModal) profileModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (profileModal) profileModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    openProfileBtn?.addEventListener('click', openModal);
    document.getElementById('mobile-profile-trigger')?.addEventListener('click', openModal);
    closeProfileBtn?.addEventListener('click', closeModal);
    cancelModalBtn?.addEventListener('click', closeModal);

    profileModal?.addEventListener('click', function (e) {
        if (e.target === profileModal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && profileModal && profileModal.style.display !== 'none') {
            closeModal();
        }
    });
});
</script>
</body>
</html>

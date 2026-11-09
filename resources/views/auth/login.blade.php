@extends('layouts.app', ['title' => 'Đăng nhập Farm2Fork'])

@section('content')
<div class="auth-page">
    <div class="f2f-card auth-split">
        <div class="auth-visual">
            <div class="auth-brand" style="display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:56px;width:auto;max-width:240px;object-fit:contain;">
            </div>
            <div>
                <h2>Truy xuất nông sản minh bạch, từ nông trại đến bàn ăn</h2>
                <div class="auth-journey">
                    <div class="auth-journey-step">
                        <div class="auth-journey-marker">
                            <span class="auth-journey-icon">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 20h10"/>
                                    <path d="M10 20c0-5.5 2-9 2-14"/>
                                    <path d="M12 6c2.5-3 6.5-2 7 2-3.5 1.5-6 .5-7-2Z"/>
                                    <path d="M12 11c-2.5-2.5-6-1.5-6.5 2 3 1.5 5.5.5 6.5-2Z"/>
                                </svg>
                            </span>
                            <div class="auth-journey-line"></div>
                        </div>
                        <div class="auth-journey-content">
                            <strong class="auth-journey-title">Nông trại</strong>
                            <span class="auth-journey-desc">Ghi nhật ký canh tác, khai báo lô thu hoạch</span>
                        </div>
                    </div>

                    <div class="auth-journey-step">
                        <div class="auth-journey-marker">
                            <span class="auth-journey-icon">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </span>
                            <div class="auth-journey-line"></div>
                        </div>
                        <div class="auth-journey-content">
                            <strong class="auth-journey-title">Kiểm định</strong>
                            <span class="auth-journey-desc">Chất lượng được ghi nhận theo từng lô</span>
                        </div>
                    </div>

                    <div class="auth-journey-step">
                        <div class="auth-journey-marker">
                            <span class="auth-journey-icon">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/>
                                    <path d="M10 10h4"/>
                                    <circle cx="12" cy="18" r="1.5" fill="currentColor"/>
                                </svg>
                            </span>
                            <div class="auth-journey-line"></div>
                        </div>
                        <div class="auth-journey-content">
                            <strong class="auth-journey-title">Kho lạnh</strong>
                            <span class="auth-journey-desc">Theo dõi nhiệt độ, độ ẩm và vận chuyển</span>
                        </div>
                    </div>

                    <div class="auth-journey-step">
                        <div class="auth-journey-marker">
                            <span class="auth-journey-icon">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="6" height="6" x="3" y="3" rx="1.5"/>
                                    <rect width="6" height="6" x="15" y="3" rx="1.5"/>
                                    <rect width="6" height="6" x="3" y="15" rx="1.5"/>
                                    <path d="M15 15h2a2 2 0 0 1 2 2v2"/>
                                    <path d="M15 21v-2"/>
                                    <path d="M21 15v.01"/>
                                    <path d="M10 7h1"/>
                                    <path d="M7 10v1"/>
                                    <path d="M10 17h4"/>
                                </svg>
                            </span>
                        </div>
                        <div class="auth-journey-content">
                            <strong class="auth-journey-title">Bàn ăn</strong>
                            <span class="auth-journey-desc">Quét QR để biết nguồn gốc sản phẩm</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="auth-visual-footer" style="font-size:13px">Hệ thống quản lý nội bộ chuỗi cung ứng Farm2Fork.</p>
        </div>

        <div class="auth-form-side">
            <p class="f2f-eyebrow">Chào mừng trở lại</p>
            <h1 class="f2f-heading">Đăng nhập</h1>
            <p class="f2f-subtitle">Nhập tài khoản đã được cấp bởi Farm2Fork để tiếp tục.</p>

            <form method="POST" action="{{ route('auth.login.submit') }}" class="f2f-form">
                @csrf
                <div class="auth-field">
                    <label class="f2f-label" for="login">Tài khoản hoặc email</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" class="f2f-input" placeholder="vd: nongho@example.com" required autofocus>
                </div>

                <div class="auth-field">
                    <div class="auth-password-header">
                        <label class="f2f-label" for="password">Mật khẩu</label>
                        <a href="{{ route('auth.password.forgot') }}" class="f2f-link">Quên mật khẩu?</a>
                    </div>
                    <div class="f2f-password-row">
                        <input id="password" name="password" type="password" class="f2f-input" placeholder="Nhập mật khẩu" required>
                        <button class="f2f-btn-ghost" type="button" data-toggle-password="password">Hiện</button>
                    </div>
                </div>

                <label class="f2f-checkbox">
                    <input type="checkbox" name="remember" value="1">
                    <span>Duy trì đăng nhập</span>
                </label>

                <div class="auth-actions">
                    <button type="submit" class="f2f-btn-primary">Đăng nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

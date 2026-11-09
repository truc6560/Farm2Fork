@extends('layouts.app', ['title' => ($title ?? 'Thông báo lời mời') . ' · Farm2Fork'])

@section('content')
<div class="auth-page">
    <div class="f2f-card auth-split">
        <div class="auth-visual">
            <div class="auth-brand" style="display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:56px;width:auto;max-width:240px;object-fit:contain;">
            </div>
            <div>
                <h2>Bảo mật & Quyền riêng tư trong chuỗi</h2>
                <p>Mỗi liên kết mời tham gia hệ thống Farm2Fork đều được giới hạn thời gian và mã hóa bảo mật nghiêm ngặt.</p>
                <ul class="auth-points">
                    <li><span>✓</span> Mỗi liên kết chỉ sử dụng được 1 lần</li>
                    <li><span>✓</span> Tự động vô hiệu khi hết hạn hoặc thu hồi</li>
                    <li><span>✓</span> Bảo vệ an toàn dữ liệu chuỗi cung ứng</li>
                </ul>
            </div>
            <p class="auth-visual-footer" style="font-size:13px">Hệ thống quản lý nội bộ chuỗi cung ứng Farm2Fork.</p>
        </div>

        <div class="auth-form-side" style="display:flex;flex-direction:column;justify-content:center;">
            <div style="text-align:center;padding:20px 0;">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#fef2f2;color:#dc2626;margin-bottom:16px;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>

                <p class="f2f-eyebrow" style="color:#dc2626;">Thông báo hệ thống</p>
                <h1 class="f2f-heading" style="font-size:22px;margin-bottom:12px;">{{ $title ?? 'Liên kết không hợp lệ' }}</h1>
                <p class="f2f-subtitle" style="font-size:14px;line-height:1.6;color:#475569;max-width:420px;margin:0 auto 24px auto;">
                    {{ $message ?? 'Liên kết mời tham gia không còn hiệu lực hoặc không tồn tại.' }}
                </p>

                <div style="display:flex;flex-direction:column;gap:10px;max-width:320px;margin:0 auto;">
                    @if(!empty($canLogin))
                        <a href="{{ route('auth.login.form') }}" class="f2f-btn-primary" style="text-decoration:none;display:block;text-align:center;padding:12px 20px;font-weight:600;">
                            Đến trang Đăng nhập
                        </a>
                    @else
                        <a href="{{ route('auth.login.form') }}" class="f2f-btn-ghost" style="text-decoration:none;display:block;text-align:center;padding:12px 20px;">
                            Quay lại trang Đăng nhập
                        </a>
                    @endif
                </div>

                <p style="font-size:13px;color:#94a3b8;margin-top:28px;">
                    Nếu cần trợ giúp, xin vui lòng liên hệ trực tiếp với Quản trị viên phụ trách hệ thống Farm2Fork.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection


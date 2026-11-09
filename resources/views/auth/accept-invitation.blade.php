@extends('layouts.app', ['title' => 'Kích hoạt tài khoản · Farm2Fork'])

@section('content')
<div class="auth-page">
    <div class="f2f-card auth-split">
        <div class="auth-visual">
            <div class="auth-brand" style="display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:56px;width:auto;max-width:240px;object-fit:contain;">
            </div>
            <div>
                <h2>Chào mừng bạn đến với mạng lưới Farm2Fork</h2>
                <p>Nền tảng quản lý chuỗi cung ứng nông sản và giám sát nhiệt độ lạnh theo thời gian thực.</p>
                <ul class="auth-points">
                    <li><span>✓</span> Tài khoản được xác thực bởi Quản trị viên</li>
                    <li><span>✓</span> Chủ động thiết lập mật khẩu bảo mật riêng</li>
                    <li><span>✓</span> Đăng nhập tự động ngay sau khi kích hoạt</li>
                </ul>
            </div>
            <p class="auth-visual-footer" style="font-size:13px">Hệ thống vận hành chuỗi cung ứng lạnh nội bộ Farm2Fork.</p>
        </div>

        <div class="auth-form-side">
            <p class="f2f-eyebrow">Lời mời thành viên</p>
            <h1 class="f2f-heading">Kích hoạt tài khoản</h1>
            <p class="f2f-subtitle">Vui lòng hoàn tất thông tin cá nhân và thiết lập mật khẩu đăng nhập.</p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #1b4d3e;border-radius:8px;padding:14px 16px;margin-bottom:20px;">
                <div style="font-size:13px;color:#64748b;margin-bottom:4px;">Email tài khoản:</div>
                <div style="font-weight:700;color:#0f172a;font-size:15px;margin-bottom:8px;">{{ $invitation->email }}</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                    <span class="f2f-tag" style="background:#ecfdf5;color:#065f46;border-color:#a7f3d0;font-weight:600;">
                        {{ $invitation->vaiTro->ten_vai_tro ?? 'Thành viên' }}
                    </span>
                    <span style="font-size:12px;color:#64748b;">
                        {{ $invitation->pham_vi_chi_tiet }}
                    </span>
                </div>
            </div>

            @if($errors->any())
                <div class="f2f-alert f2f-alert-error" style="margin-bottom:16px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('invitations.submit', ['token' => $token]) }}" class="f2f-form">
                @csrf

                <div class="auth-field">
                    <label class="f2f-label" for="ho_ten">Họ và tên <span style="color:#dc2626;">*</span></label>
                    <input id="ho_ten" name="ho_ten" type="text" value="{{ old('ho_ten') }}" class="f2f-input" placeholder="vd: Nguyễn Văn An" required autofocus>
                </div>

                <div class="auth-field">
                    <label class="f2f-label" for="so_dien_thoai">Số điện thoại (tùy chọn)</label>
                    <input id="so_dien_thoai" name="so_dien_thoai" type="tel" value="{{ old('so_dien_thoai') }}" class="f2f-input" placeholder="vd: 0912 345 678">
                </div>

                <div class="auth-field">
                    <label class="f2f-label" for="password">Mật khẩu mới <span style="color:#dc2626;">*</span></label>
                    <div class="f2f-password-row">
                        <input id="password" name="password" type="password" class="f2f-input" placeholder="Tối thiểu 10 ký tự..." required>
                        <button class="f2f-btn-ghost" type="button" data-toggle-password="password">Hiện</button>
                    </div>
                </div>

                <div class="auth-field">
                    <label class="f2f-label" for="password_confirmation">Xác nhận mật khẩu mới <span style="color:#dc2626;">*</span></label>
                    <div class="f2f-password-row">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="f2f-input" placeholder="Nhập lại mật khẩu..." required>
                        <button class="f2f-btn-ghost" type="button" data-toggle-password="password_confirmation">Hiện</button>
                    </div>
                </div>

                <p style="font-size:12px;color:#64748b;margin-top:6px;line-height:1.4;">
                    🛡️ Yêu cầu: Mật khẩu tối thiểu 10 ký tự, có chữ hoa (A-Z), chữ thường (a-z), chữ số (0-9) và ký tự đặc biệt (@, #, $, !...).
                </p>

                <div class="auth-actions" style="margin-top:20px;">
                    <button type="submit" class="f2f-btn-primary" style="min-height:48px;font-size:15px;width:100%;font-weight:600;">
                        Kích hoạt tài khoản & Đăng nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


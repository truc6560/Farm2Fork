@extends('layouts.app', ['title' => 'Đặt lại mật khẩu · Farm2Fork'])

@section('content')
<div class="auth-page">
    <div class="f2f-card auth-split">
        <div class="auth-visual">
            <div class="auth-brand" style="display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:56px;width:auto;max-width:240px;object-fit:contain;">
            </div>
            <div>
                <h2>Thiết lập mật khẩu mới</h2>
                <p>Mật khẩu mới cần đáp ứng chính sách bảo mật hệ thống để bảo vệ thông tin truy xuất nguồn gốc và vận hành chuỗi lạnh.</p>
                <ul class="auth-points">
                    <li><span>✓</span> Tối thiểu 10 ký tự</li>
                    <li><span>✓</span> Ít nhất 1 chữ in hoa (A-Z) và 1 chữ thường (a-z)</li>
                    <li><span>✓</span> Ít nhất 1 chữ số (0-9)</li>
                    <li><span>✓</span> Ít nhất 1 ký tự đặc biệt (@, #, $, %,...)</li>
                </ul>
            </div>
            <p class="auth-visual-footer" style="font-size:13px">Hệ thống Farm2Fork bảo mật tiêu chuẩn ngành.</p>
        </div>

        <div class="auth-form-side">
            <p class="f2f-eyebrow">Bảo mật tài khoản</p>
            <h1 class="f2f-heading">Đặt lại mật khẩu</h1>
            <p class="f2f-subtitle">Nhập mật khẩu mới cho tài khoản: <strong>{{ $email }}</strong></p>

            @if($errors->any())
                <div class="f2f-alert f2f-alert-error" style="margin-bottom:16px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('auth.password.update') }}" class="f2f-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="auth-field">
                    <label class="f2f-label" for="password">Mật khẩu mới</label>
                    <div class="f2f-password-row">
                        <input id="password" name="password" type="password" class="f2f-input" placeholder="Tối thiểu 10 ký tự phức tạp" required autofocus>
                        <button class="f2f-btn-ghost" type="button" data-toggle-password="password">Hiện</button>
                    </div>
                </div>

                <div class="auth-field">
                    <label class="f2f-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                    <div class="f2f-password-row">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="f2f-input" placeholder="Nhập lại mật khẩu mới" required>
                        <button class="f2f-btn-ghost" type="button" data-toggle-password="password_confirmation">Hiện</button>
                    </div>
                </div>

                <div class="auth-actions" style="margin-top:20px;">
                    <button type="submit" class="f2f-btn-primary" style="width:100%;">Lưu mật khẩu mới & Đăng nhập</button>
                </div>
            </form>

            <div class="auth-switch" style="margin-top:24px;">
                <a href="{{ route('auth.login.form') }}" class="f2f-link">&larr; Quay lại trang đăng nhập</a>
            </div>
        </div>
    </div>
</div>
@endsection


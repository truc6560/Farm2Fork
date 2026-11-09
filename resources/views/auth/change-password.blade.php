@extends('layouts.app', ['title' => 'Đổi mật khẩu'])

@section('content')
<div class="auth-page">
    <div class="f2f-card auth-split">
        <div class="auth-visual">
            <div class="auth-brand" style="display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:56px;width:auto;max-width:240px;object-fit:contain;">
            </div>
            <div>
                <h2>Bảo vệ tài khoản của bạn</h2>
                <p>Mật khẩu mạnh giúp bảo vệ hồ sơ cơ sở và dữ liệu truy xuất khỏi truy cập trái phép.</p>
                <ul class="auth-points">
                    <li><span>✓</span> Tối thiểu 10 ký tự</li>
                    <li><span>✓</span> Gồm chữ hoa, chữ thường, số và ký tự đặc biệt</li>
                    <li><span>✓</span> Không dùng lại mật khẩu cũ</li>
                </ul>
            </div>
            <p style="font-size:13px">Sau khi đổi xong bạn sẽ được chuyển vào hệ thống.</p>
        </div>

        <div class="auth-form-side">
            <p class="f2f-eyebrow">Bảo mật tài khoản</p>
            <h1 class="f2f-heading">Đổi mật khẩu</h1>
            <p class="f2f-subtitle">
                @if($mustChange)
                    Bạn bắt buộc đổi mật khẩu ban đầu trước khi sử dụng chức năng nghiệp vụ.
                @else
                    Cập nhật mật khẩu để tăng cường bảo mật.
                @endif
            </p>

            <form method="POST" action="{{ route('auth.change-password.update') }}" class="f2f-form">
                @csrf
                @method('PUT')

                <div class="auth-field">
                    <label class="f2f-label" for="current_password">Mật khẩu hiện tại</label>
                    <input id="current_password" name="current_password" type="password" class="f2f-input" required>
                </div>

                <div class="auth-field">
                    <label class="f2f-label" for="new_password">Mật khẩu mới</label>
                    <input id="new_password" name="new_password" type="password" class="f2f-input" required>
                    <p class="f2f-hint">Tối thiểu 10 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</p>
                </div>

                <div class="auth-field">
                    <label class="f2f-label" for="new_password_confirmation">Xác nhận mật khẩu mới</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" class="f2f-input" required>
                </div>

                <div class="auth-actions">
                    <button type="submit" class="f2f-btn-primary">Cập nhật mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

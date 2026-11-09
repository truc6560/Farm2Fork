@extends('layouts.app', ['title' => 'Quên mật khẩu · Farm2Fork'])

@section('content')
<div class="auth-page">
    <div class="f2f-card auth-split">
        <div class="auth-visual">
            <div class="auth-brand" style="display:flex;align-items:center;">
                <img src="{{ asset('ảnh/logo.png') }}" alt="Farm2Fork Logo" style="height:56px;width:auto;max-width:240px;object-fit:contain;">
            </div>
            <div>
                <h2>Khôi phục quyền truy cập tài khoản Farm2Fork</h2>
                <p>Hệ thống hỗ trợ gửi hướng dẫn đặt lại mật khẩu an toàn theo quy chuẩn bảo mật chuỗi cung ứng.</p>
                <ul class="auth-points">
                    <li><span>✓</span> Mã xác thực được mã hóa an toàn</li>
                    <li><span>✓</span> Thời hạn liên kết tự hủy sau 60 phút</li>
                    <li><span>✓</span> Áp dụng chính sách mật khẩu mạnh</li>
                </ul>
            </div>
            <p class="auth-visual-footer" style="font-size:13px">Cần hỗ trợ trực tiếp? Liên hệ quản trị viên đơn vị của bạn.</p>
        </div>

        <div class="auth-form-side">
            <p class="f2f-eyebrow">Khôi phục tài khoản</p>
            <h1 class="f2f-heading">Quên mật khẩu?</h1>
            <p class="f2f-subtitle">Nhập địa chỉ email đăng ký để nhận liên kết đặt lại mật khẩu mới.</p>

            @if(session('status'))
                <div class="f2f-alert f2f-alert-success" style="margin-bottom:16px;">
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="f2f-alert f2f-alert-error" style="margin-bottom:16px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('auth.password.email') }}" class="f2f-form">
                @csrf
                <div class="auth-field">
                    <label class="f2f-label" for="email">Địa chỉ email tài khoản</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="f2f-input" placeholder="vd: nongho@example.com" required autofocus>
                </div>

                <div class="auth-actions" style="margin-top:20px;">
                    <button type="submit" class="f2f-btn-primary" style="width:100%;">Gửi liên kết đặt lại mật khẩu</button>
                </div>
            </form>

            <div class="auth-switch" style="margin-top:24px;">
                Nhớ mật khẩu?
                <a href="{{ route('auth.login.form') }}" class="f2f-link">Quay lại đăng nhập</a>
            </div>
        </div>
    </div>
</div>
@endsection


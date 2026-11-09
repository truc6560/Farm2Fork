<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AccountStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Vui lòng nhập tài khoản hoặc email.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $loginValue = trim($credentials['login']);
        $password = $credentials['password'];

        $attemptPayload = [
            'email' => $loginValue,
            'password' => $password,
        ];

        if (!Auth::attempt($attemptPayload, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'Thông tin đăng nhập không đúng hoặc tài khoản chưa được cấp quyền truy cập.',
            ]);
        }

        $user = Auth::user();

        if ($user && !in_array($user->trang_thai, AccountStatus::activeStatuses(), true)) {
            Auth::logout();
            $request->session()->invalidate();

            $message = $user->trang_thai === AccountStatus::CHO_KICH_HOAT
                ? 'Tài khoản của bạn chưa được kích hoạt. Vui lòng chờ phê duyệt hồ sơ.'
                : 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';

            throw ValidationException::withMessages(['login' => $message]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login.form');
    }
}

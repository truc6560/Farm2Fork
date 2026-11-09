<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\TaiKhoanHeThong;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Hiển thị giao diện yêu cầu quên mật khẩu.
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Tiếp nhận yêu cầu, tạo mã đặt lại mật khẩu và gửi liên kết.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email của bạn.',
            'email.email'    => 'Địa chỉ email không đúng định dạng.',
        ]);

        $email = trim(strtolower($validated['email']));

        // Kiểm tra tài khoản tồn tại trong hệ thống
        $user = TaiKhoanHeThong::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Địa chỉ email này chưa được đăng ký trong hệ thống Farm2Fork.',
            ])->withInput();
        }

        if ($user->trang_thai !== 'DANG_HOAT_DONG') {
            return back()->withErrors([
                'email' => "Tài khoản của bạn đang ở trạng thái '{$user->trang_thai}'. Vui lòng liên hệ quản trị viên.",
            ])->withInput();
        }

        // Tạo token ngẫu nhiên bảo mật
        $token = Str::random(60);

        // Xóa các yêu cầu cũ cho email này
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Lưu token mới
        DB::table('password_reset_tokens')->insert([
            'email'      => $email,
            'token'      => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Tạo đường link đặt lại mật khẩu
        $resetUrl = route('auth.password.reset', [
            'token' => $token,
            'email' => $email,
        ]);

        try {
            Mail::to($email)->send(new PasswordResetMail($resetUrl, $email));
        } catch (\Throwable $e) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            Log::error('Không thể gửi email đặt lại mật khẩu Farm2Fork.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Không thể gửi email lúc này. Vui lòng kiểm tra cấu hình email hoặc thử lại sau.',
            ])->withInput();
        }

        // Ghi nhật ký hệ thống
        Log::info("Yêu cầu đặt lại mật khẩu Farm2Fork cho tài khoản [{$email}]. Link: {$resetUrl}");

        return back()->with('status', "Liên kết đặt lại mật khẩu đã được gửi tới email {$email}. Liên kết có hiệu lực trong 60 phút.");
    }

    /**
     * Hiển thị giao diện nhập mật khẩu mới.
     */
    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email');

        if (empty($email)) {
            return redirect()->route('auth.password.forgot')
                ->withErrors(['email' => 'Yêu cầu không hợp lệ hoặc thiếu thông tin email.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Xác thực token và cập nhật mật khẩu mới.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'max:191'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:10',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'token.required'     => 'Thiếu mã thông báo xác thực.',
            'email.required'     => 'Vui lòng nhập email.',
            'password.required'  => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min'       => 'Mật khẩu mới phải có tối thiểu 10 ký tự.',
            'password.regex'     => 'Mật khẩu mới phải có ít nhất một chữ hoa, chữ thường, chữ số và một ký tự đặc biệt.',
        ]);

        $email = trim(strtolower($validated['email']));
        $rawToken = $validated['token'];

        // Kiểm tra bản ghi token
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return back()->withErrors([
                'email' => 'Yêu cầu đặt lại mật khẩu không tồn tại hoặc đã hết hạn.',
            ])->withInput();
        }

        // Kiểm tra thời hạn hiệu lực (60 phút)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('auth.password.forgot')->withErrors([
                'email' => 'Liên kết đặt lại mật khẩu đã hết hạn sau 60 phút. Vui lòng gửi lại yêu cầu.',
            ]);
        }

        // Kiểm tra tính hợp lệ của token
        if (!Hash::check($rawToken, $record->token)) {
            return back()->withErrors([
                'token' => 'Mã xác thực đặt lại mật khẩu không hợp lệ.',
            ])->withInput();
        }

        // Cập nhật mật khẩu cho tài khoản
        $user = TaiKhoanHeThong::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Không tìm thấy tài khoản người dùng này.']);
        }

        $user->mat_khau_hash = Hash::make($validated['password']);
        $user->save();

        // Xóa token đã sử dụng
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Xóa cờ bắt buộc đổi mật khẩu nếu có
        app(\App\Support\InitialPasswordStore::class)->clearForceChange((int)$user->tai_khoan_id);

        Log::info("Tài khoản [{$email}] đã đặt lại mật khẩu thành công qua chức năng Quên mật khẩu.");

        return redirect()->route('auth.login.form')->with(
            'status',
            'Mật khẩu của bạn đã được cập nhật thành công! Vui lòng đăng nhập với mật khẩu mới.'
        );
    }
}


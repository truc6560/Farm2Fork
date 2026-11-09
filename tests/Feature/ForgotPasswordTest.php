<?php

namespace Tests\Feature;

use App\Models\TaiKhoanHeThong;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }
    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Quên mật khẩu?');
    }

    public function test_reset_link_can_be_requested_for_valid_user(): void
    {
        $user = TaiKhoanHeThong::where('trang_thai', 'DANG_HOAT_DONG')->first();
        $this->assertNotNull($user);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Mail::assertSent(PasswordResetMail::class, fn ($mail) => $mail->recipientEmail === $user->email);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = TaiKhoanHeThong::where('trang_thai', 'DANG_HOAT_DONG')->first();
        $this->assertNotNull($user);

        $rawToken = 'sample-test-token-12345678901234567890';
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($rawToken),
            'created_at' => now(),
        ]);

        $newPassword = 'Password@2026';

        $response = $this->post('/reset-password', [
            'token' => $rawToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->mat_khau_hash));
    }

    public function test_public_registration_route_returns_404(): void
    {
        $response = $this->get('/register-organization');
        $response->assertStatus(404);

        $postResponse = $this->post('/register-organization', []);
        $postResponse->assertStatus(404);
    }

    public function test_login_page_renders_internal_messaging(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Nông trại');
        $response->assertSee('Kiểm định');
        $response->assertSee('Kho lạnh');
        $response->assertSee('Bàn ăn');
        $response->assertSee('Hệ thống quản lý nội bộ chuỗi cung ứng Farm2Fork.');
        $response->assertSee('Nhập tài khoản đã được cấp bởi Farm2Fork để tiếp tục.');
        $response->assertDontSee('register-organization');
        $response->assertDontSee('Đăng ký tham gia hệ thống');
    }
}


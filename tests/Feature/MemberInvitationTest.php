<?php

namespace Tests\Feature;

use App\Models\KhoLanh;
use App\Models\LoiMoi;
use App\Models\TaiKhoanHeThong;
use App\Models\VaiTro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberInvitationTest extends TestCase
{
    protected function getAdminUser(): TaiKhoanHeThong
    {
        $adminRole = VaiTro::where('ma_vai_tro', 'ADMIN')->firstOrFail();
        return TaiKhoanHeThong::where('vai_tro_id', $adminRole->vai_tro_id)->firstOrFail();
    }

    protected function getNonAdminUser(): TaiKhoanHeThong
    {
        $nonAdminRole = VaiTro::where('ma_vai_tro', '<>', 'ADMIN')->firstOrFail();
        return TaiKhoanHeThong::where('vai_tro_id', $nonAdminRole->vai_tro_id)->firstOrFail();
    }

    public function test_admin_can_view_members_page(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get('/admin/members');

        $response->assertStatus(200);
        $response->assertSee('Mời thành viên mới');
        $response->assertSee('Thành viên hiện có');
        $response->assertSee('Lời mời tham gia');
    }

    public function test_non_admin_cannot_access_members_page(): void
    {
        $nonAdmin = $this->getNonAdminUser();

        $response = $this->actingAs($nonAdmin)->get('/admin/members');

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/members');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_invite_member_successfully(): void
    {
        $admin = $this->getAdminUser();
        $testEmail = 'new_member_' . uniqid() . '@example.com';
        $role = VaiTro::where('ma_vai_tro', 'NV_KHO_LANH')->firstOrFail();
        $kho = KhoLanh::first();

        $response = $this->actingAs($admin)->post('/admin/members/invite', [
            'email' => $testEmail,
            'vai_tro_id' => $role->vai_tro_id,
            'kho_lanh_id' => $kho?->kho_lanh_id,
        ]);

        $response->assertRedirect(route('admin.members.index', ['tab' => 'invitations']));
        $response->assertSessionHas('status');
        $response->assertSessionHas('latest_invitation_url');

        $invitation = LoiMoi::where('email', $testEmail)->first();
        $this->assertNotNull($invitation);
        $this->assertEquals(LoiMoi::TRANG_THAI_CHO, $invitation->trang_thai);
        $this->assertEquals($role->vai_tro_id, $invitation->vai_tro_id);
        $this->assertEquals($admin->tai_khoan_id, $invitation->nguoi_moi_id);
        $this->assertNotEmpty($invitation->ma_hash);
        $this->assertEquals(64, strlen($invitation->ma_hash));
    }

    public function test_cannot_invite_existing_account_email(): void
    {
        $admin = $this->getAdminUser();
        $role = VaiTro::where('ma_vai_tro', 'NGUOI_SAN_XUAT')->firstOrFail();

        $response = $this->actingAs($admin)->post('/admin/members/invite', [
            'email' => $admin->email,
            'vai_tro_id' => $role->vai_tro_id,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_cannot_invite_duplicate_active_pending_email(): void
    {
        $admin = $this->getAdminUser();
        $testEmail = 'pending_dup_' . uniqid() . '@example.com';
        $role = VaiTro::where('ma_vai_tro', 'QC')->firstOrFail();

        // First invite
        $this->actingAs($admin)->post('/admin/members/invite', [
            'email' => $testEmail,
            'vai_tro_id' => $role->vai_tro_id,
        ]);

        // Second invite with same email
        $response = $this->actingAs($admin)->post('/admin/members/invite', [
            'email' => $testEmail,
            'vai_tro_id' => $role->vai_tro_id,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_resend_and_refresh_invitation(): void
    {
        $admin = $this->getAdminUser();
        $testEmail = 'resend_test_' . uniqid() . '@example.com';
        $role = VaiTro::where('ma_vai_tro', 'TAI_XE')->firstOrFail();

        $this->actingAs($admin)->post('/admin/members/invite', [
            'email' => $testEmail,
            'vai_tro_id' => $role->vai_tro_id,
        ]);

        $invitation = LoiMoi::where('email', $testEmail)->firstOrFail();
        $oldHash = $invitation->ma_hash;

        // Resend
        $response = $this->actingAs($admin)->post("/admin/members/invitations/{$invitation->id}/resend");
        $response->assertRedirect(route('admin.members.index', ['tab' => 'invitations']));

        $invitation->refresh();
        $this->assertNotEquals($oldHash, $invitation->ma_hash);
        $this->assertEquals(LoiMoi::TRANG_THAI_CHO, $invitation->trang_thai);
    }

    public function test_admin_can_revoke_invitation(): void
    {
        $admin = $this->getAdminUser();
        $testEmail = 'revoke_test_' . uniqid() . '@example.com';
        $role = VaiTro::where('ma_vai_tro', 'QUAN_LY_CHUOI')->firstOrFail();

        $this->actingAs($admin)->post('/admin/members/invite', [
            'email' => $testEmail,
            'vai_tro_id' => $role->vai_tro_id,
        ]);

        $invitation = LoiMoi::where('email', $testEmail)->firstOrFail();

        $response = $this->actingAs($admin)->post("/admin/members/invitations/{$invitation->id}/revoke");
        $response->assertRedirect(route('admin.members.index', ['tab' => 'invitations']));

        $invitation->refresh();
        $this->assertEquals(LoiMoi::TRANG_THAI_DA_THU_HOI, $invitation->trang_thai);
    }

    public function test_invited_user_can_view_accept_form_and_set_password(): void
    {
        $admin = $this->getAdminUser();
        $rawToken = Str::random(64);
        $testEmail = 'accepted_user_' . uniqid() . '@example.com';
        $role = VaiTro::where('ma_vai_tro', 'NV_KHO_LANH')->firstOrFail();
        $kho = KhoLanh::first();

        $invitation = LoiMoi::create([
            'email' => $testEmail,
            'vai_tro_id' => $role->vai_tro_id,
            'to_chuc_id' => 1,
            'kho_lanh_id' => $kho?->kho_lanh_id,
            'ma_hash' => hash('sha256', $rawToken),
            'het_han' => now()->addDays(7),
            'trang_thai' => LoiMoi::TRANG_THAI_CHO,
            'nguoi_moi_id' => $admin->tai_khoan_id,
        ]);

        // 1. View form
        $viewResponse = $this->get("/invitations/accept/{$rawToken}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee($testEmail);
        $viewResponse->assertSee('Kích hoạt tài khoản');

        // 2. Submit form
        $password = 'Secret@2026!';
        $submitResponse = $this->post("/invitations/accept/{$rawToken}", [
            'ho_ten' => 'Người Dùng Mới',
            'so_dien_thoai' => '0987654321',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $submitResponse->assertRedirect('/dashboard');

        // 3. Verify user created
        $newUser = TaiKhoanHeThong::where('email', $testEmail)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Người Dùng Mới', $newUser->ho_ten);
        $this->assertEquals($role->vai_tro_id, $newUser->vai_tro_id);
        $this->assertTrue(Hash::check($password, $newUser->mat_khau_hash));

        // 4. Verify scope assignment (warehouse)
        if ($kho) {
            $this->assertDatabaseHas('nhan_vien_kho', [
                'tai_khoan_id' => $newUser->tai_khoan_id,
                'kho_lanh_id' => $kho->kho_lanh_id,
            ]);
        }

        // 5. Verify invitation marked as accepted
        $invitation->refresh();
        $this->assertEquals(LoiMoi::TRANG_THAI_DA_CHAP_NHAN, $invitation->trang_thai);

        // 6. User cannot reuse accepted link
        $reuseResponse = $this->get("/invitations/accept/{$rawToken}");
        $reuseResponse->assertStatus(200);
        $reuseResponse->assertSee('Lời mời đã được chấp nhận');
    }

    public function test_expired_or_revoked_invitations_are_rejected(): void
    {
        $admin = $this->getAdminUser();
        $rawTokenExpired = Str::random(64);
        $rawTokenRevoked = Str::random(64);
        $role = VaiTro::first();

        // Expired invitation
        LoiMoi::create([
            'email' => 'expired_' . uniqid() . '@example.com',
            'vai_tro_id' => $role->vai_tro_id,
            'to_chuc_id' => 1,
            'ma_hash' => hash('sha256', $rawTokenExpired),
            'het_han' => now()->subDay(),
            'trang_thai' => LoiMoi::TRANG_THAI_CHO,
            'nguoi_moi_id' => $admin->tai_khoan_id,
        ]);

        // Revoked invitation
        LoiMoi::create([
            'email' => 'revoked_' . uniqid() . '@example.com',
            'vai_tro_id' => $role->vai_tro_id,
            'to_chuc_id' => 1,
            'ma_hash' => hash('sha256', $rawTokenRevoked),
            'het_han' => now()->addDays(7),
            'trang_thai' => LoiMoi::TRANG_THAI_DA_THU_HOI,
            'nguoi_moi_id' => $admin->tai_khoan_id,
        ]);

        // Check expired
        $expResponse = $this->get("/invitations/accept/{$rawTokenExpired}");
        $expResponse->assertStatus(200);
        $expResponse->assertSee('Liên kết mời đã hết hạn');

        // Check revoked
        $revResponse = $this->get("/invitations/accept/{$rawTokenRevoked}");
        $revResponse->assertStatus(200);
        $revResponse->assertSee('Lời mời đã bị thu hồi');

        // Check invalid/random token
        $fakeResponse = $this->get('/invitations/accept/' . Str::random(64));
        $fakeResponse->assertStatus(200);
        $fakeResponse->assertSee('Liên kết không hợp lệ');
    }
}

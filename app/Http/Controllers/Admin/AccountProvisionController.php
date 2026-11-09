<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoanHeThong;
use App\Models\VaiTro;
use App\Services\OrganizationRegistrationService;
use App\Support\AccountStatus;
use App\Support\InitialPasswordStore;
use App\Support\RegistrationStatuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountProvisionController extends Controller
{
    public function __construct(
        private OrganizationRegistrationService $registrations,
        private InitialPasswordStore $initialPasswordStore
    ) {
    }

    public function provision(Request $request, string $applicationCode): RedirectResponse
    {
        $registration = $this->registrations->findByCode($applicationCode);
        abort_unless($registration, 404);

        if (($registration['status'] ?? null) !== RegistrationStatuses::DA_PHE_DUYET) {
            return back()->withErrors([
                'provision' => 'Chỉ có thể cấp tài khoản cho hồ sơ đã phê duyệt.',
            ]);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30'],
            'vai_tro_id' => ['required', 'integer', 'exists:vai_tro,vai_tro_id'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ tên người được cấp tài khoản.',
            'email.required' => 'Vui lòng nhập email tài khoản.',
            'vai_tro_id.required' => 'Vui lòng chọn vai trò.',
        ]);

        $role = VaiTro::findOrFail($validated['vai_tro_id']);

        if ($role->ma_vai_tro === 'ADMIN') {
            return back()->withErrors([
                'vai_tro_id' => 'Không cho phép cấp vai trò quản trị viên từ luồng đăng ký công khai.',
            ]);
        }

        $existing = TaiKhoanHeThong::where('email', $validated['email'])->first();
        if ($existing) {
            return back()->withErrors([
                'email' => 'Email này đã tồn tại trong hệ thống. Không thể cấp trùng tài khoản.',
            ]);
        }

        $initialPasswordPlain = Str::random(14).'!aA1';

        $created = DB::transaction(function () use ($registration, $validated, $initialPasswordPlain) {
            $account = TaiKhoanHeThong::create([
                'vai_tro_id' => $validated['vai_tro_id'],
                'to_chuc_id' => $registration['organization_id'],
                'ho_ten' => $validated['full_name'],
                'email' => $validated['email'],
                'mat_khau_hash' => Hash::make($initialPasswordPlain),
                'so_dien_thoai' => $validated['phone'] ?: null,
                'trang_thai' => AccountStatus::DANG_HOAT_DONG,
            ]);

            return $account;
        });

        $this->initialPasswordStore->markForceChange((int) $created->tai_khoan_id);
        $this->registrations->markProvisioned($applicationCode, (int) $created->tai_khoan_id);

        $mailStatus = 'SIMULATED';
        $mailMessage = 'Hệ thống đang ở chế độ phát triển: đã lưu log thông tin cấp tài khoản, chưa gửi email thực tế.';

        if (config('mail.default') !== 'log') {
            Mail::raw(
                "Tài khoản Farm2Fork của bạn đã được cấp.\nEmail: {$created->email}\nMật khẩu tạm thời được cấp bởi quản trị viên. Vui lòng đăng nhập và đổi mật khẩu ngay.",
                function ($message) use ($created) {
                    $message->to($created->email)
                        ->subject('Farm2Fork - Tài khoản được cấp');
                }
            );
            $mailStatus = 'SENT';
            $mailMessage = 'Email hướng dẫn đăng nhập đã được gửi.';
        }

        return redirect()->route('admin.registrations.show', $applicationCode)
            ->with('status', 'Cấp tài khoản thành công. '.$mailMessage)
            ->with('provisioning_info', [
                'email' => $created->email,
                'mail_status' => $mailStatus,
            ]);
    }
}

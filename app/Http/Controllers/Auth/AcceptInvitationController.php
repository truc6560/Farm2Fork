<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoiMoi;
use App\Models\TaiKhoanHeThong;
use App\Support\AccountStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    /**
     * Show the invitation acceptance form or status screen.
     */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $hash = hash('sha256', $token);

        $invitation = LoiMoi::with(['vaiTro', 'toChuc', 'khoLanh', 'vungTrong', 'phuongTien', 'nguoiMoi'])
            ->where('ma_hash', $hash)
            ->first();

        if (!$invitation) {
            return view('auth.invitation-invalid', [
                'title' => 'Liên kết không hợp lệ',
                'message' => 'Liên kết mời này không tồn tại trong hệ thống hoặc đã được cấp mã mới.',
                'canLogin' => false,
            ]);
        }

        if ($invitation->trang_thai === LoiMoi::TRANG_THAI_DA_CHAP_NHAN) {
            return view('auth.invitation-invalid', [
                'title' => 'Lời mời đã được chấp nhận',
                'message' => 'Lời mời cho tài khoản ' . $invitation->email . ' đã được chấp nhận trước đó. Vui lòng đăng nhập với mật khẩu bạn đã tạo.',
                'canLogin' => true,
            ]);
        }

        if ($invitation->trang_thai === LoiMoi::TRANG_THAI_DA_THU_HOI) {
            return view('auth.invitation-invalid', [
                'title' => 'Lời mời đã bị thu hồi',
                'message' => 'Quản trị viên đã thu hồi lời mời này. Vui lòng liên hệ với ban quản trị nếu bạn cần được cấp tài khoản.',
                'canLogin' => false,
            ]);
        }

        if ($invitation->isExpired()) {
            if ($invitation->trang_thai === LoiMoi::TRANG_THAI_CHO) {
                $invitation->update(['trang_thai' => LoiMoi::TRANG_THAI_HET_HAN]);
            }

            return view('auth.invitation-invalid', [
                'title' => 'Liên kết mời đã hết hạn',
                'message' => 'Liên kết mời chỉ có thời hạn 7 ngày và đã hết hạn vào lúc ' . $invitation->het_han->format('H:i d/m/Y') . '. Vui lòng liên hệ quản trị viên để nhận lại lời mời.',
                'canLogin' => false,
            ]);
        }

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    /**
     * Process acceptance, create user account, assign scopes, and auto-login.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);

        $invitation = LoiMoi::where('ma_hash', $hash)->first();

        if (!$invitation || !$invitation->canBeAccepted()) {
            return redirect()->route('invitations.accept', ['token' => $token]);
        }

        $validated = $request->validate([
            'ho_ten' => ['required', 'string', 'max:150'],
            'so_dien_thoai' => ['nullable', 'string', 'max:30'],
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
            'ho_ten.required' => 'Vui lòng nhập họ và tên của bạn.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có tối thiểu 10 ký tự.',
            'password.regex' => 'Mật khẩu phải bao gồm ít nhất một chữ hoa, chữ thường, chữ số và một ký tự đặc biệt.',
        ]);

        if (TaiKhoanHeThong::where('email', $invitation->email)->exists()) {
            return back()->withErrors([
                'email' => 'Địa chỉ email này đã có tài khoản trên hệ thống. Vui lòng đăng nhập.',
            ]);
        }

        $user = DB::transaction(function () use ($invitation, $validated) {
            $createdUser = TaiKhoanHeThong::create([
                'vai_tro_id' => $invitation->vai_tro_id,
                'to_chuc_id' => $invitation->to_chuc_id ?? 1,
                'ho_ten' => trim($validated['ho_ten']),
                'email' => $invitation->email,
                'mat_khau_hash' => Hash::make($validated['password']),
                'so_dien_thoai' => !empty($validated['so_dien_thoai']) ? trim($validated['so_dien_thoai']) : null,
                'trang_thai' => AccountStatus::DANG_HOAT_DONG,
            ]);

            // Phân công phạm vi theo vai trò
            if ($invitation->kho_lanh_id) {
                DB::table('nhan_vien_kho')->updateOrInsert(
                    ['tai_khoan_id' => $createdUser->tai_khoan_id, 'kho_lanh_id' => $invitation->kho_lanh_id],
                    ['ngay_bat_dau' => now()->toDateString(), 'ghi_chu' => 'Gán từ lời mời thành viên']
                );
            }

            // Phân công vùng trồng cho Người sản xuất vào bảng trung gian nhan_vien_vung_trong
            $assignedZones = [];
            if (!empty($invitation->vung_trong_ids) && is_array($invitation->vung_trong_ids)) {
                $assignedZones = array_map('intval', $invitation->vung_trong_ids);
            } elseif ($invitation->vung_trong_id) {
                $assignedZones = [(int) $invitation->vung_trong_id];
            }

            foreach ($assignedZones as $zId) {
                \App\Services\ProducerAuthorizationService::assignZoneToUser(
                    $createdUser->tai_khoan_id,
                    $zId,
                    'Gán từ lời mời thành viên'
                );

                // Cập nhật người phụ trách chính nếu đang trống để hiển thị
                DB::table('vung_trong')
                    ->where('vung_trong_id', $zId)
                    ->whereNull('nguoi_phu_trach_id')
                    ->update(['nguoi_phu_trach_id' => $createdUser->tai_khoan_id]);
            }

            $invitation->update([
                'trang_thai' => LoiMoi::TRANG_THAI_DA_CHAP_NHAN,
            ]);

            return $createdUser;
        });

        // Tự động đăng xuất tài khoản cũ nếu có và đăng nhập tài khoản mới
        if (Auth::check()) {
            Auth::logout();
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with(
            'status',
            "Chào mừng {$user->ho_ten}! Bạn đã thiết lập mật khẩu thành công và gia nhập hệ thống Farm2Fork."
        );
    }
}

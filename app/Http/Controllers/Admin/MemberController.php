<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MemberInvitationMail;
use App\Models\KhoLanh;
use App\Models\LoiMoi;
use App\Models\PhuongTien;
use App\Models\TaiKhoanHeThong;
use App\Models\ToChuc;
use App\Models\VaiTro;
use App\Models\VungTrong;
use App\Support\AccountStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * Display members and invitations lists.
     */
    public function index(Request $request): View
    {
        // 1. Existing members query
        $membersQuery = TaiKhoanHeThong::query()
            ->with(['vaiTro', 'toChuc', 'khoLanh', 'vungTrong', 'vungTrongPhuTrach']);

        if ($request->filled('q')) {
            $q = trim($request->string('q')->toString());
            $membersQuery->where(function ($w) use ($q) {
                $w->where('ho_ten', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$q}%");
            });
        }

        if ($request->filled('vai_tro_id')) {
            $membersQuery->where('vai_tro_id', (int) $request->input('vai_tro_id'));
        }

        if ($request->filled('trang_thai')) {
            $membersQuery->where('trang_thai', $request->string('trang_thai')->toString());
        }

        $members = $membersQuery->orderByDesc('tai_khoan_id')->paginate(15, ['*'], 'members_page')->withQueryString();

        // 2. Invitations query
        $invitationsQuery = LoiMoi::query()
            ->with(['vaiTro', 'toChuc', 'khoLanh', 'vungTrong', 'phuongTien', 'nguoiMoi']);

        if ($request->filled('inv_q')) {
            $invQ = trim($request->string('inv_q')->toString());
            $invitationsQuery->where('email', 'like', "%{$invQ}%");
        }

        if ($request->filled('inv_status')) {
            $invitationsQuery->where('trang_thai', $request->string('inv_status')->toString());
        }

        $invitations = $invitationsQuery->orderByDesc('id')->paginate(15, ['*'], 'invitations_page')->withQueryString();

        // Auto mark expired invitations in the current view
        foreach ($invitations as $inv) {
            if ($inv->trang_thai === LoiMoi::TRANG_THAI_CHO && $inv->isExpired()) {
                $inv->update(['trang_thai' => LoiMoi::TRANG_THAI_HET_HAN]);
            }
        }

        // 3. Metadata for invitation modal / form
        $roles = VaiTro::orderBy('vai_tro_id')->get();
        $organizations = ToChuc::orderBy('ten_to_chuc')->get();
        $warehouses = KhoLanh::orderBy('ten_kho')->get();
        $plantingZones = VungTrong::orderBy('ten_vung_trong')->get();
        $vehicles = PhuongTien::orderBy('bien_so')->get();

        return view('admin.members.index', [
            'members' => $members,
            'invitations' => $invitations,
            'roles' => $roles,
            'organizations' => $organizations,
            'warehouses' => $warehouses,
            'plantingZones' => $plantingZones,
            'vehicles' => $vehicles,
            'memberStatuses' => [
                AccountStatus::DANG_HOAT_DONG => 'Đang hoạt động',
                AccountStatus::CHO_KICH_HOAT => 'Chờ kích hoạt',
                AccountStatus::KHOA => 'Bị khóa',
            ],
            'invitationStatuses' => [
                LoiMoi::TRANG_THAI_CHO => 'Chờ chấp nhận',
                LoiMoi::TRANG_THAI_DA_CHAP_NHAN => 'Đã tham gia',
                LoiMoi::TRANG_THAI_HET_HAN => 'Hết hạn',
                LoiMoi::TRANG_THAI_DA_THU_HOI => 'Đã thu hồi',
            ],
            'activeTab' => $request->query('tab', 'members'),
            'filters' => $request->all(),
        ]);
    }

    /**
     * Send a new member invitation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:254',
                Rule::unique('tai_khoan_he_thong', 'email'),
            ],
            'vai_tro_id' => [
                'required',
                Rule::exists('vai_tro', 'vai_tro_id'),
            ],
            'to_chuc_id' => [
                'nullable',
                Rule::exists('to_chuc', 'to_chuc_id'),
            ],
            'kho_lanh_id' => [
                'nullable',
                Rule::exists('kho_lanh', 'kho_lanh_id'),
            ],
            'vung_trong_id' => [
                'nullable',
                Rule::exists('vung_trong', 'vung_trong_id'),
            ],
            'vung_trong_ids' => [
                'nullable',
                'array',
            ],
            'vung_trong_ids.*' => [
                'integer',
                Rule::exists('vung_trong', 'vung_trong_id'),
            ],
            'phuong_tien_id' => [
                'nullable',
                Rule::exists('phuong_tien', 'phuong_tien_id'),
            ],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email người được mời.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Email này đã tồn tại trong danh sách tài khoản hệ thống.',
            'vai_tro_id.required' => 'Vui lòng chọn vai trò cho thành viên.',
            'vai_tro_id.exists' => 'Vai trò được chọn không hợp lệ.',
        ]);

        $email = trim(strtolower($validated['email']));

        // Check active pending invitation for this email
        $activePending = LoiMoi::where('email', $email)
            ->where('trang_thai', LoiMoi::TRANG_THAI_CHO)
            ->where('het_han', '>', now())
            ->exists();

        if ($activePending) {
            return back()->withInput()->withErrors([
                'email' => 'Email này đã có một lời mời đang chờ xử lý và còn hạn sử dụng.',
            ]);
        }

        // Generate token and hash
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $expiresDays = config('invitation.expires_days', 7);
        $expiresAt = now()->addDays($expiresDays);

        $currentUser = $request->user();

        $zoneIds = !empty($validated['vung_trong_ids']) ? array_values(array_filter(array_map('intval', $validated['vung_trong_ids']))) : [];
        if (empty($zoneIds) && !empty($validated['vung_trong_id'])) {
            $zoneIds = [(int)$validated['vung_trong_id']];
        }

        $invitation = LoiMoi::create([
            'email' => $email,
            'vai_tro_id' => (int) $validated['vai_tro_id'],
            'to_chuc_id' => !empty($validated['to_chuc_id']) ? (int) $validated['to_chuc_id'] : ($currentUser->to_chuc_id ?? 1),
            'kho_lanh_id' => !empty($validated['kho_lanh_id']) ? (int) $validated['kho_lanh_id'] : null,
            'vung_trong_id' => !empty($zoneIds) ? $zoneIds[0] : null,
            'vung_trong_ids' => !empty($zoneIds) ? $zoneIds : null,
            'phuong_tien_id' => !empty($validated['phuong_tien_id']) ? (int) $validated['phuong_tien_id'] : null,
            'ma_hash' => $tokenHash,
            'het_han' => $expiresAt,
            'trang_thai' => LoiMoi::TRANG_THAI_CHO,
            'nguoi_moi_id' => $currentUser->tai_khoan_id,
        ]);

        $acceptUrl = route('invitations.accept', ['token' => $rawToken]);

        // Send email (logs in dev environment)
        try {
            Mail::to($email)->send(new MemberInvitationMail($invitation, $acceptUrl, $expiresDays));
        } catch (\Throwable $e) {
            Log::error("Lỗi khi gửi email mời thành viên [{$email}]: " . $e->getMessage());
        }

        // Store generated URL in session for easy 1-click copy
        $storedUrls = session('invitation_urls', []);
        $storedUrls[$invitation->id] = $acceptUrl;
        session(['invitation_urls' => $storedUrls]);

        return redirect()->route('admin.members.index', ['tab' => 'invitations'])->with([
            'status' => "Đã tạo và gửi lời mời thành công đến {$email}!",
            'latest_invitation_id' => $invitation->id,
            'latest_invitation_email' => $email,
            'latest_invitation_url' => $acceptUrl,
        ]);
    }

    /**
     * Resend an invitation (generates new token, invalidates old token).
     */
    public function resend(Request $request, int $id): RedirectResponse
    {
        $invitation = LoiMoi::findOrFail($id);

        if ($invitation->trang_thai === LoiMoi::TRANG_THAI_DA_CHAP_NHAN) {
            return back()->withErrors([
                'invitation' => 'Lời mời này đã được chấp nhận, không thể gửi lại.',
            ]);
        }

        // Generate brand new token and hash
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $expiresDays = config('invitation.expires_days', 7);
        $expiresAt = now()->addDays($expiresDays);

        $invitation->update([
            'ma_hash' => $tokenHash,
            'het_han' => $expiresAt,
            'trang_thai' => LoiMoi::TRANG_THAI_CHO,
        ]);

        $acceptUrl = route('invitations.accept', ['token' => $rawToken]);

        try {
            Mail::to($invitation->email)->send(new MemberInvitationMail($invitation, $acceptUrl, $expiresDays));
        } catch (\Throwable $e) {
            Log::error("Lỗi gửi lại email mời [{$invitation->email}]: " . $e->getMessage());
        }

        // Cache URL in session
        $storedUrls = session('invitation_urls', []);
        $storedUrls[$invitation->id] = $acceptUrl;
        session(['invitation_urls' => $storedUrls]);

        return redirect()->route('admin.members.index', ['tab' => 'invitations'])->with([
            'status' => "Đã tạo mã mới và gửi lại lời mời đến {$invitation->email} (hết hạn sau {$expiresDays} ngày)!",
            'latest_invitation_id' => $invitation->id,
            'latest_invitation_email' => $invitation->email,
            'latest_invitation_url' => $acceptUrl,
        ]);
    }

    /**
     * Revoke an active invitation.
     */
    public function revoke(Request $request, int $id): RedirectResponse
    {
        $invitation = LoiMoi::findOrFail($id);

        if ($invitation->trang_thai === LoiMoi::TRANG_THAI_DA_CHAP_NHAN) {
            return back()->withErrors([
                'invitation' => 'Lời mời này đã được chấp nhận, không thể thu hồi.',
            ]);
        }

        $invitation->update([
            'trang_thai' => LoiMoi::TRANG_THAI_DA_THU_HOI,
        ]);

        // Remove from session cache if exists
        $storedUrls = session('invitation_urls', []);
        unset($storedUrls[$invitation->id]);
        session(['invitation_urls' => $storedUrls]);

        return redirect()->route('admin.members.index', ['tab' => 'invitations'])->with(
            'status',
            "Đã thu hồi lời mời của {$invitation->email}. Liên kết mời cũ sẽ không còn hiệu lực."
        );
    }

    /**
     * Get or refresh invitation link via JSON (used for 1-click Copy Link button on UI).
     */
    public function getLink(Request $request, int $id): JsonResponse
    {
        $invitation = LoiMoi::findOrFail($id);

        if ($invitation->trang_thai === LoiMoi::TRANG_THAI_DA_CHAP_NHAN) {
            return response()->json([
                'success' => false,
                'message' => 'Lời mời này đã được chấp nhận.',
            ], 422);
        }

        if ($invitation->trang_thai === LoiMoi::TRANG_THAI_DA_THU_HOI) {
            return response()->json([
                'success' => false,
                'message' => 'Lời mời này đã bị thu hồi. Hãy bấm "Gửi lại" để mở lại lời mời.',
            ], 422);
        }

        // Check if raw token is cached in current session
        $storedUrls = session('invitation_urls', []);
        if (isset($storedUrls[$id]) && !$invitation->isExpired()) {
            return response()->json([
                'success' => true,
                'url' => $storedUrls[$id],
                'email' => $invitation->email,
                'message' => 'Đã sao chép liên kết mời!',
            ]);
        }

        // Otherwise generate a fresh valid token for demo/copying
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $expiresDays = config('invitation.expires_days', 7);

        $invitation->update([
            'ma_hash' => $tokenHash,
            'het_han' => now()->addDays($expiresDays),
            'trang_thai' => LoiMoi::TRANG_THAI_CHO,
        ]);

        $acceptUrl = route('invitations.accept', ['token' => $rawToken]);
        $storedUrls[$id] = $acceptUrl;
        session(['invitation_urls' => $storedUrls]);

        return response()->json([
            'success' => true,
            'url' => $acceptUrl,
            'email' => $invitation->email,
            'message' => 'Đã tạo mới mã xác thực và sao chép liên kết mời!',
        ]);
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoanHeThong;
use App\Models\VaiTro;
use App\Support\AccountStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $query = TaiKhoanHeThong::query()->with(['vaiTro', 'toChuc']);

        if ($request->filled('q')) {
            $q = trim($request->string('q')->toString());
            $query->where(function ($w) use ($q) {
                $w->where('ho_ten', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->string('trang_thai')->toString());
        }

        if ($request->filled('vai_tro_id')) {
            $query->where('vai_tro_id', (int) $request->input('vai_tro_id'));
        }

        $accounts = $query->orderByDesc('tai_khoan_id')->paginate(20)->withQueryString();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'roles' => VaiTro::orderBy('vai_tro_id')->get(),
            'statuses' => [
                AccountStatus::DANG_HOAT_DONG => 'Đang hoạt động',
                AccountStatus::CHO_KICH_HOAT => 'Chờ kích hoạt',
                AccountStatus::KHOA => 'Bị khóa',
            ],
            'filters' => $request->only(['q', 'trang_thai', 'vai_tro_id']),
        ]);
    }

    public function lock(Request $request, int $id): RedirectResponse
    {
        $account = TaiKhoanHeThong::findOrFail($id);

        if ($account->getAuthIdentifier() === $request->user()->getAuthIdentifier()) {
            return back()->withErrors(['account' => 'Không thể khóa chính tài khoản của bạn.']);
        }

        $account->update(['trang_thai' => AccountStatus::KHOA]);

        return back()->with('status', "Đã khóa tài khoản {$account->email}.");
    }

    public function unlock(int $id): RedirectResponse
    {
        $account = TaiKhoanHeThong::findOrFail($id);
        $account->update(['trang_thai' => AccountStatus::DANG_HOAT_DONG]);

        return back()->with('status', "Đã mở khóa tài khoản {$account->email}.");
    }

    public function changeRole(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'vai_tro_id' => ['required', 'integer', 'exists:vai_tro,vai_tro_id'],
        ], [
            'vai_tro_id.required' => 'Vui lòng chọn vai trò.',
        ]);

        $account = TaiKhoanHeThong::findOrFail($id);
        $role = VaiTro::findOrFail($validated['vai_tro_id']);

        if ($role->ma_vai_tro === 'ADMIN') {
            return back()->withErrors(['vai_tro_id' => 'Không thể gán vai trò quản trị viên tại đây.']);
        }

        $account->update(['vai_tro_id' => $validated['vai_tro_id']]);

        return back()->with('status', "Đã chuyển {$account->email} sang vai trò {$role->ten_vai_tro}.");
    }
}

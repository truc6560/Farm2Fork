<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\InitialPasswordStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(private InitialPasswordStore $store)
    {
    }

    public function show(Request $request): View
    {
        return view('auth.change-password', [
            'mustChange' => $this->store->shouldForceChange((int) $request->user()->getAuthIdentifier()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
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
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'new_password.min' => 'Mật khẩu mới tối thiểu 10 ký tự.',
            'new_password.regex' => 'Mật khẩu mới phải có chữ hoa, chữ thường, số và ký tự đặc biệt.',
        ]);

        if (!Hash::check($validated['current_password'], $user->mat_khau_hash)) {
            return back()->withErrors([
                'current_password' => 'Mật khẩu hiện tại không đúng.',
            ])->withInput();
        }

        $user->mat_khau_hash = Hash::make($validated['new_password']);
        $user->save();

        $this->store->clearForceChange((int) $user->getAuthIdentifier());

        return redirect()->route('dashboard')->with('status', 'Đổi mật khẩu thành công.');
    }
}

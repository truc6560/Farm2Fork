<?php

namespace App\Http\Middleware;

use App\Support\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !in_array($user->trang_thai, AccountStatus::activeStatuses(), true)) {
            Auth::logout();

            return redirect()->route('auth.login.form')
                ->withErrors(['login' => 'Tai khoan chua duoc kich hoat hoac dang bi khoa.']);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\InitialPasswordStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceChangePassword
{
    public function __construct(private InitialPasswordStore $store)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $isChangingPassword = $request->routeIs('auth.change-password.*');

        if ($this->store->shouldForceChange((int) $user->getAuthIdentifier()) && !$isChangingPassword) {
            return redirect()->route('auth.change-password.form')
                ->with('warning', 'Ban can doi mat khau ban dau truoc khi tiep tuc su dung he thong.');
        }

        return $next($request);
    }
}

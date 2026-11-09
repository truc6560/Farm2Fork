<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->relationLoaded('vaiTro')) {
            $user?->load('vaiTro');
        }

        $role = $user?->vaiTro?->ma_vai_tro;

        if (!$role || ($roles !== [] && !in_array($role, $roles, true))) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}

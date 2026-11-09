<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->relationLoaded('vaiTro')) {
            $user?->load('vaiTro');
        }

        if (!$user || !$user->vaiTro || $user->vaiTro->ma_vai_tro !== 'ADMIN') {
            abort(403, 'Ban khong co quyen truy cap khu vuc quan tri.');
        }

        return $next($request);
    }
}

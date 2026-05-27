<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda nonaktif. Hubungi administrator.',
            ], 403);
        }

        if (! empty($roles) && ! $user->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Role Anda tidak memiliki akses ke resource ini.',
                'required_roles' => $roles,
                'your_role' => $user->role?->code,
            ], 403);
        }

        return $next($request);
    }
}

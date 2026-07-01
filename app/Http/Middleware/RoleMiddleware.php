<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Restrict API access by users.role_id.
     */
    public function handle(Request $request, Closure $next, int ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!in_array((int) $user->role_id, $roles, true)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}

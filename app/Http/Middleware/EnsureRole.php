<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        $allowed = array_map(
            fn (string $r) => UserRole::tryFrom(strtoupper($r))?->value ?? strtolower($r),
            $roles
        );

        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (! in_array($userRole, $allowed, true) && ! in_array(strtoupper($userRole), array_map('strtoupper', $allowed), true)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.forbidden'),
                'code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}

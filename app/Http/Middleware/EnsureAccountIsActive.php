<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ($user->status ?? 'Active') !== 'Active') {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'message' => 'Access Revoked: Your account is marked as Inactive in User Management.'
            ], 403);
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{

    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $user = Auth::user();

        if ($user->user_role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have the required role.'
            ], 403);
        }

        return $next($request);
    }
}

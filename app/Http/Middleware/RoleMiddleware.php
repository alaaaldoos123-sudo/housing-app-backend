<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  // الدور المطلوب (مثل 'admin')
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. التحقق من المصادقة (هل تم تسجيل الدخول)
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $user = Auth::user();

        // 2. التحقق من الدور (هل دور المستخدم هو الدور المطلوب)
        if ($user->user_role !== $role) {
            // 403 Forbidden (ممنوع) هو الرد القياسي لرفض الصلاحيات
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have the required role.'
            ], 403);
        }

        // إذا كان كل شيء على ما يرام، استمر في تنفيذ الطلب
        return $next($request);
    }
}

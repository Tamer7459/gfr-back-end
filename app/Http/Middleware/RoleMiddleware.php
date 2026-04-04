<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح لك بالوصول'
            ], 401);
        }

        if (empty($roles)) {
            return response()->json([
                'message' => 'إعداد صلاحيات غير صحيح'
            ], 500);
        }

        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'ليس لديك صلاحية لتنفيذ هذا الإجراء'
            ], 403);
        }

        return $next($request);
    }
}

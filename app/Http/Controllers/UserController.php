<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    // ── الملف العام لمستخدم ───────────────────

    public function profile(User $user): JsonResponse
    {
        $user->loadCount(['posts', 'journals', 'reviews']);
        $user->load([
            'posts' => fn($q) => $q->latest()->take(5),
        ]);

        // إخفاء البريد الإلكتروني
        $user->makeHidden(['email', 'remember_token']);

        return response()->json($user);
    }

    // ── قائمة جميع الباحثين ───────────────────

    public function researchers(): JsonResponse
    {
        $users = User::where('role', 'user')
            ->withCount('posts')
            ->select(['id', 'name', 'specialty', 'bio', 'avatar', 'created_at'])
            ->latest()
            ->paginate(12);

        return response()->json($users);
    }
}
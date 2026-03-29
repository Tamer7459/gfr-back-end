<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    // ── عرض جميع المستخدمين ───────────────────

    public function users(Request $request): JsonResponse
    {
        $users = User::withCount(['posts', 'comments'])
            ->latest()
            ->paginate(15);

        return response()->json($users);
    }

    // ── عرض جميع المنشورات ────────────────────

    public function posts(Request $request): JsonResponse
    {
        $posts = Post::with('user:id,name,email')
            ->withCount(['comments', 'likes'])
            ->latest()
            ->paginate(15);

        return response()->json($posts);
    }

    // ── حذف مستخدم ────────────────────────────

    public function deleteUser(User $user): JsonResponse
    {
        // منع حذف الأدمن لنفسه
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'لا يمكنك حذف حسابك الخاص'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'تم حذف المستخدم بنجاح'
        ]);
    }

    // ── حذف منشور ─────────────────────────────

    public function deletePost(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'message' => 'تم حذف المنشور بنجاح'
        ]);
    }

    // ── إحصائيات لوحة الإدارة ─────────────────

    public function stats(): JsonResponse
    {
        return response()->json([
            'total_users'    => User::count(),
            'total_posts'    => Post::count(),
            'total_admins'   => User::where('role', 'admin')->count(),
            'latest_users'   => User::latest()->take(5)->get(),
            'latest_posts'   => Post::with('user:id,name')
                                    ->latest()->take(5)->get(),
        ]);
    }

    // ── تغيير دور مستخدم ──────────────────────

    public function toggleRole(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'لا يمكنك تغيير دورك الخاص'
            ], 422);
        }

        $user->update([
            'role' => $user->role === 'admin' ? 'user' : 'admin'
        ]);

        return response()->json([
            'message' => 'تم تغيير الدور بنجاح',
            'user'    => $user->fresh(),
        ]);
    }
}
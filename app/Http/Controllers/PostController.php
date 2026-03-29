<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    // ── عرض جميع المنشورات ────────────────────

    public function index(): JsonResponse
    {
        $posts = Post::with(['user:id,name,avatar,specialty', 'comments.user:id,name,avatar', 'likes'])
            ->latest()
            ->paginate(10);

        // إضافة معلومات الإعجاب لكل منشور
        $posts->getCollection()->transform(function ($post) {
            $post->likes_count    = $post->likes->count();
            $post->is_liked       = $post->likes
                ->contains('user_id', auth()->id());
            $post->comments_count = $post->comments->count();
            unset($post->likes);
            return $post;
        });

        return response()->json($posts);
    }

    // ── إنشاء منشور جديد ──────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'body'     => 'required|string|min:10',
            'keywords' => 'nullable|array',
        ]);

        $post = Post::create([
            'user_id'  => auth()->id(),
            'title'    => $request->title,
            'body'     => $request->body,
            'keywords' => $request->keywords ?? [],
        ]);

        $post->load('user:id,name,avatar,specialty');

        return response()->json([
            'message' => 'تم نشر الملخص البحثي بنجاح',
            'post'    => $post,
        ], 201);
    }

    // ── عرض منشور واحد ────────────────────────

    public function show(Post $post): JsonResponse
    {
        $post->load([
            'user:id,name,avatar,specialty',
            'comments.user:id,name,avatar',
            'likes',
        ]);

        $post->likes_count = $post->likes->count();
        $post->is_liked    = $post->likes
            ->contains('user_id', auth()->id());
        unset($post->likes);

        return response()->json($post);
    }

    // ── حذف منشور ─────────────────────────────

    public function destroy(Post $post): JsonResponse
    {
        // صاحب المنشور أو الأدمن فقط
        if (auth()->id() !== $post->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'غير مصرح لك بحذف هذا المنشور'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'تم حذف المنشور بنجاح'
        ]);
    }

    // ── إضافة تعليق ───────────────────────────

    public function addComment(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        $comment->load('user:id,name,avatar');

        return response()->json([
            'message' => 'تم إضافة التعليق',
            'comment' => $comment,
        ], 201);
    }

    // ── إعجاب / إلغاء إعجاب ──────────────────

    public function toggleLike(Post $post): JsonResponse
    {
        $existing = Like::where('post_id', $post->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            $existing->delete();
            $liked   = false;
            $message = 'تم إلغاء الإعجاب';
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
            ]);
            $liked   = true;
            $message = 'تم الإعجاب بالمنشور';
        }

        return response()->json([
            'message'     => $message,
            'liked'       => $liked,
            'likes_count' => $post->likes()->count(),
        ]);
    }
}
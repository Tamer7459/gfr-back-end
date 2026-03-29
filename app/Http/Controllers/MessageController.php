<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    // ── عرض المحادثة مع مستخدم محدد ──────────

    public function conversation(User $user): JsonResponse
    {
        $messages = Message::where(function ($q) use ($user) {
                $q->where('sender_id',   auth()->id())
                  ->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($user) {
                $q->where('sender_id',   $user->id)
                  ->where('receiver_id', auth()->id());
            })
            ->with([
                'sender:id,name,avatar',
                'receiver:id,name,avatar',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        // تحديد الرسائل كمقروءة
        Message::where('sender_id',   $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    // ── إرسال رسالة ───────────────────────────

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body'        => 'required|string|max:2000',
        ]);

        // منع إرسال رسالة لنفسك
        if ($request->receiver_id == auth()->id()) {
            return response()->json([
                'message' => 'لا يمكنك إرسال رسالة لنفسك'
            ], 422);
        }

        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'body'        => $request->body,
        ]);

        $message->load([
            'sender:id,name,avatar',
            'receiver:id,name,avatar',
        ]);

        return response()->json([
            'message' => 'تم إرسال الرسالة',
            'data'    => $message,
        ], 201);
    }

    // ── قائمة المحادثات ───────────────────────

    public function inbox(): JsonResponse
    {
        $userId = auth()->id();

        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with([
                'sender:id,name,avatar',
                'receiver:id,name,avatar',
            ])
            ->latest()
            ->get()
            ->groupBy(function ($msg) use ($userId) {
                return $msg->sender_id == $userId
                    ? $msg->receiver_id
                    : $msg->sender_id;
            })
            ->map(function ($msgs) use ($userId) {
                $last    = $msgs->first();
                $partner = $last->sender_id == $userId
                    ? $last->receiver
                    : $last->sender;

                return [
                    'user'          => $partner,
                    'last_message'  => $last->body,
                    'last_time'     => $last->created_at,
                    'unread_count'  => $msgs->where('receiver_id', $userId)
                                           ->whereNull('read_at')
                                           ->count(),
                ];
            })
            ->values();

        return response()->json($conversations);
    }
}
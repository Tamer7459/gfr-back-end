<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    // ── قائمة المحادثات ───────────────────────
    public function inbox(): JsonResponse
    {
        $userId = auth()->id();

        $messages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender:id,name,avatar,specialty', 'receiver:id,name,avatar,specialty'])
            ->latest()
            ->get();

        // تجميع المحادثات
        $conversations = $messages
            ->groupBy(function ($msg) use ($userId) {
                return $msg->sender_id == $userId
                    ? $msg->receiver_id
                    : $msg->sender_id;
            })
            ->map(function ($msgs) use ($userId) {
                $last    = $msgs->sortByDesc('created_at')->first();
                $partner = $last->sender_id == $userId
                    ? $last->sender
                    : $last->receiver;

                // تصحيح: استخدام sender/receiver بشكل صحيح
                if ($last->sender_id == $userId) {
                    $partner = $last->receiver;
                } else {
                    $partner = $last->sender;
                }

                return [
                    'user'          => $partner,
                    'last_message'  => $last->body,
                    'last_time'     => $last->created_at,
                    'unread_count'  => $msgs
                        ->where('receiver_id', $userId)
                        ->whereNull('read_at')
                        ->count(),
                ];
            })
            ->values();

        return response()->json($conversations);
    }

    // ── عرض محادثة مع مستخدم ─────────────────
    public function conversation(User $user): JsonResponse
    {
        $userId = auth()->id();

        $messages = Message::where(function ($q) use ($user, $userId) {
                $q->where('sender_id',   $userId)
                  ->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($user, $userId) {
                $q->where('sender_id',   $user->id)
                  ->where('receiver_id', $userId);
            })
            ->with([
                'sender:id,name,avatar,specialty',
                'receiver:id,name,avatar,specialty',
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        // تحديد المقروءة
        Message::where('sender_id',   $user->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    // ── إرسال رسالة ──────────────────────────
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body'        => 'required|string|max:2000',
        ]);

        if ((int)$request->receiver_id === auth()->id()) {
            return response()->json([
                'message' => 'لا يمكنك إرسال رسالة لنفسك'
            ], 422);
        }

        $msg = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'body'        => $request->body,
        ]);

        $msg->load([
            'sender:id,name,avatar,specialty',
            'receiver:id,name,avatar,specialty',
        ]);

        return response()->json([
            'message' => 'تم إرسال الرسالة',
            'data'    => $msg,
        ], 201);
    }
}
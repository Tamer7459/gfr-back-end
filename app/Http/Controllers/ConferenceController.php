<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use App\Models\ConferenceAttendee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConferenceController extends Controller
{
    // ── عرض جميع المؤتمرات ───────────────────

    public function index(): JsonResponse
    {
        $conferences = Conference::with('organizer:id,name,specialty')
            ->withCount(['attendees' => fn($q) =>
                $q->where('status', '!=', 'cancelled')
            ])
            ->latest()
            ->paginate(10);

        // إضافة حالة تسجيل المستخدم الحالي
        $conferences->getCollection()->transform(function ($conf) {
            $conf->is_registered = $conf->isRegistered(auth()->id());
            return $conf;
        });

        return response()->json($conferences);
    }

    // ── إنشاء مؤتمر جديد ─────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'required|string|min:20',
            'location'            => 'nullable|string|max:255',
            'start_date'          => 'required|date|after:now',
            'end_date'            => 'required|date|after:start_date',
            'submission_deadline' => 'nullable|string|max:100',
            'max_attendees'       => 'nullable|integer|min:1|max:10000',
        ]);

        $conference = Conference::create([
            'organizer_id'        => auth()->id(),
            'title'               => $request->title,
            'description'         => $request->description,
            'location'            => $request->location,
            'start_date'          => $request->start_date,
            'end_date'            => $request->end_date,
            'submission_deadline' => $request->submission_deadline,
            'max_attendees'       => $request->max_attendees ?? 100,
            'status'              => 'upcoming',
        ]);

        $conference->load('organizer:id,name,specialty');

        return response()->json([
            'message'    => 'تم إنشاء المؤتمر بنجاح',
            'conference' => $conference,
        ], 201);
    }

    // ── عرض مؤتمر واحد ───────────────────────

    public function show(Conference $conference): JsonResponse
    {
        $conference->load([
            'organizer:id,name,specialty',
            'attendees.user:id,name,specialty,avatar',
        ]);

        $conference->is_registered = $conference->isRegistered(auth()->id());
        $conference->loadCount([
    'attendees' => fn($q) => $q->where('status', '!=', 'cancelled')
]);

        return response()->json($conference);
    }

    // ── التسجيل في مؤتمر ─────────────────────

    public function register(Conference $conference): JsonResponse
    {
        // التحقق من التسجيل المسبق
        if ($conference->isRegistered(auth()->id())) {
            return response()->json([
                'message' => 'أنت مسجل بالفعل في هذا المؤتمر'
            ], 422);
        }

        // التحقق من اكتمال الأماكن
        if ($conference->attendees_count >= $conference->max_attendees) {
            return response()->json([
                'message' => 'عذراً، المؤتمر ممتلئ'
            ], 422);
        }

        ConferenceAttendee::create([
            'conference_id' => $conference->id,
            'user_id'       => auth()->id(),
            'status'        => 'registered',
        ]);

        return response()->json([
            'message'        => 'تم التسجيل في المؤتمر بنجاح ✅',
            'is_registered'  => true,
            'attendees_count'=> $conference->fresh()->attendees_count,
        ]);
    }

    // ── إلغاء التسجيل ────────────────────────

    public function unregister(Conference $conference): JsonResponse
    {
        ConferenceAttendee::where('conference_id', $conference->id)
            ->where('user_id', auth()->id())
            ->update(['status' => 'cancelled']);

        return response()->json([
            'message'       => 'تم إلغاء التسجيل',
            'is_registered' => false,
        ]);
    }

    // ── مؤتمراتي ─────────────────────────────

    public function myConferences(): JsonResponse
    {
        $attending = auth()->user()
            ->conferences()
            ->with('organizer:id,name')
            ->latest()
            ->get();

        $organizing = auth()->user()
            ->organizedConferences()
            ->withCount('attendees')
            ->latest()
            ->get();

        return response()->json([
            'attending'  => $attending,
            'organizing' => $organizing,
        ]);
    }

    // ── إصدار شهادة حضور ─────────────────────

    public function issueCertificate(
        Conference $conference,
        int $userId
    ): JsonResponse {
        // المنظم فقط يصدر الشهادات
        if ($conference->organizer_id !== auth()->id() &&
            !auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'غير مصرح لك'
            ], 403);
        }

        $attendee = ConferenceAttendee::where('conference_id', $conference->id)
            ->where('user_id', $userId)
            ->first();

        if (!$attendee) {
            return response()->json([
                'message' => 'المستخدم غير مسجل في هذا المؤتمر'
            ], 404);
        }

        // توليد رقم شهادة فريد
        $certificateNumber = 'GFR-' . strtoupper(uniqid());

        $attendee->update([
            'status'           => 'attended',
            'certificate_path' => $certificateNumber,
        ]);

        return response()->json([
            'message'            => 'تم إصدار الشهادة بنجاح',
            'certificate_number' => $certificateNumber,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JournalController extends Controller
{
    // ── عرض جميع الأوراق ─────────────────────

    public function index(): JsonResponse
    {
        $journals = Journal::with([
            'author:id,name,specialty',
            'editor:id,name',
            'reviews',
        ])
            ->latest()
            ->paginate(10);

        return response()->json($journals);
    }

    // ── تقديم ورقة بحثية ─────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'abstract' => 'required|string|min:50',
        ]);

        $journal = Journal::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'abstract' => $request->abstract,
            'status' => 'pending',
        ]);

        $journal->load('author:id,name,specialty');

        return response()->json([
            'message' => 'تم تقديم الورقة البحثية بنجاح',
            'journal' => $journal,
        ], 201);
    }

    // ── عرض ورقة واحدة ───────────────────────

    public function show(Journal $journal): JsonResponse
    {
        $journal->load([
            'author:id,name,specialty',
            'editor:id,name',
            'reviews.reviewer:id,name',
        ]);

        // إخفاء هوية المراجعين عن الباحث
        if (auth()->id() === $journal->user_id) {
            $journal->reviews->each(function ($review) {
                $review->makeHidden('reviewer');
                $review->reviewer_name = 'مراجع مجهول';
            });
        }

        return response()->json($journal);
    }

    // ── أوراقي البحثية ────────────────────────

    public function myJournals(): JsonResponse
    {
        $journals = Journal::where('user_id', auth()->id())
            ->with(['reviews'])
            ->latest()
            ->get();

        return response()->json($journals);
    }

    // ── تعيين مراجعين (المحرر فقط) ───────────

    public function assignReviewers(Request $request, Journal $journal): JsonResponse
    {
        $request->validate([
            'reviewer_ids' => 'required|array|min:1|max:2',
            'reviewer_ids.*' => 'exists:users,id',
        ]);

        $reviewerIds = User::query()
            ->whereIn('id', $request->reviewer_ids)
            ->where('role', 'reviewer')
            ->pluck('id')
            ->all();

        if (count($reviewerIds) !== count(array_unique($request->reviewer_ids))) {
            return response()->json([
                'message' => 'يمكن تعيين مستخدمين بدور مراجع فقط'
            ], 422);
        }

        // منع تعيين الباحث مراجعاً لورقته
        if (in_array($journal->user_id, $reviewerIds)) {
            return response()->json([
                'message' => 'لا يمكن تعيين الباحث مراجعاً لورقته'
            ], 422);
        }

        $alreadyAssigned = Review::query()
            ->where('journal_id', $journal->id)
            ->whereIn('reviewer_id', $reviewerIds)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'message' => 'بعض المراجعين المحددين مُعينون مسبقاً لهذه الورقة'
            ], 422);
        }

        foreach ($reviewerIds as $reviewerId) {
            Review::firstOrCreate([
                'journal_id' => $journal->id,
                'reviewer_id' => $reviewerId,
            ], [
                'decision' => 'pending',
            ]);
        }

        $journal->update([
            'status' => 'under_review',
            'editor_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'تم تعيين المراجعين بنجاح',
            'journal' => $journal->fresh(['reviews.reviewer:id,name']),
        ]);
    }

    // ── تقديم مراجعة ─────────────────────────

    public function submitReview(Request $request, Journal $journal): JsonResponse
    {
        $request->validate([
            'feedback' => 'required|string|min:20',
            'decision' => 'required|in:accept,reject,revision',
        ]);

        $review = Review::where('journal_id', $journal->id)
            ->where('reviewer_id', auth()->id())
            ->first();

        if (!$review) {
            return response()->json([
                'message' => 'لست مراجعاً لهذه الورقة'
            ], 403);
        }

        $review->update([
            'feedback' => $request->feedback,
            'decision' => $request->decision,
        ]);

        // تحديث حالة الورقة تلقائياً
        $this->updateJournalStatus($journal);

        return response()->json([
            'message' => 'تم تقديم مراجعتك بنجاح',
            'review' => $review,
        ]);
    }

    // ── تحديث حالة الورقة ────────────────────

    private function updateJournalStatus(Journal $journal): void
    {
        $reviews = $journal->reviews;
        $completed = $reviews->whereNotIn('decision', ['pending']);

        if ($completed->count() === $reviews->count() && $reviews->count() > 0) {
            $accepted = $completed->where('decision', 'accept')->count();
            $rejected = $completed->where('decision', 'reject')->count();

            if ($accepted > $rejected) {
                $journal->update(['status' => 'accepted']);
            } elseif ($rejected >= $accepted) {
                $journal->update(['status' => 'rejected']);
            }
        }
    }

    // ── أوراق المراجع ─────────────────────────

    public function reviewerJournals(): JsonResponse
    {
        $reviews = Review::where('reviewer_id', auth()->id())
            ->with(['journal.author:id,name,specialty'])
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}
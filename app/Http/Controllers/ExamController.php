<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\ExamSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    /**
     * Display a listing of exam submissions for Reports & Analysis and Dashboard activity.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExamSubmission::query();

        if ($request->filled('course_code')) {
            $query->where('course_code', $request->course_code);
        }

        if ($request->filled('cadet_id')) {
            $query->where('cadet_id', $request->cadet_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cadet_name', 'like', "%{$search}%")
                  ->orWhere('cadet_id', 'like', "%{$search}%")
                  ->orWhere('course_code', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $submissions = $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ]
        ]);
    }

    /**
     * Dynamically generates an examination paper for StudentExamPortal.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'courseId' => 'required|string',
            'totalItems' => 'nullable|integer|min:1|max:100'
        ]);

        $limit = (int) ($validated['totalItems'] ?? 50);
        $courseCode = trim($validated['courseId']);

        $questions = Question::where(function ($query) use ($courseCode) {
            $query->whereHas('course', function ($q) use ($courseCode) {
                $q->where('code', $courseCode);
            })->orWhere('code', 'like', "%{$courseCode}%");
        })
        ->inRandomOrder()
        ->limit($limit)
        ->get();

        if ($questions->count() < $limit) {
            $remaining = $limit - $questions->count();
            $existingIds = $questions->pluck('id')->toArray();

            $extraQuestions = Question::whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->limit($remaining)
                ->get();

            $questions = $questions->concat($extraQuestions);
        }

        $formattedExam = $questions->values()->map(function ($q, $idx) {
            $options = is_array($q->options) ? $q->options : json_decode($q->options ?? '[]', true);
            
            return [
                'id' => $idx + 1,
                'db_id' => $q->id,
                'code' => $q->code ?? ('Q-' . ($idx + 1)),
                'text' => $q->text,
                'type' => $q->type ?? 'multiple_choice',
                'image_url' => $q->image_url ?? null,
                'options' => $options ?? [],
                'correctAnswer' => $q->normalized_correct_answer ?? $q->correct_answer,
                'correct_answer' => $q->normalized_correct_answer ?? $q->correct_answer,
                'bloomLevel' => $q->bloom_level ?? 'Understanding',
                'stcwStandard' => $q->stcw_standard ?? 'Table A-II/1',
                'co' => $q->course_outcome_id ?? 'CO1',
                'lo' => $q->learning_outcome_id ?? 'LO1.1'
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formattedExam->count(),
            'exam' => $formattedExam
        ]);
    }

    /**
     * Receives and persists cadet examination submissions.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cadet_id' => 'nullable|string|max:255',
            'cadet_name' => 'nullable|string|max:255',
            'course_code' => 'required|string|max:255',
            'score' => 'required|numeric|min:0',
            'total_items' => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'breakdown' => 'nullable|array',
            'submitted_at' => 'nullable|date'
        ]);

        $user = $request->user();
        $validated['cadet_id'] = $validated['cadet_id'] ?? ($user ? (string) $user->id : 'CADET-GUEST');
        $validated['cadet_name'] = $validated['cadet_name'] ?? ($user ? $user->name : 'Candidate Cadet');
        $validated['submitted_at'] = $validated['submitted_at'] ?? now();

        $submission = ExamSubmission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exam submission recorded successfully.',
            'data' => $submission
        ], 201);
    }

    /**
     * Display the specified exam submission details.
     */
    public function show(ExamSubmission $examSubmission): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $examSubmission
        ]);
    }
}
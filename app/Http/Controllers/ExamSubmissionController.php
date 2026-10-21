<?php

namespace App\Http\Controllers;

use App\Models\ExamSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamSubmissionController extends Controller
{
    /**
     * Display a listing of exam submissions with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExamSubmission::with('course:id,code,title');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('cadet_id')) {
            $query->where('cadet_id', $request->cadet_id);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cadet_name', 'like', "%{$search}%")
                  ->orWhere('cadet_id', 'like', "%{$search}%")
                  ->orWhere('course_code', 'like', "%{$search}%")
                  ->orWhere('course_title', 'like', "%{$search}%");
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
     * Store a new examination submission in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_id' => 'nullable|string|max:255',
            'cadet_id' => 'nullable|string|max:255',
            'cadet_name' => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'course_code' => 'nullable|string|max:255',
            'course_title' => 'nullable|string|max:255',
            'term' => 'nullable|string|max:255',
            'score' => 'required|numeric|min:0',
            'total_items' => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'time_spent' => 'nullable|integer|min:0',
            'breakdown' => 'nullable|array',
            'submitted_at' => 'nullable|date',
        ]);

        // Auto-assign active authenticated cadet if omitted
        $user = $request->user();
        $validated['cadet_id'] = $validated['cadet_id'] ?? ($user ? (string) $user->id : 'CADET-GUEST');
        $validated['cadet_name'] = $validated['cadet_name'] ?? ($user ? $user->name : 'Candidate Cadet');
        $validated['submitted_at'] = $validated['submitted_at'] ?? now();

        $submission = ExamSubmission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exam submission recorded successfully.',
            'data' => $submission->load('course:id,code,title')
        ], 201);
    }

    /**
     * Display the specified exam submission details.
     */
    public function show(ExamSubmission $examSubmission): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $examSubmission->load('course:id,code,title')
        ]);
    }

    /**
     * Remove the specified exam submission from storage.
     */
    public function destroy(ExamSubmission $examSubmission): JsonResponse
    {
        $examSubmission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam submission deleted successfully.'
        ]);
    }
}
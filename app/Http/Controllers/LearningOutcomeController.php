<?php

namespace App\Http\Controllers;

use App\Models\LearningOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class LearningOutcomeController extends Controller
{
    /**
     * Display a listing of learning outcomes with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LearningOutcome::with('courseOutcome.course:id,code,title');

        if ($request->filled('course_outcome_id')) {
            $query->where('course_outcome_id', $request->course_outcome_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('code');

        // Unpaginated list when querying specific course outcome or explicit all flag
        if ($request->boolean('all') || $request->filled('course_outcome_id')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()
            ]);
        }

        $perPage = (int) $request->input('per_page', 15);
        $outcomes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $outcomes->items(),
            'meta' => [
                'current_page' => $outcomes->currentPage(),
                'last_page' => $outcomes->lastPage(),
                'per_page' => $outcomes->perPage(),
                'total' => $outcomes->total(),
            ]
        ]);
    }

    /**
     * Store a newly created learning outcome in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_outcome_id' => 'required|exists:course_outcomes,id',
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('learning_outcomes')->where('course_outcome_id', $request->course_outcome_id),
            ],
            'description' => 'required|string',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['description'] = trim($validated['description']);

        $outcome = LearningOutcome::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Learning outcome created successfully.',
            'data' => $outcome->load('courseOutcome.course:id,code,title')
        ], 201);
    }

    /**
     * Display the specified learning outcome.
     */
    public function show(LearningOutcome $learningOutcome): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $learningOutcome->load('courseOutcome.course:id,code,title')
        ]);
    }

    /**
     * Update the specified learning outcome in storage.
     */
    public function update(Request $request, LearningOutcome $learningOutcome): JsonResponse
    {
        $courseOutcomeId = $request->input('course_outcome_id', $learningOutcome->course_outcome_id);

        $validated = $request->validate([
            'course_outcome_id' => 'sometimes|exists:course_outcomes,id',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('learning_outcomes')
                    ->where('course_outcome_id', $courseOutcomeId)
                    ->ignore($learningOutcome->id),
            ],
            'description' => 'sometimes|required|string',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }
        if (isset($validated['description'])) {
            $validated['description'] = trim($validated['description']);
        }

        $learningOutcome->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Learning outcome updated successfully.',
            'data' => $learningOutcome->load('courseOutcome.course:id,code,title')
        ]);
    }

    /**
     * Remove the specified learning outcome from storage with relational checks.
     */
    public function destroy(LearningOutcome $learningOutcome): JsonResponse
    {
        // Guard against deleting outcomes linked to active questions
        if (method_exists($learningOutcome, 'questions') && $learningOutcome->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete learning outcome associated with active questions.'
            ], 422);
        }

        $learningOutcome->delete();

        return response()->json([
            'success' => true,
            'message' => 'Learning outcome deleted successfully.'
        ]);
    }
}
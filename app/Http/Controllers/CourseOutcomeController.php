<?php

namespace App\Http\Controllers;

use App\Models\CourseOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CourseOutcomeController extends Controller
{
    /**
     * Display a listing of course outcomes with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CourseOutcome::with('course:id,code,title');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('code');

        // Unpaginated list for course-specific selects or explicit all query
        if ($request->boolean('all') || $request->filled('course_id')) {
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
     * Store a newly created course outcome in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('course_outcomes')->where('course_id', $request->course_id),
            ],
            'description' => 'required|string',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['description'] = trim($validated['description']);

        $outcome = CourseOutcome::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course outcome created successfully.',
            'data' => $outcome->load('course:id,code,title')
        ], 201);
    }

    /**
     * Display the specified course outcome.
     */
    public function show(CourseOutcome $courseOutcome): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $courseOutcome->load('course:id,code,title')
        ]);
    }

    /**
     * Update the specified course outcome in storage.
     */
    public function update(Request $request, CourseOutcome $courseOutcome): JsonResponse
    {
        $courseId = $request->input('course_id', $courseOutcome->course_id);

        $validated = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('course_outcomes')
                    ->where('course_id', $courseId)
                    ->ignore($courseOutcome->id),
            ],
            'description' => 'sometimes|required|string',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }
        if (isset($validated['description'])) {
            $validated['description'] = trim($validated['description']);
        }

        $courseOutcome->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course outcome updated successfully.',
            'data' => $courseOutcome->load('course:id,code,title')
        ]);
    }

    /**
     * Remove the specified course outcome from storage with relational checks.
     */
    public function destroy(CourseOutcome $courseOutcome): JsonResponse
    {
        // Guard against deleting outcomes linked to active questions
        if (method_exists($courseOutcome, 'questions') && $courseOutcome->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete course outcome associated with active questions.'
            ], 422);
        }

        $courseOutcome->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course outcome deleted successfully.'
        ]);
    }
}
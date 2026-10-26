<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * Display a listing of courses with optional filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('program') && $request->program !== 'All') {
            $query->byProgram($request->program);
        }

        $query->orderBy('code');

        // Fetch all records if 'all' flag is requested or by default for full lists
        if ($request->boolean('all', true)) {
            return response()->json([
                'success' => true,
                'data' => $query->get()
            ]);
        }

        $perPage = (int) $request->input('per_page', 15);
        $courses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ]
        ]);
    }

    /**
     * Store a newly created course in MySQL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:courses,code',
            'title' => 'required|string|max:255',
            'program' => 'nullable|string|max:255',
            'course_outcomes' => 'nullable|array',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['title'] = trim($validated['title']);
        $validated['program'] = $validated['program'] ?? 'BSMT';

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully in MySQL.',
            'data' => $course
        ], 201);
    }

    /**
     * Display details for a specific course.
     */
    public function show(Course $course): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }

    /**
     * Update specified course details in MySQL.
     */
    public function update(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:courses,code,' . $course->id,
            'title' => 'required|string|max:255',
            'program' => 'nullable|string|max:255',
            'course_outcomes' => 'nullable|array',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['title'] = trim($validated['title']);

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully in MySQL.',
            'data' => $course
        ]);
    }

    /**
     * Remove the specified course from MySQL with relational checks.
     */
    public function destroy(Course $course): JsonResponse
    {
        if (method_exists($course, 'questions') && $course->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete course with linked items in Question Bank.'
            ], 422);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully from MySQL.'
        ]);
    }
}
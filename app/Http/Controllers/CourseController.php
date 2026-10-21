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

        // Search filter by course code, title, or program
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('program', 'like', "%{$search}%");
            });
        }

        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }

        $query->orderBy('code');

        // Fetch all records for dropdowns when requested (e.g., ?all=1)
        if ($request->boolean('all')) {
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
     * Store a newly created course in storage.
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

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'data' => $course
        ], 201);
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $course
        ]);
    }

    /**
     * Update the specified course in storage.
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
            'message' => 'Course updated successfully.',
            'data' => $course
        ]);
    }

    /**
     * Remove the specified course from storage with relational integrity checks.
     */
    public function destroy(Course $course): JsonResponse
    {
        // Guard against deleting courses linked to existing questions
        if (method_exists($course, 'questions') && $course->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete course with linked items in Question Bank.'
            ], 422);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.'
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\CourseOutcome;
use Illuminate\Http\Request;

class CourseOutcomeController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseOutcome::query();
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'code' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $outcome = CourseOutcome::create($validated);
        return response()->json(['success' => true, 'data' => $outcome], 201);
    }

    public function show(CourseOutcome $courseOutcome)
    {
        return response()->json(['success' => true, 'data' => $courseOutcome]);
    }

    public function update(Request $request, CourseOutcome $courseOutcome)
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
        ]);

        $courseOutcome->update($validated);
        return response()->json(['success' => true, 'data' => $courseOutcome]);
    }

    public function destroy(CourseOutcome $courseOutcome)
    {
        $courseOutcome->delete();
        return response()->json(['success' => true, 'message' => 'Course outcome deleted.']);
    }
}
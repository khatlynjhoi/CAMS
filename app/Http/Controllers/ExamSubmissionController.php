<?php

namespace App\Http\Controllers;

use App\Models\ExamSubmission;
use Illuminate\Http\Request;

class ExamSubmissionController extends Controller
{
    public function index()
    {
        $submissions = ExamSubmission::with('course')
            ->orderByDesc('submitted_at')
            ->get();

        return response()->json($submissions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'nullable|string|max:255',
            'cadet_id' => 'nullable|string|max:255',
            'cadet_name' => 'required|string|max:255',
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

        $validated['submitted_at'] = $validated['submitted_at'] ?? now();
        $submission = ExamSubmission::create($validated);

        return response()->json([
            'success' => true,
            'data' => $submission
        ], 201);
    }

    public function show(ExamSubmission $examSubmission)
    {
        return response()->json($examSubmission->load('course'));
    }

    public function destroy(ExamSubmission $examSubmission)
    {
        $examSubmission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam submission deleted successfully.'
        ]);
    }
}
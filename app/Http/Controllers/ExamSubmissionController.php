<?php

namespace App\Http\Controllers;

use App\Models\ExamSubmission;
use Illuminate\Http\Request;

class ExamSubmissionController extends Controller
{
    public function index()
    {
        return response()->json(
            ExamSubmission::with('course')
                ->orderByDesc('submitted_at')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cadet_id' => 'nullable|string|max:255',
            'cadet_name' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'score' => 'required|numeric|min:0',
            'total_items' => 'required|integer|min:0',
            'breakdown' => 'nullable|array',
            'submitted_at' => 'nullable|date',
        ]);

        $submission = ExamSubmission::create($validated);

        return response()->json($submission, 201);
    }

    public function show(ExamSubmission $examSubmission)
    {
        return response()->json(
            $examSubmission->load('course')
        );
    }

    public function destroy(ExamSubmission $examSubmission)
    {
        $examSubmission->delete();

        return response()->json([
            'message' => 'Exam submission deleted successfully.'
        ]);
    }
}
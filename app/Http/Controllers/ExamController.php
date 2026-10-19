<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\ExamSubmission;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    /**
     * Dynamically generates an examination paper for StudentExamView.vue
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'courseId' => 'required|string',
            'totalItems' => 'nullable|integer|min:1|max:100'
        ]);

        $limit = $validated['totalItems'] ?? 10;
        $courseCode = trim($validated['courseId']);

        // Query questions matched by course code or relationship
        $questions = Question::whereHas('course', function ($q) use ($courseCode) {
            $q->where('code', $courseCode);
        })
        ->orWhere('code', 'like', "%{$courseCode}%")
        ->inRandomOrder()
        ->limit($limit)
        ->get();

        // Fallback: If course-specific questions are fewer than requested, grab general items
        if ($questions->count() < $limit) {
            $extra = Question::whereNotIn('id', $questions->pluck('id'))
                ->inRandomOrder()
                ->limit($limit - $questions->count())
                ->get();
            $questions = $questions->concat($extra);
        }

        $formattedExam = $questions->map(function ($q) {
            return [
                'id' => $q->id,
                'code' => $q->code,
                'text' => $q->text,
                'type' => $q->type ?? 'multiple_choice',
                'image_url' => $q->image_url,
                'options' => $q->options ?? [],
                'correctAnswer' => $q->correct_answer,
                'bloomLevel' => $q->bloom_level ?? 'Understanding',
                'stcwStandard' => $q->stcw_standard ?? 'Table A-II/1'
            ];
        });

        return response()->json([
            'success' => true,
            'exam' => $formattedExam
        ]);
    }

    /**
     * Receives and persists cadet examination submissions for Stage 2 Institutional Reports
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'cadet_id' => 'nullable|string|max:255',
            'cadet_name' => 'required|string|max:255',
            'course_code' => 'required|string|max:255',
            'score' => 'required|numeric|min:0',
            'total_items' => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'breakdown' => 'nullable|array',
            'submitted_at' => 'nullable|date'
        ]);

        $validated['submitted_at'] = $validated['submitted_at'] ?? now();

        $submission = ExamSubmission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Exam submission recorded successfully.',
            'data' => $submission
        ], 201);
    }
}
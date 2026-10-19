<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\SavedTest;
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
        $courseCode = $validated['courseId'];

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
     * Fetches assembled test blueprints for Stage 1 (AD-09 / AD-10) reports
     */
    public function getSavedTests()
    {
        $tests = SavedTest::with('course')->orderByDesc('created_at')->get();

        return response()->json($tests);
    }
}
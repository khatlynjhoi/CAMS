<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        // Eager load relationships so CO and LO codes/names are included in JSON output
        $query = Question::with([
            'course',
            'courseOutcome',
            'learningOutcome'
        ]);

        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        // Handle filtering by course ID or course Code passed from Test Builder
        if ($request->filled('course')) {
            $courseVal = $request->course;
            $query->where(function ($q) use ($courseVal) {
                $q->where('course_id', $courseVal)
                  ->orWhereHas('course', function ($cq) use ($courseVal) {
                      $cq->where('code', $courseVal);
                  });
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('course_outcome_id')) {
            $query->where('course_outcome_id', $request->course_outcome_id);
        }

        if ($request->filled('learning_outcome_id')) {
            $query->where('learning_outcome_id', $request->learning_outcome_id);
        }

        if ($request->filled('bloom_level')) {
            $query->where('bloom_level', $request->bloom_level);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('text', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program' => 'nullable|string|max:255',
            'term' => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'course_outcome_id' => 'nullable|string|max:255',
            'learning_outcome_id' => 'nullable|string|max:255',
            'code' => 'required|string|max:255|unique:questions,code',
            'type' => 'required|string|max:255',
            'text' => 'required|string',
            'image_url' => 'nullable|string',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable|string',
            'matching_pairs' => 'nullable|array',
            'stcw_standard' => 'nullable|string|max:255',
            'bloom_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'ai_accepted' => 'nullable|boolean',
            'retained_ai' => 'nullable|boolean',
        ]);

        $normalizedText = preg_replace(
            '/\s+/',
            ' ',
            trim($validated['text'])
        );

        $duplicate = Question::whereRaw(
            'LOWER(TRIM(REGEXP_REPLACE(text, "[[:space:]]+", " "))) = ?',
            [strtolower($normalizedText)]
        )->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Duplicate question detected.',
                'duplicate' => true,
                'existing_question' => [
                    'id' => $duplicate->id,
                    'code' => $duplicate->code,
                    'text' => $duplicate->text,
                ],
            ], 422);
        }

        $validated['text'] = $normalizedText;

        $question = Question::create($validated);
        
        // Load relationships before responding
        $question->load(['course', 'courseOutcome', 'learningOutcome']);

        return response()->json($question, 201);
    }

    public function show(Question $question)
    {
        $question->load(['course', 'courseOutcome', 'learningOutcome']);
        
        return response()->json($question);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'program' => 'nullable|string|max:255',
            'term' => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'course_outcome_id' => 'nullable|string|max:255',
            'learning_outcome_id' => 'nullable|string|max:255',
            'code' => 'required|string|max:255|unique:questions,code,' . $question->id,
            'type' => 'required|string|max:255',
            'text' => 'required|string',
            'image_url' => 'nullable|string',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable|string',
            'matching_pairs' => 'nullable|array',
            'stcw_standard' => 'nullable|string|max:255',
            'bloom_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'ai_accepted' => 'nullable|boolean',
            'retained_ai' => 'nullable|boolean',
        ]);

        $question->update($validated);
        
        // Refresh and load relationships
        $question->load(['course', 'courseOutcome', 'learningOutcome']);

        return response()->json($question);
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully.'
        ]);
    }
}
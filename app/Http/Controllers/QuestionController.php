<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::query();

        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
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

        $question = Question::create($validated);

        return response()->json($question, 201);
    }

    public function show(Question $question)
    {
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
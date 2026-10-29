<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    /**
     * Display a listing of the questions for QuestionBankView.vue.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Question::with('course');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('course_id')) {
            $query->byCourse($request->course_id);
        }

        if ($request->filled('program') && $request->program !== 'All' && $request->program !== 'Both') {
            $query->byProgram($request->program);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $query->byStatus($request->status);
        }

        $questions = $query->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program' => 'nullable|string',
            'term' => 'nullable|string',
            'course_id' => 'required|exists:courses,id',
            'course_outcome_id' => 'nullable|string',
            'learning_outcome_id' => 'nullable|string',
            'code' => 'required|string|unique:questions,code',
            'type' => 'required|string',
            'text' => 'required|string',
            'image_url' => 'nullable|string',
            'options' => 'nullable',
            'correct_answer' => 'nullable',
            'matching_pairs' => 'nullable',
            'stcw_standard' => 'nullable|string',
            'bloom_level' => 'nullable|string',
            'status' => 'nullable|string',
            'ai_accepted' => 'boolean',
            'retained_ai' => 'boolean',
        ]);

        $question = Question::create($validated);

        return response()->json($question, 201);
    }

    /**
     * Display the specified question.
     */
    public function show(Question $question): JsonResponse
    {
        return response()->json($question->load('course'));
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, Question $question): JsonResponse
    {
        $validated = $request->validate([
            'program' => 'nullable|string',
            'term' => 'nullable|string',
            'course_id' => 'sometimes|required|exists:courses,id',
            'course_outcome_id' => 'nullable|string',
            'learning_outcome_id' => 'nullable|string',
            'code' => 'sometimes|required|string|unique:questions,code,' . $question->id,
            'type' => 'sometimes|required|string',
            'text' => 'sometimes|required|string',
            'image_url' => 'nullable|string',
            'options' => 'nullable',
            'correct_answer' => 'nullable',
            'matching_pairs' => 'nullable',
            'stcw_standard' => 'nullable|string',
            'bloom_level' => 'nullable|string',
            'status' => 'nullable|string',
            'ai_accepted' => 'boolean',
            'retained_ai' => 'boolean',
        ]);

        $question->update($validated);

        return response()->json($question);
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.'
        ]);
    }
}
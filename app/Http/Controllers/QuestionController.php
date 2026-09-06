<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::with(['course']);

        // Only filter by status (case-insensitive) if passed by the request
        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
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
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'image_url' => 'nullable',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable|string',
            'matching_pairs' => 'nullable|array',
            'stcw_standard' => 'nullable|string|max:255',
            'bloom_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'ai_accepted' => 'nullable|boolean',
            'retained_ai' => 'nullable|boolean',
        ]);

        // Process File Upload if sent as binary
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('questions', 'public');
            $validated['image_url'] = '/storage/' . $path;
        } elseif ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('questions', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        unset($validated['image']);

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
        
        // Load only the valid course relationship
        $question->load(['course']);

        return response()->json($question, 201);
    }

    public function show(Question $question)
    {
        $question->load(['course']);
        
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
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'image_url' => 'nullable',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable|string',
            'matching_pairs' => 'nullable|array',
            'stcw_standard' => 'nullable|string|max:255',
            'bloom_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'ai_accepted' => 'nullable|boolean',
            'retained_ai' => 'nullable|boolean',
        ]);

        // Process File Upload if sent as binary
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('questions', 'public');
            $validated['image_url'] = '/storage/' . $path;
        } elseif ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('questions', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        unset($validated['image']);

        $normalizedText = preg_replace(
            '/\s+/',
            ' ',
            trim($validated['text'])
        );

        $validated['text'] = $normalizedText;

        $question->update($validated);
        
        $question->load(['course']);

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
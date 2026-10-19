<?php

namespace App\Http\Controllers;

use App\Models\LearningOutcome;
use Illuminate\Http\Request;

class LearningOutcomeController extends Controller
{
    public function index(Request $request)
    {
        $query = LearningOutcome::query();
        if ($request->filled('course_outcome_id')) {
            $query->where('course_outcome_id', $request->course_outcome_id);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_outcome_id' => 'required|exists:course_outcomes,id',
            'code' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $outcome = LearningOutcome::create($validated);
        return response()->json(['success' => true, 'data' => $outcome], 201);
    }

    public function show(LearningOutcome $learningOutcome)
    {
        return response()->json(['success' => true, 'data' => $learningOutcome]);
    }

    public function update(Request $request, LearningOutcome $learningOutcome)
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
        ]);

        $learningOutcome->update($validated);
        return response()->json(['success' => true, 'data' => $learningOutcome]);
    }

    public function destroy(LearningOutcome $learningOutcome)
    {
        $learningOutcome->delete();
        return response()->json(['success' => true, 'message' => 'Learning outcome deleted.']);
    }
}
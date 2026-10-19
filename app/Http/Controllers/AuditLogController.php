<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('target')) {
            $query->where('target', 'like', '%' . $request->target . '%');
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('logged_at')->orderByDesc('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'action' => 'required|string|max:255',
            'target' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'logged_at' => 'nullable|date',
        ]);

        $log = AuditLog::create($validated);

        return response()->json([
            'success' => true,
            'data' => $log
        ], 201);
    }

    public function show(AuditLog $auditLog)
    {
        return response()->json([
            'success' => true,
            'data' => $auditLog->load('user')
        ]);
    }
}
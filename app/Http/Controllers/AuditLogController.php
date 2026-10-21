<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Display a filtered and paginated listing of system audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email,role');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('target')) {
            $query->where('target', 'like', '%' . $request->target . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('logged_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('logged_at', '<=', $request->date_to);
        }

        $perPage = (int) $request->input('per_page', 50);
        $logs = $query->orderByDesc('logged_at')->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }

    /**
     * Record a new audit log entry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'action' => 'required|string|max:255',
            'target' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'logged_at' => 'nullable|date',
        ]);

        // Auto-assign active authenticated user and current timestamp if omitted
        $validated['user_id'] = $validated['user_id'] ?? $request->user()?->id;
        $validated['logged_at'] = $validated['logged_at'] ?? now();

        $log = AuditLog::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Audit log recorded successfully.',
            'data' => $log->load('user:id,name,email,role')
        ], 201);
    }

    /**
     * Display the specified audit log entry.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $auditLog->load('user:id,name,email,role')
        ]);
    }
}
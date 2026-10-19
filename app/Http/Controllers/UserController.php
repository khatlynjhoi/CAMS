<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function getManageableRoles(string $actorRole): array
    {
        $normalized = strtolower(trim($actorRole));
        if (in_array($normalized, ['admin', 'bma administrator'])) {
            return ['Admin', 'Assessment Officer', 'Instructor', 'Cadet'];
        }
        if ($normalized === 'assessment officer') {
            return ['Instructor', 'Cadet'];
        }
        if ($normalized === 'instructor') {
            return ['Cadet'];
        }
        return [];
    }

    private function getActorRole(Request $request): string
    {
        return $request->user()?->role ?? $request->header('X-User-Role', 'Admin');
    }

    public function index(Request $request)
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        if (empty($manageableRoles)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = User::whereIn('role', $manageableRoles);

        if ($request->filled('role') && $request->role !== 'All') {
            $query->where('role', $request->role);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:' . implode(',', $manageableRoles),
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Active,Inactive'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $validated['status'] ?? 'Active';

        $user = User::create($validated);

        return response()->json(['success' => true, 'data' => $user], 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'nullable|string',
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Active,Inactive',
            'password' => 'nullable|string|min:6'
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['success' => true, 'message' => 'Account removed successfully.']);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Resolve the active user's role from the session or request header.
     */
    private function getActorRole(Request $request): string
    {
        return $request->user()?->role ?? $request->header('X-User-Role', 'Admin');
    }

    /**
     * Get the list of roles the actor is authorized to manage.
     */
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

    /**
     * Display a listing of system users manageable by the authenticated actor.
     */
    public function index(Request $request): JsonResponse
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        if (empty($manageableRoles)) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthorized to manage users.'
            ], 403);
        }

        $query = User::whereIn('role', $manageableRoles);

        if ($request->filled('role') && $request->role !== 'All') {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name');

        if ($request->boolean('all', true)) {
            $users = $query->get()->map(fn ($u) => $this->formatUserPayload($u));
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginated->items())->map(fn ($u) => $this->formatUserPayload($u)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    /**
     * Store a newly created user account with password complexity enforcement.
     */
    public function store(Request $request): JsonResponse
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        if (empty($manageableRoles)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => [
                'required',
                Password::min(8)->mixedCase()->numbers()->symbols()
            ],
            'role' => 'required|string|in:' . implode(',', $manageableRoles),
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Active,Inactive'
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['email'] = strtolower(trim($validated['email']));
        $validated['password'] = Hash::make($validated['password']);
        $validated['password_changed_at'] = now();
        $validated['department'] = $validated['department'] ?? 'ICT';
        $validated['status'] = $validated['status'] ?? 'Active';

        $user = User::create($validated);

        return response()->json([
            'success' => true, 
            'message' => 'User account created successfully.',
            'data' => $this->formatUserPayload($user)
        ], 201);
    }

    /**
     * Display details for a specific user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        if (!in_array($user->role, $manageableRoles)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to view this user.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatUserPayload($user)
        ]);
    }

    /**
     * Update specified user account details and password policy.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        if (!in_array($user->role, $manageableRoles)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to edit this user.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'nullable|string|in:' . implode(',', $manageableRoles),
            'department' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:Active,Inactive',
            'password' => [
                'nullable',
                Password::min(8)->mixedCase()->numbers()->symbols()
            ]
        ]);

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }
        if (isset($validated['email'])) {
            $validated['email'] = strtolower(trim($validated['email']));
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['password_changed_at'] = now();
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true, 
            'message' => 'User account updated successfully.',
            'data' => $this->formatUserPayload($user)
        ]);
    }

    /**
     * Remove the specified user account.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $actorRole = $this->getActorRole($request);
        $manageableRoles = $this->getManageableRoles($actorRole);

        if (!in_array($user->role, $manageableRoles)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete this user.'], 403);
        }

        if ($request->user() && $request->user()->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own active account.'], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Account removed successfully.'
        ]);
    }

    /**
     * Standardize user payload output.
     */
    private function formatUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'Admin',
            'department' => !empty($user->department) ? $user->department : 'ICT',
            'status' => !empty($user->status) ? $user->status : 'Active',
        ];
    }
}
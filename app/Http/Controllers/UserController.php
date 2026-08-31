<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Whether the currently authenticated user is the account with
     * unrestricted access to every user's record (see config('auth.owner_email')).
     */
    private function isOwner(Request $request): bool
    {
        return $request->user()?->email === config('auth.owner_email');
    }

    /**
     * Whether the acting user can manage *other* users' records (create,
     * delete, edit others, change others' passwords) — the owner always
     * can, or anyone granted 'full' on the 'users' section. This never
     * extends to the owner's own record — see canManageTarget().
     */
    private function hasFullUsersAccess(Request $request): bool
    {
        if ($this->isOwner($request)) {
            return true;
        }

        return UserPermission::where('user_id', $request->user()->id)
            ->where('section', 'users')
            ->where('access', 'full')
            ->exists();
    }

    /**
     * Whether the acting user may edit/change-password the given target:
     * always allowed for their own record, otherwise only with
     * hasFullUsersAccess() — and even then, never for the owner's record
     * unless the actor *is* the owner.
     */
    private function canManageTarget(Request $request, User $target): bool
    {
        if ($request->user()->id === $target->id) {
            return true;
        }

        if ($target->email === config('auth.owner_email')) {
            return false;
        }

        return $this->hasFullUsersAccess($request);
    }

    /**
     * Get all users with their permissions
     *
     * Retrieves all user records including their per-section permissions.
     *
     * @return JsonResponse All users with permissions
     *
     * @source Database Model: User (reads with permissions relationship)
     */
    public function index(): JsonResponse
    {
        return response()->json(User::with('permissions')->get());
    }

    /**
     * Create a new user
     *
     * Validates and creates a new user record. No section permissions are
     * granted by default — the owner assigns them afterward via
     * updatePermissions(). A user with no rows for a section has no access
     * to it (see AdminNav.vue/AdminDashboard.vue's default-to-"none" logic).
     *
     * @param Request $request Contains user data (name, email, password)
     * @return JsonResponse The created user data or validation errors
     *
     * @source Database Model: User (creates)
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->hasFullUsersAccess($request)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have permission to create users'], 403);
        }

        $validated = User::validate($request->all());
        if (isset($validated['errors'])) {
            return response()->json(['status' => 'error', 'message' => array_values($validated['errors'])]);
        }

        $user = User::create($request->all());

        return response()->json($request);
    }

    /**
     * Replace a user's section-level permissions
     *
     * Requires hasFullUsersAccess() — the owner, or anyone granted 'full'
     * on the 'users' section themselves. Sections are whatever keys the
     * frontend sends (admin routes are the source of truth, not a DB
     * table), so this doesn't validate section names — only that each
     * access value is one of the three recognized levels. The owner's own
     * permissions can never be changed this way — they always have full
     * access everywhere by virtue of being the owner, not through stored
     * rows.
     *
     * @param Request $request Contains 'permissions' as {section: access}
     * @param int $id The user ID whose permissions are being set
     * @return JsonResponse Status and the resulting permission rows
     *
     * @source Database Model: UserPermission (replaces all rows for the user)
     */
    public function updatePermissions(Request $request, int $id): JsonResponse
    {
        if (! $this->hasFullUsersAccess($request)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have permission to manage permissions'], 403);
        }

        // Even with full Users access, no one can change their own
        // permissions — prevents accidentally locking themselves out.
        // Someone else with full Users access has to do it instead.
        if ($request->user()->id === $id) {
            return response()->json(['status' => 'error', 'message' => 'You cannot change your own permissions — have another user with full Users access do it.'], 403);
        }

        $target = User::find($id);
        if (! $target) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if ($target->email === config('auth.owner_email')) {
            return response()->json(['status' => 'error', 'message' => "The owner's permissions can't be changed"], 403);
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => ['required', Rule::in(['full', 'read-only', 'none'])],
        ]);

        DB::transaction(function () use ($id, $validated) {
            UserPermission::where('user_id', $id)->delete();

            foreach ($validated['permissions'] as $section => $access) {
                UserPermission::create([
                    'user_id' => $id,
                    'section' => $section,
                    'access' => $access,
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'permissions' => UserPermission::where('user_id', $id)->get(),
        ]);
    }

    /**
     * Change a user's password
     *
     * Validates the new password and updates it for the specified user.
     * The password is automatically hashed before storage.
     *
     * @param Request $request Contains 'password' field
     * @param int $id The user ID to update
     * @return array Status and message
     *
     * @source Database Model: User (updates password)
     */
    public function changePassword(Request $request, int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return ['status' => 'error', 'message' => 'Invalid user'];
        }

        if (! $this->canManageTarget($request, $user)) {
            return ['status' => 'error', 'message' => 'You do not have permission to change this password'];
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ['status' => 'error', 'message' => 'Invalid request'];
        }

        extract($validator->valid());

        $user->password = Hash::make($password);
        $user->save();

        return ['status' => 'success', 'message' => 'Password updated'];
    }

    /**
     * Update user information
     *
     * Validates and updates a user's name and email. The email uniqueness
     * check excludes the current user's existing email.
     *
     * @param Request $request Contains 'name' and 'email'
     * @param int $id The user ID to update
     * @return JsonResponse Status message or validation errors
     *
     * @source Database Model: User (updates)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
        }

        if (! $this->canManageTarget($request, $user)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have permission to edit this user'], 403);
        }

        $validator = validator($request->all(), [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => 'error', 'message' => array_values($errors)]);
        }

        $user->name  = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Delete a user
     *
     * Removes a user record from the database by ID.
     *
     * @param int $id The user ID to delete
     * @return JsonResponse The deleted user ID or error message
     *
     * @source Database Model: User (deletes)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
        }

        if ($user->email === config('auth.owner_email')) {
            return response()->json(['status' => 'error', 'message' => "The owner's account can't be deleted"], 403);
        }

        if (! $this->hasFullUsersAccess($request)) {
            return response()->json(['status' => 'error', 'message' => 'You do not have permission to delete users'], 403);
        }

        $user->delete();

        return response()->json(['deleted' => $id]);
    }
}

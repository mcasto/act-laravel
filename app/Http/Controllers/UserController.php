<?php

namespace App\Http\Controllers;

use App\Models\PermissionLevel;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users with their permissions
     *
     * Retrieves all user records including their permission relationships
     * and permission level details.
     *
     * @return JsonResponse All users with permissions
     *
     * @source Database Model: User (reads with permissions.permissionlevel relationship)
     */
    public function index(): JsonResponse
    {
        return response()->json(User::with('permissions.permissionlevel')->get());
    }

    /**
     * Create a new user with full permissions
     *
     * Validates and creates a new user record, then automatically creates
     * UserPermission records granting 'full' access for all existing
     * permission levels.
     *
     * @param Request $request Contains user data (name, email, password)
     * @return JsonResponse The created user data or validation errors
     *
     * @source Database Models:
     *   - User (creates)
     *   - PermissionLevel (reads all)
     *   - UserPermission (creates for each permission level)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = User::validate($request->all());
        if (isset($validated['errors'])) {
            return response()->json(['status' => 'error', 'message' => array_values($validated['errors'])]);
        }

        $user = User::create($request->all());
        $levels = PermissionLevel::all();
        foreach ($levels as $level) {
            UserPermission::create([
                'user_id' => $user->id,
                'permission_level_id' => $level->id,
                'access' => 'full'
            ]);
        }

        return response()->json($request);
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
        $validator = Validator::make($request->all(), [
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ['status' => 'error', 'messasge' => 'Invalid request'];
        }

        $user = User::find($id);
        if (!$user) {
            return ['status' => 'error', 'message' => 'Invalid user'];
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

        $validator = validator($request->all(), [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => 'error', 'message' => array_values($errors)]);
        }

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
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
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
        }

        $user->delete();

        return response()->json(['deleted' => $id]);
    }
}

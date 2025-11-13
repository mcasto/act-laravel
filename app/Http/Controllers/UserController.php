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
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(User::with('permissions.permissionlevel')->get());
    }

    /**
     * Store a newly created resource in storage.
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
     * Change password
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
     * Update user
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
     * Remove the specified resource from storage.
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

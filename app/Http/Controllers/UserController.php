<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        User::create($request->all());

        return response()->json($request);
    }

    /**
     * Update user
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = validator($request->all(), [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => 'error', 'message' => array_values($errors)]);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
        }

        $user->name  = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
        }

        $user->delete();

        return response()->json(['deleted' => $id]);
    }
}

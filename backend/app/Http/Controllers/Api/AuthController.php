<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
{
    $validated = $request->validate([
        'role' => ['required', 'in:student,admin'],
        'user_id' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $user = User::whereRaw(
        'BINARY user_id = ?',
        [$validated['user_id']]
    )
        ->where('role', $validated['role'])
        ->first();

    if (
        !$user ||
        !Hash::check(
            $validated['password'],
            $user->password
        )
    ) {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    $token = $user
        ->createToken('university-portal')
        ->plainTextToken;

    return response()->json([
        'message' => 'Login successful.',

        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_id' => $user->user_id,
            'email' => $user->email,
            'role' => $user->role,
        ],

        'token' => $token,
    ], 200);
}
}

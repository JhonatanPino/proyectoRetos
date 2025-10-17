<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $data = $r->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'score' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function login(Request $r)
    {
        $credentials = $r->only('username','password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        return response()->json(['token' => $token, 'expires_in' => JWTAuth::factory()->getTTL() * 60]);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(null, 204);
    }

    public function refresh()
    {
        $token = JWTAuth::parseToken()->refresh();
        return response()->json(['token' => $token, 'expires_in' => JWTAuth::factory()->getTTL() * 60]);
    }
}
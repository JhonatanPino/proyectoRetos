<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6',
        ], [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => $request->password,
            'role' => 'user',
            'score' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        // Guardar remember_token sólo si la columna existe
        if (Schema::hasColumn('users', 'remember_token')) {
            $user->setRememberToken(Str::random(10));
            $user->save();
        }

        return response()->json([
            'message' => 'Registro exitoso.',
            'data' => new UserResource($user),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 201);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $credentials = $request->only(['username','password']);

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        // Obtener el usuario a partir del token (evita problemas con el guard)
        $user = JWTAuth::setToken($token)->toUser();

        // Sólo intentar guardar remember_token si la columna existe (evita errores si la eliminaste)
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'remember_token')) {
            $user->setRememberToken(Str::random(10));
            $user->save();
        }

        return response()->json([
            'message' => 'Autenticación exitosa.',
            'data' => new UserResource($user),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            // invalidar token JWT
            JWTAuth::parseToken()->invalidate();

            // limpiar remember_token (si existe)
            if ($request->user() && Schema::hasColumn('users', 'remember_token')) {
                $user = $request->user();
                $user->setRememberToken(null);
                $user->save();
            }

            return response()->json(['message' => 'Sesión cerrada.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No se pudo cerrar la sesión.'], 500);
        }
    }

}
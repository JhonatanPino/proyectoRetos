<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api')->except(['store']); // permitir registro si lo deseas; ajusta según tu política
        $this->authorizeResource(User::class, 'user');
    }

    // Listar todos los usuarios (sin paginación)
    public function index(Request $request)
    {
        $users = User::orderByDesc('score')->get();
        return UserResource::collection($users);
    }

    // Crear usuario (StoreUserRequest valida y autoriza)
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'password' => $validated['password'], // el mutator en el modelo hashará si es necesario
            'score'    => $validated['score'] ?? 0,
            'role'     => $validated['role'],
        ]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    // Mostrar usuario
    public function show(User $user)
    {
        return new UserResource($user);
    }

    // Actualizar usuario (UpdateUserRequest valida y autoriza)
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (array_key_exists('password', $validated) && $validated['password']) {
            // dejar que el mutator procese el hash
            $user->password = $validated['password'];
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user->fresh());
    }

    // Eliminar usuario
    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api')->except(['store']);
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        $users = User::where('role', '!=', 'admin')
                 ->orderByDesc('score') 
                 ->get();
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'score'    => $validated['score'] ?? 0,
            'role'     => $validated['role'],
        ]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

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

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

}
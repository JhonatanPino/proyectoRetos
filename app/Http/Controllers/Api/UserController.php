<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $users = User::orderByDesc('score')->paginate($perPage);
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required','string','max:255','unique:users,username'],
            'password' => ['required','string','min:6'],
            'score'    => ['sometimes','integer'],
            'role'     => ['required', Rule::in(['admin','user'])],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'score'    => $validated['score'] ?? 0,
            'role'     => $validated['role'],
        ]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['sometimes','string','max:255', Rule::unique('users','username')->ignore($user->id)],
            'password' => ['sometimes','nullable','string','min:6'],
            'score'    => ['sometimes','integer'],
            'role'     => ['sometimes', Rule::in(['admin','user'])],
        ]);

        if (array_key_exists('password', $validated) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }
}
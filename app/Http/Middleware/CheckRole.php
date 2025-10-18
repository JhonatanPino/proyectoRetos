<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
                'hint' => 'Inicie sesión y reintente.'
            ], 401);
        }

        if (! in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'No autorizado.',
                'detail' => 'Su cuenta no tiene permisos para realizar esta acción.',
                'required_roles' => $roles,
                'your_role' => $user->role
            ], 403);
        }

        return $next($request);
    }
}
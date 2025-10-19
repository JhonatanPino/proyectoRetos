<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChallengeController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas que requieren autenticación
Route::middleware(['auth:api'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Obtener información del usuario autenticado
    // Accessible para Admin y User (cualquier usuario autenticado)
    Route::get('/users/me', function (Request $request) {
        return response()->json($request->user());
    });

    // Categorías
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin');

    // Retos
    Route::get('/challenges', [ChallengeController::class, 'index']);
    Route::get('/challenges/{category}', [ChallengeController::class, 'show']);
    Route::post('/challenges', [ChallengeController::class, 'store'])->middleware('role:admin');


    // Rutas administrativas (solo admin) — requiere middleware 'role:admin'
    Route::middleware(['role:admin'])->group(function () {
        //Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);
        //Route::apiResource('challenges', \App\Http\Controllers\Api\ChallengeController::class);
        //Route::apiResource('answers', \App\Http\Controllers\Api\AnswerController::class);

        // Lista de todos los usuarios (solo admin)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });

    // Rutas para usuarios autenticados (acciones sobre usuarios individuales y user-answers)
    Route::apiResource('user-answers', \App\Http\Controllers\Api\UserAnswerController::class);
    // Exponer show/update/destroy para usuarios según políticas; index ya está protegida arriba
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)
        ->only(['show','update','destroy']);
});

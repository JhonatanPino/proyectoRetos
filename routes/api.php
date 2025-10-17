<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('auth/register', [AuthController::class,'register']);
Route::post('auth/login', [AuthController::class,'login']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('auth/logout', [AuthController::class,'logout']);
    Route::post('auth/refresh', [AuthController::class,'refresh']);
    Route::get('auth/me', [AuthController::class,'me']);

    // Rutas protegidas de la API (ejemplos)
    // Route::apiResource('challenges', Api\ChallengeController::class);
    // Route::apiResource('answers', Api\AnswerController::class);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

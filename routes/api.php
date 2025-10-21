<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\AnswerController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas que requieren autenticación
Route::middleware(['auth:api'])->group(function () {
    
    // Users
    Route::get('/users/me', function (Request $request) {return response()->json($request->user());});
    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('role:admin');
    Route::post('/user/logout', [AuthController::class, 'logout']);

    // Categorías
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin');
    
    // Retos
    Route::get('/challenges', [ChallengeController::class, 'index']);
    Route::get('/challenges/{challenge}', [ChallengeController::class, 'show']);
    Route::post('/challenges', [ChallengeController::class, 'store'])->middleware('role:admin');
    Route::put('/challenges/{challenge}', [ChallengeController::class, 'update'])->middleware('role:admin');
    Route::patch('/challenges/{challenge}', [ChallengeController::class, 'update'])->middleware('role:admin');
    Route::delete('/challenges/{challenge}', [ChallengeController::class, 'destroy'])->middleware('role:admin');
    Route::post('/challenges/{challenge}/submit', [ChallengeController::class, 'submit'])->middleware('role:user');;
    Route::post('/challenges/generate-random', [ChallengeController::class, 'generateRandom'])->middleware('role:admin');

    //Answers
    Route::get('/answers', [AnswerController::class, 'index']);
    Route::post('/answers', [AnswerController::class, 'store'])->middleware('role:admin');
    Route::patch('/answers/{answer}', [AnswerController::class, 'update'])->middleware('role:admin');
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->middleware('role:admin');
});

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Challenge;
use App\Models\Answer;
use App\Models\UserAnswer;
use App\Http\Resources\UserAnswerResource;

class UserAnswerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index','show']);
    }

    public function index(Request $request)
    {
        $query = UserAnswer::with(['user', 'challenge', 'selectedAnswer']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('challenge_id')) {
            $query->where('challenge_id', $request->query('challenge_id'));
        }

        $items = $query->orderByDesc('submitted_at')->get();
        return UserAnswerResource::collection($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
            'challenge_id' => ['required','integer','exists:challenges,id'],
            'selected_answer_id' => ['required','integer','exists:answers,id'],
            'submitted_at' => ['sometimes','date'],
        ]);

        $authUser = $request->user();

        // Regla: los admins NO pueden responder retos
        if ($authUser->role === 'admin') {
            return response()->json(['message' => 'Los administradores no pueden responder retos.'], 403);
        }

        // Permisos: si no es admin, solo permitir crear para sí mismo
        if ($authUser->role !== 'admin' && $authUser->id !== (int)$validated['user_id']) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $user = User::findOrFail($validated['user_id']);
        // No permitir crear sumisiones a usuarios admin (regla: admin no responde)
        if ($user->role === 'admin') {
            return response()->json(['message' => 'No se puede crear una sumisión para un usuario administrador.'], 422);
        }

        $challenge = Challenge::findOrFail($validated['challenge_id']);
        $answer = Answer::findOrFail($validated['selected_answer_id']);

        if ($answer->challenge_id !== (int)$challenge->id) {
            return response()->json(['message' => 'La respuesta seleccionada no pertenece al reto indicado.'], 422);
        }

        $isCorrect = (bool) ($answer->is_correct ?? false);

        DB::transaction(function () use ($user, $challenge, $answer, $isCorrect, $validated, &$userAnswer) {
            // comprobar si ya existía una sumisión correcta previa (antes de insertar)
            $alreadyCorrect = false;
            if ($isCorrect) {
                $alreadyCorrect = UserAnswer::where('user_id', $user->id)
                    ->where('challenge_id', $challenge->id)
                    ->where('is_correct_submission', true)
                    ->exists();
            }

            $userAnswer = UserAnswer::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'selected_answer_id' => $answer->id,
                'is_correct_submission' => $isCorrect,
                'submitted_at' => $validated['submitted_at'] ?? now(),
            ]);

            // Sólo incrementar score si el usuario NO es admin
            if ($isCorrect && ! $alreadyCorrect && $user->role !== 'admin') {
                $points = (int) ($challenge->score_value ?? 0);
                $user->increment('score', $points);
            }
        });

        return (new UserAnswerResource($userAnswer->load(['user','challenge','selectedAnswer'])))->response()->setStatusCode(201);
    }

    public function show(UserAnswer $userAnswer)
    {
        return new UserAnswerResource($userAnswer->load(['user','challenge','selectedAnswer']));
    }

    public function update(Request $request, UserAnswer $userAnswer)
    {
        $authUser = $request->user();
        if ($authUser->role !== 'admin' && $authUser->id !== $userAnswer->user_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'selected_answer_id' => ['sometimes','integer','exists:answers,id'],
            'submitted_at' => ['sometimes','date'],
        ]);

        DB::transaction(function () use ($validated, $userAnswer, &$userAnswerUpdated) {
            $originalCorrect = (bool) $userAnswer->is_correct_submission;

            if (isset($validated['selected_answer_id'])) {
                $newAnswer = Answer::findOrFail($validated['selected_answer_id']);
                if ($newAnswer->challenge_id !== (int) $userAnswer->challenge_id) {
                    abort(422, 'La respuesta seleccionada no pertenece al reto indicado.');
                }
                $userAnswer->selected_answer_id = $newAnswer->id;
                $userAnswer->is_correct_submission = (bool) ($newAnswer->is_correct ?? false);
            }

            if (isset($validated['submitted_at'])) {
                $userAnswer->submitted_at = $validated['submitted_at'];
            }

            $userAnswer->save();

            // ajustar puntuación del usuario si cambió el estado correcto/incorrecto
            $user = $userAnswer->user;
            $challenge = $userAnswer->challenge;
            $points = (int) ($challenge->score_value ?? 0);
            $nowCorrect = (bool) $userAnswer->is_correct_submission;

            // Sólo ajustar score si el usuario objetivo NO es admin
            if ($user->role !== 'admin') {
                if (! $originalCorrect && $nowCorrect) {
                    $hadOtherCorrect = UserAnswer::where('user_id', $user->id)
                        ->where('challenge_id', $challenge->id)
                        ->where('is_correct_submission', true)
                        ->where('id', '!=', $userAnswer->id)
                        ->exists();

                    if (! $hadOtherCorrect) {
                        $user->increment('score', $points);
                    }
                } elseif ($originalCorrect && ! $nowCorrect) {
                    $otherCorrect = UserAnswer::where('user_id', $user->id)
                        ->where('challenge_id', $challenge->id)
                        ->where('is_correct_submission', true)
                        ->where('id', '!=', $userAnswer->id)
                        ->exists();

                    if (! $otherCorrect) {
                        $user->decrement('score', $points);
                    }
                }
            }

            $userAnswerUpdated = $userAnswer->fresh();
        });

        return new UserAnswerResource($userAnswerUpdated->load(['user','challenge','selectedAnswer']));
    }

    public function destroy(Request $request, UserAnswer $userAnswer)
    {
        $authUser = $request->user();
        if ($authUser->role !== 'admin' && $authUser->id !== $userAnswer->user_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        DB::transaction(function () use ($userAnswer) {
            if ($userAnswer->is_correct_submission) {
                $user = $userAnswer->user;
                $challenge = $userAnswer->challenge;
                $points = (int) ($challenge->score_value ?? 0);

                // Sólo decrementar si el usuario objetivo NO es admin
                $otherCorrect = UserAnswer::where('user_id', $user->id)
                    ->where('challenge_id', $challenge->id)
                    ->where('is_correct_submission', true)
                    ->where('id', '!=', $userAnswer->id)
                    ->exists();

                if ($user->role !== 'admin' && ! $otherCorrect) {
                    $user->decrement('score', $points);
                }
            }

            $userAnswer->delete();
        });

        return response()->noContent();
    }
}

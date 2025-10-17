<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Challenge;
use App\Models\Answer;
use App\Models\UserAnswer;
use App\Http\Resources\UserAnswerResource;

class UserAnswerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = UserAnswer::query()->with(['user', 'challenge', 'selectedAnswer']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }
        if ($request->filled('challenge_id')) {
            $query->where('challenge_id', $request->query('challenge_id'));
        }

        $items = $query->orderByDesc('submitted_at')->paginate($perPage);
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

        $user = User::findOrFail($validated['user_id']);
        $challenge = Challenge::findOrFail($validated['challenge_id']);
        $answer = Answer::findOrFail($validated['selected_answer_id']);

        if ($answer->challenge_id !== (int) $challenge->id) {
            return response()->json(['message' => 'La respuesta seleccionada no pertenece al reto indicado.'], 422);
        }

        $isCorrect = (bool) ($answer->is_correct ?? false);

        DB::transaction(function () use ($validated, $user, $challenge, $answer, $isCorrect, &$userAnswer) {
            // comprobar si ya existía una sumisión correcta ANTES de crear
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

            if ($isCorrect && ! $alreadyCorrect) {
                $points = (int) ($challenge->score_value ?? 0);
                $user->increment('score', $points);
            }
        });

        return (new UserAnswerResource($userAnswer->load(['user','challenge','selectedAnswer'])))
            ->response()->setStatusCode(201);
    }

    public function show(UserAnswer $userAnswer)
    {
        return new UserAnswerResource($userAnswer->load(['user','challenge','selectedAnswer']));
    }

    public function update(Request $request, UserAnswer $userAnswer)
    {
        $validated = $request->validate([
            'selected_answer_id' => ['sometimes','integer','exists:answers,id'],
            'submitted_at' => ['sometimes','date'],
        ]);

        DB::transaction(function () use ($validated, $userAnswer, &$userAnswerUpdated) {
            $originalCorrect = (bool) $userAnswer->is_correct_submission;
            $originalAnswerId = $userAnswer->selected_answer_id;

            if (isset($validated['selected_answer_id'])) {
                $newAnswer = Answer::findOrFail($validated['selected_answer_id']);
                if ($newAnswer->challenge_id !== (int) $userAnswer->challenge_id) {
                    throw new \Illuminate\Validation\ValidationException(
                        \Illuminate\Support\Facades\Validator::make([], []),
                        response()->json(['message' => 'La respuesta seleccionada no pertenece al reto indicado.'], 422)
                    );
                }
                $userAnswer->selected_answer_id = $newAnswer->id;
                $userAnswer->is_correct_submission = (bool) ($newAnswer->is_correct ?? false);
            }

            if (isset($validated['submitted_at'])) {
                $userAnswer->submitted_at = $validated['submitted_at'];
            }

            $userAnswer->save();

            // Ajustar puntuación del usuario si cambió el estado correcto/incorrecto
            $user = $userAnswer->user;
            $challenge = $userAnswer->challenge;
            $points = (int) ($challenge->score_value ?? 0);
            $nowCorrect = (bool) $userAnswer->is_correct_submission;

            if (! $originalCorrect && $nowCorrect) {
                // pasar de incorrecta a correcta -> sumar si no tenía otra correcta previa
                $hadOtherCorrect = UserAnswer::where('user_id', $user->id)
                    ->where('challenge_id', $challenge->id)
                    ->where('is_correct_submission', true)
                    ->where('id', '!=', $userAnswer->id)
                    ->exists();

                if (! $hadOtherCorrect) {
                    $user->increment('score', $points);
                }
            } elseif ($originalCorrect && ! $nowCorrect) {
                // pasó de correcta a incorrecta -> restar puntos si no existe otra sumisión correcta
                $otherCorrect = UserAnswer::where('user_id', $user->id)
                    ->where('challenge_id', $challenge->id)
                    ->where('is_correct_submission', true)
                    ->where('id', '!=', $userAnswer->id)
                    ->exists();

                if (! $otherCorrect) {
                    $user->decrement('score', $points);
                }
            }

            $userAnswerUpdated = $userAnswer->fresh();
        });

        return new UserAnswerResource($userAnswerUpdated->load(['user','challenge','selectedAnswer']));
    }

    public function destroy(UserAnswer $userAnswer)
    {
        DB::transaction(function () use ($userAnswer) {
            // Si la sumisión a eliminar era correcta y no hay otra correcta, restar puntos
            if ($userAnswer->is_correct_submission) {
                $user = $userAnswer->user;
                $challenge = $userAnswer->challenge;
                $points = (int) ($challenge->score_value ?? 0);

                $otherCorrect = UserAnswer::where('user_id', $user->id)
                    ->where('challenge_id', $challenge->id)
                    ->where('is_correct_submission', true)
                    ->where('id', '!=', $userAnswer->id)
                    ->exists();

                if (! $otherCorrect) {
                    $user->decrement('score', $points);
                }
            }

            $userAnswer->delete();
        });

        return response()->noContent();
    }
}

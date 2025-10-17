<?php

namespace App\Http\Controllers\Api;

use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Answer;
use App\Http\Resources\AnswerResource;

class AnswerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Answer::query();

        if ($request->filled('challenge_id')) {
            $query->where('challenge_id', $request->query('challenge_id'));
        }

        $answers = $query->orderBy('id')->paginate($perPage);
        return AnswerResource::collection($answers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'challenge_id' => ['required','integer','exists:challenges,id'],
            'description'  => [
                'required','string','max:2000',
                Rule::unique('answers','description')->where(function ($q) use ($request) {
                    return $q->where('challenge_id', $request->challenge_id);
                }),
            ],
            'is_correct'   => ['sometimes','boolean'],
        ]);

        $answer = DB::transaction(function () use ($validated) {
            $answer = Answer::create([
                'challenge_id' => $validated['challenge_id'],
                'description'  => $validated['description'],
                'is_correct'   => $validated['is_correct'] ?? false,
            ]);

            if ($answer->is_correct) {
                Answer::where('challenge_id', $answer->challenge_id)
                      ->where('id', '!=', $answer->id)
                      ->update(['is_correct' => false]);
            }

            return $answer;
        });

        return (new AnswerResource($answer->load('challenge')))->response()->setStatusCode(201);
    }

    public function show(Answer $answer)
    {
        return new AnswerResource($answer->load('challenge'));
    }

    public function update(Request $request, Answer $answer)
    {
        $validated = $request->validate([
            'challenge_id' => ['sometimes','integer','exists:challenges,id'],
            'description'  => [
                'sometimes','string','max:2000',
                Rule::unique('answers','description')->where(function ($q) use ($request, $answer) {
                    $challengeId = $request->input('challenge_id', $answer->challenge_id);
                    return $q->where('challenge_id', $challengeId);
                })->ignore($answer->id),
            ],
            'is_correct'   => ['sometimes','boolean'],
        ]);

        DB::transaction(function () use ($validated, $answer) {
            $answer->update($validated);

            if (array_key_exists('is_correct', $validated) && $validated['is_correct']) {
                Answer::where('challenge_id', $answer->challenge_id)
                      ->where('id', '!=', $answer->id)
                      ->update(['is_correct' => false]);
            }
        });

        return new AnswerResource($answer->fresh()->load('challenge'));
    }

    public function destroy(Answer $answer)
    {
        $answer->delete();
        return response()->noContent();
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Answer;
use App\Models\Challenge;
use App\Http\Resources\AnswerResource;

class AnswerController extends Controller
{
    public function __construct()
    {
        // Rutas públicas: index, show; resto requiere autenticación
        $this->middleware('auth:api')->except(['index', 'show']);
        $this->middleware('role:admin')->only(['store','update','destroy']);

    }

    // Listar respuestas (opcional filter por challenge_id)
    public function index(Request $request)
    {
        $query = Answer::query();

        if ($request->filled('challenge_id')) {
            $query->where('challenge_id', $request->query('challenge_id'));
        }

        $answers = $query->with('challenge')->orderBy('id')->get();

        return AnswerResource::collection($answers);
    }

    // Crear respuesta (solo admin)
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'challenge_id' => ['required','integer','exists:challenges,id'],
            'description'  => ['required','string'],
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

    // Mostrar respuesta
    public function show(Answer $answer)
    {
        return new AnswerResource($answer->load('challenge', 'answers'));
    }

    // Actualizar respuesta (solo admin)
    public function update(Request $request, Answer $answer)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $v = Validator::make($request->all(), [
        'description' => ['sometimes','required','string'],
        'is_correct'  => ['sometimes','boolean'],
        'challenge_id'=> ['sometimes','integer','exists:challenges,id'],
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        // No permitir reasignar la respuesta a otro challenge
        if (isset($data['challenge_id']) && $data['challenge_id'] != $answer->challenge_id) {
            return response()->json(['message' => 'No puede cambiar el challenge_id de la respuesta.'], 422);
        }

        DB::beginTransaction();
        try {
            // Si se marca como correcta, desmarcar las demás del mismo challenge
            if (array_key_exists('is_correct', $data) && $data['is_correct']) {
                Answer::where('challenge_id', $answer->challenge_id)
                    ->where('id', '!=', $answer->id)
                    ->update(['is_correct' => false]);
            }

            // No actualizar challenge_id aunque venga (ya validado)
            unset($data['challenge_id']);

            $answer->update($data);

            DB::commit();

            return response()->json([
                'message' => 'Respuesta actualizada correctamente.',
                'data' => new AnswerResource($answer->fresh())
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error actualizando la respuesta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
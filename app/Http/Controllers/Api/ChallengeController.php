<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Category;
use App\Http\Resources\ChallengeResource;
use App\Models\Answer;
use Illuminate\Support\Facades\DB;

class ChallengeController extends Controller
{
    public function __construct()
    {
        // Rutas públicas: index, show; el resto requiere autenticación
        $this->middleware('auth:api')->except(['index', 'show']);
        $this->middleware('role:admin')->only(['store','update','destroy']);

    }

    // Listar todos los challenges con su categoría y respuestas (eager load)
    public function index()
    {
        $challenges = Challenge::with(['category', 'answers'])->get();
        return ChallengeResource::collection($challenges);
    }

    // Mostrar un challenge concreto (con relaciones)
    public function show(Challenge $challenge)
    {
        return new ChallengeResource($challenge->load(['category', 'answers']));
    }

    // Crear un nuevo challenge (solo admin)
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'score_value' => 'required|integer|min:0',
            'answers' => 'required|array|min:1',
            'answers.*.description' => 'required|string',
            'answers.*.is_correct' => 'sometimes|boolean',
        ], [
            'name.required' => 'El nombre del reto es obligatorio.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'description.required' => 'La descripción del reto es obligatoria.',
            'score_value.required' => 'El valor de puntuación es obligatorio.',
            'answers.required' => 'Debe enviar las respuestas asociadas al reto.',
            'answers.*.description.required' => 'Cada respuesta necesita una descripción.',
        ]);

        // Validar que haya al menos una respuesta marcada como correcta
        $hasCorrect = collect($validated['answers'])->contains(fn($a) => !empty($a['is_correct']));
        if (! $hasCorrect) {
            return response()->json(['message' => 'Debe marcar al menos una respuesta como correcta.'], 422);
        }

        DB::beginTransaction();
        try {
            $challenge = Challenge::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'score_value' => $validated['score_value'],
            ]);

            // Crear respuestas; garantizar exactamente una correcta (primera marcada)
            $marked = false;
            foreach ($validated['answers'] as $ans) {
                $isCorrect = false;
                if (! $marked && ! empty($ans['is_correct'])) {
                    $isCorrect = true;
                    $marked = true;
                }
                Answer::create([
                    'challenge_id' => $challenge->id,
                    'description' => $ans['description'],
                    'is_correct' => $isCorrect,
                ]);
                
            }

            DB::commit();

            return response()->json([
                'message' => 'Reto creado exitosamente.',
                'data' => new ChallengeResource($challenge->load('answers','category'))
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creando el reto.',
                'error' => $e->getMessage()
            ],500);
        }
    }

    // Actualizar un challenge (solo admin)
    public function update(Request $request, Challenge $challenge)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|integer|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'score_value' => 'sometimes|integer|min:0',
        ]);

        $challenge->update($validated);

        return new ChallengeResource($challenge->fresh()->load(['category', 'answers']));
    }

    // Eliminar un challenge (solo admin)
    public function destroy(Request $request, Challenge $challenge)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $challenge->delete();
        return response()->noContent();
    }

    // Listar challenges por categoría
    public function byCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $challenges = $category->challenges()->with(['category', 'answers'])->get();
        return ChallengeResource::collection($challenges);
    }
}
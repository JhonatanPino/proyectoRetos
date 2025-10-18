<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Category;
use App\Http\Resources\ChallengeResource;

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
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'score_value' => 'required|integer|min:0',
        ]);

        $challenge = Challenge::create($validated);

        return (new ChallengeResource($challenge->load('category')))->response()->setStatusCode(201);
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
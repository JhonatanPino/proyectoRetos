<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Category;

class ChallengeController extends Controller
{
    // Listar todos los challenges con su categoría
    public function index()
    {
        $challenges = Challenge::with('category')->get();
        return view('challenges.index', compact('challenges'));
    }

    // Crear un nuevo challenge
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'score_value' => 'required|integer|min:0'
        ]);

        $challenge = Challenge::create($validated);
        return response()->json($challenge, 201); // o new ChallengeResource($challenge)
    }

    // Challenges de una categoría específica
    public function byCategory($categoryId)
    {
        $challenges = Challenge::byCategory($categoryId)->with('category')->get();
        return response()->json($challenges);
    }
}
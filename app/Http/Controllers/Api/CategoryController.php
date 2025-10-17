<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Listar todas las categorías con conteo de challenges
    public function index()
    {
        $categories = Category::withCount('challenges')->get();
        return view('categories.index', compact('categories'));
    }

    // Crear categoría (validación incluida)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        $category = Category::create($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Categoría creada exitosamente'
        ], 201);
    }

    // Mostrar categoría con sus challenges
    public function show($id)
    {
        $category = Category::with('challenges')->findOrFail($id);
        return view('categories.show', compact('category'));
    }

    // API: Obtener categorías para dropdown
    public function apiIndex()
    {
        return Category::select('id', 'name')->get();
    }
}

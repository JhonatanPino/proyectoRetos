<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index','show']);
        $this->middleware('role:admin')->only(['store','update','destroy']);

    }

    // Listar todas las categorías con conteo de challenges
    public function index()
    {
        $categories = Category::withCount('challenges')->get();
        return CategoryResource::collection($categories);
    }

    // Crear categoría (validación incluida) — solo admin
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        $category = Category::create($validated);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    // Mostrar categoría con sus challenges
    public function show(Category $category)
    {
        return new CategoryResource($category->load('challenges'));
    }

    // Actualizar categoría — solo admin
    public function update(Request $request, Category $category)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update($validated);

        return new CategoryResource($category);
    }

    // Eliminar categoría — solo admin
    public function destroy(Request $request, Category $category)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $category->delete();
        return response()->noContent();
    }

    // API: Obtener categorías para dropdown
    public function apiIndex()
    {
        return Category::select('id', 'name')->get();
    }
}

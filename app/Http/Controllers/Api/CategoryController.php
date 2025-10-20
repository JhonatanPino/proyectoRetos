<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index','show']);
        $this->middleware('role:admin')->only(['store','update','destroy']);
    }

    public function index()
    {
        $categories = Category::withCount('challenges')->get();
        return CategoryResource::collection($categories);
    }
    
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name'
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = Category::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Categoría creada exitosamente.',
            'data' => new CategoryResource($category)
        ], 201);
    }

    
    public function show(Category $category)
    {
        return new CategoryResource($category->load('challenges'));
    }

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

    public function destroy(Request $request, Category $category)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $category->delete();
        return response()->noContent();
    }

    public function apiIndex()
    {
        return Category::select('id', 'name')->get();
    }
}

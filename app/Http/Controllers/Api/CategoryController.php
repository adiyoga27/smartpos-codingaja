<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $query->orderBy('name', 'asc');

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:categories',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $category = Category::create($validated);

        return $this->created($category, 'Kategori berhasil ditambahkan.');
    }

    public function show(Category $category): JsonResponse
    {
        return $this->success($category->load('products'));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:categories,code,'.$category->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $category->update($validated);

        return $this->success($category, 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->success(null, 'Kategori berhasil dihapus.');
    }
}

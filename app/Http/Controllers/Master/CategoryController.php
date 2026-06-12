<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Category::withCount('products')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
                });
            }
            $filtered = $query->count();
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                return [
                    $item->code,
                    $item->name,
                    $item->description ?? '-',
                    $item->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>',
                    $item->products_count,
                    '<a href="'.route('master.categories.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>'.
                    '<form action="'.route('master.categories.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus kategori ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.master.categories.index');
    }

    public function create()
    {
        return view('pages.master.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        Category::create($validated);

        return redirect()->route('master.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('pages.master.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:categories,code,'.$category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $category->update($validated);

        return redirect()->route('master.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('master.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}

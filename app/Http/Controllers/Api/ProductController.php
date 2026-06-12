<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $query->orderBy('name', 'asc');

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'purchase_unit' => $request->input('purchase_unit', $request->input('unit', 'PCS')),
            'conversion_factor' => $request->input('conversion_factor', 1),
        ]);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:products',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'barcode' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'purchase_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('products', 'public');
        }
        $validated['is_active'] = $request->boolean('is_active', true);
        $product = Product::create($validated);

        return $this->created($product->load(['category', 'supplier']), 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success($product->load(['category', 'supplier']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->merge([
            'purchase_unit' => $request->input('purchase_unit', $request->input('unit', $product->purchase_unit ?? 'PCS')),
            'conversion_factor' => $request->input('conversion_factor', $product->conversion_factor ?? 1),
        ]);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:products,code,'.$product->id,
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'barcode' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'purchase_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }
            $validated['photo'] = $request->file('photo')->store('products', 'public');
        }
        $validated['is_active'] = $request->boolean('is_active', true);
        $product->update($validated);

        return $this->success($product->load(['category', 'supplier']), 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
        }
        $product->delete();

        return $this->success(null, 'Produk berhasil dihapus.');
    }
}

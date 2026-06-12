<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::with('category', 'supplier');

            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = Product::count();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            }
            $filtered = $query->count();

            $orderableColumns = ['code', 'name', 'category_id', 'purchase_price', 'selling_price', 'wholesale_price', 'stock'];
            if ($request->filled('order')) {
                foreach ($request->input('order') as $order) {
                    $colIndex = (int) ($order['column'] ?? 0);
                    $dir = $order['dir'] ?? 'asc';
                    if (isset($orderableColumns[$colIndex])) {
                        if ($orderableColumns[$colIndex] === 'category_id') {
                            $query->join('categories', 'products.category_id', '=', 'categories.id')
                                ->orderBy('categories.name', $dir)
                                ->select('products.*');
                        } else {
                            $query->orderBy($orderableColumns[$colIndex], $dir);
                        }
                    }
                }
            } else {
                $query->orderBy('name', 'asc');
            }

            $isExport = $length === -1;

            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();

            $data = $rows->map(function ($item) {
                return [
                    $item->code,
                    $item->name,
                    $item->category?->name ?? '-',
                    formatRupiah($item->purchase_price),
                    formatRupiah($item->selling_price),
                    formatRupiah($item->wholesale_price),
                    $item->stock <= $item->min_stock ? '<span class="badge bg-danger">'.$item->stock.'</span>' : $item->stock,
                    $item->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>',
                    '<a href="'.route('master.products.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>'.
                    '<form action="'.route('master.products.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus produk ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.master.products.index');
    }

    public function create()
    {
        $categories = Category::active()->pluck('name', 'id');
        $suppliers = Supplier::active()->pluck('name', 'id');
        $code = 'PRD'.str_pad((Product::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.master.products.create', compact('categories', 'suppliers', 'code'));
    }

    public function store(Request $request)
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
        Product::create($validated);

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Category::active()->pluck('name', 'id');
        $suppliers = Supplier::active()->pluck('name', 'id');

        return view('pages.master.products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
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

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
        }
        $product->delete();

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}

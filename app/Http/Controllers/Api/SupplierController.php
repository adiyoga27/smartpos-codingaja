<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $query->orderBy('name', 'asc');

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:suppliers',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $supplier = Supplier::create($validated);

        return $this->created($supplier, 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success($supplier->load(['products', 'purchases']));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:suppliers,code,'.$supplier->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? $supplier->current_balance;
        $validated['is_active'] = $request->boolean('is_active', true);
        $supplier->update($validated);

        return $this->success($supplier, 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return $this->success(null, 'Supplier berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Tax::query();

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
            'code' => 'required|string|max:50|unique:taxes',
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:50',
            'applies_to' => 'required|in:all,sale,purchase',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $tax = Tax::create($validated);

        return $this->created($tax, 'Pajak berhasil ditambahkan.');
    }

    public function show(Tax $tax): JsonResponse
    {
        return $this->success($tax);
    }

    public function update(Request $request, Tax $tax): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:taxes,code,'.$tax->id,
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:50',
            'applies_to' => 'required|in:all,sale,purchase',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $tax->update($validated);

        return $this->success($tax, 'Pajak berhasil diperbarui.');
    }

    public function destroy(Tax $tax): JsonResponse
    {
        $tax->delete();

        return $this->success(null, 'Pajak berhasil dihapus.');
    }
}

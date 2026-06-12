<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $query->orderBy('name', 'asc');

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:customers',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:50',
            'type' => 'required|in:retail,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $customer = Customer::create($validated);

        return $this->created($customer, 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->success($customer->load('sales'));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:customers,code,'.$customer->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:50',
            'type' => 'required|in:retail,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? $customer->current_balance;
        $validated['is_active'] = $request->boolean('is_active', true);
        $customer->update($validated);

        return $this->success($customer, 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return $this->success(null, 'Pelanggan berhasil dihapus.');
    }
}

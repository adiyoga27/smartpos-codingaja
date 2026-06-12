<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PaymentMethod::with('account');

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
            'code' => 'required|string|max:50|unique:payment_methods',
            'name' => 'required|string|max:255',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_available_pos'] = $request->boolean('is_available_pos', false);
        $validated['is_available_purchase'] = $request->boolean('is_available_purchase', false);
        $validated['is_credit'] = $request->boolean('is_credit', false);

        $method = PaymentMethod::create($validated);

        return $this->created($method->load('account'), 'Metode pembayaran berhasil ditambahkan.');
    }

    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        return $this->success($paymentMethod->load('account'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code,'.$paymentMethod->id,
            'name' => 'required|string|max:255',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_available_pos'] = $request->boolean('is_available_pos', false);
        $validated['is_available_purchase'] = $request->boolean('is_available_purchase', false);
        $validated['is_credit'] = $request->boolean('is_credit', false);

        $paymentMethod->update($validated);

        return $this->success($paymentMethod->load('account'), 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return $this->success(null, 'Metode pembayaran berhasil dihapus.');
    }
}

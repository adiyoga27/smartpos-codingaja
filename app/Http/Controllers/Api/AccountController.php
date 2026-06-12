<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Account::query();

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

        $query->orderBy('code', 'asc');

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts',
            'name' => 'required|string|max:255',
            'parent_code' => 'nullable|string|max:50',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'opening_balance' => 'nullable|numeric',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $account = Account::create($validated);

        return $this->created($account, 'Akun berhasil ditambahkan.');
    }

    public function show(Account $account): JsonResponse
    {
        return $this->success($account->load('journalEntries'));
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts,code,'.$account->id,
            'name' => 'required|string|max:255',
            'parent_code' => 'nullable|string|max:50',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'opening_balance' => 'nullable|numeric',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $account->update($validated);

        return $this->success($account, 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return $this->success(null, 'Akun berhasil dihapus.');
    }
}

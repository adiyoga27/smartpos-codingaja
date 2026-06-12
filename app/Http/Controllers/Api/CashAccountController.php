<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashAccountController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = CashAccount::with('account')->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts',
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        if (! ($validated['account_id'] ?? null)) {
            $accCode = $validated['type'] === 'cash' ? '1-100'.str_pad((string) (CashAccount::withTrashed()->count() + 1), 2, '0', STR_PAD_LEFT) : '1-110'.str_pad((string) (CashAccount::withTrashed()->count() + 1), 2, '0', STR_PAD_LEFT);
            $newAcc = Account::create([
                'code' => $accCode,
                'name' => $validated['name'],
                'type' => 'asset',
                'normal_balance' => 'debit',
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'is_active' => true,
            ]);
            $validated['account_id'] = $newAcc->id;
        }

        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->boolean('is_default', false)) {
            CashAccount::where('is_default', true)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $account = CashAccount::create($validated);

        return $this->created($account->load('account'), 'Akun kas berhasil ditambahkan.');
    }

    public function show(CashAccount $cashAccount): JsonResponse
    {
        return $this->success($cashAccount->load(['account', 'transactions']));
    }

    public function update(Request $request, CashAccount $cashAccount): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts,code,'.$cashAccount->id,
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->boolean('is_default', false)) {
            CashAccount::where('is_default', true)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $cashAccount->update($validated);

        return $this->success($cashAccount->load('account'), 'Akun kas berhasil diperbarui.');
    }

    public function topup(Request $request, CashAccount $cashAccount): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500',
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $cashAccount) {
            $cashAccount->current_balance += $validated['amount'];
            $cashAccount->save();

            CashTransaction::create([
                'cash_account_id' => $cashAccount->id,
                'type' => 'in',
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? 'Topup '.$cashAccount->name,
                'transaction_date' => now(),
                'created_by' => auth()->id(),
            ]);
        });

        return $this->success($cashAccount->fresh()->load('account'), 'Topup berhasil.');
    }

    public function withdraw(Request $request, CashAccount $cashAccount): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:'.$cashAccount->current_balance,
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $cashAccount) {
            $cashAccount->current_balance -= $validated['amount'];
            $cashAccount->save();

            CashTransaction::create([
                'cash_account_id' => $cashAccount->id,
                'type' => 'out',
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? 'Penarikan '.$cashAccount->name,
                'transaction_date' => now(),
                'created_by' => auth()->id(),
            ]);
        });

        return $this->success($cashAccount->fresh()->load('account'), 'Penarikan berhasil.');
    }

    public function adjust(Request $request, CashAccount $cashAccount): JsonResponse
    {
        $validated = $request->validate([
            'new_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $cashAccount) {
            $oldBalance = $cashAccount->current_balance;
            $difference = $validated['new_balance'] - $oldBalance;

            $cashAccount->current_balance = $validated['new_balance'];
            $cashAccount->save();

            $type = $difference >= 0 ? 'in' : 'out';
            CashTransaction::create([
                'cash_account_id' => $cashAccount->id,
                'type' => $type,
                'amount' => abs($difference),
                'description' => $validated['notes'] ?? 'Penyesuaian saldo '.$cashAccount->name,
                'transaction_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $kasAccount = Account::where('code', '1-1000')->first();
            $modalAccount = Account::where('code', '3-1000')->first();

            if ($kasAccount && $modalAccount && abs($difference) > 0) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Penyesuaian saldo '.$cashAccount->name,
                    'source' => 'adjustment',
                    'reference_id' => $cashAccount->id,
                    'reference_type' => CashAccount::class,
                    'total_debit' => abs($difference),
                    'total_credit' => abs($difference),
                    'created_by' => auth()->id(),
                ]);

                if ($difference > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => $difference, 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $modalAccount->id, 'debit' => 0, 'credit' => $difference]);
                } else {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $modalAccount->id, 'debit' => abs($difference), 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => 0, 'credit' => abs($difference)]);
                }
            }
        });

        return $this->success($cashAccount->fresh()->load('account'), 'Saldo berhasil disesuaikan.');
    }

    public function destroy(CashAccount $cashAccount): JsonResponse
    {
        $cashAccount->delete();

        return $this->success(null, 'Akun kas berhasil dihapus.');
    }
}

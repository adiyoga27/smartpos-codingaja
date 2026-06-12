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

class CashTransactionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = CashTransaction::with(['cashAccount', 'targetAccount', 'creator'])->latest();

        if ($request->filled('cash_account_id')) {
            $query->where('cash_account_id', $request->cash_account_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->to);
        }

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'type' => 'required|in:in,out,transfer',
            'reference_number' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'nullable|exists:accounts,id',
            'target_account_id' => 'nullable|exists:cash_accounts,id',
            'description' => 'nullable|string',
        ]);

        $transaction = null;

        DB::transaction(function () use ($validated, &$transaction) {
            $transaction = CashTransaction::create([
                'cash_account_id' => $validated['cash_account_id'],
                'type' => $validated['type'],
                'reference_number' => $validated['reference_number'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'account_id' => $validated['account_id'] ?? null,
                'target_account_id' => $validated['target_account_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $cashAccount = CashAccount::find($validated['cash_account_id']);

            if ($validated['type'] === 'in') {
                $cashAccount->current_balance += $validated['amount'];
            } elseif ($validated['type'] === 'out') {
                $cashAccount->current_balance -= $validated['amount'];
            }

            if ($validated['type'] === 'transfer' && ($validated['target_account_id'] ?? null)) {
                $targetAccount = CashAccount::find($validated['target_account_id']);
                if ($targetAccount) {
                    $targetAccount->current_balance += $validated['amount'];
                    $targetAccount->save();
                }
                $cashAccount->current_balance -= $validated['amount'];
            }

            $cashAccount->save();

            $kasAccount = Account::where('code', '1-1000')->first();
            if ($kasAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => $validated['transaction_date'],
                    'description' => $validated['description'] ?? 'Transaksi kas',
                    'source' => 'cash',
                    'reference_id' => $transaction->id,
                    'reference_type' => CashTransaction::class,
                    'total_debit' => $validated['amount'],
                    'total_credit' => $validated['amount'],
                    'created_by' => auth()->id(),
                ]);

                if ($validated['type'] === 'in') {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => $validated['amount'], 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $validated['account_id'] ?? $kasAccount->id, 'debit' => 0, 'credit' => $validated['amount']]);
                } else {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $validated['account_id'] ?? $kasAccount->id, 'debit' => $validated['amount'], 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => 0, 'credit' => $validated['amount']]);
                }
            }
        });

        return $this->created($transaction->load(['cashAccount', 'targetAccount']), 'Transaksi kas berhasil dibuat.');
    }

    public function show(CashTransaction $cashTransaction): JsonResponse
    {
        return $this->success($cashTransaction->load(['cashAccount', 'targetAccount', 'creator']));
    }
}

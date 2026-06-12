<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\PayablePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Payable::with(['supplier', 'purchase'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('search')) {
            $query->where('document_number', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function show(Payable $payable): JsonResponse
    {
        return $this->success($payable->load(['supplier', 'purchase', 'payments']));
    }

    public function storePayment(Request $request, Payable $payable): JsonResponse
    {
        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:'.$payable->remaining_amount,
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $payable) {
            PayablePayment::create([
                'payable_id' => $payable->id,
                'supplier_id' => $payable->supplier_id,
                'cash_account_id' => $validated['cash_account_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $payable->paid_amount += $validated['amount'];
            $payable->remaining_amount = max(0, $payable->amount - $payable->paid_amount);
            $payable->status = $payable->remaining_amount <= 0 ? 'paid' : 'partial';
            $payable->save();

            $cashAccount = CashAccount::find($validated['cash_account_id']);
            $cashAccount->current_balance -= $validated['amount'];
            $cashAccount->save();

            CashTransaction::create([
                'cash_account_id' => $validated['cash_account_id'],
                'type' => 'out',
                'amount' => $validated['amount'],
                'description' => 'Pembayaran hutang - '.$payable->document_number,
                'transaction_date' => $validated['payment_date'],
                'created_by' => auth()->id(),
            ]);

            $kasAccount = Account::where('code', '1-1000')->first();
            $hutangAccount = Account::where('code', '2-1000')->first();

            if ($kasAccount && $hutangAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => $validated['payment_date'],
                    'description' => 'Pembayaran hutang - '.$payable->document_number,
                    'source' => 'payment',
                    'reference_id' => $payable->id,
                    'reference_type' => Payable::class,
                    'total_debit' => $validated['amount'],
                    'total_credit' => $validated['amount'],
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutangAccount->id, 'debit' => $validated['amount'], 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => 0, 'credit' => $validated['amount']]);
            }
        });

        return $this->success($payable->fresh()->load('payments'), 'Pembayaran hutang berhasil.');
    }
}

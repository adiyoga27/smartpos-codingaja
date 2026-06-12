<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Receivable::with(['customer', 'sale'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('search')) {
            $query->where('document_number', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function show(Receivable $receivable): JsonResponse
    {
        return $this->success($receivable->load(['customer', 'sale', 'payments']));
    }

    public function storePayment(Request $request, Receivable $receivable): JsonResponse
    {
        $validated = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:'.$receivable->remaining_amount,
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $receivable) {
            ReceivablePayment::create([
                'receivable_id' => $receivable->id,
                'customer_id' => $receivable->customer_id,
                'cash_account_id' => $validated['cash_account_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $receivable->paid_amount += $validated['amount'];
            $receivable->remaining_amount = max(0, $receivable->amount - $receivable->paid_amount);
            $receivable->status = $receivable->remaining_amount <= 0 ? 'paid' : 'open';
            $receivable->save();

            if ($receivable->sale_id) {
                $sale = $receivable->sale;
                $totalPaid = $sale->receivables()->sum('paid_amount');
                $sale->update([
                    'status' => $sale->total <= $totalPaid ? 'paid' : 'unpaid',
                    'paid_amount' => $totalPaid,
                ]);
            }

            $cashAccount = CashAccount::find($validated['cash_account_id']);
            $cashAccount->current_balance += $validated['amount'];
            $cashAccount->save();

            CashTransaction::create([
                'cash_account_id' => $validated['cash_account_id'],
                'type' => 'in',
                'amount' => $validated['amount'],
                'description' => 'Penerimaan piutang - '.$receivable->document_number,
                'transaction_date' => $validated['payment_date'],
                'created_by' => auth()->id(),
            ]);

            $kasAccount = Account::where('code', '1-1000')->first();
            $piutangAccount = Account::where('code', '1-1200')->first();

            if ($kasAccount && $piutangAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => $validated['payment_date'],
                    'description' => 'Pembayaran piutang - '.$receivable->document_number,
                    'source' => 'payment',
                    'reference_id' => $receivable->id,
                    'reference_type' => Receivable::class,
                    'total_debit' => $validated['amount'],
                    'total_credit' => $validated['amount'],
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => $validated['amount'], 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $piutangAccount->id, 'debit' => 0, 'credit' => $validated['amount']]);
            }
        });

        return $this->success($receivable->fresh()->load('payments'), 'Pembayaran piutang berhasil.');
    }
}

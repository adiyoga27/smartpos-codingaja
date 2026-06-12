<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CashTransaction::with('cashAccount')->latest();
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->whereHas('cashAccount', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');
                })->orWhere('description', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                $typeBadge = match ($item->type) {
                    'in' => '<span class="badge bg-success">Masuk</span>',
                    'out' => '<span class="badge bg-danger">Keluar</span>',
                    default => '<span class="badge bg-info">Transfer</span>',
                };

                return [
                    $item->transaction_date->format('d/m/Y'),
                    $item->cashAccount?->name ?? '-',
                    $typeBadge,
                    formatRupiah($item->amount),
                    $item->description ?? '-',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.keuangan.cash_transactions.index');
    }

    public function create()
    {
        $cashAccounts = CashAccount::active()->get();
        $accounts = Account::active()->get();

        return view('pages.keuangan.cash_transactions.create', compact('cashAccounts', 'accounts'));
    }

    public function store(Request $request)
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

        DB::transaction(function () use ($validated) {
            $tx = CashTransaction::create([
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

            $source = CashAccount::find($validated['cash_account_id']);
            if ($validated['type'] === 'in') {
                $source->current_balance += $validated['amount'];
            } else {
                $source->current_balance -= $validated['amount'];
            }
            $source->save();

            if ($validated['type'] === 'transfer' && $validated['target_account_id']) {
                $target = CashAccount::find($validated['target_account_id']);
                $target->current_balance += $validated['amount'];
                $target->save();
            }

            $kas = Account::where('code', '1-1000')->first();
            if ($kas) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => $validated['transaction_date'],
                    'description' => $validated['description'] ?? 'Transaksi kas',
                    'source' => 'cash',
                    'reference_id' => $tx->id,
                    'reference_type' => CashTransaction::class,
                    'total_debit' => $validated['amount'],
                    'total_credit' => $validated['amount'],
                    'created_by' => auth()->id(),
                ]);
                if ($validated['type'] === 'in') {
                    $acc = Account::find($validated['account_id'] ?? $kas->id);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kas->id, 'debit' => $validated['amount'], 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $acc->id ?? $kas->id, 'debit' => 0, 'credit' => $validated['amount']]);
                } else {
                    $acc = Account::find($validated['account_id'] ?? $kas->id);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $acc->id ?? $kas->id, 'debit' => $validated['amount'], 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kas->id, 'debit' => 0, 'credit' => $validated['amount']]);
                }
            }
        });

        return redirect()->route('keuangan.cash_transactions.index')->with('success', 'Transaksi kas berhasil dicatat.');
    }
}

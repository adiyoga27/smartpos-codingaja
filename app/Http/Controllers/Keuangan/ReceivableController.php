<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Receivable::with('customer', 'sale')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                $statusBadge = match ($item->status) {
                    'open' => '<span class="badge bg-secondary">Belum</span>',
                    'partial' => '<span class="badge bg-warning">Sebagian</span>',
                    'paid' => '<span class="badge bg-success">Lunas</span>',
                    default => '<span class="badge bg-danger">Jatuh Tempo</span>',
                };
                $actions = '';
                if ($item->remaining_amount > 0) {
                    $actions = '<a href="'.route('keuangan.receivables.receive', $item).'" class="btn btn-sm btn-primary"><i class="bi bi-cash"></i> Terima</a>';
                }

                return [
                    $item->document_number,
                    $item->customer?->name ?? '-',
                    $item->due_date?->format('d/m/Y') ?? '-',
                    formatRupiah($item->amount),
                    formatRupiah($item->paid_amount),
                    formatRupiah($item->remaining_amount),
                    $statusBadge,
                    $actions,
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.keuangan.receivables.index');
    }

    public function receiveForm(Receivable $receivable)
    {
        $cashAccounts = CashAccount::active()->get();

        return view('pages.keuangan.receivables.receive', compact('receivable', 'cashAccounts'));
    }

    public function receiveStore(Request $request, Receivable $receivable)
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
            $receivable->status = $receivable->remaining_amount <= 0 ? 'paid' : 'partial';
            $receivable->save();

            $cash = CashAccount::find($validated['cash_account_id']);
            $cash->current_balance += $validated['amount'];
            $cash->save();

            $piutang = Account::where('code', '1-1200')->first();
            $kas = Account::where('code', '1-1000')->first();
            if ($piutang && $kas) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Penerimaan piutang '.$receivable->document_number,
                    'source' => 'payment',
                    'reference_id' => $receivable->id,
                    'reference_type' => Receivable::class,
                    'total_debit' => $validated['amount'],
                    'total_credit' => $validated['amount'],
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kas->id, 'debit' => $validated['amount'], 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $piutang->id, 'debit' => 0, 'credit' => $validated['amount']]);
            }
        });

        return redirect()->route('keuangan.receivables.index')->with('success', 'Penerimaan pembayaran berhasil dicatat.');
    }
}

<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CompanySetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Payable::with('supplier', 'purchase')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                $statusBadge = match ($item->status) {
                    'open' => '<span class="badge bg-secondary">Belum</span>',
                    'partial' => '<span class="badge bg-warning">Sebagian</span>',
                    'paid' => '<span class="badge bg-success">Lunas</span>',
                    default => '<span class="badge bg-danger">Jatuh Tempo</span>',
                };
                $actions = '<div class="flex gap-1 justify-center">';
                $actions .= '<a href="'.route('keuangan.payables.show', $item).'" class="btn btn-sm btn-info" title="Detail"><i class="bi bi-eye"></i></a>';
                if ($item->remaining_amount > 0) {
                    $actions .= '<a href="'.route('keuangan.payables.pay', $item).'" class="btn btn-sm btn-primary"><i class="bi bi-cash"></i> Bayar</a>';
                }
                $actions .= '</div>';

                return [
                    $item->document_number,
                    $item->supplier?->name ?? '-',
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

        return view('pages.keuangan.payables.index');
    }

    public function payForm(Payable $payable)
    {
        $payable->load('payments.cashAccount');
        $cashAccounts = CashAccount::active()->get();

        return view('pages.keuangan.payables.pay', compact('payable', 'cashAccounts'));
    }

    public function payStore(Request $request, Payable $payable)
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

            $cash = CashAccount::find($validated['cash_account_id']);
            $cash->current_balance -= $validated['amount'];
            $cash->save();

            CashTransaction::create([
                'cash_account_id' => $cash->id,
                'type' => 'out',
                'amount' => $validated['amount'],
                'date' => now(),
                'description' => 'Pembayaran hutang '.$payable->document_number,
                'reference_type' => PayablePayment::class,
                'reference_id' => $payable->id,
                'created_by' => auth()->id(),
            ]);

            $hutang = Account::where('code', '2-1000')->first();
            $kas = Account::where('code', '1-1000')->first();
            if ($hutang && $kas) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Pembayaran hutang '.$payable->document_number,
                    'source' => 'payment',
                    'reference_id' => $payable->id,
                    'reference_type' => Payable::class,
                    'total_debit' => $validated['amount'],
                    'total_credit' => $validated['amount'],
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutang->id, 'debit' => $validated['amount'], 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kas->id, 'debit' => 0, 'credit' => $validated['amount']]);
            }
        });

        return redirect()->route('keuangan.payables.index')->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function create()
    {
        $suppliers = Supplier::active()->pluck('name', 'id');
        $prefix = CompanySetting::first()->doc_prefix_po ?? 'PO';

        return view('pages.keuangan.payables.create', compact('suppliers', 'prefix'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $docNumber = 'HUT-MNL-'.now()->format('Ymd').'-'.str_pad((Payable::count() + 1), 4, '0', STR_PAD_LEFT);

        Payable::create([
            'supplier_id' => $validated['supplier_id'],
            'purchase_id' => null,
            'document_number' => $docNumber,
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'paid_amount' => 0,
            'remaining_amount' => $validated['amount'],
            'status' => 'open',
        ]);

        return redirect()->route('keuangan.payables.index')->with('success', 'Hutang manual berhasil ditambahkan.');
    }

    public function show(Payable $payable)
    {
        $payable->load('supplier', 'purchase', 'payments.cashAccount');

        return view('pages.keuangan.payables.show', compact('payable'));
    }
}

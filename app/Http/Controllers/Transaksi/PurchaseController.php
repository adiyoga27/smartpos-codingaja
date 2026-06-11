<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CompanySetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMutation;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Purchase::with('supplier')->latest();

            if ($request->filled('type') && $request->type === 'direct') {
                $query->where('document_number', 'like', '%-DIRECT-%');
            } elseif ($request->filled('type') && $request->type === 'po') {
                $query->where('document_number', 'not like', '%-DIRECT-%');
            }

            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');
            $isPO = $request->filled('type') && $request->type === 'po';

            $total = Purchase::count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();

            if ($isPO) {
                $query->with('items');
            }

            $data = $query->skip($start)->take($length)->get()->map(function ($item) use ($isPO) {
                $isPaid = $item->paid_amount >= $item->total;
                $statusBadge = $isPaid
                    ? '<span class="badge bg-success">Lunas</span>'
                    : '<span class="badge bg-danger">Belum Lunas</span>';

                $detailUrl = route('transaksi.purchases.show', $item);
                $printUrl = route('transaksi.purchases.print', $item);

                if ($isPO) {
                    $payUrl = route('transaksi.purchases.pay', $item);
                    $receiveUrl = route('transaksi.purchases.receive.form', $item);
                    $totalQty = $item->items->sum('quantity');
                    $receivedQty = $item->items->sum('received_quantity');
                    $allReceived = $receivedQty >= $totalQty;

                    $actions = '<div class="flex gap-1">';
                    if (! $isPaid) {
                        $actions .= '<a href="'.$payUrl.'" class="btn btn-sm btn-success" title="Bayar"><i class="bi bi-cash-coin"></i></a>';
                    }
                    if (! $allReceived) {
                        $actions .= '<a href="'.$receiveUrl.'" class="btn btn-sm btn-primary" title="Terima Barang"><i class="bi bi-box-arrow-in-down"></i></a>';
                    }
                    $actions .= '<a href="'.$detailUrl.'" class="btn btn-sm btn-info" title="Detail"><i class="bi bi-eye"></i></a>';
                    $actions .= '<a href="'.$printUrl.'" target="_blank" class="btn btn-sm btn-warning" title="Print"><i class="bi bi-printer"></i></a>';
                    if (! $isPaid && $item->status !== 'cancelled') {
                        $actions .= '<form action="'.route('transaksi.purchases.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus PO ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button></form>';
                    }
                    $actions .= '</div>';
                } else {
                    $actions = '<a href="'.$detailUrl.'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>'
                        .'<a href="'.$printUrl.'" target="_blank" class="btn btn-sm btn-warning"><i class="bi bi-printer"></i></a>';
                    if (! $isPaid && $item->status !== 'cancelled') {
                        $actions .= '<form action="'.route('transaksi.purchases.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus data ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>';
                    }
                }

                $row = [
                    $item->document_number,
                    $item->supplier?->name ?? '-',
                    $item->purchase_date->format('d/m/Y'),
                    formatRupiah($item->total),
                ];

                if ($isPO) {
                    $pct = $totalQty > 0 ? round(($receivedQty / $totalQty) * 100) : 0;
                    $pctBadge = $pct >= 100
                        ? '<span class="badge bg-success">'.$pct.'%</span>'
                        : '<span class="badge bg-warning">'.$pct.'%</span>';

                    $sisa = max(0, $item->total - $item->paid_amount);
                    $sisaText = $sisa > 0 ? formatRupiah($sisa) : '<span class="text-success">Lunas</span>';

                    $row[] = $pctBadge;
                    $row[] = $sisaText;
                }

                $row[] = $statusBadge;
                $row[] = $actions;

                return $row;
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.transaksi.purchases.index');
    }

    public function create()
    {
        $suppliers = Supplier::active()->pluck('name', 'id');
        $products = Product::active()->get();
        $cashAccounts = CashAccount::active()->get();
        $prefix = CompanySetting::first()->doc_prefix_po ?? 'PO';
        $documentNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((Purchase::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.transaksi.purchases.create', compact('suppliers', 'products', 'cashAccounts', 'documentNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|string|unique:purchases',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += max(0, $total);
            }
            $tax = 0;
            $total = $subtotal + $tax;

            $purchase = Purchase::create([
                'document_number' => $validated['document_number'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => max(0, $lineTotal),
                ]);
            }
        });

        return redirect()->route('transaksi.purchases.index')->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('items.product', 'supplier', 'payables');
        $receiveHistory = StockMutation::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->with('product', 'creator')
            ->latest()
            ->get();
        $paymentHistory = CashTransaction::where('description', 'like', '%'.$purchase->document_number.'%')
            ->with('cashAccount', 'creator')
            ->latest()
            ->get();

        return view('pages.transaksi.purchases.show', compact('purchase', 'receiveHistory', 'paymentHistory'));
    }

    public function payForm(Purchase $purchase)
    {
        $purchase->load('items.product', 'supplier');
        $cashAccounts = CashAccount::active()->get();
        $paymentHistory = CashTransaction::where('description', 'like', '%'.$purchase->document_number.'%')
            ->with('cashAccount', 'creator')
            ->latest()
            ->get();

        return view('pages.transaksi.purchases.pay', compact('purchase', 'cashAccounts', 'paymentHistory'));
    }

    public function payStore(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.cash_account_id' => 'required|exists:cash_accounts,id',
            'payments.*.amount' => 'required|numeric|min:500',
        ]);

        DB::transaction(function () use ($purchase, $validated) {
            $newPaid = 0;
            foreach ($validated['payments'] as $payment) {
                $amount = (float) $payment['amount'];
                $cashAccount = CashAccount::find($payment['cash_account_id']);
                if ($cashAccount) {
                    $cashAccount->current_balance -= $amount;
                    $cashAccount->save();

                    CashTransaction::create([
                        'cash_account_id' => $cashAccount->id,
                        'type' => 'out',
                        'amount' => $amount,
                        'transaction_date' => now(),
                        'description' => 'Pembayaran PO '.$purchase->document_number,
                        'created_by' => auth()->id(),
                    ]);
                }
                $newPaid += $amount;
            }
            $purchase->paid_amount += $newPaid;
            $purchase->save();

            $remaining = max(0, $purchase->total - $purchase->paid_amount);

            $payable = Payable::where('purchase_id', $purchase->id)->first();
            if ($payable) {
                $payable->update([
                    'paid_amount' => $purchase->paid_amount,
                    'remaining_amount' => $remaining,
                    'status' => $remaining <= 0 ? 'paid' : 'partial',
                ]);
            } else {
                $dueDate = $purchase->due_date ?? ($remaining > 0 ? now()->addDays(30)->format('Y-m-d') : null);
                Payable::create([
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'document_number' => $purchase->document_number,
                    'due_date' => $dueDate,
                    'amount' => $purchase->total,
                    'paid_amount' => $purchase->paid_amount,
                    'remaining_amount' => $remaining,
                    'status' => $remaining <= 0 ? 'paid' : 'partial',
                ]);
            }

            $hutangAccount = Account::where('code', '2-1000')->first();
            if ($hutangAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Jurnal pembayaran PO '.$purchase->document_number,
                    'source' => 'purchase_payment',
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'total_debit' => $newPaid,
                    'total_credit' => $newPaid,
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutangAccount->id, 'debit' => $newPaid, 'credit' => 0]);

                foreach ($validated['payments'] as $payment) {
                    $cashAccount = CashAccount::find($payment['cash_account_id']);
                    if ($cashAccount && $cashAccount->account_id) {
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAccount->account_id, 'debit' => 0, 'credit' => (float) $payment['amount']]);
                    }
                }
            }
        });

        return redirect()->route('transaksi.purchases.pay', $purchase)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function receiveForm(Purchase $purchase)
    {
        $purchase->load('items.product', 'supplier');
        $receiveHistory = StockMutation::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->with('product', 'creator')
            ->latest()
            ->get();

        return view('pages.transaksi.purchases.terima', compact('purchase', 'receiveHistory'));
    }

    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status === 'cancelled') {
            return back()->with('error', 'PO tidak dapat diterima.');
        }

        $request->validate([
            'items' => 'nullable|array',
            'items.*' => 'numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.cash_account_id' => 'required|exists:cash_accounts,id',
            'payments.*.amount' => 'required|numeric|min:500',
        ]);

        DB::transaction(function () use ($request, $purchase) {
            $items = $request->input('items', []);
            foreach ($items as $itemId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) {
                    continue;
                }
                $item = PurchaseItem::find($itemId);
                if ($item) {
                    $oldReceived = $item->received_quantity;
                    $item->received_quantity += $qty;
                    $item->save();
                    $product = $item->product;
                    $oldStock = $product->stock;
                    $product->stock += $qty;
                    $product->save();
                    StockMutation::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity' => $qty,
                        'stock_before' => $oldStock,
                        'stock_after' => $product->stock,
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'notes' => 'Penerimaan PO '.$purchase->document_number,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            $totalReceived = $purchase->items->sum('received_quantity');
            $totalQty = $purchase->items->sum('quantity');
            if ($totalReceived >= $totalQty) {
                $purchase->status = 'completed';
            } elseif ($totalReceived > 0) {
                $purchase->status = 'partial';
            }
            $purchase->save();

            $payments = $request->input('payments', []);
            if (! empty($payments)) {
                $newPaid = 0;
                foreach ($payments as $payment) {
                    $amount = (float) $payment['amount'];
                    $cashAccount = CashAccount::find($payment['cash_account_id']);
                    if ($cashAccount) {
                        $cashAccount->current_balance -= $amount;
                        $cashAccount->save();

                        CashTransaction::create([
                            'cash_account_id' => $cashAccount->id,
                            'type' => 'out',
                            'amount' => $amount,
                            'transaction_date' => now(),
                            'description' => 'Pembayaran PO '.$purchase->document_number,
                            'created_by' => auth()->id(),
                        ]);
                    }
                    $newPaid += $amount;
                }
                $purchase->paid_amount += $newPaid;
                $purchase->save();

                $remaining = max(0, $purchase->total - $purchase->paid_amount);
                $dueDate = $purchase->due_date ?? ($remaining > 0 ? now()->addDays(30)->format('Y-m-d') : null);

                $payable = Payable::where('purchase_id', $purchase->id)->first();
                if ($payable) {
                    $payable->update([
                        'paid_amount' => $purchase->paid_amount,
                        'remaining_amount' => $remaining,
                        'status' => $remaining <= 0 ? 'paid' : 'partial',
                    ]);
                } else {
                    Payable::create([
                        'supplier_id' => $purchase->supplier_id,
                        'purchase_id' => $purchase->id,
                        'document_number' => $purchase->document_number,
                        'due_date' => $dueDate,
                        'amount' => $purchase->total,
                        'paid_amount' => $purchase->paid_amount,
                        'remaining_amount' => $remaining,
                        'status' => $remaining <= 0 ? 'paid' : ($purchase->paid_amount > 0 ? 'partial' : 'open'),
                    ]);
                }

                $hutangAccount = Account::where('code', '2-1000')->first();
                if ($hutangAccount) {
                    $paymentJournal = Journal::create([
                        'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                        'journal_date' => now(),
                        'description' => 'Jurnal pembayaran PO '.$purchase->document_number,
                        'source' => 'purchase_payment',
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'total_debit' => $newPaid,
                        'total_credit' => $newPaid,
                        'created_by' => auth()->id(),
                    ]);
                    JournalEntry::create(['journal_id' => $paymentJournal->id, 'account_id' => $hutangAccount->id, 'debit' => $newPaid, 'credit' => 0]);

                    foreach ($payments as $payment) {
                        $cashAccount = CashAccount::find($payment['cash_account_id']);
                        if ($cashAccount && $cashAccount->account_id) {
                            JournalEntry::create(['journal_id' => $paymentJournal->id, 'account_id' => $cashAccount->account_id, 'debit' => 0, 'credit' => (float) $payment['amount']]);
                        }
                    }
                }

                $hasJournal = Journal::where('reference_type', Purchase::class)
                    ->where('reference_id', $purchase->id)
                    ->where('source', 'purchase')
                    ->exists();
                if (! $hasJournal) {
                    $inventoryAccount = Account::where('code', '1-1300')->first();
                    if ($inventoryAccount && $hutangAccount) {
                        $journal = Journal::create([
                            'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                            'journal_date' => now(),
                            'description' => 'Jurnal otomatis PO '.$purchase->document_number,
                            'source' => 'purchase',
                            'reference_id' => $purchase->id,
                            'reference_type' => Purchase::class,
                            'total_debit' => $purchase->total,
                            'total_credit' => $purchase->total,
                            'created_by' => auth()->id(),
                        ]);
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $inventoryAccount->id, 'debit' => $purchase->total, 'credit' => 0]);
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutangAccount->id, 'debit' => 0, 'credit' => $purchase->total]);
                    }
                }
            }
        });

        return back()->with('success', 'Penerimaan & pembayaran berhasil dicatat.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return redirect()->route('transaksi.purchases.index')->with('success', 'PO berhasil dihapus.');
    }

    public function direct()
    {
        $suppliers = Supplier::pluck('name', 'id');
        $products = Product::active()->get();
        $cashAccounts = CashAccount::active()->get();
        $prefix = CompanySetting::first()->doc_prefix_po ?? 'PO';
        $documentNumber = $prefix.'-DIRECT-'.now()->format('Ymd').'-'.str_pad((Purchase::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.transaksi.purchases.direct', compact('suppliers', 'products', 'cashAccounts', 'documentNumber'));
    }

    public function storeDirect(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|string|unique:purchases',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.cash_account_id' => 'required|exists:cash_accounts,id',
            'payments.*.amount' => 'required|numeric|min:500',
        ], [
            'payments.required' => 'Minimal satu metode pembayaran harus diisi.',
        ]);

        DB::transaction(function () use ($validated) {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += max(0, $total);
            }
            $tax = 0;
            $total = $subtotal + $tax;

            $paidAmount = 0;
            if (! empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    $paidAmount += (float) $payment['amount'];
                }
            }

            $purchase = Purchase::create([
                'document_number' => $validated['document_number'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => min($paidAmount, $total),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => max(0, $lineTotal),
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $oldStock = $product->stock;
                    $product->stock += $item['quantity'];
                    $product->save();

                    StockMutation::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity' => $item['quantity'],
                        'stock_before' => $oldStock,
                        'stock_after' => $product->stock,
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'notes' => 'Pembelian langsung '.$purchase->document_number,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            if (! empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    $cashAccount = CashAccount::find($payment['cash_account_id']);
                    if ($cashAccount) {
                        $cashAccount->current_balance -= (float) $payment['amount'];
                        $cashAccount->save();

                        CashTransaction::create([
                            'cash_account_id' => $cashAccount->id,
                            'type' => 'out',
                            'amount' => (float) $payment['amount'],
                            'transaction_date' => now(),
                            'description' => 'Pembayaran pembelian '.$purchase->document_number,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            $remaining = max(0, $total - $paidAmount);
            $dueDate = $validated['due_date'] ?? ($remaining > 0 ? now()->addDays(30)->format('Y-m-d') : null);

            if ($remaining > 0) {
                Payable::create([
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'document_number' => $purchase->document_number,
                    'due_date' => $dueDate,
                    'amount' => $purchase->total,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remaining,
                    'status' => $paidAmount > 0 ? 'partial' : 'open',
                ]);
            }

            $inventoryAccount = Account::where('code', '1-1300')->first();
            $hutangAccount = Account::where('code', '2-1000')->first();
            if ($inventoryAccount && $hutangAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Jurnal pembelian langsung '.$purchase->document_number,
                    'source' => 'purchase',
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'total_debit' => $purchase->total,
                    'total_credit' => $purchase->total,
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $inventoryAccount->id, 'debit' => $purchase->total, 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutangAccount->id, 'debit' => 0, 'credit' => $purchase->total]);

                if ($paidAmount > 0 && ! empty($validated['payments'])) {
                    $paymentJournal = Journal::create([
                        'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                        'journal_date' => now(),
                        'description' => 'Jurnal pembayaran pembelian '.$purchase->document_number,
                        'source' => 'purchase_payment',
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'total_debit' => $paidAmount,
                        'total_credit' => $paidAmount,
                        'created_by' => auth()->id(),
                    ]);
                    JournalEntry::create(['journal_id' => $paymentJournal->id, 'account_id' => $hutangAccount->id, 'debit' => $paidAmount, 'credit' => 0]);

                    foreach ($validated['payments'] as $payment) {
                        $cashAccount = CashAccount::find($payment['cash_account_id']);
                        if ($cashAccount && $cashAccount->account_id) {
                            JournalEntry::create(['journal_id' => $paymentJournal->id, 'account_id' => $cashAccount->account_id, 'debit' => 0, 'credit' => (float) $payment['amount']]);
                        }
                    }
                }
            }
        });

        return redirect()->route('transaksi.purchases.index')->with('success', 'Pembelian langsung berhasil disimpan.');
    }

    public function print(Purchase $purchase)
    {
        $purchase->load(['items.product', 'supplier', 'creator']);
        $company = CompanySetting::first();

        return view('pages.transaksi.purchases.print', compact('purchase', 'company'));
    }
}

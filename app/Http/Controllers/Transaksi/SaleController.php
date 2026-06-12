<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMutation;
use App\Models\Tax;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function kasir(Request $request)
    {
        $mode = $request->query('mode');

        if (! in_array($mode, ['toko', 'reseller'])) {
            return view('pages.transaksi.pos.mode');
        }

        $customers = Customer::active()->get();
        $products = Product::active()->with('category')
            ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
            ->orderByRaw('stock DESC')
            ->orderBy('name')
            ->get();
        $taxes = Tax::active()->get();
        $defaultTax = Tax::active()->whereIn('applies_to', ['sale', 'all'])->first();
        $cashAccounts = CashAccount::active()->get();
        $defaultCashAccount = CashAccount::active()->where('is_default', true)->first();
        $cashAccountsJson = $cashAccounts->map(function ($a) {
            return ['id' => $a->id, 'name' => $a->name, 'type' => $a->type, 'is_default' => $a->is_default];
        })->values()->toJson();
        $paymentMethods = PaymentMethod::active()->where('is_available_pos', true)->get();
        $prefix = CompanySetting::first()->doc_prefix_inv ?? 'INV';
        $invoiceNumber = $prefix.'-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -5));
        $productsJson = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'retail_price' => (float) $p->selling_price,
                'wholesale_price' => (float) $p->wholesale_price,
                'stock' => (float) $p->stock,
            ];
        })->values()->toJson();

        return view('pages.transaksi.pos.kasir', compact('customers', 'products', 'productsJson', 'taxes', 'defaultTax', 'cashAccounts', 'defaultCashAccount', 'cashAccountsJson', 'paymentMethods', 'invoiceNumber'));
    }

    public function store(Request $request)
    {
        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        $isCredit = $paymentMethod && $paymentMethod->is_credit;

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:sales',
            'customer_id' => $isCredit ? 'required|exists:customers,id' : 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'sale_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'cash_account_id' => $isCredit ? 'nullable|exists:cash_accounts,id' : 'nullable|exists:cash_accounts,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'total_discount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ], [
            'customer_id.required' => 'Silahkan pilih customer terlebih dahulu untuk pembayaran kredit.',
        ]);

        $sale = null;

        DB::transaction(function () use ($validated, &$sale, $paymentMethod, $isCredit) {
            $subtotal = 0;
            $itemDiscount = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += max(0, $lineTotal);
                $itemDiscount += ($item['discount'] ?? 0);
            }
            $tax = 0;
            if ($validated['tax_id'] ?? null) {
                $taxRate = Tax::find($validated['tax_id'])?->rate ?? 0;
                $tax = round(($subtotal - $itemDiscount) * $taxRate / 100);
            }
            $totalDiscount = $validated['total_discount'] ?? 0;
            $total = max(0, $subtotal + $tax - $totalDiscount);
            $paidAmount = $validated['paid_amount'] ?? $total;
            $change = max(0, $paidAmount - $total);

            $status = $isCredit ? 'unpaid' : (($paidAmount >= $total) ? 'paid' : 'partial');

            $cashAccountId = $validated['cash_account_id'] ?? null;
            if (! $isCredit && ! $cashAccountId) {
                $cashAccountId = CashAccount::active()->where('is_default', true)->value('id');
            }

            $customerName = $validated['customer_name'] ?? null;
            if (! $customerName && ($validated['customer_id'] ?? null)) {
                $customerName = Customer::find($validated['customer_id'])?->name;
            }

            $sale = Sale::create([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $customerName,
                'sale_date' => $validated['sale_date'],
                'due_date' => $validated['due_date'] ?? null,
                'payment_method_id' => $validated['payment_method_id'],
                'tax_id' => $validated['tax_id'] ?? null,
                'tax_amount' => $tax,
                'cash_account_id' => $cashAccountId,
                'status' => $status,
                'subtotal' => $subtotal,
                'item_discount' => $itemDiscount,
                'total_discount' => $totalDiscount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => min($paidAmount, $total),
                'change_amount' => $change,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => max(0, $lineTotal),
                ]);
                $product = Product::find($item['product_id']);
                $oldStock = $product->stock;
                $product->stock -= $item['quantity'];
                $product->save();
                StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'stock_before' => $oldStock,
                    'stock_after' => $product->stock,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'created_by' => auth()->id(),
                ]);
            }

            // Create receivable if credit
            if ($isCredit && $validated['customer_id']) {
                Receivable::create([
                    'customer_id' => $validated['customer_id'],
                    'sale_id' => $sale->id,
                    'document_number' => $sale->invoice_number,
                    'due_date' => $validated['due_date'] ?? now()->addDays(30),
                    'amount' => $total,
                    'paid_amount' => 0,
                    'remaining_amount' => $total,
                    'status' => 'open',
                ]);
            }

            // Update cash account balance
            if (! $isCredit && $cashAccountId) {
                $cashAccount = CashAccount::find($cashAccountId);
                if ($cashAccount) {
                    $cashAccount->current_balance += $total;
                    $cashAccount->save();

                    CashTransaction::create([
                        'cash_account_id' => $cashAccountId,
                        'type' => 'in',
                        'amount' => $total,
                        'description' => 'Penjualan POS - '.$sale->invoice_number,
                        'transaction_date' => now(),
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Auto journal
            $penjualanAccount = Account::where('code', '4-1000')->first();
            if ($penjualanAccount && $paymentMethod->account_id) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Jurnal penjualan '.$sale->invoice_number,
                    'source' => 'sale',
                    'reference_id' => $sale->id,
                    'reference_type' => Sale::class,
                    'total_debit' => $total,
                    'total_credit' => $total,
                    'created_by' => auth()->id(),
                ]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $paymentMethod->account_id, 'debit' => $total, 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $penjualanAccount->id, 'debit' => 0, 'credit' => $total]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaksi berhasil disimpan.', 'sale_id' => $sale->id]);
        }

        return redirect()->route('pos.kasir')->with('success', 'Transaksi berhasil disimpan.')->with('last_sale_id', $sale->id);
    }

    public function printA4(Sale $sale)
    {
        $sale->load(['items.product', 'creator', 'paymentMethod']);
        $company = CompanySetting::first();

        return view('pages.transaksi.pos.print_a4', compact('sale', 'company'));
    }

    public function printThermal(Sale $sale)
    {
        $sale->load(['items.product', 'paymentMethod']);
        $company = CompanySetting::first();

        return view('pages.transaksi.pos.print_thermal', compact('sale', 'company'));
    }

    public function printEpson(Sale $sale)
    {
        $sale->load(['items.product', 'paymentMethod']);
        $company = CompanySetting::first();

        return view('pages.transaksi.pos.print_epson', compact('sale', 'company'));
    }

    public function downloadPdf(Sale $sale)
    {
        $sale->load(['items.product', 'paymentMethod', 'customer', 'creator']);
        $company = CompanySetting::first();
        $isPdf = true;

        $pdf = Pdf::loadView('pages.transaksi.pos.print_epson', compact('sale', 'company', 'isPdf'))
            ->setPaper('A4', 'landscape');

        return $pdf->download('Invoice-'.$sale->invoice_number.'.pdf');
    }

    public function riwayat(Request $request)
    {
        if ($request->ajax()) {
            $query = Sale::with(['customer', 'paymentMethod', 'receivables'])->latest();
            if ($request->filled('from')) {
                $query->whereDate('sale_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('sale_date', '<=', $request->to);
            }
            if ($request->filled('payment_method_id')) {
                $query->where('payment_method_id', $request->payment_method_id);
            }
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                $statusBadge = match ($item->status) {
                    'paid' => '<span class="badge bg-success">Lunas</span>',
                    default => '<span class="badge bg-danger">Belum Lunas</span>',
                };

                $actions = '';
                if (auth()->user()->can('view_sale')) {
                    $actions .= '<div class="flex gap-1.5 items-center justify-center">'
                        .'<button type="button" onclick="printReceipt(\''.route('pos.print-epson', $item).'\')" class="bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-800 px-2.5 py-1.5 rounded-lg shadow-sm transition-all" title="Cetak"><i class="bi bi-printer-fill"></i></button>'
                        .'<a href="'.route('pos.detail', $item).'" class="bg-info-50 text-info-600 hover:bg-info-100 hover:text-info-700 px-2.5 py-1.5 rounded-lg shadow-sm transition-all" title="Detail"><i class="bi bi-eye"></i></a>';
                    if (auth()->user()->can('edit_sale')) {
                        $actions .= '<a href="'.route('pos.edit', $item).'" class="bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 px-2.5 py-1.5 rounded-lg shadow-sm transition-all" title="Edit"><i class="bi bi-pencil"></i></a>';
                    }
                    $receivable = $item->receivables->firstWhere('remaining_amount', '>', 0);
                    if ($receivable) {
                        $actions .= '<a href="'.route('keuangan.receivables.receive', $receivable).'" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 px-2.5 py-1.5 rounded-lg shadow-sm transition-all" title="Pembayaran"><i class="bi bi-cash-coin"></i></a>';
                    }
                    if (auth()->user()->can('delete_sale')) {
                        $actions .= '<button type="button" class="bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 px-2.5 py-1.5 rounded-lg shadow-sm transition-all btn-delete-sale" data-id="'.$item->id.'" data-invoice="'.$item->invoice_number.'" title="Hapus"><i class="bi bi-trash"></i></button>';
                    }
                    $actions .= '</div>';
                }

                return [
                    $item->invoice_number,
                    $item->customer?->name ?? $item->customer_name ?? 'Umum',
                    $item->sale_date->format('d/m/Y'),
                    $item->paymentMethod?->name ?? '-',
                    formatRupiah($item->total),
                    $statusBadge,
                    $actions,
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        $paymentMethods = PaymentMethod::active()->where('is_available_pos', true)->get();

        return view('pages.transaksi.pos.riwayat', compact('paymentMethods'));
    }

    public function riwayatDetail(Sale $sale)
    {
        $sale->load(['items.product', 'paymentMethod', 'customer']);

        $statusBadge = match ($sale->status) {
            'paid' => '<span class="badge bg-success">Lunas</span>',
            'partial' => '<span class="badge bg-warning">Sebagian</span>',
            'unpaid' => '<span class="badge bg-danger">Belum Bayar</span>',
            default => '<span class="badge bg-secondary">Batal</span>',
        };

        return response()->json([
            'data' => [
                'invoice' => $sale->invoice_number,
                'customer' => $sale->customer?->name ?? $sale->customer_name ?? 'Umum',
                'date' => $sale->sale_date->format('d/m/Y H:i'),
                'method' => $sale->paymentMethod?->name ?? '-',
                'status' => $sale->status,
                'status_badge' => $statusBadge,
                'subtotal' => formatRupiah($sale->subtotal),
                'discount' => formatRupiah($sale->item_discount + $sale->total_discount),
                'tax' => formatRupiah($sale->tax),
                'total' => formatRupiah($sale->total),
                'paid' => formatRupiah($sale->paid_amount),
                'notes' => $sale->notes,
                'items' => $sale->items->map(fn ($i) => [
                    'name' => $i->product?->name ?? '-',
                    'qty' => $i->quantity,
                    'price' => formatRupiah($i->unit_price),
                    'disc' => formatRupiah($i->discount),
                    'total' => formatRupiah($i->total),
                ]),
            ],
        ]);
    }

    public function detail(Sale $sale)
    {
        $sale->load(['items.product', 'paymentMethod', 'customer', 'creator']);

        return view('pages.transaksi.pos.detail', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $sale->load(['items.product', 'paymentMethod', 'customer']);
        $paymentMethods = PaymentMethod::active()->where('is_available_pos', true)->get();
        $taxes = Tax::active()->get();

        $isCredit = $sale->paymentMethod && $sale->paymentMethod->is_credit;
        $dueDate = null;
        if ($isCredit) {
            $receivable = Receivable::where('sale_id', $sale->id)->first();
            $dueDate = $receivable?->due_date?->format('Y-m-d') ?? $sale->due_date?->format('Y-m-d');
        }

        return view('pages.transaksi.pos.edit', compact('sale', 'paymentMethods', 'taxes', 'isCredit', 'dueDate'));
    }

    public function recentSales()
    {
        $sales = Sale::with(['paymentMethod', 'customer'])
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'customer' => $sale->customer?->name ?? $sale->customer_name ?? 'Umum',
                    'date' => $sale->sale_date->format('d/m/Y H:i'),
                    'method' => $sale->paymentMethod?->name ?? '-',
                    'total' => formatRupiah($sale->total),
                    'status' => $sale->status,
                    'print_a4' => route('pos.print-a4', $sale),
                    'print_thermal' => route('pos.print-thermal', $sale),
                    'print_epson' => route('pos.print-epson', $sale),
                ];
            });

        return response()->json($sales);
    }

    public function customerQuickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'type' => 'in:retail,wholesale',
        ]);

        $count = Customer::withTrashed()->count() + 1;
        $customer = Customer::create([
            'code' => 'CUS'.str_pad((string) $count, 3, '0', STR_PAD_LEFT),
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'type' => $validated['type'] ?? 'retail',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        return response()->json([
            'success' => true,
            'customer' => ['id' => $customer->id, 'name' => $customer->name, 'code' => $customer->code],
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        if (! auth()->user()->can('edit_sale')) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:paid,partial,unpaid,cancelled',
            'due_date' => 'nullable|date',
            'sale_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'total_discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        $sale->load('items');

        DB::transaction(function () use ($validated, $sale) {
            // 1. Revert old stock
            foreach ($sale->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->stock += $oldItem->quantity;
                    $product->save();
                }
            }

            // 2. Delete old stock mutations
            StockMutation::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->delete();

            // 3. Update each item
            $subtotal = 0;
            $itemDiscount = 0;
            $itemIds = [];

            foreach ($validated['items'] as $data) {
                $item = SaleItem::find($data['id']);
                if (! $item || $item->sale_id !== $sale->id) {
                    continue;
                }

                $qty = (float) $data['quantity'];
                $price = (float) $data['unit_price'];
                $disc = (float) ($data['discount'] ?? 0);
                $lineTotal = max(0, ($qty * $price) - $disc);

                $item->update([
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $disc,
                    'total' => $lineTotal,
                ]);

                $subtotal += $qty * $price;
                $itemDiscount += $disc;
                $itemIds[] = $item->id;

                // 4. Deduct new stock
                $product = Product::find($item->product_id);
                if ($product) {
                    $oldStock = $product->stock;
                    $product->stock -= $qty;
                    $product->save();

                    StockMutation::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $qty,
                        'stock_before' => $oldStock,
                        'stock_after' => $product->stock,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => 'Update transaksi '.$sale->invoice_number,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // 5. Delete items that were removed (not in submitted list)
            SaleItem::where('sale_id', $sale->id)->whereNotIn('id', $itemIds)->delete();

            // 6. Recalculate totals
            $tax = 0;
            if ($sale->tax_id) {
                $taxRate = Tax::find($sale->tax_id)?->rate ?? 0;
                $tax = round(($subtotal - $itemDiscount) * $taxRate / 100);
            }
            $totalDiscount = $validated['total_discount'] ?? $sale->total_discount;
            $total = max(0, $subtotal + $tax - $totalDiscount - $itemDiscount);
            $paidAmount = $validated['paid_amount'] ?? $sale->paid_amount;
            $change = max(0, $paidAmount - $total);

            // 7. Update sale
            $sale->update([
                'status' => $validated['status'],
                'subtotal' => $subtotal,
                'item_discount' => $itemDiscount,
                'total_discount' => $totalDiscount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $change,
                'due_date' => $validated['due_date'] ?? $sale->due_date,
                'sale_date' => $validated['sale_date'] ?? $sale->sale_date,
            ]);

            // 8. Update receivable if exists
            $receivable = Receivable::where('sale_id', $sale->id)->first();
            if ($receivable) {
                $receivable->update([
                    'amount' => $total,
                    'remaining_amount' => max(0, $total - $receivable->paid_amount),
                    'due_date' => $validated['due_date'] ?? $receivable->due_date,
                ]);
            }

            // 9. Update cash transaction if exists
            $cashTransaction = CashTransaction::where('description', 'Penjualan POS - '.$sale->invoice_number)
                ->where('type', 'in')
                ->first();
            if ($cashTransaction) {
                $oldAmount = $cashTransaction->amount;
                $cashTransaction->update(['amount' => $total]);

                $cashAccount = CashAccount::find($cashTransaction->cash_account_id);
                if ($cashAccount) {
                    $cashAccount->current_balance = $cashAccount->current_balance - $oldAmount + $total;
                    $cashAccount->save();
                }
            }

            // 10. Update journal if exists
            $journal = Journal::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->first();
            if ($journal) {
                $journal->update([
                    'total_debit' => $total,
                    'total_credit' => $total,
                ]);
                JournalEntry::where('journal_id', $journal->id)->update([
                    'debit' => DB::raw("CASE WHEN debit > 0 THEN {$total} ELSE 0 END"),
                    'credit' => DB::raw("CASE WHEN credit > 0 THEN {$total} ELSE 0 END"),
                ]);
            }
        });

        return redirect()->route('pos.detail', $sale)->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Sale $sale)
    {
        if (! auth()->user()->can('delete_sale')) {
            abort(403);
        }

        if ($sale->saleReturns()->exists()) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak dapat dihapus karena sudah ada retur.'], 422);
        }

        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $oldStock = $product->stock;
                    $product->stock += $item->quantity;
                    $product->save();

                    StockMutation::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'stock_before' => $oldStock,
                        'stock_after' => $product->stock,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => 'Pembatalan transaksi '.$sale->invoice_number,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            StockMutation::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->where('type', 'out')
                ->delete();

            $receivable = Receivable::where('sale_id', $sale->id)->first();
            if ($receivable) {
                ReceivablePayment::where('receivable_id', $receivable->id)->delete();
                $receivable->delete();
            }

            $cashTransaction = CashTransaction::where('description', 'Penjualan POS - '.$sale->invoice_number)
                ->where('type', 'in')
                ->first();
            if ($cashTransaction) {
                $cashAccount = CashAccount::find($cashTransaction->cash_account_id);
                if ($cashAccount) {
                    $cashAccount->current_balance -= $cashTransaction->amount;
                    $cashAccount->save();
                }
                $cashTransaction->delete();
            }

            $journal = Journal::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->first();
            if ($journal) {
                JournalEntry::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            $sale->delete();
        });

        return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
    }
}

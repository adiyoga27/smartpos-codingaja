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
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMutation;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function kasir()
    {
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

        return view('pages.transaksi.pos.kasir', compact('customers', 'products', 'taxes', 'defaultTax', 'cashAccounts', 'defaultCashAccount', 'cashAccountsJson', 'paymentMethods', 'invoiceNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:sales',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'sale_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'total_discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        $sale = null;

        DB::transaction(function () use ($validated, &$sale) {
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

            $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
            $isCredit = $paymentMethod->is_credit;
            $status = $isCredit ? 'unpaid' : (($paidAmount >= $total) ? 'paid' : 'partial');

            $cashAccountId = $validated['cash_account_id'] ?? null;
            if (! $isCredit && ! $cashAccountId) {
                $cashAccountId = CashAccount::active()->where('is_default', true)->value('id');
            }

            $sale = Sale::create([
                'invoice_number' => $validated['invoice_number'],
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'sale_date' => $validated['sale_date'],
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
                    'due_date' => now()->addDays(30),
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

    public function riwayat(Request $request)
    {
        if ($request->ajax()) {
            $query = Sale::with(['customer', 'paymentMethod'])->latest();
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
                    'partial' => '<span class="badge bg-warning">Sebagian</span>',
                    'unpaid' => '<span class="badge bg-danger">Belum Bayar</span>',
                    default => '<span class="badge bg-secondary">Batal</span>',
                };

                $actions = '';
                if (auth()->user()->can('view_sale')) {
                    $actions .= '<div class="flex gap-1">'
                        .'<a href="'.route('pos.print-a4', $item).'" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak A4"><i class="bi bi-printer"></i></a>'
                        .'<a href="'.route('pos.print-thermal', $item).'" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Thermal"><i class="bi bi-receipt"></i></a>'
                        .'<button type="button" class="btn btn-sm btn-outline-info btn-detail" data-id="'.$item->id.'" title="Detail"><i class="bi bi-eye"></i></button>'
                        .'</div>';
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
                ];
            });

        return response()->json($sales);
    }
}

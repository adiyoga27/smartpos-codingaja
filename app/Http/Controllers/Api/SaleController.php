<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['customer', 'paymentMethod', 'items.product'])->latest();

        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->to);
        }
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%');
            });
        }

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        $isCredit = $paymentMethod && $paymentMethod->is_credit;

        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:sales',
            'customer_id' => $isCredit ? 'required|exists:customers,id' : 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'sale_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
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

            $penjualanAccount = Account::where('code', '4-1000')->first();
            if ($penjualanAccount && $paymentMethod->account_id) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
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

        return $this->created($sale->load(['items.product', 'customer', 'paymentMethod']), 'Transaksi berhasil disimpan.');
    }

    public function show(Sale $sale): JsonResponse
    {
        return $this->success($sale->load(['items.product', 'customer', 'paymentMethod', 'creator', 'receivables']));
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
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
            foreach ($sale->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->stock += $oldItem->quantity;
                    $product->save();
                }
            }

            StockMutation::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->delete();

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

            SaleItem::where('sale_id', $sale->id)->whereNotIn('id', $itemIds)->delete();

            $tax = 0;
            if ($sale->tax_id) {
                $taxRate = Tax::find($sale->tax_id)?->rate ?? 0;
                $tax = round(($subtotal - $itemDiscount) * $taxRate / 100);
            }
            $totalDiscount = $validated['total_discount'] ?? $sale->total_discount;
            $total = max(0, $subtotal + $tax - $totalDiscount - $itemDiscount);
            $paidAmount = $validated['paid_amount'] ?? $sale->paid_amount;
            $change = max(0, $paidAmount - $total);

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

            $receivable = Receivable::where('sale_id', $sale->id)->first();
            if ($receivable) {
                $receivable->update([
                    'amount' => $total,
                    'remaining_amount' => max(0, $total - $receivable->paid_amount),
                    'due_date' => $validated['due_date'] ?? $receivable->due_date,
                ]);
            }

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

            $journal = Journal::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->first();
            if ($journal) {
                $journal->update(['total_debit' => $total, 'total_credit' => $total]);
                JournalEntry::where('journal_id', $journal->id)->update([
                    'debit' => DB::raw("CASE WHEN debit > 0 THEN {$total} ELSE 0 END"),
                    'credit' => DB::raw("CASE WHEN credit > 0 THEN {$total} ELSE 0 END"),
                ]);
            }
        });

        return $this->success($sale->fresh()->load(['items.product', 'customer', 'paymentMethod']), 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Sale $sale): JsonResponse
    {
        if ($sale->saleReturns()->exists()) {
            return $this->error('Transaksi tidak dapat dihapus karena sudah ada retur.', 422);
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

        return $this->success(null, 'Transaksi berhasil dihapus.');
    }
}

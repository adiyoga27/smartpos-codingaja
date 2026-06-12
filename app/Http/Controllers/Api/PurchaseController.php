<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMutation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::with(['supplier', 'items.product'])->latest();

        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }
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

    public function store(Request $request): JsonResponse
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

        $purchase = null;

        DB::transaction(function () use ($validated, &$purchase) {
            $subtotal = 0;
            $discountTotal = 0;
            foreach ($validated['items'] as $item) {
                $disc = $item['discount'] ?? 0;
                $discountTotal += $disc;
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $total = max(0, $subtotal - $discountTotal);

            $purchase = Purchase::create([
                'document_number' => $validated['document_number'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount' => $discountTotal,
                'tax' => 0,
                'total' => $total,
                'paid_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $disc = $item['discount'] ?? 0;
                $lineTotal = ($item['quantity'] * $item['unit_price']) - $disc;
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $disc,
                    'total' => max(0, $lineTotal),
                ]);
            }
        });

        return $this->created($purchase->load(['supplier', 'items.product']), 'Pembelian berhasil dibuat.');
    }

    public function show(Purchase $purchase): JsonResponse
    {
        return $this->success($purchase->load(['supplier', 'items.product', 'creator', 'payables']));
    }

    public function receive(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*' => 'numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.cash_account_id' => 'required|exists:cash_accounts,id',
            'payments.*.amount' => 'required|numeric|min:500',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            $allReceived = true;
            if ($validated['items'] ?? null) {
                foreach ($purchase->items as $item) {
                    $newQty = $validated['items'][$item->id] ?? 0;
                    $receivedQty = $item->received_quantity + $newQty;
                    $item->update(['received_quantity' => min($receivedQty, $item->quantity)]);

                    if ($newQty > 0) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $oldStock = $product->stock;
                            $product->stock += $newQty;
                            $product->save();

                            StockMutation::create([
                                'product_id' => $product->id,
                                'type' => 'in',
                                'quantity' => $newQty,
                                'stock_before' => $oldStock,
                                'stock_after' => $product->stock,
                                'reference_type' => Purchase::class,
                                'reference_id' => $purchase->id,
                                'notes' => 'Penerimaan barang '.$purchase->document_number,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }

                    $leftover = $item->fresh()->quantity - $item->fresh()->received_quantity;
                    if ($leftover > 0.001) {
                        $allReceived = false;
                    }
                }
            }

            $purchase->update(['status' => $allReceived ? 'completed' : 'partial']);

            if ($validated['payments'] ?? null) {
                $totalPaid = 0;
                foreach ($validated['payments'] as $payment) {
                    $cashAccount = CashAccount::find($payment['cash_account_id']);
                    $cashAccount->current_balance -= $payment['amount'];
                    $cashAccount->save();
                    $totalPaid += $payment['amount'];

                    CashTransaction::create([
                        'cash_account_id' => $payment['cash_account_id'],
                        'type' => 'out',
                        'amount' => $payment['amount'],
                        'description' => 'Pembayaran pembelian '.$purchase->document_number,
                        'transaction_date' => now(),
                        'created_by' => auth()->id(),
                    ]);
                }

                $purchase->update(['paid_amount' => $purchase->paid_amount + $totalPaid]);

                $payable = Payable::firstOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'supplier_id' => $purchase->supplier_id,
                        'document_number' => $purchase->document_number,
                        'due_date' => $purchase->due_date ?? now()->addDays(30),
                        'amount' => $purchase->total,
                        'paid_amount' => 0,
                        'remaining_amount' => $purchase->total,
                        'status' => 'open',
                    ]
                );
                $payable->paid_amount += $totalPaid;
                $payable->remaining_amount = max(0, $payable->amount - $payable->paid_amount);
                $payable->status = $payable->remaining_amount <= 0 ? 'paid' : 'partial';
                $payable->save();

                foreach ($validated['payments'] as $payment) {
                    PayablePayment::create([
                        'payable_id' => $payable->id,
                        'supplier_id' => $purchase->supplier_id,
                        'cash_account_id' => $payment['cash_account_id'],
                        'payment_date' => now(),
                        'amount' => $payment['amount'],
                        'created_by' => auth()->id(),
                    ]);
                }

                $inventoryAccount = Account::where('code', '1-1300')->first();
                $hutangAccount = Account::where('code', '2-1000')->first();
                if ($inventoryAccount && $hutangAccount) {
                    $journal = Journal::create([
                        'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                        'journal_date' => now(),
                        'description' => 'Pembayaran pembelian '.$purchase->document_number,
                        'source' => 'purchase',
                        'reference_id' => $purchase->id,
                        'reference_type' => Purchase::class,
                        'total_debit' => $totalPaid,
                        'total_credit' => $totalPaid,
                        'created_by' => auth()->id(),
                    ]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutangAccount->id, 'debit' => $totalPaid, 'credit' => 0]);
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $payment['cash_account_id'], 'debit' => 0, 'credit' => $totalPaid]);
                }
            }
        });

        return $this->success($purchase->fresh()->load(['supplier', 'items.product', 'payables']), 'Penerimaan dan/atau pembayaran berhasil.');
    }

    public function pay(Request $request, Purchase $purchase): JsonResponse
    {
        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.cash_account_id' => 'required|exists:cash_accounts,id',
            'payments.*.amount' => 'required|numeric|min:500',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            $totalPaid = 0;
            foreach ($validated['payments'] as $payment) {
                $cashAccount = CashAccount::find($payment['cash_account_id']);
                $cashAccount->current_balance -= $payment['amount'];
                $cashAccount->save();
                $totalPaid += $payment['amount'];

                CashTransaction::create([
                    'cash_account_id' => $payment['cash_account_id'],
                    'type' => 'out',
                    'amount' => $payment['amount'],
                    'description' => 'Pembayaran pembelian '.$purchase->document_number,
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                ]);
            }

            $purchase->update(['paid_amount' => $purchase->paid_amount + $totalPaid]);

            $payable = Payable::firstOrCreate(
                ['purchase_id' => $purchase->id],
                [
                    'supplier_id' => $purchase->supplier_id,
                    'document_number' => $purchase->document_number,
                    'due_date' => $purchase->due_date ?? now()->addDays(30),
                    'amount' => $purchase->total,
                    'paid_amount' => 0,
                    'remaining_amount' => $purchase->total,
                    'status' => 'open',
                ]
            );
            $payable->paid_amount += $totalPaid;
            $payable->remaining_amount = max(0, $payable->amount - $payable->paid_amount);
            $payable->status = $payable->remaining_amount <= 0 ? 'paid' : 'partial';
            $payable->save();

            foreach ($validated['payments'] as $payment) {
                PayablePayment::create([
                    'payable_id' => $payable->id,
                    'supplier_id' => $purchase->supplier_id,
                    'cash_account_id' => $payment['cash_account_id'],
                    'payment_date' => now(),
                    'amount' => $payment['amount'],
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return $this->success($purchase->fresh()->load(['supplier', 'items.product', 'payables']), 'Pembayaran berhasil.');
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        DB::transaction(function () use ($purchase) {
            PayablePayment::whereHas('payable', fn ($q) => $q->where('purchase_id', $purchase->id))->delete();
            Payable::where('purchase_id', $purchase->id)->delete();
            StockMutation::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->delete();
            Journal::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->delete();
            $purchase->items()->delete();
            $purchase->delete();
        });

        return $this->success(null, 'Pembelian berhasil dihapus.');
    }
}

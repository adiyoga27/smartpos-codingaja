<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMutation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseReturn::with(['purchase', 'supplier', 'items.product'])->latest();

        if ($request->filled('from')) {
            $query->whereDate('return_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('return_date', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $query->where('document_number', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => 'required|string|unique:purchase_returns',
            'purchase_id' => 'required|exists:purchases,id',
            'return_date' => 'required|date',
            'reason' => 'required|string',
            'refund_method' => 'required|in:cash,credit',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $return = null;

        DB::transaction(function () use ($validated, &$return) {
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['unit_price'];
            }

            $purchase = Purchase::find($validated['purchase_id']);

            $return = PurchaseReturn::create([
                'document_number' => $validated['document_number'],
                'purchase_id' => $validated['purchase_id'],
                'supplier_id' => $purchase->supplier_id,
                'return_date' => $validated['return_date'],
                'total' => $total,
                'reason' => $validated['reason'],
                'refund_method' => $validated['refund_method'],
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $oldStock = $product->stock;
                    $product->stock -= $item['quantity'];
                    $product->save();

                    StockMutation::create([
                        'product_id' => $product->id,
                        'type' => 'return_in',
                        'quantity' => $item['quantity'],
                        'stock_before' => $oldStock,
                        'stock_after' => $product->stock,
                        'reference_type' => PurchaseReturn::class,
                        'reference_id' => $return->id,
                        'notes' => 'Retur pembelian '.$return->document_number,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $payable = Payable::where('purchase_id', $validated['purchase_id'])->first();
            if ($payable) {
                $payable->amount = max(0, $payable->amount - $total);
                $payable->remaining_amount = max(0, $payable->remaining_amount - $total);
                $payable->status = $payable->remaining_amount <= 0 ? 'paid' : ($payable->paid_amount > 0 ? 'partial' : 'open');
                $payable->save();
            }

            $hutangAccount = Account::where('code', '2-1000')->first();
            $inventoryAccount = Account::where('code', '1-1300')->first();

            if ($hutangAccount && $inventoryAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Retur pembelian '.$return->document_number,
                    'source' => 'return',
                    'reference_id' => $return->id,
                    'reference_type' => PurchaseReturn::class,
                    'total_debit' => $total,
                    'total_credit' => $total,
                    'created_by' => auth()->id(),
                ]);

                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $hutangAccount->id, 'debit' => $total, 'credit' => 0]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $inventoryAccount->id, 'debit' => 0, 'credit' => $total]);
            }
        });

        return $this->created($return->load(['purchase', 'items.product']), 'Retur pembelian berhasil dibuat.');
    }

    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        return $this->success($purchaseReturn->load(['purchase', 'supplier', 'items.product', 'creator']));
    }
}

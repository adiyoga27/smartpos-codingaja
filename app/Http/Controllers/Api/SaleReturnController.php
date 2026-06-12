<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMutation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = SaleReturn::with(['sale', 'customer', 'items.product'])->latest();

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
            'document_number' => 'required|string|unique:sale_returns',
            'sale_id' => 'required|exists:sales,id',
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

            $sale = Sale::find($validated['sale_id']);
            $customerId = $sale->customer_id;

            $return = SaleReturn::create([
                'document_number' => $validated['document_number'],
                'sale_id' => $validated['sale_id'],
                'customer_id' => $customerId,
                'return_date' => $validated['return_date'],
                'total' => $total,
                'reason' => $validated['reason'],
                'refund_method' => $validated['refund_method'],
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $oldStock = $product->stock;
                    $product->stock += $item['quantity'];
                    $product->save();

                    StockMutation::create([
                        'product_id' => $product->id,
                        'type' => 'return_out',
                        'quantity' => $item['quantity'],
                        'stock_before' => $oldStock,
                        'stock_after' => $product->stock,
                        'reference_type' => SaleReturn::class,
                        'reference_id' => $return->id,
                        'notes' => 'Retur penjualan '.$return->document_number,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $receivable = Receivable::where('sale_id', $validated['sale_id'])->first();
            if ($receivable) {
                $receivable->amount = max(0, $receivable->amount - $total);
                $receivable->remaining_amount = max(0, $receivable->remaining_amount - $total);
                $receivable->status = $receivable->remaining_amount <= 0 ? 'paid' : ($receivable->paid_amount > 0 ? 'partial' : 'open');
                $receivable->save();
            }

            $returAccount = Account::where('code', '4-1100')->first();
            $kasAccount = Account::where('code', '1-1000')->first();
            $piutangAccount = Account::where('code', '1-1200')->first();

            if ($returAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((string) (Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Retur penjualan '.$return->document_number,
                    'source' => 'return',
                    'reference_id' => $return->id,
                    'reference_type' => SaleReturn::class,
                    'total_debit' => $total,
                    'total_credit' => $total,
                    'created_by' => auth()->id(),
                ]);

                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $returAccount->id, 'debit' => $total, 'credit' => 0]);

                if ($validated['refund_method'] === 'cash' && $kasAccount) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => 0, 'credit' => $total]);
                } elseif ($piutangAccount) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $piutangAccount->id, 'debit' => 0, 'credit' => $total]);
                }
            }
        });

        return $this->created($return->load(['sale', 'items.product']), 'Retur penjualan berhasil dibuat.');
    }

    public function show(SaleReturn $saleReturn): JsonResponse
    {
        return $this->success($saleReturn->load(['sale', 'customer', 'items.product', 'creator']));
    }
}

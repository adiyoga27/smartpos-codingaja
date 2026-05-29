<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseReturn::with('purchase', 'supplier')->latest();
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
                return [
                    $item->document_number,
                    $item->purchase?->document_number ?? '-',
                    $item->supplier?->name ?? '-',
                    $item->return_date->format('d/m/Y'),
                    formatRupiah($item->total),
                    '<a href="'.route('transaksi.purchase_returns.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.transaksi.purchase_returns.index');
    }

    public function create()
    {
        $purchases = Purchase::where('status', 'completed')->with('items.product')->get();
        $prefix = CompanySetting::first()->doc_prefix_return_in ?? 'RPB';
        $documentNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((PurchaseReturn::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.transaksi.purchase_returns.create', compact('purchases', 'documentNumber'));
    }

    public function store(Request $request)
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

        DB::transaction(function () use ($validated) {
            $purchase = Purchase::find($validated['purchase_id']);
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['unit_price'];
            }
            $return = PurchaseReturn::create([
                'document_number' => $validated['document_number'],
                'purchase_id' => $purchase->id,
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
                    'created_by' => auth()->id(),
                ]);
            }
            $payable = Payable::where('purchase_id', $purchase->id)->first();
            if ($payable) {
                $payable->amount -= $total;
                $payable->remaining_amount = max(0, $payable->amount - $payable->paid_amount);
                $payable->save();
            }
            $inventoryAccount = Account::where('code', '1-1300')->first();
            $hutangAccount = Account::where('code', '2-1000')->first();
            if ($inventoryAccount && $hutangAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Jurnal return pembelian '.$return->document_number,
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

        return redirect()->route('transaksi.purchase_returns.index')->with('success', 'Return pembelian berhasil dicatat.');
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load('items.product', 'purchase', 'supplier');

        return view('pages.transaksi.purchase_returns.show', compact('purchaseReturn'));
    }
}

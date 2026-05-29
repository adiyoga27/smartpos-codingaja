<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SaleReturn::with('sale', 'customer')->latest();
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
                    $item->sale?->invoice_number ?? '-',
                    $item->customer?->name ?? '-',
                    $item->return_date->format('d/m/Y'),
                    formatRupiah($item->total),
                    '<a href="'.route('transaksi.sale_returns.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.transaksi.sale_returns.index');
    }

    public function create()
    {
        $sales = Sale::with('items.product')->where('status', '!=', 'cancelled')->get();
        $prefix = CompanySetting::first()->doc_prefix_return_out ?? 'RJ';
        $documentNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((SaleReturn::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.transaksi.sale_returns.create', compact('sales', 'documentNumber'));
    }

    public function store(Request $request)
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

        DB::transaction(function () use ($validated) {
            $sale = Sale::find($validated['sale_id']);
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['quantity'] * $item['unit_price'];
                $product = Product::find($item['product_id']);
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
                    'reference_id' => 0,
                    'created_by' => auth()->id(),
                ]);
            }
            $return = SaleReturn::create([
                'document_number' => $validated['document_number'],
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
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
            }
            $receivable = Receivable::where('sale_id', $sale->id)->first();
            if ($receivable) {
                $receivable->amount -= $total;
                $receivable->remaining_amount = max(0, $receivable->amount - $receivable->paid_amount);
                $receivable->save();
            }
            $returAccount = Account::where('code', '4-1100')->first();
            $cashAccount = Account::where('code', '1-1000')->first();
            $piutangAccount = Account::where('code', '1-1200')->first();
            if ($returAccount) {
                $journal = Journal::create([
                    'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                    'journal_date' => now(),
                    'description' => 'Jurnal return penjualan '.$return->document_number,
                    'source' => 'return',
                    'reference_id' => $return->id,
                    'reference_type' => SaleReturn::class,
                    'total_debit' => $total,
                    'total_credit' => $total,
                    'created_by' => auth()->id(),
                ]);
                if ($validated['refund_method'] === 'cash' && $cashAccount) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $total]);
                } elseif ($piutangAccount) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $piutangAccount->id, 'debit' => 0, 'credit' => $total]);
                }
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $returAccount->id, 'debit' => $total, 'credit' => 0]);
            }
        });

        return redirect()->route('transaksi.sale_returns.index')->with('success', 'Return penjualan berhasil dicatat.');
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load('items.product', 'sale', 'customer');

        return view('pages.transaksi.sale_returns.show', compact('saleReturn'));
    }
}

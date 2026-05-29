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
                $statusBadge = match ($item->status) {
                    'draft' => '<span class="badge bg-secondary">Draft</span>',
                    'sent' => '<span class="badge bg-info">Dikirim</span>',
                    'partial' => '<span class="badge bg-warning">Sebagian</span>',
                    'completed' => '<span class="badge bg-success">Selesai</span>',
                    default => '<span class="badge bg-danger">Batal</span>',
                };
                $actions = '<a href="'.route('transaksi.purchases.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>';
                if (! in_array($item->status, ['completed', 'cancelled'])) {
                    $actions .= '<form action="'.route('transaksi.purchases.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus PO ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return [
                    $item->document_number,
                    $item->supplier?->name ?? '-',
                    $item->purchase_date->format('d/m/Y'),
                    formatRupiah($item->total),
                    $statusBadge,
                    $actions,
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.transaksi.purchases.index');
    }

    public function create()
    {
        $suppliers = Supplier::active()->pluck('name', 'id');
        $products = Product::active()->get();
        $prefix = CompanySetting::first()->doc_prefix_po ?? 'PO';
        $documentNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((Purchase::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.transaksi.purchases.create', compact('suppliers', 'products', 'documentNumber'));
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

        return view('pages.transaksi.purchases.show', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase)
    {
        if (! in_array($purchase->status, ['draft', 'sent', 'partial'])) {
            return back()->with('error', 'PO tidak dapat diterima.');
        }
        DB::transaction(function () use ($request, $purchase) {
            $items = $request->input('items', []);
            foreach ($items as $itemId => $qty) {
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

            if ($purchase->status === 'completed') {
                Payable::create([
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'document_number' => $purchase->document_number,
                    'due_date' => $purchase->due_date,
                    'amount' => $purchase->total,
                    'paid_amount' => 0,
                    'remaining_amount' => $purchase->total,
                    'status' => 'open',
                ]);
                $inventoryAccount = Account::where('code', '1-1300')->first();
                $hutangAccount = Account::where('code', '2-1000')->first();
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
        });

        return back()->with('success', 'Penerimaan berhasil dicatat.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return redirect()->route('transaksi.purchases.index')->with('success', 'PO berhasil dihapus.');
    }
}

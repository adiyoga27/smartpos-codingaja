<?php

namespace App\Http\Controllers\Stok;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMutationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::select('products.*')
                ->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_mutations WHERE product_id = products.id AND type IN ("in","return_out")),0) as total_in')
                ->selectRaw('COALESCE((SELECT SUM(quantity) FROM stock_mutations WHERE product_id = products.id AND type IN ("out","return_in")),0) as total_out');

            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = Product::count();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
                });
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                $stockBadge = $item->stock <= $item->min_stock ? '<span class="badge bg-danger">'.formatQty($item->stock).'</span>' : formatQty($item->stock);

                return [
                    $item->code,
                    $item->name,
                    $stockBadge,
                    formatQty($item->min_stock),
                    formatQty($item->total_in ?? 0),
                    formatQty($item->total_out ?? 0),
                    '<a href="'.route('stok.mutations.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> Detail</a>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.stok.mutations.index');
    }

    public function show(Request $request, Product $product)
    {
        if ($request->ajax()) {
            $query = StockMutation::with('creator')->where('product_id', $product->id)->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('notes', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                $typeBadge = match ($item->type) {
                    'in' => '<span class="badge bg-success">Masuk</span>',
                    'out' => '<span class="badge bg-danger">Keluar</span>',
                    'adjustment' => '<span class="badge bg-warning">Adjust</span>',
                    'opname' => '<span class="badge bg-info">Opname</span>',
                    default => '<span class="badge bg-secondary">'.$item->type.'</span>',
                };

                return [
                    $item->created_at->format('d/m/Y H:i'),
                    $typeBadge,
                    formatQty($item->quantity),
                    formatQty($item->stock_before),
                    formatQty($item->stock_after),
                    $item->notes ?? '-',
                    $item->creator?->name ?? '-',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.stok.mutations.show', compact('product'));
    }

    public function opnameForm()
    {
        $products = Product::active()->get();

        return view('pages.stok.opname', compact('products'));
    }

    public function opnameStore(Request $request)
    {
        $items = $request->input('items', []);
        DB::transaction(function () use ($items) {
            foreach ($items as $productId => $physicalQty) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }
                $difference = $physicalQty - $product->stock;
                if ($difference == 0) {
                    continue;
                }
                $oldStock = $product->stock;
                $product->stock = $physicalQty;
                $product->save();
                StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'opname',
                    'quantity' => abs($difference),
                    'stock_before' => $oldStock,
                    'stock_after' => $product->stock,
                    'notes' => 'Stock opname adjustment',
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('stok.mutations.index')->with('success', 'Stock opname berhasil disimpan.');
    }
}

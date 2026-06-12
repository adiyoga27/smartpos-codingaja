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
            if ($request->filled('low_stock') && $request->input('low_stock') === '1') {
                $query->whereColumn('stock', '<=', 'min_stock');
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
                });
            }
            $filtered = $query->count();

            $orderableColumns = ['code', 'name', 'stock', 'min_stock', 'total_in', 'total_out'];
            if ($request->filled('order')) {
                foreach ($request->input('order') as $order) {
                    $colIndex = (int) ($order['column'] ?? 0);
                    $dir = $order['dir'] ?? 'asc';
                    if (isset($orderableColumns[$colIndex])) {
                        $query->orderBy($orderableColumns[$colIndex], $dir);
                    }
                }
            } else {
                $query->orderBy('stock', 'desc');
            }

            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
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
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
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

    public function opnameHistory(Request $request)
    {
        if ($request->ajax()) {
            $query = StockMutation::with(['creator'])
                ->selectRaw('opname_number, MIN(created_at) as created_at, MIN(created_by) as created_by, COUNT(*) as total_items')
                ->where('type', 'opname')
                ->whereNotNull('opname_number')
                ->groupBy('opname_number')
                ->latest('created_at');

            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = StockMutation::where('type', 'opname')->whereNotNull('opname_number')->distinct('opname_number')->count('opname_number');
            if ($search) {
                $query->where('opname_number', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                return [
                    $item->opname_number,
                    $item->created_at->format('d/m/Y H:i'),
                    $item->total_items.' produk',
                    $item->creator?->name ?? '-',
                    '<a href="'.route('stok.opname.detail', $item->opname_number).'" class="btn btn-sm btn-outline-info">Detail</a>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.stok.opname_history');
    }

    public function opnameDetail(string $opnameNumber)
    {
        $mutations = StockMutation::with(['product', 'creator'])
            ->where('type', 'opname')
            ->where('opname_number', $opnameNumber)
            ->get();

        return view('pages.stok.opname_detail', compact('mutations', 'opnameNumber'));
    }

    public function opnameIndex()
    {
        return view('pages.stok.opname');
    }

    public function opnameSelect()
    {
        $products = Product::active()->orderBy('name')->get();

        return view('pages.stok.opname_select', compact('products'));
    }

    public function opnameForm(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        if (empty($productIds)) {
            return redirect()->route('stok.opname.select')->with('error', 'Pilih minimal 1 produk.');
        }
        $products = Product::active()->whereIn('id', $productIds)->orderBy('name')->get();

        return view('pages.stok.opname_form', compact('products'));
    }

    public function opnameStore(Request $request)
    {
        $items = $request->input('items', []);

        $count = StockMutation::where('type', 'opname')
            ->whereNotNull('opname_number')
            ->distinct('opname_number')
            ->count('opname_number');
        $opnameNumber = 'OPN-'.now()->format('Ymd').'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($items, $opnameNumber) {
            foreach ($items as $productId => $data) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }
                $physicalQty = (float) ($data['qty'] ?? $data);
                $difference = $physicalQty - $product->stock;
                if ($difference == 0) {
                    continue;
                }
                $oldStock = $product->stock;
                $product->stock = $physicalQty;
                $product->save();
                $note = $data['notes'] ?? 'Stock opname adjustment';

                StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'opname',
                    'quantity' => abs($difference),
                    'stock_before' => $oldStock,
                    'stock_after' => $product->stock,
                    'notes' => $note,
                    'opname_number' => $opnameNumber,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('stok.opname')->with('success', 'Stock opname '.$opnameNumber.' berhasil disimpan.');
    }
}

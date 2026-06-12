<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMutationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category'])
            ->select('products.*')
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_mutations WHERE product_id = products.id AND type = ?) as total_in', ['in'])
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_mutations WHERE product_id = products.id AND type IN (?, ?)) as total_out', ['out', 'return_in']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $query->orderBy('name', 'asc');

        return $this->paginate($query);
    }

    public function history(Request $request, Product $product): JsonResponse
    {
        $query = StockMutation::with(['creator'])->where('product_id', $product->id)->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $this->paginate($query);
    }

    public function opnameIndex(Request $request): JsonResponse
    {
        $query = StockMutation::with(['product', 'creator'])
            ->where('type', 'opname')
            ->select('opname_number', 'created_at', 'created_by', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as items_count'))
            ->groupBy('opname_number', 'created_at', 'created_by')
            ->latest('created_at');

        return $this->paginate($query);
    }

    public function opnameDetail(string $opnameNumber): JsonResponse
    {
        $mutations = StockMutation::with(['product', 'creator'])
            ->where('opname_number', $opnameNumber)
            ->get();

        return $this->success([
            'opname_number' => $opnameNumber,
            'items' => $mutations,
        ]);
    }

    public function opnameStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.physical_qty' => 'required|numeric|min:0',
        ]);

        $opnameNumber = 'OPN-'.now()->format('Ymd').'-'.str_pad((string) ((StockMutation::where('type', 'opname')->distinct('opname_number')->count()) + 1), 4, '0', STR_PAD_LEFT);

        $mutations = [];

        DB::transaction(function () use ($validated, $opnameNumber, &$mutations) {
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (! $product) {
                    continue;
                }

                $systemQty = $product->stock;
                $physicalQty = $item['physical_qty'];
                $difference = $physicalQty - $systemQty;

                if (abs($difference) < 0.001) {
                    continue;
                }

                $oldStock = $product->stock;
                $product->stock = $physicalQty;
                $product->save();

                $mutation = StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'opname',
                    'quantity' => abs($difference),
                    'stock_before' => $oldStock,
                    'stock_after' => $physicalQty,
                    'notes' => 'Opname: sistem '.$systemQty.', fisik '.$physicalQty,
                    'opname_number' => $opnameNumber,
                    'created_by' => auth()->id(),
                ]);

                $mutations[] = $mutation;
            }
        });

        return $this->created([
            'opname_number' => $opnameNumber,
            'items_count' => count($mutations),
            'mutations' => $mutations,
        ], 'Stok opname berhasil disimpan.');
    }
}

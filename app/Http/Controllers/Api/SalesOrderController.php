<?php

namespace App\Http\Controllers\Api;

use App\Models\CompanySetting;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMutation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = SalesOrder::with(['customer', 'items.product'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('search')) {
            $query->where('document_number', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => 'required|string|unique:sales_orders',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        $so = null;

        DB::transaction(function () use ($validated, &$so) {
            $subtotal = 0;
            $discountTotal = 0;
            foreach ($validated['items'] as $item) {
                $disc = $item['discount'] ?? 0;
                $discountTotal += $disc;
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $total = max(0, $subtotal - $discountTotal);

            $so = SalesOrder::create([
                'document_number' => $validated['document_number'],
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
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
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'delivered_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $disc,
                    'total' => max(0, $lineTotal),
                ]);
            }
        });

        return $this->created($so->load(['customer', 'items.product']), 'Sales Order berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder): JsonResponse
    {
        return $this->success($salesOrder->load(['customer', 'items.product', 'deliveryOrders.items', 'creator']));
    }

    public function deliver(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        if (in_array($salesOrder->status, ['completed', 'cancelled'])) {
            return $this->error('SO sudah selesai atau dibatalkan.', 422);
        }

        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        $do = null;

        DB::transaction(function () use ($validated, $salesOrder, &$do) {
            $prefix = CompanySetting::first()->doc_prefix_do ?? 'DO';
            $docNum = $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) ((DeliveryOrder::withTrashed()->count() ?? 0) + 1), 4, '0', STR_PAD_LEFT);

            $do = DeliveryOrder::create([
                'document_number' => $docNum,
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'delivery_date' => $validated['delivery_date'],
                'total' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $totalDo = 0;
            $allDelivered = true;

            foreach ($validated['items'] as $itemData) {
                $soItem = SalesOrderItem::where('sales_order_id', $salesOrder->id)
                    ->where('product_id', $itemData['product_id'])
                    ->first();

                if (! $soItem) {
                    continue;
                }

                $maxDeliverable = max(0, $soItem->quantity - $soItem->delivered_quantity);
                $qty = min((float) ($itemData['quantity'] ?? 0), $maxDeliverable);

                if ($qty <= 0) {
                    continue;
                }

                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $qty,
                ]);

                $soItem->delivered_quantity += $qty;
                $soItem->save();

                $product = Product::find($itemData['product_id']);
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
                        'reference_type' => DeliveryOrder::class,
                        'reference_id' => $do->id,
                        'notes' => 'Delivery Order '.$docNum,
                        'created_by' => auth()->id(),
                    ]);
                }

                $totalDo += $qty * $soItem->unit_price;

                if ($soItem->fresh()->delivered_quantity < $soItem->quantity) {
                    $allDelivered = false;
                }
            }

            $do->update(['total' => $totalDo]);
            $salesOrder->update(['status' => $allDelivered ? 'completed' : 'partial']);
        });

        return $this->created($do->load(['items.product', 'customer', 'salesOrder']), 'Delivery Order berhasil dibuat.');
    }

    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        if ($salesOrder->deliveryOrders()->exists()) {
            return $this->error('SO tidak dapat dihapus karena sudah ada delivery order.', 422);
        }

        $salesOrder->items()->delete();
        $salesOrder->delete();

        return $this->success(null, 'Sales Order berhasil dihapus.');
    }
}

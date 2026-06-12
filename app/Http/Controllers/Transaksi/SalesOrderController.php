<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesOrder::with('customer')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                $statusBadge = match ($item->status) {
                    'draft' => '<span class="badge bg-secondary">Draft</span>',
                    'sent' => '<span class="badge bg-info">Dikirim</span>',
                    'partial' => '<span class="badge bg-warning">Sebagian</span>',
                    'completed' => '<span class="badge bg-success">Selesai</span>',
                    default => '<span class="badge bg-danger">Batal</span>',
                };
                $actions = '<a href="'.route('transaksi.sales_orders.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>'
                    .'<a href="'.route('transaksi.sales_orders.print', $item).'" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-printer"></i></a>';
                if (! in_array($item->status, ['completed', 'cancelled'])) {
                    $actions .= '<form action="'.route('transaksi.sales_orders.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus SO ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return [
                    $item->document_number,
                    $item->customer?->name ?? '-',
                    $item->order_date->format('d/m/Y'),
                    formatRupiah($item->total),
                    $statusBadge,
                    $actions,
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.transaksi.sales_orders.index');
    }

    public function create()
    {
        $customers = Customer::active()->get();
        $products = Product::active()->get();
        $prefix = CompanySetting::first()->doc_prefix_so ?? 'SO';
        $documentNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((SalesOrder::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.transaksi.sales_orders.create', compact('customers', 'products', 'documentNumber'));
    }

    public function store(Request $request)
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

        DB::transaction(function () use ($validated) {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += max(0, $total);
            }
            $tax = 0;
            $total = $subtotal + $tax;

            $salesOrder = SalesOrder::create([
                'document_number' => $validated['document_number'],
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
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
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'delivered_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => max(0, $lineTotal),
                ]);
            }
        });

        return redirect()->route('transaksi.sales_orders.index')->with('success', 'Sales Order berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load('items.product', 'customer', 'deliveryOrders');

        return view('pages.transaksi.sales_orders.show', compact('salesOrder'));
    }

    public function deliver(Request $request, SalesOrder $salesOrder)
    {
        if (! in_array($salesOrder->status, ['draft', 'sent', 'partial'])) {
            return back()->with('error', 'SO tidak dapat dikirim.');
        }

        $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, $salesOrder) {
            $itemInputs = $request->input('items', []);
            $prefix = CompanySetting::first()->doc_prefix_do ?? 'DO';
            $docNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((DeliveryOrder::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);

            $doTotal = 0;
            $deliveryOrder = DeliveryOrder::create([
                'document_number' => $docNumber,
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'delivery_date' => $request->input('delivery_date'),
                'total' => 0,
                'notes' => $request->input('notes'),
                'created_by' => auth()->id(),
            ]);

            foreach ($itemInputs as $itemId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $soItem = SalesOrderItem::find($itemId);
                if (! $soItem) {
                    continue;
                }

                $maxDeliver = max(0, $soItem->quantity - $soItem->delivered_quantity);
                if ($maxDeliver <= 0) {
                    continue;
                }

                $qty = min($qty, $maxDeliver);

                $unitPrice = $soItem->unit_price;
                $itemDiscount = $soItem->discount > 0 ? ($soItem->discount / $soItem->quantity) * $qty : 0;
                $lineTotal = max(0, ($qty * $unitPrice) - $itemDiscount);
                $doTotal += $lineTotal;

                $soItem->delivered_quantity += $qty;
                $soItem->save();

                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $soItem->product_id,
                    'quantity' => $qty,
                ]);

                $product = $soItem->product;
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
                    'reference_id' => $deliveryOrder->id,
                    'notes' => 'Pengiriman DO '.$deliveryOrder->document_number.' (SO '.$salesOrder->document_number.')',
                    'created_by' => auth()->id(),
                ]);
            }

            $deliveryOrder->total = $doTotal;
            $deliveryOrder->save();

            $totalDelivered = $salesOrder->items->sum('delivered_quantity');
            $totalQty = $salesOrder->items->sum('quantity');

            if ($totalDelivered >= $totalQty) {
                $salesOrder->status = 'completed';
            } elseif ($totalDelivered > 0) {
                $salesOrder->status = 'partial';
            }
            $salesOrder->save();
        });

        return back()->with('success', 'Delivery Order berhasil dibuat.');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if (in_array($salesOrder->status, ['completed'])) {
            return back()->with('error', 'SO yang sudah selesai tidak dapat dihapus.');
        }

        $salesOrder->delete();

        return redirect()->route('transaksi.sales_orders.index')->with('success', 'SO berhasil dihapus.');
    }

    public function print(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items.product', 'customer', 'creator']);
        $company = CompanySetting::first();

        return view('pages.transaksi.sales_orders.print', compact('salesOrder', 'company'));
    }
}

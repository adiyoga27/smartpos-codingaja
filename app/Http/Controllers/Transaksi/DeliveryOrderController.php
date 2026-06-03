<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DeliveryOrder::with(['salesOrder', 'customer'])->latest();
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
                    $item->salesOrder?->document_number ?? '-',
                    $item->customer?->name ?? '-',
                    $item->delivery_date->format('d/m/Y'),
                    formatRupiah($item->total),
                    '<a href="'.route('transaksi.delivery_orders.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>'
                        .'<a href="'.route('transaksi.delivery_orders.print', $item).'" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-printer"></i></a>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.transaksi.delivery_orders.index');
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['items.salesOrderItem.product', 'items.product', 'salesOrder', 'customer']);

        return view('pages.transaksi.delivery_orders.show', compact('deliveryOrder'));
    }

    public function print(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['items.salesOrderItem.product', 'items.product', 'salesOrder', 'customer']);
        $company = CompanySetting::first();

        return view('pages.transaksi.delivery_orders.print', compact('deliveryOrder', 'company'));
    }
}

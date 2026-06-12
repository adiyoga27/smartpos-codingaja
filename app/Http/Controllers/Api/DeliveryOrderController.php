<?php

namespace App\Http\Controllers\Api;

use App\Models\DeliveryOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryOrderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryOrder::with(['customer', 'salesOrder', 'items.product'])->latest();

        if ($request->filled('from')) {
            $query->whereDate('delivery_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('delivery_date', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $query->where('document_number', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function show(DeliveryOrder $deliveryOrder): JsonResponse
    {
        return $this->success($deliveryOrder->load(['customer', 'salesOrder', 'items.product', 'creator']));
    }
}

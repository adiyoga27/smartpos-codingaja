<?php

namespace App\Http\Controllers\Api;

use App\Models\CashTransaction;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function salesReport(Request $request): JsonResponse
    {
        $query = Sale::with(['customer', 'paymentMethod']);

        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->to);
        }
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        $sales = $query->orderBy('sale_date')->get();

        $totalCash = $sales->filter(fn ($s) => ! optional($s->paymentMethod)->is_credit)->sum('total');
        $totalCredit = $sales->filter(fn ($s) => optional($s->paymentMethod)->is_credit)->sum('total');
        $total = $sales->sum('total');

        return $this->success([
            'from' => $request->from,
            'to' => $request->to,
            'total_sales' => $sales->count(),
            'total_cash' => round($totalCash, 2),
            'total_credit' => round($totalCredit, 2),
            'total_amount' => round($total, 2),
            'sales' => $sales->map(fn ($s) => [
                'invoice' => $s->invoice_number,
                'date' => $s->sale_date->format('d/m/Y'),
                'customer' => $s->customer?->name ?? $s->customer_name ?? 'Umum',
                'method' => $s->paymentMethod?->name ?? '-',
                'total' => (float) $s->total,
                'status' => $s->status,
            ]),
        ]);
    }

    public function purchaseReport(Request $request): JsonResponse
    {
        $query = Purchase::with('supplier');

        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->orderBy('purchase_date')->get();

        return $this->success([
            'from' => $request->from,
            'to' => $request->to,
            'total_purchases' => $purchases->count(),
            'total_amount' => round($purchases->sum('total'), 2),
            'purchases' => $purchases->map(fn ($p) => [
                'document' => $p->document_number,
                'date' => $p->purchase_date->format('d/m/Y'),
                'supplier' => $p->supplier?->name ?? '-',
                'total' => (float) $p->total,
                'status' => $p->status,
            ]),
        ]);
    }

    public function receivableReport(Request $request): JsonResponse
    {
        $query = Receivable::with(['customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $receivables = $query->orderBy('due_date')->get();

        return $this->success([
            'total_receivables' => $receivables->count(),
            'total_amount' => round($receivables->sum('amount'), 2),
            'total_paid' => round($receivables->sum('paid_amount'), 2),
            'total_remaining' => round($receivables->sum('remaining_amount'), 2),
            'receivables' => $receivables->map(fn ($r) => [
                'document' => $r->document_number,
                'customer' => $r->customer?->name ?? '-',
                'due_date' => $r->due_date?->format('d/m/Y'),
                'amount' => (float) $r->amount,
                'paid' => (float) $r->paid_amount,
                'remaining' => (float) $r->remaining_amount,
                'status' => $r->status,
            ]),
        ]);
    }

    public function payableReport(Request $request): JsonResponse
    {
        $query = Payable::with(['supplier']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payables = $query->orderBy('due_date')->get();

        return $this->success([
            'total_payables' => $payables->count(),
            'total_amount' => round($payables->sum('amount'), 2),
            'total_paid' => round($payables->sum('paid_amount'), 2),
            'total_remaining' => round($payables->sum('remaining_amount'), 2),
            'payables' => $payables->map(fn ($r) => [
                'document' => $r->document_number,
                'supplier' => $r->supplier?->name ?? '-',
                'due_date' => $r->due_date?->format('d/m/Y'),
                'amount' => (float) $r->amount,
                'paid' => (float) $r->paid_amount,
                'remaining' => (float) $r->remaining_amount,
                'status' => $r->status,
            ]),
        ]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $from = $request->from;
        $to = $request->to;

        $saleCashIn = Sale::whereHas('paymentMethod', fn ($q) => $q->where('is_credit', false))
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->sum('total');

        $cashIn = CashTransaction::where('type', 'in')
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->sum('amount');

        $cashOut = CashTransaction::where('type', 'out')
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->sum('amount');

        $totalIn = $saleCashIn + $cashIn;
        $totalOut = $cashOut;
        $netCash = $totalIn - $totalOut;

        return $this->success([
            'from' => $from,
            'to' => $to,
            'cash_sales' => round($saleCashIn, 2),
            'other_cash_in' => round($cashIn, 2),
            'total_cash_in' => round($totalIn, 2),
            'total_cash_out' => round($totalOut, 2),
            'net_cash' => round($netCash, 2),
        ]);
    }

    public function stockReport(Request $request): JsonResponse
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->orderBy('name')->get();

        $totalValue = $products->sum(fn ($p) => $p->stock * $p->purchase_price);

        return $this->success([
            'total_products' => $products->count(),
            'total_stock_value' => round($totalValue, 2),
            'low_stock_count' => $products->where('stock', '<=', \DB::raw('min_stock'))->count(),
            'products' => $products->map(fn ($p) => [
                'code' => $p->code,
                'name' => $p->name,
                'category' => $p->category?->name ?? '-',
                'stock' => (float) $p->stock,
                'min_stock' => (float) $p->min_stock,
                'unit' => $p->unit,
                'purchase_price' => (float) $p->purchase_price,
                'stock_value' => round($p->stock * $p->purchase_price, 2),
                'is_low_stock' => $p->stock <= $p->min_stock,
            ]),
        ]);
    }
}

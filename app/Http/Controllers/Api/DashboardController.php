<?php

namespace App\Http\Controllers\Api;

use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $monthEnd = now()->endOfMonth()->format('Y-m-d');

        $salesToday = Sale::whereDate('sale_date', $today)->sum('total');
        $salesTodayCount = Sale::whereDate('sale_date', $today)->count();

        $purchasesMonth = Purchase::whereDate('purchase_date', '>=', $monthStart)
            ->whereDate('purchase_date', '<=', $monthEnd)
            ->sum('total');

        $totalReceivables = Receivable::where('remaining_amount', '>', 0)->sum('remaining_amount');
        $totalPayables = Payable::where('remaining_amount', '>', 0)->sum('remaining_amount');

        $recentSales = Sale::with(['customer', 'paymentMethod'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($s) => [
                'invoice' => $s->invoice_number,
                'customer' => $s->customer?->name ?? $s->customer_name ?? 'Umum',
                'total' => (float) $s->total,
                'status' => $s->status,
                'date' => $s->sale_date->format('d/m/Y H:i'),
            ]);

        $lowStockProducts = Product::with('category')
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'stock' => (float) $p->stock,
                'min_stock' => (float) $p->min_stock,
            ]);

        $overduePayables = Payable::with('supplier')
            ->whereIn('status', ['open', 'partial'])
            ->whereDate('due_date', '<', now())
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'document' => $p->document_number,
                'supplier' => $p->supplier?->name ?? '-',
                'due_date' => $p->due_date->format('d/m/Y'),
                'remaining' => (float) $p->remaining_amount,
            ]);

        $overdueReceivables = Receivable::with('customer')
            ->whereIn('status', ['open', 'partial'])
            ->whereDate('due_date', '<', now())
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(fn ($r) => [
                'document' => $r->document_number,
                'customer' => $r->customer?->name ?? '-',
                'due_date' => $r->due_date->format('d/m/Y'),
                'remaining' => (float) $r->remaining_amount,
            ]);

        return $this->success([
            'sales_today' => round($salesToday, 2),
            'sales_today_count' => $salesTodayCount,
            'purchases_this_month' => round($purchasesMonth, 2),
            'total_receivables' => round($totalReceivables, 2),
            'total_payables' => round($totalPayables, 2),
            'recent_sales' => $recentSales,
            'low_stock_products' => $lowStockProducts,
            'overdue_payables' => $overduePayables,
            'overdue_receivables' => $overdueReceivables,
        ]);
    }
}

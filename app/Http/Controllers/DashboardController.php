<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $salesToday = Sale::whereDate('sale_date', $today)->where('status', '!=', 'cancelled')->sum('total');
        $purchasesThisMonth = Purchase::whereDate('purchase_date', '>=', $startOfMonth)->where('status', '!=', 'cancelled')->sum('total');
        $totalReceivable = Receivable::where('status', '!=', 'paid')->sum('remaining_amount');
        $totalPayable = Payable::where('status', '!=', 'paid')->sum('remaining_amount');

        $recentSales = Sale::with('customer')->latest()->take(5)->get();
        $lowStockProducts = Product::lowStock()->with('category')->take(5)->get();

        $sales7Days = Sale::selectRaw('DATE(sale_date) as date, SUM(total) as total')
            ->whereDate('sale_date', '>=', Carbon::today()->subDays(6))
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->with('product')
            ->whereHas('sale', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $overduePayables = Payable::where('due_date', '<', $today)->where('status', '!=', 'paid')->count();
        $overdueReceivables = Receivable::where('due_date', '<', $today)->where('status', '!=', 'paid')->count();

        return view('pages.dashboard', compact(
            'salesToday', 'purchasesThisMonth', 'totalReceivable', 'totalPayable',
            'recentSales', 'lowStockProducts', 'sales7Days', 'topProducts',
            'overduePayables', 'overdueReceivables'
        ));
    }
}

<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('pages.laporan.index');
    }

    public function pembelian(Request $request)
    {
        if ($request->ajax()) {
            $query = Purchase::with('supplier')->latest();
            if ($request->filled('from')) {
                $query->whereDate('purchase_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('purchase_date', '<=', $request->to);
            }
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    });
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                return [
                    $item->document_number,
                    $item->supplier?->name ?? '-',
                    $item->purchase_date->format('d/m/Y'),
                    formatRupiah($item->total),
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.laporan.pembelian');
    }

    public function penjualan(Request $request)
    {
        if ($request->ajax()) {
            $query = Sale::with(['customer', 'paymentMethod'])->latest();
            if ($request->filled('from')) {
                $query->whereDate('sale_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('sale_date', '<=', $request->to);
            }
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                return [
                    $item->invoice_number,
                    $item->customer?->name ?? $item->customer_name ?? 'Umum',
                    $item->sale_date->format('d/m/Y'),
                    $item->paymentMethod?->name ?? '-',
                    formatRupiah($item->total),
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.laporan.penjualan');
    }

    public function hutang(Request $request)
    {
        if ($request->ajax()) {
            $query = Payable::with('supplier')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%')
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    });
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                return [
                    $item->document_number,
                    $item->supplier?->name ?? '-',
                    $item->due_date?->format('d/m/Y') ?? '-',
                    formatRupiah($item->amount),
                    formatRupiah($item->paid_amount),
                    formatRupiah($item->remaining_amount),
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.laporan.hutang');
    }

    public function piutang(Request $request)
    {
        if ($request->ajax()) {
            $query = Receivable::with('customer')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('document_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    });
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                return [
                    $item->document_number,
                    $item->customer?->name ?? '-',
                    $item->due_date?->format('d/m/Y') ?? '-',
                    formatRupiah($item->amount),
                    formatRupiah($item->paid_amount),
                    formatRupiah($item->remaining_amount),
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.laporan.piutang');
    }

    public function arusKas(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->endOfMonth()->format('Y-m-d'));

        $saleCashIn = Sale::whereHas('paymentMethod', fn ($q) => $q->where('is_credit', false))
            ->whereBetween('sale_date', [$from, $to])
            ->sum('total');

        $cashTransactionIn = CashTransaction::where('type', 'in')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $cashTransactionOut = CashTransaction::where('type', 'out')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $totalIn = $saleCashIn + $cashTransactionIn;
        $totalOut = $cashTransactionOut;
        $netCash = $totalIn - $totalOut;

        return view('pages.laporan.arus_kas', compact(
            'from', 'to',
            'saleCashIn', 'cashTransactionIn', 'totalIn',
            'cashTransactionOut', 'totalOut',
            'netCash'
        ));
    }

    public function stok(Request $request)
    {
        $products = Product::with('category')->orderBy('name')->get();

        return view('pages.laporan.stok', compact('products'));
    }
}

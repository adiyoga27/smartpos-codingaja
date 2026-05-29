<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
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
            $query = Sale::with('customer')->latest();
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
                    ucfirst($item->payment_method),
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
        return view('pages.laporan.arus_kas');
    }

    public function stok(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::with('category')->latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                return [
                    $item->code,
                    $item->name,
                    $item->category?->name ?? '-',
                    $item->stock,
                    $item->min_stock,
                    $item->stock <= $item->min_stock ? '<span class="badge bg-danger">Menipis</span>' : '<span class="badge bg-success">Aman</span>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.laporan.stok');
    }
}

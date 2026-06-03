@extends('layouts.app')
@section('title', 'Detail Transaksi - '.$sale->invoice_number)
@section('breadcrumb')
    <a href="{{ route('pos.kasir') }}" class="text-slate-400 hover:text-slate-600">POS</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('pos.riwayat') }}" class="text-slate-400 hover:text-slate-600">Riwayat</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Detail</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-2">
    <div>
        <h4 class="font-bold mb-1">Detail Transaksi</h4>
        <p class="text-sm text-slate-400">{{ $sale->invoice_number }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('pos.print-a4', $sale) }}" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak A4</a>
        <a href="{{ route('pos.print-thermal', $sale) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="bi bi-receipt"></i> Thermal</a>
        <a href="{{ route('pos.riwayat') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="card">
            <div class="card-header font-medium">Item Transaksi</div>
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr><th>Produk</th><th class="text-center">Qty</th><th>Harga</th><th>Diskon</th><th class="text-right">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse($sale->items as $item)
                            <tr>
                                <td>{{ $item->product?->name ?? '-' }}</td>
                                <td class="text-center">{{ formatQty($item->quantity) }}</td>
                                <td>{{ formatRupiah($item->unit_price) }}</td>
                                <td>{{ formatRupiah($item->discount) }}</td>
                                <td class="text-right font-medium">{{ formatRupiah($item->total) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-slate-400 py-4">Tidak ada item</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="text-right font-medium">Subtotal</td><td class="text-right">{{ formatRupiah($sale->subtotal) }}</td></tr>
                            <tr><td colspan="4" class="text-right font-medium">Diskon Item</td><td class="text-right text-red-500">{{ formatRupiah($sale->item_discount) }}</td></tr>
                            <tr><td colspan="4" class="text-right font-medium">Diskon Tambahan</td><td class="text-right text-red-500">{{ formatRupiah($sale->total_discount) }}</td></tr>
                            <tr><td colspan="4" class="text-right font-medium">Pajak</td><td class="text-right text-amber-600">{{ formatRupiah($sale->tax) }}</td></tr>
                            <tr class="bg-primary-50 font-bold"><td colspan="4" class="text-right text-lg">Total</td><td class="text-right text-lg text-primary-700">{{ formatRupiah($sale->total) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card">
            <div class="card-header font-medium">Informasi</div>
            <div class="card-body">
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Invoice</dt><dd class="text-sm font-mono font-medium">{{ $sale->invoice_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Tanggal</dt><dd class="text-sm">{{ $sale->sale_date->isoFormat('dddd, D MMMM Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Customer</dt><dd class="text-sm font-medium">{{ $sale->customer?->name ?? $sale->customer_name ?? 'Umum' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Metode</dt><dd class="text-sm">{{ $sale->paymentMethod?->name ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Kasir</dt><dd class="text-sm">{{ $sale->creator?->name ?? '-' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header font-medium">Pembayaran</div>
            <div class="card-body">
                <dl class="space-y-3">
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Status</dt>
                        <dd>
                            @if($sale->status === 'paid')
                                <span class="badge bg-success">Lunas</span>
                            @elseif($sale->status === 'partial')
                                <span class="badge bg-warning">Sebagian</span>
                            @elseif($sale->status === 'unpaid')
                                <span class="badge bg-danger">Belum Bayar</span>
                            @else
                                <span class="badge bg-secondary">Batal</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Dibayar</dt><dd class="text-sm font-medium text-emerald-600">{{ formatRupiah($sale->paid_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Kembalian</dt><dd class="text-sm">{{ formatRupiah($sale->change_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Sisa</dt><dd class="text-sm font-medium text-red-500">{{ formatRupiah(max(0, $sale->total - $sale->paid_amount)) }}</dd></div>
                </dl>
            </div>
        </div>

        @if($sale->notes)
        <div class="card">
            <div class="card-header font-medium">Catatan</div>
            <div class="card-body"><p class="text-sm text-slate-600">{{ $sale->notes }}</p></div>
        </div>
        @endif
    </div>
</div>
@endsection

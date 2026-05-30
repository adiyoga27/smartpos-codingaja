@extends('layouts.app')
@section('title', 'Edit Transaksi - '.$sale->invoice_number)
@section('breadcrumb')
    <a href="{{ route('pos.kasir') }}" class="text-slate-400 hover:text-slate-600">POS</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('pos.riwayat') }}" class="text-slate-400 hover:text-slate-600">Riwayat</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h4 class="font-bold mb-1">Edit Transaksi</h4>
        <p class="text-sm text-slate-400">{{ $sale->invoice_number }}</p>
    </div>
    <a href="{{ route('pos.detail', $sale) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<form action="#" method="POST" class="space-y-5">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="card">
            <div class="card-header">Customer</div>
            <div class="card-body">
                <div><label class="form-label">Nama Customer</label>
                    <input type="text" class="form-input" value="{{ $sale->customer?->name ?? $sale->customer_name ?? 'Umum' }}" disabled>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Metode & Status</div>
            <div class="card-body space-y-3">
                <div><label class="form-label">Metode Pembayaran</label>
                    <select name="payment_method_id" class="form-select" disabled>
                        @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->id }}" {{ $sale->payment_method_id == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="paid" {{ $sale->status === 'paid' ? 'selected' : '' }}>Lunas</option>
                        <option value="partial" {{ $sale->status === 'partial' ? 'selected' : '' }}>Sebagian</option>
                        <option value="unpaid" {{ $sale->status === 'unpaid' ? 'selected' : '' }}>Belum Bayar</option>
                        <option value="cancelled" {{ $sale->status === 'cancelled' ? 'selected' : '' }}>Batal</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Keuangan</div>
            <div class="card-body">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Subtotal</dt><dd class="text-sm">{{ formatRupiah($sale->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Diskon</dt><dd class="text-sm text-red-500">{{ formatRupiah($sale->item_discount + $sale->total_discount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Pajak</dt><dd class="text-sm">{{ formatRupiah($sale->tax) }}</dd></div>
                    <div class="flex justify-between font-bold border-t pt-2"><dt class="text-xs">Total</dt><dd class="text-primary-700">{{ formatRupiah($sale->total) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Dibayar</dt><dd class="text-sm text-emerald-600">{{ formatRupiah($sale->paid_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Kembalian</dt><dd class="text-sm">{{ formatRupiah($sale->change_amount) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Item</div>
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Produk</th><th class="text-center">Qty</th><th>Harga</th><th>Diskon</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td>{{ formatRupiah($item->unit_price) }}</td>
                            <td>{{ formatRupiah($item->discount) }}</td>
                            <td class="text-right">{{ formatRupiah($item->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg mr-1"></i> Simpan Perubahan</button>
        <a href="{{ route('pos.detail', $sale) }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection

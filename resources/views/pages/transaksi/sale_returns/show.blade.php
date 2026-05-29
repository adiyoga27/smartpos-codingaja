@extends('layouts.app')
@section('title', 'Detail Return Penjualan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.sale_returns.index') }}">Return Penjualan</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Detail Return Penjualan</div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div><strong>No. Dokumen:</strong> {{ $saleReturn->document_number }}</div>
            <div><strong>Invoice:</strong> {{ $saleReturn->sale?->invoice_number ?? '-' }}</div>
            <div><strong>Customer:</strong> {{ $saleReturn->customer?->name ?? '-' }}</div>
            <div><strong>Tanggal:</strong> {{ $saleReturn->return_date->format('d/m/Y') }}</div>
            <div><strong>Refund:</strong> {{ ucfirst($saleReturn->refund_method) }}</div>
            <div class="md:col-span-3"><strong>Alasan:</strong> {{ $saleReturn->reason }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach($saleReturn->items as $item)
                    <tr><td>{{ $item->product?->name ?? '-' }}</td><td>{{ $item->quantity }}</td><td>{{ formatRupiah($item->unit_price) }}</td><td>{{ formatRupiah($item->total) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="text-right"><h5>Total: <strong>{{ formatRupiah($saleReturn->total) }}</strong></h5></div>
        <a href="{{ route('transaksi.sale_returns.index') }}" class="btn btn-secondary btn-md">Kembali</a>
    </div>
</div>
@endsection

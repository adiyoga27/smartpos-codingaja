@extends('layouts.app')
@section('title', 'Detail PO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index') }}">Pembelian</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header flex items-center justify-between">
        <span>Detail Purchase Order</span>
        <div class="flex gap-2">
            <a href="{{ route('transaksi.purchases.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div><strong>No. Dokumen:</strong> {{ $purchase->document_number }}</div>
            <div><strong>Supplier:</strong> {{ $purchase->supplier?->name ?? '-' }}</div>
            <div><strong>Tanggal:</strong> {{ $purchase->purchase_date->format('d/m/Y') }}</div>
            <div><strong>Status:</strong>
                @if($purchase->status == 'draft') <span class="badge badge-slate">Draft</span>
                @elseif($purchase->status == 'sent') <span class="badge badge-info">Dikirim</span>
                @elseif($purchase->status == 'partial') <span class="badge badge-warning">Sebagian</span>
                @elseif($purchase->status == 'completed') <span class="badge badge-success">Selesai</span>
                @else <span class="badge badge-danger">Batal</span>
                @endif
            </div>
        </div>
        <h6>Item</h6>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Produk</th><th>Qty</th><th>Diterima</th><th>Harga</th><th>Diskon</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach($purchase->items as $item)
                    <tr>
                        <td>{{ $item->product?->name ?? '-' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->received_quantity }}</td>
                        <td>{{ formatRupiah($item->unit_price) }}</td>
                        <td>{{ formatRupiah($item->discount) }}</td>
                        <td>{{ formatRupiah($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div></div>
            <div class="text-right">
                <p>Subtotal: <strong>{{ formatRupiah($purchase->subtotal) }}</strong></p>
                <p>Diskon: <strong>{{ formatRupiah($purchase->discount) }}</strong></p>
                <p>PPN: <strong>{{ formatRupiah($purchase->tax) }}</strong></p>
                <h5>Total: <strong>{{ formatRupiah($purchase->total) }}</strong></h5>
            </div>
        </div>
        @if(!in_array($purchase->status, ['completed','cancelled']))
        <hr>
        <h6>Penerimaan Barang</h6>
        <form action="{{ route('transaksi.purchases.receive', $purchase) }}" method="POST">
            @csrf @method('PATCH')
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Produk</th><th>Qty Order</th><th>Sudah Diterima</th><th>Qty Terima</th></tr></thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->received_quantity }}</td>
                            <td>
                                @if($item->received_quantity < $item->quantity)
                                <input type="number" name="items[{{ $item->id }}]" class="form-input" value="0" min="0" max="{{ $item->quantity - $item->received_quantity }}">
                                @else
                                <span class="badge badge-success">Lengkap</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Penerimaan</button>
        </form>
        @endif
    </div>
</div>
@endsection

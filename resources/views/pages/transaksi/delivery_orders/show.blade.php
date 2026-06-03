@extends('layouts.app')
@section('title', 'Detail DO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.delivery_orders.index') }}">Delivery Order</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-header flex flex-wrap items-center justify-between gap-2">
        <h5 class="mb-0">Delivery Order #{{ $deliveryOrder->document_number }}</h5>
        <div class="flex gap-2">
            <a href="{{ route('transaksi.delivery_orders.print', $deliveryOrder) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Cetak</a>
            <a href="{{ route('transaksi.sales_orders.show', $deliveryOrder->salesOrder) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-text"></i> Lihat SO</a>
            <a href="{{ route('transaksi.delivery_orders.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-3">
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">No. SO</small>
                <strong class="d-block mt-1">
                    <a href="{{ route('transaksi.sales_orders.show', $deliveryOrder->salesOrder) }}" class="text-decoration-none">{{ $deliveryOrder->salesOrder?->document_number ?? '-' }}</a>
                </strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Customer</small>
                <strong class="d-block mt-1">{{ $deliveryOrder->customer?->name ?? '-' }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Tanggal Kirim</small>
                <strong class="d-block mt-1">{{ $deliveryOrder->delivery_date->format('d/m/Y') }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total Item</small>
                <strong class="d-block mt-1">{{ $deliveryOrder->items->count() }} produk</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total Nilai</small>
                <strong class="d-block mt-1 text-primary">{{ formatRupiah($deliveryOrder->total) }}</strong>
            </div>
        </div>
        @if($deliveryOrder->notes)
        <div class="bg-slate-50 rounded p-3 mb-3">
            <small class="text-slate-500 d-block">Catatan</small>
            <span class="mt-1 d-block">{{ $deliveryOrder->notes }}</span>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-box-seam"></i> Item Dikirim</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Produk</th><th class="text-center">Qty Dikirim</th><th class="text-center">Qty di SO</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead>
                <tbody>
                    @foreach($deliveryOrder->items as $idx => $item)
                    @php $soItem = $item->salesOrderItem; @endphp
                    <tr>
                        <td class="text-slate-400">{{ $idx + 1 }}</td>
                        <td class="fw-medium">{{ $soItem?->product?->name ?? $item->product?->name ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ formatQty($item->quantity) }}</td>
                        <td class="text-center text-slate-500">{{ $soItem ? formatQty($soItem->quantity) : '-' }}</td>
                        <td class="text-end">{{ $soItem ? formatRupiah($soItem->unit_price) : '-' }}</td>
                        <td class="text-end fw-medium">
                            @php $sub = $soItem ? $item->quantity * $soItem->unit_price : 0; @endphp
                            {{ formatRupiah(max(0, $sub - ($soItem ? ($soItem->discount / max(1, $soItem->quantity)) * $item->quantity : 0))) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="table-active">
                        <td colspan="5" class="text-end fw-bold fs-5">Total</td>
                        <td class="text-end fw-bold fs-5 text-primary">{{ formatRupiah($deliveryOrder->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

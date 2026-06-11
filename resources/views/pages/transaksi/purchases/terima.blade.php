@extends('layouts.app')
@section('title', 'Terima PO #'.$purchase->document_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index', ['type' => 'po']) }}">Purchase Order</a></li>
    <li class="breadcrumb-item active">Terima Barang</li>
@endsection
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Info PO</h6></div>
        <div class="card-body">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">No. PO</dt><dd class="font-medium">{{ $purchase->document_number }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Supplier</dt><dd>{{ $purchase->supplier?->name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Tanggal</dt><dd>{{ $purchase->purchase_date->format('d/m/Y') }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="card lg:col-span-2">
        <div class="card-header"><h6 class="mb-0">Item PO</h6></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Produk</th><th class="text-center">Qty Order</th><th class="text-center">Sudah Diterima</th><th class="text-center">Sisa</th></tr></thead>
                <tbody>
                    @foreach($purchase->items as $item)
                    @php $sisa = max(0, $item->quantity - $item->received_quantity); @endphp
                    <tr>
                        <td>{{ $item->product?->name ?? '-' }}</td>
                        <td class="text-center">{{ formatQty($item->quantity) }}</td>
                        <td class="text-center">{{ formatQty($item->received_quantity) }}</td>
                        <td class="text-center">
                            @if($sisa > 0) <span class="badge bg-warning">{{ formatQty($sisa) }}</span>
                            @else <span class="badge bg-success"><i class="bi bi-check"></i></span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@php $hasRemainingItem = $purchase->items->contains(fn($i) => $i->received_quantity < $i->quantity); @endphp
@if($hasRemainingItem)
<div class="card border-primary mb-4">
    <div class="card-header bg-primary-50"><h6 class="mb-0"><i class="bi bi-box-arrow-in-down"></i> Terima Barang</h6></div>
    <div class="card-body">
        <form action="{{ route('transaksi.purchases.receive', $purchase) }}" method="POST">
            @csrf @method('PATCH')
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light"><tr><th>Produk</th><th class="text-center">Qty Order</th><th class="text-center">Sudah Diterima</th><th class="text-center" style="min-width:140px">Qty Terima Sekarang</th></tr></thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->product?->name ?? '-' }}</td>
                            <td class="text-center">{{ formatQty($item->quantity) }}</td>
                            <td class="text-center">{{ formatQty($item->received_quantity) }}</td>
                            <td class="text-center">
                                @php $maxReceive = $item->quantity - $item->received_quantity; @endphp
                                @if($maxReceive > 0)
                                <input type="number" name="items[{{ $item->id }}]" class="form-input form-input-sm text-center" value="0" min="0" max="{{ $maxReceive }}" step="0.01" placeholder="0">
                                @else
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Lengkap</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Penerimaan</button>
            <a href="{{ route('transaksi.purchases.show', $purchase) }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@else
<div class="text-center py-8 text-emerald-600">
    <i class="bi bi-check-circle text-4xl block mb-3"></i>
    <span class="text-lg font-bold">Semua barang sudah diterima.</span>
</div>
@endif

@if($receiveHistory->isNotEmpty())
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-1"></i> Riwayat Penerimaan</h6></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Tanggal</th><th>Produk</th><th class="text-center">Qty</th><th>Oleh</th></tr></thead>
            <tbody>
                @foreach($receiveHistory as $rx)
                <tr>
                    <td class="text-sm">{{ $rx->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-sm">{{ $rx->product?->name ?? '-' }}</td>
                    <td class="text-center text-sm text-emerald-600 fw-medium">+{{ formatQty($rx->quantity) }}</td>
                    <td class="text-sm">{{ $rx->creator?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

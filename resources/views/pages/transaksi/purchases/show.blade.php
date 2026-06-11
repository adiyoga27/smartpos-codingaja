@extends('layouts.app')
@section('title', 'Detail PO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index', ['type' => 'po']) }}">Purchase Order</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
@php $isLunas = $purchase->paid_amount >= $purchase->total; @endphp
@php $hasRemainingItem = $purchase->items->contains(fn($i) => $i->received_quantity < $i->quantity); @endphp
<div class="card mb-4">
    <div class="card-header flex flex-wrap items-center justify-between gap-2">
        <h5 class="mb-0">Purchase Order #{{ $purchase->document_number }}</h5>
        <div class="flex gap-2">
            @if(!$isLunas)
            <a href="{{ route('transaksi.purchases.pay', $purchase) }}" class="btn btn-success btn-sm"><i class="bi bi-cash-coin"></i> Bayar</a>
            @endif
            @if($hasRemainingItem)
            <a href="{{ route('transaksi.purchases.receive.form', $purchase) }}" class="btn btn-primary btn-sm"><i class="bi bi-box-arrow-in-down"></i> Terima</a>
            @endif
            <a href="{{ route('transaksi.purchases.print', $purchase) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Cetak</a>
            <a href="{{ route('transaksi.purchases.index', ['type' => 'po']) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Status</small>
                @if($isLunas) <span class="badge badge-success mt-1">Lunas</span>
                @else <span class="badge badge-danger mt-1">Belum Lunas</span>
                @endif
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Supplier</small>
                <strong class="d-block mt-1">{{ $purchase->supplier?->name ?? '-' }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Tanggal</small>
                <strong class="d-block mt-1">{{ $purchase->purchase_date->format('d/m/Y') }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total PO</small>
                <strong class="d-block mt-1 text-primary">{{ formatRupiah($purchase->total) }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Dibayar</small>
                <strong class="d-block mt-1 text-emerald-600">{{ formatRupiah($purchase->paid_amount) }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Sisa</small>
                <strong class="d-block mt-1 {{ $purchase->total - $purchase->paid_amount > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                    {{ formatRupiah(max(0, $purchase->total - $purchase->paid_amount)) }}
                </strong>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Item Purchase Order</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Produk</th><th class="text-center">Qty Order</th><th class="text-center">Diterima</th><th class="text-center">Sisa</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach($purchase->items as $item)
                    @php $sisa = max(0, $item->quantity - $item->received_quantity); @endphp
                    <tr>
                        <td class="fw-medium">{{ $item->product?->name ?? '-' }}</td>
                        <td class="text-center">{{ formatQty($item->quantity) }}</td>
                        <td class="text-center">
                            <span class="{{ $sisa <= 0 ? 'text-emerald-600 fw-bold' : '' }}">{{ formatQty($item->received_quantity) }}</span>
                        </td>
                        <td class="text-center">
                            @if($sisa > 0) <span class="badge bg-warning">{{ formatQty($sisa) }}</span>
                            @else <span class="badge bg-success"><i class="bi bi-check"></i></span>
                            @endif
                        </td>
                        <td class="text-end">{{ formatRupiah($item->unit_price) }}</td>
                        <td class="text-end">{{ formatRupiah($item->discount) }}</td>
                        <td class="text-end fw-medium">{{ formatRupiah($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr>
                        <td colspan="6" class="text-end fw-bold">Subtotal</td>
                        <td class="text-end fw-bold">{{ formatRupiah($purchase->subtotal) }}</td>
                    </tr>
                    @if($purchase->discount > 0)
                    <tr><td colspan="6" class="text-end">Diskon</td><td class="text-end">{{ formatRupiah($purchase->discount) }}</td></tr>
                    @endif
                    <tr class="table-active">
                        <td colspan="6" class="text-end fw-bold fs-5">Total</td>
                        <td class="text-end fw-bold fs-5 text-primary">{{ formatRupiah($purchase->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if($receiveHistory->isNotEmpty() || $paymentHistory->isNotEmpty())
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @if($receiveHistory->isNotEmpty())
    <div class="card">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-box-arrow-in-down me-1"></i> Riwayat Penerimaan</h6></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Tanggal</th><th>Produk</th><th class="text-center">Qty</th><th>Oleh</th></tr></thead>
                <tbody>
                    @foreach($receiveHistory as $rx)
                    <tr>
                        <td class="text-xs">{{ $rx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-xs">{{ $rx->product?->name ?? '-' }}</td>
                        <td class="text-center text-xs fw-medium text-emerald-600">+{{ formatQty($rx->quantity) }}</td>
                        <td class="text-xs">{{ $rx->creator?->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($paymentHistory->isNotEmpty())
    <div class="card">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-cash-stack me-1"></i> Riwayat Pembayaran</h6></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Tanggal</th><th>Akun Kas</th><th class="text-end">Jumlah</th><th>Oleh</th></tr></thead>
                <tbody>
                    @foreach($paymentHistory as $tx)
                    <tr>
                        <td class="text-xs">{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                        <td class="text-xs">{{ $tx->cashAccount?->name ?? '-' }}</td>
                        <td class="text-end text-xs fw-medium text-red-500">-{{ formatRupiah($tx->amount) }}</td>
                        <td class="text-xs">{{ $tx->creator?->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif
@endsection

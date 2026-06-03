@extends('layouts.app')
@section('title', 'Detail SO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.sales_orders.index') }}">Sales Order</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-header flex flex-wrap items-center justify-between gap-2">
        <h5 class="mb-0">Sales Order #{{ $salesOrder->document_number }}</h5>
        <div class="flex gap-2">
            <a href="{{ route('transaksi.sales_orders.print', $salesOrder) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Cetak</a>
            <a href="{{ route('transaksi.sales_orders.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Status</small>
                @if($salesOrder->status == 'draft') <span class="badge badge-slate mt-1">Draft</span>
                @elseif($salesOrder->status == 'sent') <span class="badge badge-info mt-1">Dikirim</span>
                @elseif($salesOrder->status == 'partial') <span class="badge badge-warning mt-1">Sebagian</span>
                @elseif($salesOrder->status == 'completed') <span class="badge badge-success mt-1">Selesai</span>
                @else <span class="badge badge-danger mt-1">Batal</span>
                @endif
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Customer</small>
                <strong class="d-block mt-1">{{ $salesOrder->customer?->name ?? '-' }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Tanggal Order</small>
                <strong class="d-block mt-1">{{ $salesOrder->order_date->format('d/m/Y') }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Jatuh Tempo</small>
                <strong class="d-block mt-1">{{ $salesOrder->due_date ? $salesOrder->due_date->format('d/m/Y') : '-' }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total Order</small>
                <strong class="d-block mt-1 text-primary">{{ formatRupiah($salesOrder->total) }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total Dikirim</small>
                @php $deliveredTotal = $salesOrder->items->sum(fn($i) => $i->quantity > 0 ? ($i->delivered_quantity / $i->quantity) * $i->total : 0); @endphp
                <strong class="d-block mt-1 text-success">{{ formatRupiah($deliveredTotal) }}</strong>
            </div>
        </div>
        @if($salesOrder->notes)
        <div class="bg-slate-50 rounded p-3 mb-3">
            <small class="text-slate-500 d-block">Catatan</small>
            <span class="mt-1 d-block">{{ $salesOrder->notes }}</span>
        </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Item Sales Order</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Produk</th><th class="text-center">Qty Order</th><th class="text-center">Dikirim</th><th class="text-center">Sisa</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach($salesOrder->items as $item)
                    @php $remaining = max(0, $item->quantity - $item->delivered_quantity); @endphp
                    <tr>
                        <td class="fw-medium">{{ $item->product?->name ?? '-' }}</td>
                        <td class="text-center">{{ formatQty($item->quantity) }}</td>
                        <td class="text-center">{{ formatQty($item->delivered_quantity) }}</td>
                        <td class="text-center">
                            @if($remaining > 0)
                                <span class="badge bg-warning text-dark">{{ formatQty($remaining) }}</span>
                            @else
                                <span class="badge bg-success">Lengkap</span>
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
                        <td class="text-end fw-bold">{{ formatRupiah($salesOrder->subtotal) }}</td>
                    </tr>
                    @if($salesOrder->discount > 0)
                    <tr>
                        <td colspan="6" class="text-end">Diskon</td>
                        <td class="text-end">{{ formatRupiah($salesOrder->discount) }}</td>
                    </tr>
                    @endif
                    @if($salesOrder->tax > 0)
                    <tr>
                        <td colspan="6" class="text-end">PPN</td>
                        <td class="text-end">{{ formatRupiah($salesOrder->tax) }}</td>
                    </tr>
                    @endif
                    <tr class="table-active">
                        <td colspan="6" class="text-end fw-bold fs-5">Total</td>
                        <td class="text-end fw-bold fs-5 text-primary">{{ formatRupiah($salesOrder->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if(!in_array($salesOrder->status, ['completed','cancelled']))
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary-50"><h6 class="mb-0"><i class="bi bi-truck"></i> Buat Delivery Order (Pengiriman)</h6></div>
    <div class="card-body">
        <form action="{{ route('transaksi.sales_orders.deliver', $salesOrder) }}" method="POST">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tanggal Kirim</label>
                    <input type="date" name="delivery_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" class="form-input" placeholder="Catatan pengiriman...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light"><tr><th>Produk</th><th class="text-center">Qty Order</th><th class="text-center">Sudah Dikirim</th><th class="text-center">Sisa</th><th class="text-center" style="min-width:140px">Qty Kirim</th></tr></thead>
                    <tbody>
                        @foreach($salesOrder->items as $item)
                        @php $sisa = max(0, $item->quantity - $item->delivered_quantity); @endphp
                        <tr>
                            <td class="fw-medium">{{ $item->product?->name ?? '-' }}</td>
                            <td class="text-center">{{ formatQty($item->quantity) }}</td>
                            <td class="text-center">{{ formatQty($item->delivered_quantity) }}</td>
                            <td class="text-center">
                                @if($sisa > 0)
                                    <span class="badge bg-warning text-dark">{{ formatQty($sisa) }}</span>
                                @else
                                    <span class="badge bg-success">Lengkap</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($sisa > 0)
                                <input type="number" name="items[{{ $item->id }}]" class="form-input form-input-sm text-center" value="0" min="0" max="{{ $sisa }}" step="0.01">
                                @else
                                <span class="badge bg-success">Lengkap</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Buat Delivery Order</button>
        </form>
    </div>
</div>
@endif

@if($salesOrder->deliveryOrders->count() > 0)
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Delivery Order</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>No. DO</th><th>Tanggal</th><th class="text-end">Total</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    @foreach($salesOrder->deliveryOrders as $do)
                    <tr>
                        <td class="fw-medium">{{ $do->document_number }}</td>
                        <td>{{ $do->delivery_date->format('d/m/Y') }}</td>
                        <td class="text-end">{{ formatRupiah($do->total) }}</td>
                        <td class="text-center"><a href="{{ route('transaksi.delivery_orders.show', $do) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

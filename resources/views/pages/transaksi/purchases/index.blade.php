@extends('layouts.app')
@php $isDirect = request('type') === 'direct'; @endphp
@section('title', $isDirect ? 'Pembelian Langsung' : 'Purchase Order')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index') }}">Transaksi</a></li>
    <li class="breadcrumb-item active">{{ $isDirect ? 'Pembelian Langsung' : 'Purchase Order' }}</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">{{ $isDirect ? 'Pembelian Langsung' : 'Purchase Order' }}</h4>
    <div class="flex gap-2">
        @if($isDirect)
        <a href="{{ route('transaksi.purchases.direct') }}" class="btn btn-success btn-md"><i class="bi bi-cart-check"></i> Buat Pembelian Langsung</a>
        @else
        <a href="{{ route('transaksi.purchases.create') }}" class="btn btn-primary btn-md"><i class="bi bi-plus-lg"></i> Buat PO</a>
        <a href="{{ route('transaksi.purchases.direct') }}" class="btn btn-success btn-md"><i class="bi bi-cart-check"></i> Pembelian Langsung</a>
        @endif
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-striped" id="purchases-table" style="width:100%">
                <thead>
                    <tr>
                        <th>No. PO</th><th>Supplier</th><th>Tanggal</th><th>Total</th>
                        @if($isDirect)
                        <th>Status</th><th>Aksi</th>
                        @else
                        <th>Stok Terima</th><th>Sisa Hutang</th><th>Status</th><th>Aksi</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#purchases-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('transaksi.purchases.index') }}',
            data: function(d) { d.type = '{{ request('type', '') }}'; }
        },
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-clipboard"></i>' },
            { extend: 'csv', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-filetype-csv"></i>' },
            { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm', text: '<i class="bi bi-file-earmark-pdf"></i>' },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-printer"></i>' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        columnDefs: [
            { orderable: false, targets: {{ $isDirect ? '[4,5]' : '[4,5,6,7]' }} }
        ]
    });
});
</script>
@endpush

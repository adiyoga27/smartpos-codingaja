@extends('layouts.app')
@section('title', 'Riwayat Penjualan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pos.kasir') }}">POS</a></li>
    <li class="breadcrumb-item active">Riwayat</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Riwayat Penjualan</h4>
    <form id="filter-form" class="flex gap-2">
        <input type="date" id="from" name="from" class="form-input" value="{{ request('from') }}">
        <input type="date" id="to" name="to" class="form-input" value="{{ request('to') }}">
        <select id="payment_method" name="payment_method_id" class="form-select">
            <option value="">Semua</option>
            @foreach($paymentMethods as $pm)
            <option value="{{ $pm->id }}" {{ request('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
            @endforeach
        </select>
        <button type="button" id="btn-filter" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    </form>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="sales-history-table" style="width:100%">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Tanggal</th><th>Metode</th><th>Total</th><th>Status</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#sales-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('pos.riwayat') }}',
            data: function(d) {
                d.from = $('#from').val();
                d.to = $('#to').val();
                d.payment_method_id = $('#payment_method').val();
            }
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
            { orderable: false, targets: [5] }
        ]
    });
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });
});
</script>
@endpush

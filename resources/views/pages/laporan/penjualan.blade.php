@extends('layouts.app')
@section('title', 'Laporan Penjualan')
@section('breadcrumb')
    <a href="{{ route('laporan.index') }}" class="text-slate-400 hover:text-slate-600">Laporan</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Penjualan</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Laporan Penjualan</h4>
    <form id="filter-form" class="flex gap-2">
        <input type="date" id="from" name="from" class="form-input" value="{{ request('from') }}">
        <input type="date" id="to" name="to" class="form-input" value="{{ request('to') }}">
        <button type="button" id="btn-filter" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    </form>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="report-penjualan-table" style="width:100%">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Tanggal</th><th>Metode</th><th>Total</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#report-penjualan-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('laporan.penjualan') }}',
            data: function(d) {
                d.from = $('#from').val();
                d.to = $('#to').val();
            }
        },
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-clipboard"></i>', exportOptions: { modifier: { page: 'all' } } },
            { extend: 'csv', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-filetype-csv"></i>', exportOptions: { modifier: { page: 'all' } } },
            { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="bi bi-file-earmark-excel"></i> Excel', exportOptions: { modifier: { page: 'all' } } },
            { extend: 'pdf', className: 'btn btn-danger btn-sm', text: '<i class="bi bi-file-earmark-pdf"></i>', exportOptions: { modifier: { page: 'all' } } },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-printer"></i>', exportOptions: { modifier: { page: 'all' } } },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        }
    });
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });
});
</script>
@endpush

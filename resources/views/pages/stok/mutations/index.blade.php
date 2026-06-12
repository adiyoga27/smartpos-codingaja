@extends('layouts.app')
@section('title', 'Kartu Stok')
@section('breadcrumb')
    <a href="{{ route('stok.mutations.index') }}" class="text-slate-400 hover:text-slate-600">Stok</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Kartu Stok</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Kartu Stok</h4>
    <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
            <input type="checkbox" id="lowStockFilter" class="form-checkbox rounded border-slate-300 text-primary-600 focus:ring-primary-500">
            <span>Tampilkan stok dibawah minimal</span>
        </label>
        <a href="{{ route('stok.opname') }}" class="btn btn-primary btn-md"><i class="bi bi-clipboard-check"></i> Stock Opname</a>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="mutations-table" style="width:100%">
                <thead><tr><th>Kode</th><th>Nama</th><th>Stok</th><th>Min</th><th>Masuk</th><th>Keluar</th><th>Aksi</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#mutations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('stok.mutations.index') }}',
            data: function(d) {
                d.low_stock = $('#lowStockFilter').is(':checked') ? '1' : '0';
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
        },
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        order: [[2, 'desc']]
    });

    $('#lowStockFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush

@extends('layouts.app')
@section('title', 'Mutasi Stok')
@section('breadcrumb')
    <a href="{{ route('stok.mutations.index') }}" class="text-slate-400 hover:text-slate-600">Stok</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Mutasi</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Mutasi Stok - {{ $product->name }}</div>
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="mutations-show-table" style="width:100%">
                <thead><tr><th>Tanggal</th><th>Tipe</th><th>Qty</th><th>Stok Sebelum</th><th>Stok Sesudah</th><th>Keterangan</th><th>Oleh</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#mutations-show-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('stok.mutations.show', $product) }}',
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
            { orderable: false, targets: [1, 5, 6] }
        ]
    });
});
</script>
@endpush

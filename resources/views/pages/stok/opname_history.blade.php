@extends('layouts.app')
@section('title', 'Riwayat Stock Opname')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stok.mutations.index') }}">Stok</a></li>
    <li class="breadcrumb-item active">Riwayat Opname</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Riwayat Stock Opname</h4>
    <a href="{{ route('stok.opname.select') }}" class="btn btn-primary btn-md"><i class="bi bi-clipboard-check"></i> Stock Opname Baru</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-striped" id="opname-history-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Nomor Opname</th>
                        <th>Tanggal</th>
                        <th>Total Item</th>
                        <th>User</th>
                        <th class="text-center">Aksi</th>
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
    $('#opname-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('stok.opname.history') }}',
            type: 'GET'
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
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [4] }]
    });
});
</script>
@endpush

@extends('layouts.app')
@section('title', 'Stock Opname')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stok.mutations.index') }}">Stok</a></li>
    <li class="breadcrumb-item active">Stock Opname</li>
@endsection
@section('content')

<div class="card">
    <div class="card-header flex items-center justify-between">
        <h6 class="mb-0"><i class="bi bi-clock-history text-primary-500 me-1"></i> Riwayat Stock Opname</h6>
        <a href="{{ route('stok.opname') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Stock Opname Baru
        </a>
    </div>
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
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-clipboard"></i>' },
            { extend: 'csv', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-filetype-csv"></i>' },
            { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm', text: '<i class="bi bi-file-earmark-pdf"></i>' },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-printer"></i>' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [4] }]
    });
});
</script>
@endpush


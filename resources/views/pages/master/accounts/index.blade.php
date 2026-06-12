@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('breadcrumb')
    <a href="{{ route('master.accounts.index') }}" class="text-slate-400 hover:text-slate-600">Data Master</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Akun Biaya</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Chart of Accounts</h4>
    <a href="{{ route('master.accounts.create') }}" class="btn btn-primary btn-md"><i class="bi bi-plus-lg"></i> Tambah Akun</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-striped" id="accounts-table" style="width:100%">
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Normal</th><th>Saldo Awal</th><th>Status</th><th>Aksi</th></tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#accounts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('master.accounts.index') }}',
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
            { orderable: false, targets: [5, 6] }
        ]
    });
});
</script>
@endpush

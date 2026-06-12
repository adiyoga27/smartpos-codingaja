@extends('layouts.app')
@section('title', 'Role & Permission')
@section('breadcrumb')
    <a href="{{ route('roles.index') }}" class="text-slate-400 hover:text-slate-600">Pengguna</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Role</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Role & Permission</h4>
    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-md"><i class="bi bi-plus-lg"></i> Tambah Role</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="roles-table" style="width:100%">
                <thead><tr><th>Nama Role</th><th>Jumlah Permission</th><th>Aksi</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#roles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('roles.index') }}',
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
            { orderable: false, targets: [2] }
        ]
    });
});
</script>
@endpush

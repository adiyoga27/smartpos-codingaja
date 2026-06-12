@extends('layouts.app')
@section('title', 'Laporan Stok')
@section('breadcrumb')
    <a href="{{ route('laporan.index') }}" class="text-slate-400 hover:text-slate-600">Laporan</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Stok</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Laporan Stok</h4>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="report-stok-table" style="width:100%">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Min</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($products as $p)
                    <tr>
                        <td>{{ $p->code }}</td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->category?->name ?? '-' }}</td>
                        <td>{{ $p->stock }}</td>
                        <td>{{ $p->min_stock }}</td>
                        <td>{{ $p->stock <= $p->min_stock ? 'Menipis' : 'Aman' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#report-stok-table').DataTable({
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
        order: [[1, 'asc']]
    });
});
</script>
@endpush

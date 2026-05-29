@extends('layouts.app')
@section('title', 'Laporan Hutang')
@section('breadcrumb')
    <a href="{{ route('laporan.index') }}" class="text-slate-400 hover:text-slate-600">Laporan</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Hutang</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Laporan Hutang</h4>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="report-hutang-table" style="width:100%">
                <thead><tr><th>No. Dokumen</th><th>Supplier</th><th>Jatuh Tempo</th><th>Jumlah</th><th>Dibayar</th><th>Sisa</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#report-hutang-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('laporan.hutang') }}',
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
        }
    });
});
</script>
@endpush

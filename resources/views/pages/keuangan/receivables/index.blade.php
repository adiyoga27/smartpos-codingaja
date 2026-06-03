@extends('layouts.app')
@section('title', 'Terima Piutang')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.receivables.index') }}">Keuangan</a></li>
    <li class="breadcrumb-item active">Terima Piutang</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Daftar Piutang</h4>
    <a href="{{ route('keuangan.receivables.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Manual</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-striped" id="receivables-table" style="width:100%">
                <thead>
                    <tr><th>No. Dokumen</th><th>Customer</th><th>Jatuh Tempo</th><th>Jumlah</th><th>Diterima</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#receivables-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('keuangan.receivables.index') }}',
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
            { orderable: false, targets: [6, 7] }
        ]
    });
});
</script>
@endpush

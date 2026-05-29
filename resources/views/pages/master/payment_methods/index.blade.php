@extends('layouts.app')
@section('title', 'Metode Pembayaran')
@section('breadcrumb')
    <a href="{{ route('master.payment_methods.index') }}" class="text-slate-400 hover:text-slate-600">Data Master</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Metode Pembayaran</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Metode Pembayaran</h4>
    <a href="{{ route('master.payment_methods.create') }}" class="btn btn-primary btn-md"><i class="bi bi-plus-lg"></i> Tambah Metode</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-striped" id="payment-methods-table" style="width:100%">
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>Akun Terkait</th><th>Efek Akun</th><th>POS</th><th>Pembelian</th><th>Status</th><th>Aksi</th></tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#payment-methods-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('master.payment_methods.index') }}',
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

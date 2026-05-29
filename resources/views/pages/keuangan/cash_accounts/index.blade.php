@extends('layouts.app')
@section('title', 'Akun Kas & Bank')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.cash_accounts.index') }}">Kas & Bank</a></li>
    <li class="breadcrumb-item active">Daftar</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Akun Kas & Bank</h4>
    <a href="{{ route('keuangan.cash_accounts.create') }}" class="btn btn-primary btn-md"><i class="bi bi-plus-lg"></i> Tambah Akun</a>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    @foreach($accounts as $acc)
    <div>
        <div class="stat-card flex items-center">
            <div class="stat-icon {{ $acc->type == 'cash' ? 'bg-green-500/10 text-green-500' : 'bg-sky-500/10 text-sky-500' }} mr-3"><i class="bi {{ $acc->type == 'cash' ? 'bi-cash' : 'bi-bank' }}"></i></div>
            <div>
                <div class="text-slate-400 text-sm">{{ $acc->name }}</div>
                <div class="font-bold text-2xl">{{ formatRupiah($acc->current_balance) }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="cash-accounts-table" style="width:100%">
                <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Bank</th><th>No. Rekening</th><th>Saldo</th><th>Aksi</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#cash-accounts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('keuangan.cash_accounts.index') }}',
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
            { orderable: false, targets: [2, 6] }
        ]
    });
});
</script>
@endpush

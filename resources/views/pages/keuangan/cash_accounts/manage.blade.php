@extends('layouts.app')
@section('title', 'Kelola Akun - '.$cashAccount->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.cash_accounts.index') }}">Kas & Bank</a></li>
    <li class="breadcrumb-item active">Kelola Akun</li>
@endsection
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="card">
        <div class="card-header flex items-center gap-2">
            <i class="bi {{ $cashAccount->type == 'cash' ? 'bi-cash text-green-500' : 'bi-bank text-sky-500' }}"></i>
            {{ $cashAccount->name }}
        </div>
        <div class="card-body">
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-slate-500 text-sm">Kode</span><span class="font-medium">{{ $cashAccount->code }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500 text-sm">Tipe</span><span>{{ $cashAccount->type == 'cash' ? 'Kas' : 'Bank' }}</span></div>
                @if($cashAccount->bank_name)
                <div class="flex justify-between"><span class="text-slate-500 text-sm">Bank</span><span>{{ $cashAccount->bank_name }}</span></div>
                @endif
                @if($cashAccount->account_number)
                <div class="flex justify-between"><span class="text-slate-500 text-sm">No. Rek</span><span>{{ $cashAccount->account_number }}</span></div>
                @endif
                <div class="flex justify-between pt-2 border-t"><span class="text-slate-500 text-sm font-medium">Saldo Saat Ini</span><span class="font-bold text-lg text-primary-600">{{ formatRupiah($cashAccount->current_balance) }}</span></div>
            </div>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header">Transaksi Saldo</div>
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                    <h5 class="font-semibold text-emerald-700 mb-3 flex items-center gap-1"><i class="bi bi-plus-circle-fill"></i> Top-Up Saldo</h5>
                    <form action="{{ route('keuangan.cash_accounts.topup', $cashAccount) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label text-sm">Jumlah (Rp)</label>
                            <input type="text" name="amount" class="form-input rupiah-input" placeholder="Minimal Rp 500" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-sm">Keterangan</label>
                            <input type="text" name="description" class="form-input text-sm" placeholder="Contoh: Setoran tunai">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-full"><i class="bi bi-plus-circle mr-1"></i> Top-Up</button>
                    </form>
                </div>

                <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                    <h5 class="font-semibold text-red-700 mb-3 flex items-center gap-1"><i class="bi bi-dash-circle-fill"></i> Penarikan Saldo</h5>
                    <form action="{{ route('keuangan.cash_accounts.withdraw', $cashAccount) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label text-sm">Jumlah (Rp)</label>
                            <input type="text" name="amount" class="form-input rupiah-input" placeholder="Maks {{ formatRupiah($cashAccount->current_balance) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-sm">Keterangan</label>
                            <input type="text" name="description" class="form-input text-sm" placeholder="Contoh: Biaya operasional">
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm w-full"><i class="bi bi-dash-circle mr-1"></i> Tarik</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header flex items-center justify-between flex-wrap gap-2">
        <span>Riwayat Transaksi</span>
        <div class="flex gap-2">
            <select id="filterType" class="form-select form-select-sm" style="width:auto">
                <option value="">Semua Tipe</option>
                <option value="in">Top-Up / Masuk</option>
                <option value="out">Penarikan / Keluar</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="transactions-table" style="width:100%">
                <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Debit</th><th>Credit</th><th>Saldo</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#transactions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('keuangan.cash_accounts.transactions', $cashAccount) }}',
            data: function(d) {
                d.type = $('#filterType').val();
            }
        },
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
            { orderable: false, targets: [1, 2, 3, 4] }
        ]
    });

    $('#filterType').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush



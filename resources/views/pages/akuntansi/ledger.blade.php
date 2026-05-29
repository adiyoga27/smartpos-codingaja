@extends('layouts.app')
@section('title', 'Buku Besar')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('akuntansi.ledger') }}">Akuntansi</a></li>
    <li class="breadcrumb-item active">Buku Besar</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Buku Besar</h4>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('akuntansi.ledger') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <div class="col-span-full md:col-span-1">
                <label class="form-label">Akun</label>
                <select name="account_id" class="form-select" required>
                    <option value="">- Pilih Akun -</option>
                    @foreach($accounts as $acc)<option value="{{ $acc->id }}" {{ request('account_id')==$acc->id?'selected':'' }}>{{ $acc->code }} - {{ $acc->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Dari</label><input type="date" name="from" class="form-input" value="{{ request('from', now()->startOfMonth()->format('Y-m-d')) }}" required></div>
            <div><label class="form-label">Sampai</label><input type="date" name="to" class="form-input" value="{{ request('to', now()->format('Y-m-d')) }}" required></div>
            <div><button class="btn btn-primary btn-md w-full"><i class="bi bi-search mr-1"></i>Tampilkan</button></div>
        </form>
    </div>
</div>
@if($selectedAccount)
<div class="card">
    <div class="card-header">{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</div>
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0">
                <thead><tr><th>Tanggal</th><th>No. Jurnal</th><th>Keterangan</th><th>Debit</th><th>Kredit</th><th>Saldo</th></tr></thead>
                <tbody>
                    @php $running = $balance; @endphp
                    <tr><td colspan="5"><strong>Saldo Awal</strong></td><td class="text-right"><strong>{{ formatRupiah($running) }}</strong></td></tr>
                    @forelse($entries as $entry)
                    @php
                        if($selectedAccount->normal_balance == 'debit') { $running += $entry->debit - $entry->credit; }
                        else { $running += $entry->credit - $entry->debit; }
                    @endphp
                    <tr>
                        <td>{{ $entry->journal->journal_date->format('d/m/Y') }}</td>
                        <td>{{ $entry->journal->journal_number }}</td>
                        <td>{{ $entry->description ?? $entry->journal->description ?? '-' }}</td>
                        <td class="text-right">{{ $entry->debit > 0 ? formatRupiah($entry->debit) : '-' }}</td>
                        <td class="text-right">{{ $entry->credit > 0 ? formatRupiah($entry->credit) : '-' }}</td>
                        <td class="text-right">{{ formatRupiah($running) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-slate-400">Tidak ada transaksi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@extends('layouts.app')
@section('title', 'Transaksi Kas Baru')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.cash_transactions.index') }}">Transaksi</a></li>
    <li class="breadcrumb-item active">Baru</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Transaksi Kas Baru</div>
    <div class="card-body">
        <form action="{{ route('keuangan.cash_transactions.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Akun Kas/Bank</label>
                    <select name="cash_account_id" class="form-select" required><option value="">- Pilih -</option>@foreach($cashAccounts as $acc)<option value="{{ $acc->id }}">{{ $acc->name }}</option>@endforeach</select>
                </div>
                <div><label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required><option value="in">Kas Masuk</option><option value="out">Kas Keluar</option><option value="transfer">Transfer</option></select>
                </div>
                <div><label class="form-label">Tanggal</label><input type="date" name="transaction_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required></div>
                <div><label class="form-label">Nominal</label><input type="number" name="amount" class="form-input" value="0" min="0.01" step="0.01" required></div>
                <div><label class="form-label">Akun Lawan (Opsional)</label>
                    <select name="account_id" class="form-select"><option value="">- Pilih -</option>@foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>@endforeach</select>
                </div>
                <div><label class="form-label">Akun Tujuan (Transfer)</label>
                    <select name="target_account_id" class="form-select"><option value="">- Pilih -</option>@foreach($cashAccounts as $acc)<option value="{{ $acc->id }}">{{ $acc->name }}</option>@endforeach</select>
                </div>
                <div class="col-span-full"><label class="form-label">Keterangan</label><textarea name="description" class="form-input" rows="2"></textarea></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('keuangan.cash_transactions.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

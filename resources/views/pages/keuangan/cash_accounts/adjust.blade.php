@extends('layouts.app')
@section('title', 'Sesuaikan Saldo')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.cash_accounts.index') }}">Kas & Bank</a></li>
    <li class="breadcrumb-item active">Sesuaikan Saldo</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Sesuaikan Saldo - {{ $cashAccount->name }}</div>
    <div class="card-body">
        <div class="mb-4 p-4 bg-slate-50 rounded-lg">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-slate-500">Kode</span>
                    <p class="font-semibold">{{ $cashAccount->code }}</p>
                </div>
                <div>
                    <span class="text-sm text-slate-500">Saldo Saat Ini</span>
                    <p class="font-bold text-xl text-primary-600">{{ formatRupiah($cashAccount->current_balance) }}</p>
                </div>
            </div>
        </div>
        <form action="{{ route('keuangan.cash_accounts.adjust.store', $cashAccount) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Saldo Baru (Rp)</label>
                    <input type="text" name="new_balance" class="form-input rupiah-input" value="{{ old('new_balance', $cashAccount->current_balance) }}" required>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" class="form-input" value="{{ old('notes') }}" placeholder="Alasan penyesuaian...">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i> Simpan Penyesuaian</button>
                <a href="{{ route('keuangan.cash_accounts.index') }}" class="btn btn-secondary btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

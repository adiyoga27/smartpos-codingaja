@extends('layouts.app')
@section('title', 'Tambah Akun Kas/Bank')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.cash_accounts.index') }}">Kas & Bank</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Akun Kas/Bank</div>
    <div class="card-body">
        <form action="{{ route('keuangan.cash_accounts.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" required></div>
                <div><label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required><option value="cash">Kas</option><option value="bank">Bank</option></select>
                </div>
                <div>
                    <label class="form-label">Akun COA (Chart of Accounts)</label>
                    <select name="account_id" class="form-select select2">
                        <option value="">- Otomatis buat baru -</option>
                        @foreach($coaAccounts as $coa)
                        <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-slate-400">Kosongkan untuk auto-buat Akun COA baru</small>
                </div>
                <div><label class="form-label">Nama Bank</label><input type="text" name="bank_name" class="form-input"></div>
                <div><label class="form-label">No. Rekening</label><input type="text" name="account_number" class="form-input"></div>
                <div><label class="form-label">Saldo Awal</label><input type="number" name="opening_balance" class="form-input" value="0" required></div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_default" value="1">
                        <label>Jadikan Default (POS)</label>
                    </div>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('keuangan.cash_accounts.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
$(document).ready(function() { $('.select2').select2({ theme: 'bootstrap-5', width: '100%' }); });
</script>
@endpush
@endsection

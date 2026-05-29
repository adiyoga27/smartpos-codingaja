@extends('layouts.app')
@section('title', 'Edit Akun Kas/Bank')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.cash_accounts.index') }}">Kas & Bank</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Akun Kas/Bank</div>
    <div class="card-body">
        <form action="{{ route('keuangan.cash_accounts.update', $cashAccount) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" value="{{ old('code', $cashAccount->code) }}" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name', $cashAccount->name) }}" required></div>
                <div><label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required><option value="cash" {{ old('type', $cashAccount->type) == 'cash' ? 'selected' : '' }}>Kas</option><option value="bank" {{ old('type', $cashAccount->type) == 'bank' ? 'selected' : '' }}>Bank</option></select>
                </div>
                <div><label class="form-label">Nama Bank</label><input type="text" name="bank_name" class="form-input" value="{{ old('bank_name', $cashAccount->bank_name) }}"></div>
                <div><label class="form-label">No. Rekening</label><input type="text" name="account_number" class="form-input" value="{{ old('account_number', $cashAccount->account_number) }}"></div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $cashAccount->is_default) ? 'checked' : '' }}>
                        <label>Jadikan Default (POS)</label>
                    </div>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                    <a href="{{ route('keuangan.cash_accounts.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

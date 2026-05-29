@extends('layouts.app')
@section('title', 'Tambah Akun')
@section('breadcrumb')
    <a href="{{ route('master.accounts.index') }}" class="text-slate-400 hover:text-slate-600">Akun</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Tambah</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Akun</div>
    <div class="card-body">
        <form action="{{ route('master.accounts.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" value="{{ old('code') }}" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name') }}" required></div>
                <div><label class="form-label">Induk</label>
                    <select name="parent_code" class="form-select"><option value="">- Tanpa Induk -</option>@foreach($parents as $code=>$name)<option value="{{ $code }}" {{ old('parent_code')==$code?'selected':'' }}>{{ $name }}</option>@endforeach</select>
                </div>
                <div><label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required>
                        <option value="asset" {{ old('type')=='asset'?'selected':'' }}>Aset</option>
                        <option value="liability" {{ old('type')=='liability'?'selected':'' }}>Liabilitas</option>
                        <option value="equity" {{ old('type')=='equity'?'selected':'' }}>Ekuitas</option>
                        <option value="revenue" {{ old('type')=='revenue'?'selected':'' }}>Pendapatan</option>
                        <option value="expense" {{ old('type')=='expense'?'selected':'' }}>Beban</option>
                    </select>
                </div>
                <div><label class="form-label">Normal Balance</label>
                    <select name="normal_balance" class="form-select" required>
                        <option value="debit" {{ old('normal_balance')=='debit'?'selected':'' }}>Debit</option>
                        <option value="credit" {{ old('normal_balance')=='credit'?'selected':'' }}>Kredit</option>
                    </select>
                </div>
                <div><label class="form-label">Saldo Awal</label><input type="number" name="opening_balance" class="form-input" value="{{ old('opening_balance',0) }}"></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('master.accounts.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

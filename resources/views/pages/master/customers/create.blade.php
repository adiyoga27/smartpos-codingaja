@extends('layouts.app')
@section('title', 'Tambah Customer')
@section('breadcrumb')
    <a href="{{ route('master.customers.index') }}" class="text-slate-400 hover:text-slate-600">Customer</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Tambah</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Customer</div>
    <div class="card-body">
        <form action="{{ route('master.customers.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" value="{{ old('code', $code) }}" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name') }}" required></div>
                <div><label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required>
                        <option value="retail" {{ old('type')=='retail'?'selected':'' }}>Retail</option>
                        <option value="wholesale" {{ old('type')=='wholesale'?'selected':'' }}>Reseller</option>
                    </select>
                </div>
                <div><label class="form-label">Telepon</label><input type="text" name="phone" class="form-input" value="{{ old('phone') }}"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email') }}"></div>
                <div><label class="form-label">NPWP</label><input type="text" name="npwp" class="form-input" value="{{ old('npwp') }}"></div>
                <div class="md:col-span-2"><label class="form-label">Alamat</label><textarea name="address" class="form-input" rows="2">{{ old('address') }}</textarea></div>
                <div><label class="form-label">Limit Kredit</label><input type="number" name="credit_limit" class="form-input" value="{{ old('credit_limit',0) }}"></div>
                <div><label class="form-label">Saldo Awal</label><input type="number" name="opening_balance" class="form-input" value="{{ old('opening_balance',0) }}"></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('master.customers.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

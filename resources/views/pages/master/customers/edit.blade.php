@extends('layouts.app')
@section('title', 'Edit Customer')
@section('breadcrumb')
    <a href="{{ route('master.customers.index') }}" class="text-slate-400 hover:text-slate-600">Customer</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Customer</div>
    <div class="card-body">
        <form action="{{ route('master.customers.update', $customer) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" value="{{ old('code', $customer->code) }}" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name', $customer->name) }}" required></div>
                <div><label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required>
                        <option value="retail" {{ old('type', $customer->type)=='retail'?'selected':'' }}>Retail</option>
                        <option value="wholesale" {{ old('type', $customer->type)=='wholesale'?'selected':'' }}>Reseller</option>
                    </select>
                </div>
                <div><label class="form-label">Telepon</label><input type="text" name="phone" class="form-input" value="{{ old('phone', $customer->phone) }}"></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email', $customer->email) }}"></div>
                <div><label class="form-label">NPWP</label><input type="text" name="npwp" class="form-input" value="{{ old('npwp', $customer->npwp) }}"></div>
                <div class="md:col-span-2"><label class="form-label">Alamat</label><textarea name="address" class="form-input" rows="2">{{ old('address', $customer->address) }}</textarea></div>
                <div><label class="form-label">Limit Kredit</label><input type="number" name="credit_limit" class="form-input" value="{{ old('credit_limit', $customer->credit_limit) }}"></div>
                <div><label class="form-label">Saldo Awal</label><input type="number" name="opening_balance" class="form-input" value="{{ old('opening_balance', $customer->opening_balance) }}"></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                    <a href="{{ route('master.customers.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

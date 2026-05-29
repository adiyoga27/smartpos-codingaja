@extends('layouts.app')
@section('title', 'Tambah Metode Pembayaran')
@section('breadcrumb')
    <a href="{{ route('master.payment_methods.index') }}" class="text-slate-400 hover:text-slate-600">Metode Pembayaran</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Tambah</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Metode Pembayaran</div>
    <div class="card-body">
        <form action="{{ route('master.payment_methods.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" value="{{ old('code') }}" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name') }}" required></div>
                <div><label class="form-label">Akun Terkait</label>
                    <select name="account_id" class="form-select">
                        <option value="">- Pilih Akun -</option>
                        @foreach($accounts as $id => $name)
                            <option value="{{ $id }}" {{ old('account_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="form-label">Efek Pada Akun</label>
                    <select name="effect" class="form-select" required>
                        <option value="add" {{ old('effect') == 'add' ? 'selected' : '' }}>+ Menambah Saldo Akun</option>
                        <option value="subtract" {{ old('effect') == 'subtract' ? 'selected' : '' }}>- Mengurangi Saldo Akun</option>
                    </select>
                </div>
                <div class="md:col-span-2"><label class="form-label">Deskripsi</label><textarea name="description" class="form-input" rows="3">{{ old('description') }}</textarea></div>
                <div><label class="form-check"><input type="hidden" name="is_available_pos" value="0"><input type="checkbox" name="is_available_pos" value="1" class="form-check-input" {{ old('is_available_pos', true) ? 'checked' : '' }}> Tersedia di POS</label></div>
                <div><label class="form-check"><input type="hidden" name="is_available_purchase" value="0"><input type="checkbox" name="is_available_purchase" value="1" class="form-check-input" {{ old('is_available_purchase', true) ? 'checked' : '' }}> Tersedia di Pembelian</label></div>
                <div><label class="form-check"><input type="hidden" name="is_credit" value="0"><input type="checkbox" name="is_credit" value="1" class="form-check-input" {{ old('is_credit') ? 'checked' : '' }}> Kredit (Hutang/Piutang)</label></div>
                <div><label class="form-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}> Aktif</label></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('master.payment_methods.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

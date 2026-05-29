@extends('layouts.app')
@section('title', 'Edit Metode Pembayaran')
@section('breadcrumb')
    <a href="{{ route('master.payment_methods.index') }}" class="text-slate-400 hover:text-slate-600">Metode Pembayaran</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Metode Pembayaran</div>
    <div class="card-body">
        <form action="{{ route('master.payment_methods.update', $paymentMethod) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="form-label">Kode</label><input type="text" name="code" class="form-input" value="{{ old('code', $paymentMethod->code) }}" required></div>
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name', $paymentMethod->name) }}" required></div>
                <div><label class="form-label">Akun Terkait</label>
                    <select name="account_id" class="form-select">
                        <option value="">- Pilih Akun -</option>
                        @foreach($accounts as $id => $name)
                            <option value="{{ $id }}" {{ old('account_id', $paymentMethod->account_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="form-label">Efek Pada Akun</label>
                    <select name="effect" class="form-select" required>
                        <option value="add" {{ old('effect', $paymentMethod->effect) == 'add' ? 'selected' : '' }}>+ Menambah Saldo Akun</option>
                        <option value="subtract" {{ old('effect', $paymentMethod->effect) == 'subtract' ? 'selected' : '' }}>- Mengurangi Saldo Akun</option>
                    </select>
                </div>
                <div class="md:col-span-2"><label class="form-label">Deskripsi</label><textarea name="description" class="form-input" rows="3">{{ old('description', $paymentMethod->description) }}</textarea></div>
                <div><label class="form-check"><input type="hidden" name="is_available_pos" value="0"><input type="checkbox" name="is_available_pos" value="1" class="form-check-input" {{ old('is_available_pos', $paymentMethod->is_available_pos) ? 'checked' : '' }}> Tersedia di POS</label></div>
                <div><label class="form-check"><input type="hidden" name="is_available_purchase" value="0"><input type="checkbox" name="is_available_purchase" value="1" class="form-check-input" {{ old('is_available_purchase', $paymentMethod->is_available_purchase) ? 'checked' : '' }}> Tersedia di Pembelian</label></div>
                <div><label class="form-check"><input type="hidden" name="is_credit" value="0"><input type="checkbox" name="is_credit" value="1" class="form-check-input" {{ old('is_credit', $paymentMethod->is_credit) ? 'checked' : '' }}> Kredit (Hutang/Piutang)</label></div>
                <div><label class="form-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $paymentMethod->is_active) ? 'checked' : '' }}> Aktif</label></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                    <a href="{{ route('master.payment_methods.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Edit Pajak')
@section('breadcrumb')
    <a href="{{ route('master.taxes.index') }}" class="text-slate-400 hover:text-slate-600">Pajak</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Pajak</div>
    <div class="card-body">
        <form action="{{ route('master.taxes.update', $tax) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-input @error('code') is-invalid @enderror" value="{{ old('code', $tax->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $tax->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Rate (%)</label>
                    <input type="number" name="rate" step="0.01" class="form-input @error('rate') is-invalid @enderror" value="{{ old('rate', $tax->rate) }}" required>
                    @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required>
                        <option value="ppn" {{ old('type', $tax->type) == 'ppn' ? 'selected' : '' }}>PPN (Pajak Pertambahan Nilai)</option>
                        <option value="pph" {{ old('type', $tax->type) == 'pph' ? 'selected' : '' }}>PPh (Pajak Penghasilan)</option>
                        <option value="other" {{ old('type', $tax->type) == 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Default Untuk</label>
                    <select name="applies_to" class="form-select" required>
                        <option value="all" {{ old('applies_to', $tax->applies_to) == 'all' ? 'selected' : '' }}>Semua Transaksi</option>
                        <option value="sale" {{ old('applies_to', $tax->applies_to) == 'sale' ? 'selected' : '' }}>POS / Penjualan</option>
                        <option value="purchase" {{ old('applies_to', $tax->applies_to) == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-input" rows="2">{{ old('description', $tax->description) }}</textarea>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $tax->is_active) ? 'checked' : '' }}>
                        <label>Aktif</label>
                    </div>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-warning btn-md"><i class="bi bi-pencil mr-1"></i>Update</button>
                    <a href="{{ route('master.taxes.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

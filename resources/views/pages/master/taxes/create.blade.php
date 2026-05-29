@extends('layouts.app')
@section('title', 'Tambah Pajak')
@section('breadcrumb')
    <a href="{{ route('master.taxes.index') }}" class="text-slate-400 hover:text-slate-600">Pajak</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Tambah</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Pajak</div>
    <div class="card-body">
        <form action="{{ route('master.taxes.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-input @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="PPN" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Pajak Pertambahan Nilai" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Rate (%)</label>
                    <input type="number" name="rate" step="0.01" class="form-input @error('rate') is-invalid @enderror" value="{{ old('rate', 11) }}" required>
                    @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select" required>
                        <option value="ppn" {{ old('type') == 'ppn' ? 'selected' : '' }}>PPN (Pajak Pertambahan Nilai)</option>
                        <option value="pph" {{ old('type') == 'pph' ? 'selected' : '' }}>PPh (Pajak Penghasilan)</option>
                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Default Untuk</label>
                    <select name="applies_to" class="form-select" required>
                        <option value="all" {{ old('applies_to') == 'all' ? 'selected' : '' }}>Semua Transaksi</option>
                        <option value="sale" {{ old('applies_to') == 'sale' ? 'selected' : '' }}>POS / Penjualan</option>
                        <option value="purchase" {{ old('applies_to') == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-input" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <label>Aktif</label>
                    </div>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('master.taxes.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

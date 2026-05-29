@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('breadcrumb')
    <a href="{{ route('master.products.index') }}" class="text-slate-400 hover:text-slate-600">Produk</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Tambah</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Produk</div>
    <div class="card-body">
        <form action="{{ route('master.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-input @error('code') is-invalid @enderror" value="{{ old('code', $code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-input" value="{{ old('barcode') }}">
                </div>
                <div>
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select"><option value="">- Pilih -</option>@foreach($categories as $id=>$name)<option value="{{ $id }}" {{ old('category_id')==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select"><option value="">- Pilih -</option>@foreach($suppliers as $id=>$name)<option value="{{ $id }}" {{ old('supplier_id')==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit" class="form-input" value="{{ old('unit','PCS') }}" required>
                </div>
                <div>
                    <label class="form-label">Harga Beli</label>
                    <input type="number" name="purchase_price" class="form-input @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price',0) }}" required>
                    @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Harga Jual</label>
                    <input type="number" name="selling_price" class="form-input @error('selling_price') is-invalid @enderror" value="{{ old('selling_price',0) }}" required>
                    @error('selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Harga Grosir</label>
                    <input type="number" name="wholesale_price" class="form-input" value="{{ old('wholesale_price',0) }}">
                </div>
                <div>
                    <label class="form-label">Stok Awal</label>
                    <input type="number" name="stock" class="form-input" value="{{ old('stock',0) }}" required>
                </div>
                <div>
                    <label class="form-label">Stok Minimum</label>
                    <input type="number" name="min_stock" class="form-input" value="{{ old('min_stock',0) }}" required>
                </div>
                <div>
                    <label class="form-label">Stok Maksimum</label>
                    <input type="number" name="max_stock" class="form-input" value="{{ old('max_stock',0) }}">
                </div>
                <div>
                    <label class="form-label">Foto</label>
                    <input type="file" name="photo" class="form-input" accept="image/*">
                </div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <label>Aktif</label>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-input" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('master.products.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

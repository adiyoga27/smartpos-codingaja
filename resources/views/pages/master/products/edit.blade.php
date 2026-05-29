@extends('layouts.app')
@section('title', 'Edit Produk')
@section('breadcrumb')
    <a href="{{ route('master.products.index') }}" class="text-slate-400 hover:text-slate-600">Produk</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Produk</div>
    <div class="card-body">
        <form action="{{ route('master.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-input" value="{{ old('code', $product->code) }}" required>
                </div>
                <div>
                    <label class="form-label">Barcode</label>
                    <input type="text" name="barcode" class="form-input" value="{{ old('barcode', $product->barcode) }}">
                </div>
                <div>
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $product->name) }}" required>
                </div>
                <div>
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select"><option value="">- Pilih -</option>@foreach($categories as $id=>$name)<option value="{{ $id }}" {{ old('category_id', $product->category_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select"><option value="">- Pilih -</option>@foreach($suppliers as $id=>$name)<option value="{{ $id }}" {{ old('supplier_id', $product->supplier_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit" class="form-input" value="{{ old('unit', $product->unit) }}" required>
                </div>
                <div>
                    <label class="form-label">Harga Beli</label>
                    <input type="number" name="purchase_price" class="form-input" value="{{ old('purchase_price', $product->purchase_price) }}" required>
                </div>
                <div>
                    <label class="form-label">Harga Jual</label>
                    <input type="number" name="selling_price" class="form-input" value="{{ old('selling_price', $product->selling_price) }}" required>
                </div>
                <div>
                    <label class="form-label">Harga Grosir</label>
                    <input type="number" name="wholesale_price" class="form-input" value="{{ old('wholesale_price', $product->wholesale_price) }}">
                </div>
                <div>
                    <label class="form-label">Stok</label>
                    <input type="number" name="stock" class="form-input" value="{{ old('stock', $product->stock) }}" required>
                </div>
                <div>
                    <label class="form-label">Stok Minimum</label>
                    <input type="number" name="min_stock" class="form-input" value="{{ old('min_stock', $product->min_stock) }}" required>
                </div>
                <div>
                    <label class="form-label">Stok Maksimum</label>
                    <input type="number" name="max_stock" class="form-input" value="{{ old('max_stock', $product->max_stock) }}">
                </div>
                <div>
                    <label class="form-label">Foto</label>
                    <input type="file" name="photo" class="form-input" accept="image/*">
                    @if($product->photo)
                        <div class="mt-2"><img src="{{ asset('storage/'.$product->photo) }}" class="rounded-lg border border-slate-200" style="height:80px;"></div>
                    @endif
                </div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label>Aktif</label>
                    </div>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                    <a href="{{ route('master.products.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

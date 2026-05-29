@extends('layouts.app')
@section('title', 'Edit Kategori')
@section('breadcrumb')
    <a href="{{ route('master.categories.index') }}" class="text-slate-400 hover:text-slate-600">Kategori</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Kategori Produk</div>
    <div class="card-body">
        <form action="{{ route('master.categories.update', $category) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-input @error('code') is-invalid @enderror" value="{{ old('code', $category->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-input" rows="2">{{ old('description', $category->description) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Icon</label>
                    <input type="text" name="icon" class="form-input" value="{{ old('icon', $category->icon) }}">
                </div>
                <div class="flex items-center">
                    <div class="flex items-center gap-2 mt-3">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                        <label>Aktif</label>
                    </div>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                    <a href="{{ route('master.categories.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

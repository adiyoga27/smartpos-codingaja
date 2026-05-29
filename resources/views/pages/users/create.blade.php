@extends('layouts.app')
@section('title', 'Tambah User')
@section('breadcrumb')
    <a href="{{ route('users.index') }}" class="text-slate-400 hover:text-slate-600">User</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Tambah</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah User</div>
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name') }}" required></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email') }}" required></div>
                <div><label class="form-label">Password</label><input type="password" name="password" class="form-input" required></div>
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">- Pilih Role -</option>
                        @foreach($roles as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

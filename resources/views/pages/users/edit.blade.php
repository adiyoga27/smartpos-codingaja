@extends('layouts.app')
@section('title', 'Edit User')
@section('breadcrumb')
    <a href="{{ route('users.index') }}" class="text-slate-400 hover:text-slate-600">User</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit User</div>
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required></div>
                <div><label class="form-label">Password (kosongkan jika tidak diubah)</label><input type="password" name="password" class="form-input"></div>
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">- Pilih Role -</option>
                        @foreach($roles as $name)<option value="{{ $name }}" {{ $user->hasRole($name)?'selected':'' }}>{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

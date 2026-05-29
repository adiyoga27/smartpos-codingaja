@extends('layouts.app')
@section('title', 'Edit Role')
@section('breadcrumb')
    <a href="{{ route('roles.index') }}" class="text-slate-400 hover:text-slate-600">Role</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Edit Role</div>
    <div class="card-body">
        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div><label class="form-label">Nama Role</label><input type="text" name="name" class="form-input" value="{{ old('name', $role->name) }}" required></div>
            </div>
            <h6 class="mb-3">Permission</h6>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($permissions as $module => $perms)
                <div>
                    <div class="card h-full">
                        <div class="card-header bg-slate-50 text-sm font-bold">{{ ucfirst($module) }}</div>
                        <div class="card-body p-2">
                            @foreach($perms as $perm)
                            <div class="flex items-center gap-2 py-0.5">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="perm_{{ $perm->id }}" {{ in_array($perm->name, $rolePermissions) ? 'checked' : '' }}>
                                <label class="text-sm" for="perm_{{ $perm->id }}">{{ ucfirst(explode('_', $perm->name)[0]) }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui</button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

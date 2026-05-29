@extends('layouts.app')
@section('title', 'Profil')
@section('breadcrumb')
    <span class="text-slate-600">Profil</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Profil Pengguna</div>
    <div class="card-body">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="form-label">Nama</label><input type="text" name="name" class="form-input" value="{{ old('name', auth()->user()->name) }}" required></div>
                <div><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email', auth()->user()->email) }}" required></div>
                <div><label class="form-label">Foto</label><input type="file" name="photo" class="form-input" accept="image/*"></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Perbarui Profil</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

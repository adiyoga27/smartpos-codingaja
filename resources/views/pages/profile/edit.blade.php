@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="card">
        <div class="card-header">Informasi Profil</div>
        <div class="card-body">
            <p class="text-sm text-slate-500 mb-4">Perbarui informasi profil dan alamat email Anda.</p>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-input" value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Foto Profil</label>
                        <input type="file" name="photo" class="form-input" accept="image/*">
                        @if(auth()->user()->photo)
                            <div class="mt-2"><img src="{{ asset('storage/'.auth()->user()->photo) }}" class="rounded-lg h-20"></div>
                        @endif
                        @error('photo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @if(session('status') === 'profile-updated')
                        <p class="col-span-full text-sm text-emerald-600">Profil berhasil diperbarui.</p>
                    @endif
                    <div class="col-span-full">
                        <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i> Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Ubah Password</div>
        <div class="card-body">
            <p class="text-sm text-slate-500 mb-4">Gunakan password yang panjang dan acak agar akun tetap aman.</p>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-input" autocomplete="current-password" required>
                        @error('current_password', 'updatePassword')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div></div>
                    <div>
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-input" autocomplete="new-password" required>
                        @error('password', 'updatePassword')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password" required>
                        @error('password_confirmation', 'updatePassword')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @if(session('status') === 'password-updated')
                        <p class="col-span-full text-sm text-emerald-600">Password berhasil diubah.</p>
                    @endif
                    <div class="col-span-full">
                        <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-shield-lock mr-1"></i> Ubah Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-red-200">
        <div class="card-header text-red-600">Hapus Akun</div>
        <div class="card-body">
            <p class="text-sm text-slate-500 mb-4">Setelah akun dihapus, semua data akan dihapus permanen. Pastikan Anda telah mencadangkan data penting.</p>
            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan.')">
                @csrf @method('DELETE')
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Anda</label>
                    <input type="password" name="password" class="form-input" placeholder="Masukkan password untuk konfirmasi" required>
                    @error('password', 'userDeletion')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-danger btn-md"><i class="bi bi-trash mr-1"></i> Hapus Akun</button>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.guest')
@section('title', 'Daftar Akun')
@section('content')
<div class="w-full max-w-4xl flex rounded-2xl overflow-hidden shadow-2xl bg-white min-h-[600px]">
    <div class="hidden md:flex w-1/2 flex-col items-center justify-center p-10 text-white relative overflow-hidden"
         style="background: linear-gradient(135deg, #2563EB 0%, #4f46e5 50%, #7c3aed 100%);">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-20 -right-20 w-60 h-60 bg-white rounded-full"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white rounded-full"></div>
        </div>
        <div class="relative z-10 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <i class="bi bi-shop-window text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight mb-2">SmartPOS</h1>
            <p class="text-blue-100 text-sm max-w-xs mx-auto leading-relaxed">Kelola bisnis Anda dengan sistem POS modern dan terintegrasi.</p>
        </div>
    </div>

    <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center">
        <div class="max-w-sm mx-auto w-full">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-800">Buat Akun Baru</h2>
                <p class="text-slate-500 text-sm mt-1">Daftar untuk mulai menggunakan SmartPOS</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" id="name"
                           class="form-input @error('name') is-invalid @enderror"
                           placeholder="Nama lengkap Anda" value="{{ old('name') }}" required>
                    @error('name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username"
                           class="form-input @error('username') is-invalid @enderror"
                           placeholder="Username Anda" value="{{ old('username') }}" required>
                    @error('username')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" name="email" id="email"
                           class="form-input @error('email') is-invalid @enderror"
                           placeholder="email@example.com" value="{{ old('email') }}" required>
                    @error('email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" name="password" id="password"
                           class="form-input @error('password') is-invalid @enderror"
                           placeholder="Minimal 8 karakter" required>
                    @error('password')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-input"
                           placeholder="Ulangi kata sandi" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <i class="bi bi-person-plus"></i> Daftar
                </button>
            </form>

            <p class="text-center mt-6 text-sm text-slate-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-medium">Masuk</a>
            </p>
        </div>
    </div>
</div>
@endsection

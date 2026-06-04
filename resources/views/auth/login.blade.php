@extends('layouts.guest')
@section('title', 'Masuk')
@section('content')
<div class="w-full max-w-4xl flex rounded-2xl overflow-hidden shadow-2xl bg-white min-h-[550px]">
    <!-- Left Brand Panel -->
    <div class="hidden md:flex w-1/2 flex-col items-center justify-center p-10 text-white relative overflow-hidden"
         style="background: linear-gradient(135deg, #2563EB 0%, #4f46e5 50%, #7c3aed 100%);">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-20 -right-20 w-60 h-60 bg-white rounded-full"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-white rounded-full"></div>
        </div>
        <div class="relative z-10 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <i class="bi bi-shop-window text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight mb-2">SmartPOS</h1>
            <p class="text-blue-100 text-sm max-w-xs mx-auto leading-relaxed">Sistem Point of Sale modern untuk mengelola bisnis Anda dengan lebih efisien.</p>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center">
        <div class="max-w-sm mx-auto w-full">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-800">Selamat Datang</h2>
                <p class="text-slate-500 text-sm mt-1">Masuk ke akun Anda untuk melanjutkan</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success mb-6">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="login" class="form-label">Username atau Email</label>
                    <input type="text" name="login" id="login"
                           class="form-input @error('login') is-invalid @enderror"
                           placeholder="username atau email@example.com" value="{{ old('login') }}" required autofocus>
                    @error('login')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" name="password" id="password"
                           class="form-input @error('password') is-invalid @enderror"
                           placeholder="Masukkan kata sandi" required>
                    @error('password')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" id="remember"
                               class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-slate-600">Ingat saya</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lupa password?</a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>


        </div>
    </div>
</div>
@endsection

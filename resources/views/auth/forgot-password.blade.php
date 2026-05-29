@extends('layouts.guest')
@section('title', 'Reset Password')
@section('content')
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-amber-50 flex items-center justify-center">
                <i class="bi bi-key text-2xl text-amber-600"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800">Lupa Password?</h2>
            <p class="text-slate-500 text-sm mt-2">Masukkan email Anda, kami akan mengirimkan link reset password.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-6">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email"
                       class="form-input @error('email') is-invalid @enderror"
                       placeholder="email@example.com" value="{{ old('email') }}" required>
                @error('email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-md w-full">
                <i class="bi bi-send"></i> Kirim Link Reset
            </button>
        </form>

        <div class="text-center mt-5">
            <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-primary-600 transition-colors">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke login
            </a>
        </div>
    </div>
</div>
@endsection

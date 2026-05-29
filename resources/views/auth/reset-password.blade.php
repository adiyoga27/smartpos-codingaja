@extends('layouts.guest')
@section('title', 'Reset Password')
@section('content')
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-primary-50 flex items-center justify-center">
                <i class="bi bi-shield-lock text-2xl text-primary-600"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800">Reset Kata Sandi</h2>
            <p class="text-slate-500 text-sm mt-2">Masukkan kata sandi baru untuk akun Anda.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email"
                       class="form-input @error('email') is-invalid @enderror"
                       placeholder="email@example.com" value="{{ old('email', $request->email) }}" required>
                @error('email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="form-label">Kata Sandi Baru</label>
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
            <button type="submit" class="btn btn-primary btn-md w-full">
                <i class="bi bi-check-lg"></i> Reset Password
            </button>
        </form>
    </div>
</div>
@endsection

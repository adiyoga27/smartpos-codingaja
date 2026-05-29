@extends('layouts.guest')
@section('title', 'Konfirmasi Password')
@section('content')
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-amber-50 flex items-center justify-center">
                <i class="bi bi-shield-check text-2xl text-amber-600"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800">Konfirmasi Password</h2>
            <p class="text-slate-500 text-sm mt-2">Ini adalah area aman. Silakan konfirmasi password Anda sebelum melanjutkan.</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf
            <div>
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" name="password" id="password"
                       class="form-input @error('password') is-invalid @enderror"
                       placeholder="Masukkan kata sandi" required autocomplete="current-password">
                @if ($errors->has('password'))
                    <p class="mt-1.5 text-xs text-red-500">{{ $errors->first('password') }}</p>
                @endif
            </div>
            <button type="submit" class="btn btn-primary btn-md w-full">
                Konfirmasi
            </button>
        </form>
    </div>
</div>
@endsection

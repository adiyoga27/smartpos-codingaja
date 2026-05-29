@extends('layouts.guest')
@section('title', 'Verifikasi Email')
@section('content')
<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-primary-50 flex items-center justify-center">
            <i class="bi bi-envelope-check text-3xl text-primary-600"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-800 mb-2">Verifikasi Email</h2>
        <p class="text-slate-500 text-sm leading-relaxed mb-6">Terima kasih telah mendaftar! Silakan verifikasi email Anda dengan mengklik link yang kami kirim.</p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-6 text-left">Link verifikasi baru telah dikirim ke email Anda.</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-primary btn-md w-full">
                <i class="bi bi-arrow-repeat"></i> Kirim Ulang Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">Keluar</button>
        </form>
    </div>
</div>
@endsection

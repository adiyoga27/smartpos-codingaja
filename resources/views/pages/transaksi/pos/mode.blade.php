@extends('layouts.pos')
@section('title', 'POS Kasir')
@section('content')
<div class="h-full flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center">
                <i class="bi bi-cart3 text-3xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800">POS Kasir</h2>
            <p class="text-slate-400 mt-1">Pilih mode transaksi untuk memulai</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('pos.kasir', ['mode' => 'toko']) }}"
               class="group relative bg-white rounded-2xl border-2 border-slate-200 hover:border-blue-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-200 p-6 text-center overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                        <i class="bi bi-shop text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Toko</h3>
                    <p class="text-xs text-slate-400">Harga retail / eceran</p>
                </div>
            </a>

            <a href="{{ route('pos.kasir', ['mode' => 'reseller']) }}"
               class="group relative bg-white rounded-2xl border-2 border-slate-200 hover:border-purple-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-200 p-6 text-center overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                        <i class="bi bi-people-fill text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Reseller</h3>
                    <p class="text-xs text-slate-400">Harga grosir untuk reseller</p>
                </div>
            </a>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">
                <i class="bi bi-arrow-left mr-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Laporan')
@section('breadcrumb')
    <span class="text-slate-600">Laporan</span>
@endsection
@section('content')
<div class="mb-6">
    <h4 class="font-bold mb-1">Laporan</h4>
    <p class="text-sm text-slate-400">Pilih jenis laporan yang ingin Anda lihat</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <a href="{{ route('laporan.pembelian') }}" class="card p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 border-t-4 border-t-blue-500 group">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition-colors">
                <i class="bi bi-cart-check text-2xl text-blue-600"></i>
            </div>
            <div>
                <h6 class="font-semibold text-slate-800 mb-1">Pembelian</h6>
                <p class="text-xs text-slate-400 mb-3">Laporan transaksi pembelian barang</p>
                <span class="text-xs font-medium text-blue-600 group-hover:text-blue-700">Lihat Laporan <i class="bi bi-arrow-right text-[10px]"></i></span>
            </div>
        </div>
    </a>

    <a href="{{ route('laporan.penjualan') }}" class="card p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 border-t-4 border-t-emerald-500 group">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 transition-colors">
                <i class="bi bi-cash-coin text-2xl text-emerald-600"></i>
            </div>
            <div>
                <h6 class="font-semibold text-slate-800 mb-1">Penjualan</h6>
                <p class="text-xs text-slate-400 mb-3">Laporan transaksi penjualan POS</p>
                <span class="text-xs font-medium text-emerald-600 group-hover:text-emerald-700">Lihat Laporan <i class="bi bi-arrow-right text-[10px]"></i></span>
            </div>
        </div>
    </a>

    <a href="{{ route('laporan.hutang') }}" class="card p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 border-t-4 border-t-orange-500 group">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-100 transition-colors">
                <i class="bi bi-arrow-up-circle text-2xl text-orange-600"></i>
            </div>
            <div>
                <h6 class="font-semibold text-slate-800 mb-1">Hutang</h6>
                <p class="text-xs text-slate-400 mb-3">Laporan hutang kepada supplier</p>
                <span class="text-xs font-medium text-orange-600 group-hover:text-orange-700">Lihat Laporan <i class="bi bi-arrow-right text-[10px]"></i></span>
            </div>
        </div>
    </a>

    <a href="{{ route('laporan.piutang') }}" class="card p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 border-t-4 border-t-red-500 group">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center shrink-0 group-hover:bg-red-100 transition-colors">
                <i class="bi bi-arrow-down-circle text-2xl text-red-600"></i>
            </div>
            <div>
                <h6 class="font-semibold text-slate-800 mb-1">Piutang</h6>
                <p class="text-xs text-slate-400 mb-3">Laporan piutang dari customer</p>
                <span class="text-xs font-medium text-red-600 group-hover:text-red-700">Lihat Laporan <i class="bi bi-arrow-right text-[10px]"></i></span>
            </div>
        </div>
    </a>

    <a href="{{ route('laporan.arus_kas') }}" class="card p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 border-t-4 border-t-violet-500 group">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center shrink-0 group-hover:bg-violet-100 transition-colors">
                <i class="bi bi-bank text-2xl text-violet-600"></i>
            </div>
            <div>
                <h6 class="font-semibold text-slate-800 mb-1">Arus Kas</h6>
                <p class="text-xs text-slate-400 mb-3">Laporan aliran kas masuk & keluar</p>
                <span class="text-xs font-medium text-violet-600 group-hover:text-violet-700">Lihat Laporan <i class="bi bi-arrow-right text-[10px]"></i></span>
            </div>
        </div>
    </a>

    <a href="{{ route('laporan.stok') }}" class="card p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 border-t-4 border-t-sky-500 group">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center shrink-0 group-hover:bg-sky-100 transition-colors">
                <i class="bi bi-box-seam text-2xl text-sky-600"></i>
            </div>
            <div>
                <h6 class="font-semibold text-slate-800 mb-1">Stok</h6>
                <p class="text-xs text-slate-400 mb-3">Laporan stok & mutasi barang</p>
                <span class="text-xs font-medium text-sky-600 group-hover:text-sky-700">Lihat Laporan <i class="bi bi-arrow-right text-[10px]"></i></span>
            </div>
        </div>
    </a>
</div>
@endsection

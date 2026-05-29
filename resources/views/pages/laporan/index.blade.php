@extends('layouts.app')
@section('title', 'Laporan')
@section('breadcrumb')
    <span class="text-slate-600">Laporan</span>
@endsection
@section('content')
<h4 class="font-bold mb-4">Laporan</h4>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
    <div class="card text-center p-4">
        <div class="stat-icon"><i class="bi bi-cart-check text-4xl"></i></div>
        <h6>Pembelian</h6>
        <a href="{{ route('laporan.pembelian') }}" class="btn btn-secondary btn-sm mt-2">Lihat Laporan</a>
    </div>
    <div class="card text-center p-4">
        <div class="stat-icon"><i class="bi bi-cash-coin text-4xl"></i></div>
        <h6>Penjualan</h6>
        <a href="{{ route('laporan.penjualan') }}" class="btn btn-secondary btn-sm mt-2">Lihat Laporan</a>
    </div>
    <div class="card text-center p-4">
        <div class="stat-icon"><i class="bi bi-arrow-up-circle text-4xl"></i></div>
        <h6>Hutang</h6>
        <a href="{{ route('laporan.hutang') }}" class="btn btn-secondary btn-sm mt-2">Lihat Laporan</a>
    </div>
    <div class="card text-center p-4">
        <div class="stat-icon"><i class="bi bi-arrow-down-circle text-4xl"></i></div>
        <h6>Piutang</h6>
        <a href="{{ route('laporan.piutang') }}" class="btn btn-secondary btn-sm mt-2">Lihat Laporan</a>
    </div>
    <div class="card text-center p-4">
        <div class="stat-icon"><i class="bi bi-bank text-4xl"></i></div>
        <h6>Arus Kas</h6>
        <a href="{{ route('laporan.arus_kas') }}" class="btn btn-secondary btn-sm mt-2">Lihat Laporan</a>
    </div>
    <div class="card text-center p-4">
        <div class="stat-icon"><i class="bi bi-box-seam text-4xl"></i></div>
        <h6>Stok</h6>
        <a href="{{ route('laporan.stok') }}" class="btn btn-secondary btn-sm mt-2">Lihat Laporan</a>
    </div>
</div>
@endsection

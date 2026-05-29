@extends('layouts.app')
@section('title', 'Laporan Arus Kas')
@section('breadcrumb')
    <a href="{{ route('laporan.index') }}" class="text-slate-400 hover:text-slate-600">Laporan</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Arus Kas</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Laporan Arus Kas</h4>
</div>
<div class="card">
    <div class="card-body text-center text-slate-400">
        <i class="bi bi-bank text-4xl mb-3"></i>
        <p>Fitur laporan arus kas akan ditampilkan berdasarkan transaksi kas yang tercatat.</p>
    </div>
</div>
@endsection

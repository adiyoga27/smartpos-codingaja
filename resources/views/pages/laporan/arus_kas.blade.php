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
    <form method="GET" class="flex gap-2 items-end">
        <div>
            <label class="form-label text-xs">Dari</label>
            <input type="date" name="from" class="form-input" value="{{ $from }}">
        </div>
        <div>
            <label class="form-label text-xs">Sampai</label>
            <input type="date" name="to" class="form-input" value="{{ $to }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="card border-success border-start-3">
        <div class="card-body p-4">
            <div class="text-sm text-slate-500 uppercase tracking-wide mb-1">Kas Masuk</div>
            <div class="text-xl font-bold text-success">{{ formatRupiah($totalIn) }}</div>
        </div>
    </div>
    <div class="card border-danger border-start-3">
        <div class="card-body p-4">
            <div class="text-sm text-slate-500 uppercase tracking-wide mb-1">Kas Keluar</div>
            <div class="text-xl font-bold text-danger">{{ formatRupiah($totalOut) }}</div>
        </div>
    </div>
    <div class="card border-{{ $netCash >= 0 ? 'primary' : 'danger' }} border-start-3">
        <div class="card-body p-4">
            <div class="text-sm text-slate-500 uppercase tracking-wide mb-1">Saldo Bersih</div>
            <div class="text-xl font-bold {{ $netCash >= 0 ? 'text-primary' : 'text-danger' }}">{{ formatRupiah($netCash) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-slate-50">
        <h5 class="mb-0 text-sm font-semibold">Rincian Arus Kas</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Keterangan</th>
                    <th class="text-end">Masuk</th>
                    <th class="text-end">Keluar</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fw-semibold bg-success-light">
                    <td>Kas Masuk</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="ps-4">Penjualan Tunai</td>
                    <td class="text-end text-success">{{ formatRupiah($saleCashIn) }}</td>
                    <td class="text-end"></td>
                </tr>
                <tr>
                    <td class="ps-4">Transaksi Kas Masuk</td>
                    <td class="text-end text-success">{{ formatRupiah($cashTransactionIn) }}</td>
                    <td class="text-end"></td>
                </tr>
                <tr class="fw-semibold bg-danger-light">
                    <td>Kas Keluar</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="ps-4">Transaksi Kas Keluar</td>
                    <td class="text-end"></td>
                    <td class="text-end text-danger">{{ formatRupiah($cashTransactionOut) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-bold bg-slate-100">
                    <td>Total</td>
                    <td class="text-end">{{ formatRupiah($totalIn) }}</td>
                    <td class="text-end">{{ formatRupiah($totalOut) }}</td>
                </tr>
                <tr class="fw-bold">
                    <td>Saldo Bersih</td>
                    <td colspan="2" class="text-end {{ $netCash >= 0 ? 'text-primary' : 'text-danger' }}">{{ formatRupiah($netCash) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

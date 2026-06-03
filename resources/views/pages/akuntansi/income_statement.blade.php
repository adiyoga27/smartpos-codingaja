@extends('layouts.app')
@section('title', 'Laba Rugi')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('akuntansi.income_statement') }}">Akuntansi</a></li>
    <li class="breadcrumb-item active">Laba Rugi</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Laba Rugi</h4>
    <form action="{{ route('akuntansi.income_statement') }}" method="GET" class="flex gap-2">
        <input type="date" name="from" class="form-input text-sm" value="{{ $from }}">
        <span class="self-center text-slate-400">s/d</span>
        <input type="date" name="to" class="form-input text-sm" value="{{ $to }}">
        <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    </form>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <div class="card">
            <div class="card-header">Pendapatan</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Akun</th><th class="text-right">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($revenues as $acc)
                        <tr><td>{{ $acc->code }} - {{ $acc->name }}</td><td class="text-right">{{ formatRupiah($acc->total_credit ?? 0) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold"><td>Total Pendapatan</td><td class="text-right">{{ formatRupiah($totalRevenue) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-header">Beban</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Akun</th><th class="text-right">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($expenses as $acc)
                        <tr><td>{{ $acc->code }} - {{ $acc->name }}</td><td class="text-right">{{ formatRupiah($acc->total_debit ?? 0) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold"><td>Total Beban</td><td class="text-right">{{ formatRupiah($totalExpense) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="card mt-3">
    <div class="card-body">
        <div class="flex justify-between items-center">
            <span class="text-lg font-bold">Laba / Rugi Bersih</span>
            <span class="text-xl font-extrabold {{ $totalRevenue - $totalExpense >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                {{ formatRupiah($totalRevenue - $totalExpense) }}
                @if($totalRevenue - $totalExpense >= 0)
                    <span class="text-sm font-normal">(Laba)</span>
                @else
                    <span class="text-sm font-normal">(Rugi)</span>
                @endif
            </span>
        </div>
    </div>
</div>
@endsection

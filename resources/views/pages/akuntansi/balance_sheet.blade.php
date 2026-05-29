@extends('layouts.app')
@section('title', 'Neraca')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('akuntansi.balance_sheet') }}">Akuntansi</a></li>
    <li class="breadcrumb-item active">Neraca</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Neraca</h4>
    <form action="{{ route('akuntansi.balance_sheet') }}" method="GET" class="flex gap-2">
        <input type="date" name="date" class="form-input text-sm" value="{{ $date }}">
        <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    </form>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <div class="card">
            <div class="card-header">Aset</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        @forelse($assets as $acc)
                        <tr><td>{{ $acc->code }} - {{ $acc->name }}</td><td class="text-right">{{ formatRupiah($acc->opening_balance + ($acc->balance ?? 0)) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-header">Liabilitas</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        @forelse($liabilities as $acc)
                        <tr><td>{{ $acc->code }} - {{ $acc->name }}</td><td class="text-right">{{ formatRupiah($acc->opening_balance) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">Ekuitas</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        @forelse($equity as $acc)
                        <tr><td>{{ $acc->code }} - {{ $acc->name }}</td><td class="text-right">{{ formatRupiah($acc->opening_balance) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-slate-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

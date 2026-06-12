@extends('layouts.app')
@section('title', 'Detail Opname '.$opnameNumber)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stok.mutations.index') }}">Stok</a></li>
    <li class="breadcrumb-item"><a href="{{ route('stok.opname.history') }}">Riwayat Opname</a></li>
    <li class="breadcrumb-item active">{{ $opnameNumber }}</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4">
    <h4 class="font-bold mb-0">Detail Opname: {{ $opnameNumber }}</h4>
    <a href="{{ route('stok.opname.history') }}" class="btn btn-secondary btn-md"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th>Kode</th>
                        <th>Stok Sebelum</th>
                        <th>Stok Setelah</th>
                        <th>Selisih</th>
                        <th>Catatan</th>
                        <th>User</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mutations as $i => $m)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $m->product?->name ?? '-' }}</td>
                        <td>{{ $m->product?->code ?? '-' }}</td>
                        <td>{{ formatQty($m->stock_before) }}</td>
                        <td>{{ formatQty($m->stock_after) }}</td>
                        <td>
                            @php $diff = $m->stock_after - $m->stock_before; @endphp
                            @if($diff > 0)
                            <span class="text-emerald-600">+{{ formatQty($diff) }}</span>
                            @elseif($diff < 0)
                            <span class="text-red-600">{{ formatQty($diff) }}</span>
                            @else
                            <span class="text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="text-xs text-slate-500 max-w-[200px] truncate" title="{{ $m->notes }}">{{ $m->notes }}</td>
                        <td>{{ $m->creator?->name ?? '-' }}</td>
                        <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

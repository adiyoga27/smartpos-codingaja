@extends('layouts.app')
@section('title', 'Detail Jurnal')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('akuntansi.journals.index') }}">Jurnal</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Detail Jurnal</div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div><strong>No. Jurnal:</strong> {{ $journal->journal_number }}</div>
            <div><strong>Tanggal:</strong> {{ $journal->journal_date->format('d/m/Y') }}</div>
            <div><strong>Sumber:</strong> {{ ucfirst($journal->source) }}</div>
            <div><strong>Dibuat:</strong> {{ $journal->creator?->name ?? '-' }}</div>
            <div class="col-span-full"><strong>Keterangan:</strong> {{ $journal->description ?? '-' }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="table border border-slate-200">
                <thead><tr><th>Akun</th><th>Keterangan</th><th>Debit</th><th>Kredit</th></tr></thead>
                <tbody>
                    @foreach($journal->entries as $entry)
                    <tr>
                        <td>{{ $entry->account?->code ?? '-' }} - {{ $entry->account?->name ?? '-' }}</td>
                        <td>{{ $entry->description ?? '-' }}</td>
                        <td class="text-right">{{ $entry->debit > 0 ? formatRupiah($entry->debit) : '-' }}</td>
                        <td class="text-right">{{ $entry->credit > 0 ? formatRupiah($entry->credit) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="2">Total</th><th class="text-right">{{ formatRupiah($journal->total_debit) }}</th><th class="text-right">{{ formatRupiah($journal->total_credit) }}</th></tr>
                </tfoot>
            </table>
        </div>
        <a href="{{ route('akuntansi.journals.index') }}" class="btn btn-secondary btn-md">Kembali</a>
    </div>
</div>
@endsection

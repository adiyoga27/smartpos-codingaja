@extends('layouts.app')
@section('title', 'Penerimaan Piutang')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.receivables.index') }}">Piutang</a></li>
    <li class="breadcrumb-item active">Terima</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Form Penerimaan Piutang</div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div><strong>No. Dokumen:</strong> {{ $receivable->document_number }}</div>
            <div><strong>Customer:</strong> {{ $receivable->customer?->name ?? '-' }}</div>
            <div><strong>Sisa Piutang:</strong> <span class="text-red-500 font-bold">{{ formatRupiah($receivable->remaining_amount) }}</span></div>
        </div>
        <form action="{{ route('keuangan.receivables.receive.store', $receivable) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Akun Kas/Bank</label>
                    <select name="cash_account_id" class="form-select" required>
                        <option value="">- Pilih -</option>
                        @foreach($cashAccounts as $acc)<option value="{{ $acc->id }}">{{ $acc->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Tanggal Terima</label>
                    <input type="date" name="payment_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Nominal</label>
                    <input type="number" name="amount" class="form-input" max="{{ $receivable->remaining_amount }}" value="{{ $receivable->remaining_amount }}" required>
                </div>
                <div class="col-span-full">
                    <label class="form-label">Keterangan</label>
                    <textarea name="notes" class="form-input" rows="2"></textarea>
                </div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Penerimaan</button>
                    <a href="{{ route('keuangan.receivables.index') }}" class="btn btn-secondary btn-md">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

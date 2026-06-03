@extends('layouts.app')
@section('title', 'Tambah Hutang Manual')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.payables.index') }}">Hutang</a></li>
    <li class="breadcrumb-item active">Tambah Manual</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Tambah Hutang Manual</div>
    <div class="card-body">
        <form action="{{ route('keuangan.payables.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">- Pilih Supplier -</option>
                        @foreach($suppliers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Jumlah (Rp)</label>
                    <input type="text" name="amount" class="form-input rupiah-input" value="{{ old('amount') }}" required>
                </div>
                <div>
                    <label class="form-label">Jatuh Tempo</label>
                    <input type="date" name="due_date" class="form-input" value="{{ old('due_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" class="form-input" value="{{ old('notes') }}">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i> Simpan</button>
                <a href="{{ route('keuangan.payables.index') }}" class="btn btn-secondary btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

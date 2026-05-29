@extends('layouts.app')
@section('title', 'Input Jurnal')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('akuntansi.journals.index') }}">Jurnal</a></li>
    <li class="breadcrumb-item active">Input</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Input Jurnal Manual</div>
    <div class="card-body">
        <form action="{{ route('akuntansi.journals.store') }}" method="POST" id="journalForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div><label class="form-label">No. Jurnal</label><input type="text" name="journal_number" class="form-input" value="{{ $journalNumber }}" readonly required></div>
                <div><label class="form-label">Tanggal</label><input type="date" name="journal_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required></div>
                <div class="col-span-full"><label class="form-label">Keterangan</label><input type="text" name="description" class="form-input" placeholder="Keterangan jurnal"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Detail Jurnal</label>
                <div class="overflow-x-auto">
                    <table class="table border border-slate-200" id="journalEntries">
                        <thead><tr><th>Akun</th><th>Keterangan</th><th>Debit</th><th>Kredit</th><th></th></tr></thead>
                        <tbody>
                            <tr class="entry-row">
                                <td><select name="entries[0][account_id]" class="form-select" required><option value="">- Pilih Akun -</option>@foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>@endforeach</select></td>
                                <td><input type="text" name="entries[0][description]" class="form-input" placeholder="Keterangan baris"></td>
                                <td><input type="number" name="entries[0][debit]" class="form-input debit" value="0" min="0" step="0.01"></td>
                                <td><input type="number" name="entries[0][credit]" class="form-input credit" value="0" min="0" step="0.01"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="bi bi-trash"></i></button></td>
                            </tr>
                            <tr class="entry-row">
                                <td><select name="entries[1][account_id]" class="form-select" required><option value="">- Pilih Akun -</option>@foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>@endforeach</select></td>
                                <td><input type="text" name="entries[1][description]" class="form-input" placeholder="Keterangan baris"></td>
                                <td><input type="number" name="entries[1][debit]" class="form-input debit" value="0" min="0" step="0.01"></td>
                                <td><input type="number" name="entries[1][credit]" class="form-input credit" value="0" min="0" step="0.01"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-success btn-sm" id="addEntry"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
            </div>
            <div class="flex items-center justify-between mb-3">
                <div>Total Debit: <strong id="totalDebit">0</strong></div>
                <div>Total Kredit: <strong id="totalCredit">0</strong></div>
                <div>Selisih: <strong id="diff" class="text-red-500">0</strong></div>
            </div>
            <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Jurnal</button>
            <a href="{{ route('akuntansi.journals.index') }}" class="btn btn-secondary btn-md">Batal</a>
        </form>
    </div>
</div>
<script>
    function updateTotals() {
        let td = 0, tc = 0;
        document.querySelectorAll('.entry-row').forEach(row => {
            td += parseFloat(row.querySelector('.debit').value)||0;
            tc += parseFloat(row.querySelector('.credit').value)||0;
        });
        document.getElementById('totalDebit').textContent = td.toLocaleString('id-ID');
        document.getElementById('totalCredit').textContent = tc.toLocaleString('id-ID');
        let diff = Math.abs(td - tc);
        let diffEl = document.getElementById('diff');
        diffEl.textContent = diff.toLocaleString('id-ID');
        diffEl.className = diff < 0.01 ? 'text-green-500' : 'text-red-500';
    }
    document.getElementById('journalEntries').addEventListener('input', updateTotals);
    document.getElementById('addEntry').addEventListener('click', function() {
        let tbody = document.querySelector('#journalEntries tbody');
        let idx = tbody.querySelectorAll('tr').length;
        let clone = tbody.querySelector('tr').cloneNode(true);
        clone.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '['+idx+']');
            if(el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = el.classList.contains('debit') || el.classList.contains('credit') ? 0 : '';
        });
        tbody.appendChild(clone);
    });
    document.getElementById('journalEntries').addEventListener('click', function(e) {
        let btn = e.target.closest('.remove-entry');
        if(btn && document.querySelectorAll('.entry-row').length > 2) {
            btn.closest('tr').remove(); updateTotals();
        }
    });
    document.getElementById('journalForm').addEventListener('submit', function(e) {
        let td = 0, tc = 0;
        document.querySelectorAll('.entry-row').forEach(row => {
            td += parseFloat(row.querySelector('.debit').value)||0;
            tc += parseFloat(row.querySelector('.credit').value)||0;
        });
        if(Math.abs(td - tc) > 0.01) { e.preventDefault(); alert('Total debit harus sama dengan total kredit!'); }
    });
</script>
@endsection

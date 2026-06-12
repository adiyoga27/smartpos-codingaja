@extends('layouts.app')
@section('title', 'Input Stok Fisik')
@section('breadcrumb')
    <a href="{{ route('stok.mutations.index') }}" class="text-slate-400 hover:text-slate-600">Stok</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('stok.opname') }}" class="text-slate-400 hover:text-slate-600">Opname</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('stok.opname.select') }}" class="text-slate-400 hover:text-slate-600">Pilih Produk</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Stok Fisik</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Input Stok Fisik</h4>
    <a href="{{ route('stok.opname.select') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali Pilih</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="opnameSearch" class="form-input form-input-sm" placeholder="Cari produk..." onkeyup="filterOpname()">
        </div>
        <form action="{{ route('stok.opname.store') }}" method="POST">
            @csrf
            @foreach($products as $product)
            <input type="hidden" name="product_ids[]" value="{{ $product->id }}">
            @endforeach
            <div class="overflow-x-auto max-h-[60vh] overflow-y-auto">
                <table class="table border border-slate-200" id="opnameTable">
                    <thead class="sticky top-0 bg-slate-50 z-10">
                        <tr>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th>Stok Sistem</th>
                            <th>Stok Fisik</th>
                            <th>Selisih</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr class="opname-row">
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->code }}</td>
                            <td class="system-stock" data-idx="{{ $loop->index }}">{{ formatQty($product->stock) }}</td>
                            <td><input type="number" name="items[{{ $product->id }}][qty]" class="form-input physical-stock" data-idx="{{ $loop->index }}" value="{{ $product->stock }}" step="0.01"></td>
                            <td class="diff text-right" data-idx="{{ $loop->index }}">0</td>
                            <td><input type="text" name="items[{{ $product->id }}][notes]" class="form-input form-input-sm" placeholder="Alasan selisih..."></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Opname</button>
                <a href="{{ route('stok.opname.select') }}" class="btn btn-secondary btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function filterOpname() {
    let q = document.getElementById('opnameSearch').value.toLowerCase();
    document.querySelectorAll('.opname-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
document.querySelectorAll('.physical-stock').forEach(el => {
    el.addEventListener('input', function() {
        let idx = this.dataset.idx;
        let sys = parseFloat(document.querySelector('.system-stock[data-idx="'+idx+'"]').textContent.replace(/[^\d,-]/g, '').replace(',', '.'))||0;
        let phys = parseFloat(this.value)||0;
        let diff = phys - sys;
        let diffEl = document.querySelector('.diff[data-idx="'+idx+'"]');
        diffEl.textContent = diff.toLocaleString('id-ID');
        diffEl.className = 'diff text-right ' + (diff < 0 ? 'text-red-500' : diff > 0 ? 'text-green-500' : 'text-slate-400');
    });
});
</script>
@endpush

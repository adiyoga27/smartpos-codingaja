@extends('layouts.app')
@section('title', 'Pilih Produk Stock Opname')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stok.mutations.index') }}">Stok</a></li>
    <li class="breadcrumb-item"><a href="{{ route('stok.opname.history') }}">Riwayat Opname</a></li>
    <li class="breadcrumb-item active">Pilih Produk</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header flex items-center justify-between">
        <h6 class="mb-0"><i class="bi bi-clipboard-check text-primary-500 me-1"></i> Pilih Produk untuk Stock Opname</h6>
        <a href="{{ route('stok.opname.history') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clock-history"></i> Riwayat</a>
    </div>
    <div class="card-body">
        <form action="{{ route('stok.opname') }}" method="GET" id="selectForm">
            <div class="mb-3">
                <div class="flex items-center gap-3 mb-2">
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" id="selectAll"> <span class="text-sm">Pilih Semua</span>
                    </label>
                    <span class="text-xs text-slate-400" id="selectedCount">0 produk dipilih</span>
                </div>
                <input type="text" id="searchInput" class="form-input form-input-sm" placeholder="Cari produk..." onkeyup="filterProducts()">
            </div>
            <div class="overflow-y-auto max-h-[500px]">
                <table class="table table-striped">
                    <thead><tr><th width="40"></th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th></tr></thead>
                    <tbody id="productTable">
                        @foreach($products as $product)
                        <tr class="product-row">
                            <td><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox"></td>
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td>{{ formatQty($product->stock) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-md" id="submitBtn" disabled>
                    <i class="bi bi-arrow-right mr-1"></i> Lanjut Stock Opname
                </button>
                <a href="{{ route('stok.opname.history') }}" class="btn btn-secondary btn-md">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.product-checkbox').forEach(cb => {
    cb.addEventListener('change', updateCount);
});
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = this.checked);
    updateCount();
});
function updateCount() {
    let count = document.querySelectorAll('.product-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count + ' produk dipilih';
    document.getElementById('submitBtn').disabled = count === 0;
}
function filterProducts() {
    let q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.product-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush

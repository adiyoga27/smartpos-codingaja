@extends('layouts.app')
@section('title', 'Stock Opname')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stok.mutations.index') }}">Stok</a></li>
    <li class="breadcrumb-item active">Stock Opname</li>
@endsection
@section('content')

<div class="card mb-4">
    <div class="card-header flex items-center justify-between">
        <h6 class="mb-0"><i class="bi bi-clock-history text-primary-500 me-1"></i> Riwayat Stock Opname</h6>
        <button type="button" class="btn btn-primary btn-sm" onclick="toggleNewOpname()">
            <i class="bi bi-plus-lg"></i> Buat Opname Baru
        </button>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-striped" id="opname-history-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Nomor Opname</th>
                        <th>Tanggal</th>
                        <th>Total Item</th>
                        <th>User</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="card" id="newOpnameCard" style="display:none;">
    <div class="card-header flex items-center justify-between">
        <h6 class="mb-0"><i class="bi bi-clipboard-check text-primary-500 me-1"></i> Pilih Produk untuk Stock Opname</h6>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleNewOpname()"><i class="bi bi-x-lg"></i> Tutup</button>
    </div>
    <div class="card-body">
        <form action="{{ route('stok.opname.store') }}" method="POST" id="selectForm">
            @csrf
            <div class="mb-3">
                <div class="flex items-center gap-3 mb-2">
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" id="selectAll"> <span class="text-sm">Pilih Semua</span>
                    </label>
                    <span class="text-xs text-slate-400" id="selectedCount">0 produk dipilih</span>
                </div>
                <input type="text" id="searchInput" class="form-input form-input-sm" placeholder="Cari produk..." onkeyup="filterProducts()">
            </div>
            <div class="overflow-y-auto max-h-[400px]">
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
                    <i class="bi bi-arrow-right mr-1"></i> Lanjut ke Input Stok Fisik
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card" id="opnameFormCard" style="display:none;">
    <div class="card-header flex items-center justify-between">
        <h6 class="mb-0"><i class="bi bi-pencil-square text-primary-500 me-1"></i> Input Stok Fisik</h6>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="backToSelect()"><i class="bi bi-arrow-left"></i> Kembali Pilih</button>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="opnameSearch" class="form-input form-input-sm" placeholder="Cari produk..." onkeyup="filterOpname()">
        </div>
        <form action="{{ route('stok.opname.store') }}" method="POST" id="opnameStoreForm">
            @csrf
            <div id="opnameFields"></div>
            <div class="overflow-x-auto max-h-[50vh] overflow-y-auto">
                <table class="table border border-slate-200" id="opnameTable">
                    <thead class="sticky top-0 bg-slate-50 z-10">
                        <tr><th>Produk</th><th>Kode</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th></tr>
                    </thead>
                    <tbody id="opnameTbody"></tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Opname</button>
                <button type="button" onclick="backToSelect()" class="btn btn-secondary btn-md">Batal</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
let allProducts = {!! $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'stock' => (float) $p->stock])->values()->toJson() !!};

$(document).ready(function() {
    $('#opname-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '{{ route('stok.opname.history') }}', type: 'GET' },
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-clipboard"></i>' },
            { extend: 'csv', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-filetype-csv"></i>' },
            { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm', text: '<i class="bi bi-file-earmark-pdf"></i>' },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-printer"></i>' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [4] }]
    });

    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = this.checked);
        updateCount();
    });
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.addEventListener('change', updateCount));

    document.getElementById('selectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let ids = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
        if (ids.length === 0) return;
        renderOpnameTable(ids);
        document.getElementById('newOpnameCard').style.display = 'none';
        document.getElementById('opnameFormCard').style.display = '';
    });
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

function toggleNewOpname() {
    let card = document.getElementById('newOpnameCard');
    card.style.display = card.style.display === 'none' ? '' : 'none';
    document.getElementById('opnameFormCard').style.display = 'none';
}

function backToSelect() {
    document.getElementById('opnameFormCard').style.display = 'none';
    document.getElementById('newOpnameCard').style.display = '';
}

function renderOpnameTable(ids) {
    let tbody = document.getElementById('opnameTbody');
    let fields = document.getElementById('opnameFields');
    fields.innerHTML = '';
    tbody.innerHTML = allProducts.filter(p => ids.includes(String(p.id))).map((p, idx) => {
        fields.insertAdjacentHTML('beforeend', '<input type="hidden" name="product_ids[]" value="' + p.id + '">');
        return `<tr class="opname-row">
            <td>${p.name}</td>
            <td>${p.code}</td>
            <td class="system-stock" data-idx="${idx}">${p.stock.toLocaleString('id-ID')}</td>
            <td><input type="number" name="items[${p.id}]" class="form-input physical-stock" data-idx="${idx}" value="${p.stock}" step="0.01"></td>
            <td class="diff text-right" data-idx="${idx}">0</td>
        </tr>`;
    }).join('');

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
}

function filterOpname() {
    let q = document.getElementById('opnameSearch').value.toLowerCase();
    document.querySelectorAll('#opnameTbody .opname-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush

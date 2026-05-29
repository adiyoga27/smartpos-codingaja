@extends('layouts.app')
@section('title', 'Buat PO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index') }}">Pembelian</a></li>
    <li class="breadcrumb-item active">Buat PO</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Buat Purchase Order</div>
    <div class="card-body">
        <form action="{{ route('transaksi.purchases.store') }}" method="POST" id="poForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-3">
                <div><label class="form-label">No. Dokumen</label><input type="text" name="document_number" class="form-input" value="{{ $documentNumber }}" readonly required></div>
                <div><label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select" required><option value="">- Pilih -</option>@foreach($suppliers as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                </div>
                <div><label class="form-label">Tanggal</label><input type="date" name="purchase_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required></div>
                <div><label class="form-label">Jatuh Tempo</label><input type="date" name="due_date" class="form-input"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Item</label>
                <div class="overflow-x-auto">
                    <table class="table" id="itemsTable">
                        <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Diskon</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                            <tr class="item-row">
                                <td>
                                    <select name="items[0][product_id]" class="form-select product-select" required>
                                        <option value="">- Pilih Produk -</option>
                                        @foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->purchase_price }}">{{ $p->name }}</option>@endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][quantity]" class="form-input qty" value="1" min="0.01" step="0.01" required></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-input price" value="0" min="0" required></td>
                                <td><input type="number" name="items[0][discount]" class="form-input discount" value="0" min="0"></td>
                                <td class="line-total">0</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-success btn-sm" id="addRow"><i class="bi bi-plus-lg"></i> Tambah Item</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div><label class="form-label">Catatan</label><textarea name="notes" class="form-input" rows="2"></textarea></div>
                <div class="text-right">
                    <h5>Total: <span id="grandTotal">Rp 0</span></h5>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan PO</button>
            <a href="{{ route('transaksi.purchases.index') }}" class="btn btn-secondary btn-md">Batal</a>
        </form>
    </div>
</div>
<script>
    function formatRupiah(angka) { return 'Rp ' + parseFloat(angka).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function updateRow(row) {
        let qty = parseFloat(row.querySelector('.qty').value) || 0;
        let price = parseFloat(row.querySelector('.price').value) || 0;
        let discount = parseFloat(row.querySelector('.discount').value) || 0;
        row.querySelector('.line-total').textContent = formatRupiah(Math.max(0, qty*price - discount));
        updateGrand();
    }
    function updateGrand() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            let qty = parseFloat(row.querySelector('.qty').value)||0;
            let price = parseFloat(row.querySelector('.price').value)||0;
            let discount = parseFloat(row.querySelector('.discount').value)||0;
            total += Math.max(0, qty*price - discount);
        });
        document.getElementById('grandTotal').textContent = formatRupiah(total);
    }
    document.getElementById('addRow').addEventListener('click', function() {
        let tbody = document.querySelector('#itemsTable tbody');
        let idx = tbody.querySelectorAll('tr').length;
        let clone = tbody.querySelector('tr').cloneNode(true);
        clone.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '['+idx+']');
            if(el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = el.classList.contains('qty') ? 1 : el.classList.contains('price') || el.classList.contains('discount') ? 0 : '';
        });
        clone.querySelector('.line-total').textContent = '0';
        tbody.appendChild(clone);
    });
    document.getElementById('itemsTable').addEventListener('change', function(e) {
        if(e.target.classList.contains('product-select')) {
            let price = e.target.options[e.target.selectedIndex].dataset.price || 0;
            let row = e.target.closest('tr');
            row.querySelector('.price').value = price;
            updateRow(row);
        }
    });
    document.getElementById('itemsTable').addEventListener('input', function(e) {
        if(e.target.classList.contains('qty') || e.target.classList.contains('price') || e.target.classList.contains('discount')) {
            updateRow(e.target.closest('tr'));
        }
    });
    document.getElementById('itemsTable').addEventListener('click', function(e) {
        let btn = e.target.closest('.remove-row');
        if(btn && document.querySelectorAll('.item-row').length > 1) {
            btn.closest('tr').remove();
            updateGrand();
        }
    });
</script>
@endsection

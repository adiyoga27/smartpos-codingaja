@extends('layouts.app')
@section('title', 'Buat Return Penjualan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.sale_returns.index') }}">Return Penjualan</a></li>
    <li class="breadcrumb-item active">Buat</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Buat Return Penjualan</div>
    <div class="card-body">
        <form action="{{ route('transaksi.sale_returns.store') }}" method="POST" id="returnForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-3">
                <div><label class="form-label">No. Dokumen</label><input type="text" name="document_number" class="form-input" value="{{ $documentNumber }}" readonly required></div>
                <div><label class="form-label">Invoice</label>
                    <select name="sale_id" class="form-select" id="saleSelect" required>
                        <option value="">- Pilih Invoice -</option>
                        @foreach($sales as $sale)
                        <option value="{{ $sale->id }}">{{ $sale->invoice_number }} - {{ $sale->customer?->name ?? 'Umum' }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="form-label">Tanggal Return</label><input type="date" name="return_date" class="form-input" value="{{ now()->format('Y-m-d') }}" required></div>
                <div><label class="form-label">Refund</label>
                    <select name="refund_method" class="form-select" required><option value="credit">Kredit ke Piutang</option><option value="cash">Tunai</option></select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Item</label>
                <div class="overflow-x-auto">
                    <table class="table" id="itemsTable">
                        <thead><tr><th>Produk</th><th>Qty Return</th><th>Harga</th><th>Total</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-success btn-sm" id="addRow"><i class="bi bi-plus-lg"></i> Tambah Item</button>
            </div>
            <div class="mb-3">
                <label class="form-label">Alasan Return</label>
                <textarea name="reason" class="form-input" rows="2" required></textarea>
            </div>
            <div class="text-right mb-3"><h5>Total: <span id="grandTotal">Rp 0</span></h5></div>
            <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Return</button>
            <a href="{{ route('transaksi.sale_returns.index') }}" class="btn btn-secondary btn-md">Batal</a>
        </form>
    </div>
</div>
<script>
    let sales = @json($sales->map(fn($s) => ['id'=>$s->id, 'items'=>$s->items->map(fn($i)=>['product_id'=>$i->product_id,'product_name'=>$i->product?->name ?? '-','unit_price'=>$i->unit_price])]));
    function formatRupiah(angka) { return 'Rp ' + parseFloat(angka).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function updateGrand() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            let qty = parseFloat(row.querySelector('.qty').value)||0;
            let price = parseFloat(row.querySelector('.price').value)||0;
            row.querySelector('.line-total').textContent = formatRupiah(qty*price);
            total += qty*price;
        });
        document.getElementById('grandTotal').textContent = formatRupiah(total);
    }
    document.getElementById('saleSelect').addEventListener('change', function() {
        let tbody = document.querySelector('#itemsTable tbody');
        tbody.innerHTML = '';
        let sale = sales.find(s => s.id == this.value);
        if(!sale) return;
        sale.items.forEach((it, idx) => {
            tbody.innerHTML += `<tr class="item-row">
                <td><input type="hidden" name="items[${idx}][product_id]" value="${it.product_id}">${it.product_name}</td>
                <td><input type="number" name="items[${idx}][quantity]" class="form-input qty" value="1" min="0.01" step="0.01" required></td>
                <td><input type="number" name="items[${idx}][unit_price]" class="form-input price" value="${it.unit_price}" min="0" required></td>
                <td class="line-total">${formatRupiah(it.unit_price)}</td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
            </tr>`;
        });
        updateGrand();
    });
    document.getElementById('itemsTable').addEventListener('input', function(e) { if(e.target.classList.contains('qty') || e.target.classList.contains('price')) updateGrand(); });
    document.getElementById('itemsTable').addEventListener('click', function(e) { if(e.target.closest('.remove-row')) e.target.closest('tr').remove(); updateGrand(); });
</script>
@endsection

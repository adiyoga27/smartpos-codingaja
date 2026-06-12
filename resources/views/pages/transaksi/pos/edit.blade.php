@extends('layouts.app')
@section('title', 'Edit Transaksi - '.$sale->invoice_number)
@section('breadcrumb')
    <a href="{{ route('pos.kasir') }}" class="text-slate-400 hover:text-slate-600">POS</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('pos.riwayat') }}" class="text-slate-400 hover:text-slate-600">Riwayat</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Edit</span>
@endsection
@section('content')
<div x-data="editForm()">
<div class="flex items-center justify-between mb-6">
    <div>
        <h4 class="font-bold mb-1">Edit Transaksi</h4>
        <p class="text-sm text-slate-400">{{ $sale->invoice_number }}</p>
    </div>
    <a href="{{ route('pos.detail', $sale) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<form action="{{ route('pos.update', $sale) }}" method="POST" @submit.prevent="handleSubmit">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="card">
            <div class="card-header">Customer</div>
            <div class="card-body">
                <div><label class="form-label">Nama Customer</label>
                    <input type="text" class="form-input" value="{{ $sale->customer?->name ?? $sale->customer_name ?? 'Umum' }}" disabled>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Metode & Status</div>
            <div class="card-body space-y-3">
                <div><label class="form-label">Metode Pembayaran</label>
                    <select name="payment_method_id" class="form-select" disabled>
                        @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->id }}" {{ $sale->payment_method_id == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="form-label">Status</label>
                    <select name="status" class="form-select" x-model="status">
                        <option value="paid">Lunas</option>
                        <option value="partial">Sebagian</option>
                        <option value="unpaid">Belum Bayar</option>
                        <option value="cancelled">Batal</option>
                    </select>
                </div>
                @if($isCredit)
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="form-label">Tgl Mulai</label>
                        <input type="date" name="sale_date" class="form-input" value="{{ $sale->sale_date->format('Y-m-d') }}">
                    </div>
                    <div><label class="form-label">Tgl Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-input" value="{{ $dueDate }}">
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header">Keuangan</div>
            <div class="card-body">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Subtotal</dt><dd class="text-sm" x-text="formatRp(subtotal())"></dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Diskon</dt><dd class="text-sm text-red-500" x-text="formatRp(discountTotal())"></dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Pajak</dt><dd class="text-sm">{{ formatRupiah($sale->tax) }}</dd></div>
                    <div class="flex justify-between font-bold border-t pt-2"><dt class="text-xs">Total</dt><dd class="text-primary-700" x-text="formatRp(grandTotal())"></dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Dibayar</dt><dd class="text-sm text-emerald-600">{{ formatRupiah($sale->paid_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-xs text-slate-400">Kembalian</dt><dd class="text-sm">{{ formatRupiah($sale->change_amount) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Item</div>
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Produk</th><th style="width:100px">Qty</th><th style="width:140px">Harga /Pcs</th><th style="width:120px">Diskon</th><th class="text-right" style="width:130px">Total</th></tr></thead>
                    <tbody>
                        <template x-for="(it, idx) in items" :key="it.id">
                            <tr>
                                <td>
                                    <span class="text-sm font-medium" x-text="it.product_name"></span>
                                </td>
                                <td>
                                    <input type="number" :value="it.quantity" min="0.01" step="0.01" class="form-input form-input-sm text-sm text-center"
                                           @input="updateItem(idx, 'quantity', $event.target.value)">
                                </td>
                                <td>
                                    <input type="text" :value="fmtNum(it.unit_price)" class="form-input form-input-sm text-sm text-right input-rupiah"
                                           @input="updateItem(idx, 'unit_price', $event.target.value); formatRupiahInput($event.target)">
                                </td>
                                <td>
                                    <input type="text" :value="it.discount > 0 ? fmtNum(it.discount) : '0'" class="form-input form-input-sm text-sm text-right input-rupiah"
                                           @input="updateItem(idx, 'discount', $event.target.value); formatRupiahInput($event.target)">
                                </td>
                                <td class="text-right">
                                    <span class="text-sm font-bold text-primary-600" x-text="formatRp(lineTotal(it))"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Perubahan</button>
        <a href="{{ route('pos.detail', $sale) }}" class="btn btn-secondary btn-md">Batal</a>
    </div>
    </div>
</form>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('editForm', () => ({
        status: '{{ $sale->status }}',
        items: {!! $sale->items->map(fn($i) => [
            'id' => $i->id,
            'product_id' => $i->product_id,
            'product_name' => $i->product?->name ?? '-',
            'quantity' => (float) $i->quantity,
            'unit_price' => (float) $i->unit_price,
            'discount' => (float) $i->discount,
        ])->values()->toJson() !!},

        subtotal() {
            return this.items.reduce((s, it) => s + it.quantity * it.unit_price, 0);
        },
        discountTotal() {
            return this.items.reduce((s, it) => s + (it.discount || 0), 0);
        },
        grandTotal() {
            return Math.max(0, this.subtotal() - this.discountTotal());
        },
        lineTotal(it) {
            return Math.max(0, it.quantity * it.unit_price - (it.discount || 0));
        },

        fmtNum(val) {
            let n = parseFloat(val) || 0;
            return n % 1 === 0 ? n.toLocaleString('id-ID') : n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatRp(angka) {
            return 'Rp ' + Math.round(parseFloat(angka)).toLocaleString('id-ID');
        },

        parseRupiah(val) {
            return parseFloat((val || '0').replace(/\./g, '').replace(',', '.')) || 0;
        },

        formatRupiahInput(el) {
            let cursor = el.selectionStart, raw = el.value.replace(/[^\d,]/g, '');
            let parts = raw.split(',');
            if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
            let intPart = parseInt(parts[0] || '0', 10), decPart = parts.length > 1 ? parts[1].substring(0, 2) : '';
            let val = intPart.toLocaleString('id-ID');
            if (raw.includes(',')) val += ',' + decPart;
            let diff = el.value.length - val.length;
            el.value = val;
            el.setSelectionRange(Math.max(0, cursor - diff), Math.max(0, cursor - diff));
        },

        updateItem(idx, field, val) {
            if (!this.items[idx]) return;
            let it = this.items[idx];
            if (field === 'quantity') {
                let q = parseFloat(val) || 0;
                it.quantity = q;
            } else if (field === 'unit_price') {
                it.unit_price = this.parseRupiah(val);
            } else if (field === 'discount') {
                it.discount = this.parseRupiah(val);
            }
        },

        handleSubmit(e) {
            if (this.items.length === 0) {
                alert('Minimal 1 item diperlukan.');
                return;
            }
            let form = e.target;
            form.querySelectorAll('input[name^="items["]').forEach(el => el.remove());
            this.items.forEach((it, idx) => {
                form.insertAdjacentHTML('beforeend',
                    `<input type="hidden" name="items[${idx}][id]" value="${it.id}">` +
                    `<input type="hidden" name="items[${idx}][quantity]" value="${it.quantity}">` +
                    `<input type="hidden" name="items[${idx}][unit_price]" value="${it.unit_price}">` +
                    `<input type="hidden" name="items[${idx}][discount]" value="${it.discount || 0}">`);
            });
            form.submit();
        }
    }));
});
</script>
@endpush

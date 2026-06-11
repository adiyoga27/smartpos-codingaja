@extends('layouts.app')
@section('title', 'Detail PO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index') }}">Pembelian</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-header flex flex-wrap items-center justify-between gap-2">
        <h5 class="mb-0">Purchase Order #{{ $purchase->document_number }}</h5>
        <div class="flex gap-2">
            <a href="{{ route('transaksi.purchases.print', $purchase) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> Cetak</a>
            <a href="{{ route('transaksi.purchases.index') }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Status</small>
                @if($purchase->status == 'draft') <span class="badge badge-slate mt-1">Draft</span>
                @elseif($purchase->status == 'sent') <span class="badge badge-info mt-1">Dikirim</span>
                @elseif($purchase->status == 'partial') <span class="badge badge-warning mt-1">Sebagian</span>
                @elseif($purchase->status == 'completed') <span class="badge badge-success mt-1">Selesai</span>
                @else <span class="badge badge-danger mt-1">Batal</span>
                @endif
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Supplier</small>
                <strong class="d-block mt-1">{{ $purchase->supplier?->name ?? '-' }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Tanggal</small>
                <strong class="d-block mt-1">{{ $purchase->purchase_date->format('d/m/Y') }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Jatuh Tempo</small>
                <strong class="d-block mt-1">{{ $purchase->due_date ? $purchase->due_date->format('d/m/Y') : '-' }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total PO</small>
                <strong class="d-block mt-1 text-primary">{{ formatRupiah($purchase->total) }}</strong>
            </div>
            <div class="bg-slate-50 rounded p-3">
                <small class="text-slate-500 d-block">Total Diterima</small>
                @php $receivedTotal = $purchase->items->sum(fn($i) => $i->quantity > 0 ? ($i->received_quantity / $i->quantity) * $i->total : 0); @endphp
                <strong class="d-block mt-1 text-success">{{ formatRupiah($receivedTotal) }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Item Purchase Order</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Produk</th><th class="text-center">Qty</th><th class="text-center">Diterima</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach($purchase->items as $item)
                    <tr>
                        <td class="fw-medium">{{ $item->product?->name ?? '-' }}</td>
                        <td class="text-center">{{ formatQty($item->quantity) }}</td>
                        <td class="text-center">{{ formatQty($item->received_quantity) }}</td>
                        <td class="text-end">{{ formatRupiah($item->unit_price) }}</td>
                        <td class="text-end">{{ formatRupiah($item->discount) }}</td>
                        <td class="text-end fw-medium">{{ formatRupiah($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Subtotal</td>
                        <td class="text-end fw-bold">{{ formatRupiah($purchase->subtotal) }}</td>
                    </tr>
                    @if($purchase->discount > 0)
                    <tr>
                        <td colspan="5" class="text-end">Diskon</td>
                        <td class="text-end">{{ formatRupiah($purchase->discount) }}</td>
                    </tr>
                    @endif
                    @if($purchase->tax > 0)
                    <tr>
                        <td colspan="5" class="text-end">PPN</td>
                        <td class="text-end">{{ formatRupiah($purchase->tax) }}</td>
                    </tr>
                    @endif
                    <tr class="table-active">
                        <td colspan="5" class="text-end fw-bold fs-5">Total</td>
                        <td class="text-end fw-bold fs-5 text-primary">{{ formatRupiah($purchase->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@if(!in_array($purchase->status, ['completed','cancelled']))
<div class="card border-primary" x-data="receiveForm()">
    <div class="card-header bg-primary-50"><h6 class="mb-0"><i class="bi bi-box-arrow-in-down"></i> Penerimaan Barang</h6></div>
    <div class="card-body">
        <form action="{{ route('transaksi.purchases.receive', $purchase) }}" method="POST" @submit.prevent="handleSubmit">
            @csrf @method('PATCH')
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-light"><tr><th>Produk</th><th class="text-center">Qty Order</th><th class="text-center">Sudah Diterima</th><th class="text-center" style="min-width:140px">Qty Terima</th></tr></thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->product?->name ?? '-' }}</td>
                            <td class="text-center">{{ formatQty($item->quantity) }}</td>
                            <td class="text-center">{{ formatQty($item->received_quantity) }}</td>
                            <td class="text-center">
                                @if($item->received_quantity < $item->quantity)
                                <input type="number" name="items[{{ $item->id }}]" class="form-input form-input-sm text-center" value="0" min="0" max="{{ $item->quantity - $item->received_quantity }}" step="0.01">
                                @else
                                <span class="badge bg-success">Lengkap</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-slate-50 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                        <i class="bi bi-wallet2 text-slate-500"></i> Metode Pembayaran
                    </span>
                    <button type="button" @click="addPayment()" class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                        <i class="bi bi-plus-circle text-sm"></i> Tambah
                    </button>
                </div>

                <template x-for="(pmt, idx) in payments" :key="idx">
                    <div class="flex items-center gap-2 mb-2 p-2 bg-white rounded-lg border border-slate-200">
                        <select class="form-select form-select-sm flex-1" :data-idx="idx" x-ref="selects" required>
                            <option value="">- Pilih Kas/Bank -</option>
                            @foreach($cashAccounts as $ca)
                            <option value="{{ $ca->id }}">{{ $ca->name }} ({{ formatRupiah($ca->current_balance) }})</option>
                            @endforeach
                        </select>
                        <div class="relative w-32">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 z-10">Rp</span>
                            <input type="text" x-model="pmt.amount" class="form-input form-input-sm pl-7 text-right text-sm" placeholder="0">
                        </div>
                        <button type="button" @click="payments.splice(idx, 1)" class="text-red-400 hover:text-red-600 p-1 flex-shrink-0">
                            <i class="bi bi-x-circle text-sm"></i>
                        </button>
                    </div>
                </template>

                <div x-show="payments.length === 0" class="text-center py-3 text-xs text-slate-400">
                    Belum ada metode pembayaran. Klik <span class="text-blue-500 font-medium">Tambah</span> jika ada pembayaran.
                </div>

                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200" x-show="payments.length > 0">
                    <span class="text-xs text-slate-500">Total Dibayar</span>
                    <span class="text-sm font-bold text-emerald-600" x-text="formatRp(paidTotal())"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Penerimaan</button>
        </form>
    </div>
</div>
@endif
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('receiveForm', () => ({
        payments: [],
        addPayment() { this.payments.push({ amount: '' }); },
        paidTotal() { return this.payments.reduce((s, p) => s + (parseInt(p.amount) || 0), 0); },
        formatRp(angka) { return 'Rp ' + Math.round(parseFloat(angka)).toLocaleString('id-ID'); },
        handleSubmit(e) {
            let form = e.target;
            form.querySelectorAll('input[name^="payments["]').forEach(el => el.remove());
            this.payments.forEach((p, i) => {
                let sel = form.querySelector(`select[data-idx="${i}"]`);
                if (sel && sel.value && parseInt(p.amount) > 0) {
                    form.insertAdjacentHTML('beforeend',
                        `<input type="hidden" name="payments[${i}][cash_account_id]" value="${sel.value}">` +
                        `<input type="hidden" name="payments[${i}][amount]" value="${p.amount}">`);
                }
            });
            form.submit();
        }
    }));
});
</script>
@endpush
@endsection

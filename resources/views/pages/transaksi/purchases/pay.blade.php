@extends('layouts.app')
@section('title', 'Bayar PO #'.$purchase->document_number)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index', ['type' => 'po']) }}">Purchase Order</a></li>
    <li class="breadcrumb-item active">Bayar</li>
@endsection
@section('content')
@php $remaining = max(0, $purchase->total - $purchase->paid_amount); @endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Info PO</h6></div>
        <div class="card-body">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">No. PO</dt><dd class="font-medium">{{ $purchase->document_number }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Supplier</dt><dd>{{ $purchase->supplier?->name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Total</dt><dd class="font-bold">{{ formatRupiah($purchase->total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Dibayar</dt><dd class="text-emerald-600 font-medium">{{ formatRupiah($purchase->paid_amount) }}</dd></div>
                <div class="flex justify-between pt-2 border-t"><dt class="text-slate-500 font-medium">Sisa</dt><dd class="font-bold text-lg {{ $remaining > 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $remaining > 0 ? formatRupiah($remaining) : 'Lunas' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card lg:col-span-2" x-data="payForm()">
        <div class="card-header"><h6 class="mb-0">Pembayaran</h6></div>
        <div class="card-body">
            @if($remaining > 0)
            <form action="{{ route('transaksi.purchases.pay.store', $purchase) }}" method="POST" @submit.prevent="handleSubmit">
                @csrf
                <div class="bg-slate-50 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-600">Metode Pembayaran</span>
                        <button type="button" @click="addPayment()" class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                            <i class="bi bi-plus-circle"></i> Tambah
                        </button>
                    </div>
                    <template x-for="(pmt, idx) in payments" :key="idx">
                        <div class="flex items-center gap-2 mb-2 p-2 bg-white rounded-lg border border-slate-200">
                            <select class="form-select form-select-sm flex-1" :class="'paySel-'+idx" required>
                                <option value="">- Pilih Kas/Bank -</option>
                                @foreach($cashAccounts as $ca)
                                <option value="{{ $ca->id }}">{{ $ca->name }} ({{ formatRupiah($ca->current_balance) }})</option>
                                @endforeach
                            </select>
                            <div class="relative w-36">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-slate-400 z-10">Rp</span>
                                <input type="text" x-model="pmt.amount" class="form-input form-input-sm pl-7 text-right" placeholder="0">
                            </div>
                            <button type="button" @click="payments.splice(idx, 1)" class="text-red-400 hover:text-red-600 p-1">
                                <i class="bi bi-x-circle text-sm"></i>
                            </button>
                        </div>
                    </template>
                    <div x-show="payments.length === 0" class="text-center py-4 text-xs text-slate-400">
                        Klik <span class="text-blue-500 font-medium">Tambah</span> untuk menambah metode pembayaran.
                    </div>
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200" x-show="payments.length > 0">
                        <span class="text-sm text-slate-500">Total Bayar</span>
                        <span class="text-lg font-bold text-emerald-600" x-text="formatRp(paidTotal())"></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" :disabled="payments.length === 0">
                    <i class="bi bi-cash-coin me-1"></i> Bayar
                </button>
                <a href="{{ route('transaksi.purchases.show', $purchase) }}" class="btn btn-secondary">Kembali</a>
            </form>
            @else
            <div class="text-center py-8 text-emerald-600">
                <i class="bi bi-check-circle text-4xl block mb-3"></i>
                <span class="text-lg font-bold">Pembayaran sudah Lunas</span>
            </div>
            @endif
        </div>
    </div>
</div>

@if($paymentHistory->isNotEmpty())
<div class="card">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-1"></i> Riwayat Pembayaran</h6></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Tanggal</th><th>Akun Kas</th><th class="text-end">Jumlah</th><th>Oleh</th></tr></thead>
            <tbody>
                @foreach($paymentHistory as $tx)
                <tr>
                    <td class="text-sm">{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                    <td class="text-sm">{{ $tx->cashAccount?->name ?? '-' }}</td>
                    <td class="text-end text-sm text-red-500 fw-medium">-{{ formatRupiah($tx->amount) }}</td>
                    <td class="text-sm">{{ $tx->creator?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-active">
                    <td colspan="2" class="text-end fw-bold">Total</td>
                    <td class="text-end fw-bold text-red-500">{{ formatRupiah($paymentHistory->sum('amount')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('payForm', () => ({
        payments: [],
        addPayment() { this.payments.push({ amount: '' }); },
        paidTotal() { return this.payments.reduce((s, p) => s + (parseInt(p.amount) || 0), 0); },
        formatRp(angka) { return 'Rp ' + Math.round(parseFloat(angka)).toLocaleString('id-ID'); },
        handleSubmit(e) {
            let form = e.target;
            form.querySelectorAll('input[name^="payments["]').forEach(el => el.remove());
            this.payments.forEach((p, i) => {
                let sel = form.querySelector(`select.paySel-${i}`);
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

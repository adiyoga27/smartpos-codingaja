@extends('layouts.app')
@section('title', 'Penerimaan Piutang')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.receivables.index') }}" class="text-slate-500 hover:text-primary-600 transition-colors">Piutang</a></li>
    <li class="breadcrumb-item active text-slate-800 font-semibold" aria-current="page">Terima</li>
@endsection

@section('content')
<div class="max-w-4xl mx-auto pb-10" x-data="receiveForm({{ $receivable->remaining_amount }})">
    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-wallet2 text-primary-600"></i> Penerimaan Piutang
            </h2>
            <p class="text-sm text-slate-500 mt-1">Selesaikan penerimaan pembayaran piutang dari customer sesuai nominal yang tersisa.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('keuangan.receivables.index') }}" class="btn btn-light rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm hover:bg-slate-100 transition-all">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Dokumen Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-full -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center border border-slate-100 shadow-sm z-10">
                <i class="bi bi-file-earmark-text text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-0.5">No. Dokumen</p>
                <p class="text-sm font-bold text-slate-800">{{ $receivable->document_number }}</p>
            </div>
        </div>
        
        <!-- Customer Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-16 h-16 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center border border-indigo-100 shadow-sm z-10">
                <i class="bi bi-person text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-0.5">Customer</p>
                <p class="text-sm font-bold text-slate-800">{{ $receivable->customer?->name ?? 'Umum' }}</p>
            </div>
        </div>

        <!-- Jatuh Tempo Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-16 h-16 bg-amber-50 rounded-bl-full -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center border border-amber-100 shadow-sm z-10">
                <i class="bi bi-calendar-event text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-0.5">Jatuh Tempo</p>
                <p class="text-sm font-bold {{ $receivable->due_date < now() ? 'text-red-500' : 'text-slate-800' }}">
                    {{ $receivable->due_date ? $receivable->due_date->format('d M Y') : '-' }}
                </p>
            </div>
        </div>

        <!-- Sisa Piutang Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 border border-emerald-600 shadow-lg shadow-emerald-500/30 flex items-center gap-4 relative overflow-hidden text-white">
            <div class="absolute right-0 top-0 w-24 h-24 bg-white opacity-10 rounded-full blur-xl -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-white/20 text-white flex items-center justify-center shadow-sm z-10 backdrop-blur-sm">
                <i class="bi bi-cash-stack text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-emerald-100 tracking-wider mb-0.5">Sisa Piutang</p>
                <p class="text-lg font-black">{{ formatRupiah($receivable->remaining_amount) }}</p>
            </div>
        </div>
    </div>

    <!-- Form Penerimaan -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-1 h-full bg-primary-500"></div>
        <div class="p-5 sm:p-6 border-b border-slate-50">
            <h6 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-1">
                <i class="bi bi-pencil-square text-primary-500"></i> Detail Penerimaan
            </h6>
        </div>
        
        <div class="p-5 sm:p-6">
            <form action="{{ route('keuangan.receivables.receive.store', $receivable) }}" method="POST" id="formTerima">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Akun Kas/Bank <span class="text-red-500">*</span></label>
                        <select name="cash_account_id" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" required>
                            <option value="">- Pilih Akun -</option>
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: {{ formatRupiah($acc->current_balance) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Terima <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Nominal Terima <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-500 font-bold">Rp</span>
                            </div>
                            <!-- Input tampilan dengan format -->
                            <input type="text" x-model="displayAmount" @input="formatInput" class="w-full bg-white border border-slate-200 text-slate-800 text-lg rounded-xl pl-12 pr-4 py-3 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm font-bold" required>
                            
                            <!-- Input hidden yang akan disubmit -->
                            <input type="hidden" name="amount" x-model="amount">
                            
                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center">
                                <button type="button" @click="setLunas()" class="bg-primary-50 text-primary-600 hover:bg-primary-100 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">Terima Lunas</button>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <i class="bi bi-info-circle"></i> Maksimal penerimaan: <span class="font-bold text-slate-600">{{ formatRupiah($receivable->remaining_amount) }}</span>
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Keterangan / Catatan</label>
                        <textarea name="notes" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" rows="3" placeholder="Tambahkan catatan penerimaan jika perlu..."></textarea>
                    </div>
                </div>

                <div class="mt-8 pt-5 border-t border-slate-100 flex justify-end gap-3">
                    <a href="{{ route('keuangan.receivables.index') }}" class="btn btn-light rounded-xl px-5 py-2.5 font-medium shadow-sm hover:bg-slate-100 transition-all">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-6 py-2.5 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center gap-2 font-bold" :disabled="amount <= 0 || amount > maxAmount">
                        <i class="bi bi-check2-circle text-lg"></i> Simpan Penerimaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($receivable->payments->isNotEmpty())
    <!-- Riwayat Pembayaran -->
    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-1 h-full bg-slate-400"></div>
        <div class="p-5 sm:p-6 border-b border-slate-50">
            <h6 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-1">
                <i class="bi bi-clock-history text-slate-500"></i> Riwayat Penerimaan
            </h6>
            <p class="text-xs text-slate-500">Histori cicilan atau penerimaan yang sudah dilakukan untuk piutang ini.</p>
        </div>
        <div class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase text-slate-500 font-semibold">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Akun Kas/Bank</th>
                            <th class="px-5 py-3">Keterangan</th>
                            <th class="px-5 py-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($receivable->payments as $payment)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 font-medium text-slate-800">
                                <span class="bg-slate-100 px-2 py-1 rounded-lg text-xs">{{ $payment->cashAccount?->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3">{{ $payment->notes ?: '-' }}</td>
                            <td class="px-5 py-3 text-right font-bold text-emerald-600">{{ formatRupiah($payment->amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-100">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-bold text-slate-600">Total Telah Diterima</td>
                            <td class="px-5 py-3 text-right font-black text-slate-800">{{ formatRupiah($receivable->paid_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('receiveForm', (maxRemaining) => ({
        maxAmount: maxRemaining,
        amount: maxRemaining,
        displayAmount: '',

        init() {
            this.setLunas();
        },

        setLunas() {
            this.amount = this.maxAmount;
            this.displayAmount = this.formatNumber(this.amount);
        },

        formatNumber(val) {
            let n = parseFloat(val) || 0;
            return n.toLocaleString('id-ID');
        },

        formatInput(e) {
            let raw = e.target.value.replace(/[^\d]/g, '');
            let parsed = parseInt(raw, 10) || 0;
            
            // Prevent exceeding max amount visually
            if(parsed > this.maxAmount) {
                if(typeof showToast === 'function') showToast('Maksimal penerimaan ' + this.formatNumber(this.maxAmount), 'warning');
                parsed = this.maxAmount;
            }
            
            this.amount = parsed;
            
            let cursor = e.target.selectionStart;
            let val = parsed === 0 && raw === '' ? '' : parsed.toLocaleString('id-ID');
            
            let diff = e.target.value.length - val.length;
            this.displayAmount = val;
            
            this.$nextTick(() => {
                e.target.setSelectionRange(Math.max(0, cursor - diff), Math.max(0, cursor - diff));
            });
        }
    }));
});
</script>
@endpush
@endsection

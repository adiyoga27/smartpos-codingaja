@extends('layouts.app')
@section('title', 'Buat Return Penjualan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.sale_returns.index') }}" class="text-slate-500 hover:text-primary-600 transition-colors">Return Penjualan</a></li>
    <li class="breadcrumb-item active text-slate-800 font-semibold" aria-current="page">Buat Return</li>
@endsection
@section('content')
<div x-data="returnForm()" class="max-w-7xl mx-auto pb-10">
<form action="{{ route('transaksi.sale_returns.store') }}" method="POST" id="returnForm" @submit.prevent="handleSubmit">
    @csrf

    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-arrow-return-left text-primary-600"></i> Buat Return Penjualan
            </h2>
            <p class="text-sm text-slate-500 mt-1">Lengkapi form di bawah ini untuk mencatat pengembalian barang dari pelanggan.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('transaksi.sale_returns.index') }}" class="btn btn-light rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm hover:bg-slate-100 transition-all">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-5 py-2 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center gap-2" :disabled="items.length === 0 || !saleId">
                <i class="bi bi-check2-circle text-lg"></i> Simpan Return
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Details & Items -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Detail Informasi Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-primary-500"></div>
                <div class="p-5 sm:p-6">
                    <h6 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-5">
                        <i class="bi bi-info-circle text-primary-500"></i> Informasi Umum
                    </h6>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">No. Dokumen</label>
                            <input type="text" name="document_number" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-sm rounded-xl px-4 py-2.5 font-mono" value="{{ $documentNumber }}" readonly required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Return</label>
                            <input type="date" name="return_date" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Pilih Invoice Penjualan</label>
                            <div class="relative">
                                <select name="sale_id" x-model="saleId" @change="loadItems()" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm select2" required>
                                    <option value="">- Pilih Invoice -</option>
                                    @foreach($sales as $sale)
                                    <option value="{{ $sale->id }}">{{ $sale->invoice_number }} - {{ $sale->customer?->name ?? 'Umum' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Alasan Return <span class="text-red-500">*</span></label>
                            <textarea name="reason" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" rows="2" placeholder="Jelaskan alasan pengembalian barang..." required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Return Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h6 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-box-seam text-indigo-500"></i> Item Return
                        </h6>
                        <p class="text-xs text-slate-500 mt-1">Pilih invoice untuk memunculkan item barang yang direturn</p>
                    </div>
                </div>
                
                <div class="p-0">
                    <!-- Empty State -->
                    <div class="text-center py-16" x-show="items.length === 0">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="bi bi-receipt text-4xl text-slate-300"></i>
                        </div>
                        <h5 class="text-slate-600 font-medium mb-1">Belum ada invoice dipilih</h5>
                        <p class="text-sm text-slate-400">Silakan pilih invoice penjualan di atas terlebih dahulu.</p>
                    </div>

                    <!-- Item List -->
                    <div class="divide-y divide-slate-100" x-show="items.length > 0" x-cloak>
                        <template x-for="(it, idx) in items" :key="idx">
                            <div class="p-4 sm:p-5 hover:bg-slate-50 transition-colors group">
                                <div class="flex flex-col sm:flex-row gap-4 items-center">
                                    <!-- Product Info -->
                                    <div class="flex-1 flex items-start gap-3 w-full sm:w-auto">
                                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex-shrink-0 flex items-center justify-center border border-indigo-100 shadow-sm">
                                            <i class="bi bi-box text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800 line-clamp-2" x-text="it.product_name"></p>
                                            <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                                                <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-600 font-medium" x-text="'Harga: ' + formatRp(it.unit_price)"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Inputs -->
                                    <div class="w-full sm:w-auto flex items-center gap-3">
                                        <div class="w-24">
                                            <label class="block text-[10px] uppercase font-semibold text-slate-500 mb-1 sm:hidden">Qty Return</label>
                                            <div class="relative">
                                                <input type="number" x-model.number="it.quantity" min="0" step="0.01" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 text-center font-bold" @input="updateTrigger++">
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">Qty</span>
                                            </div>
                                        </div>
                                        <div class="w-32 text-right">
                                            <label class="block text-[10px] uppercase font-semibold text-slate-500 mb-1 sm:hidden">Total</label>
                                            <div class="text-sm font-bold text-indigo-600 bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-100" x-text="formatRp(it.quantity * it.unit_price)"></div>
                                        </div>
                                        <button type="button" @click="items.splice(idx, 1); updateTrigger++" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm flex-shrink-0" title="Hapus Item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="p-3 bg-amber-50/50 border-t border-slate-100 text-center">
                            <p class="text-xs text-amber-700"><i class="bi bi-info-circle me-1"></i> Ubah Qty menjadi 0 atau hapus item yang tidak ikut direturn.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary & Method -->
        <div class="space-y-6">
            <!-- Refund Method -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                <div class="p-5 sm:p-6 border-b border-slate-50">
                    <h6 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-1">
                        <i class="bi bi-wallet2 text-emerald-500"></i> Metode Refund
                    </h6>
                    <p class="text-xs text-slate-500">Pilih bagaimana dana dikembalikan</p>
                </div>
                
                <div class="p-5 sm:p-6 bg-slate-50/30">
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer relative group">
                            <input type="radio" name="refund_method" value="credit" x-model="refundMethod" class="peer sr-only">
                            <div class="py-3 px-2 text-center text-sm font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl transition-all duration-200 group-hover:bg-slate-100 group-active:scale-95 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-600 peer-checked:shadow-lg peer-checked:shadow-emerald-500/30">
                                <i class="bi bi-journal-minus text-lg block mb-1 peer-checked:hidden"></i>
                                <i class="bi bi-check-circle-fill text-lg block mb-1 hidden peer-checked:block"></i>
                                <span class="text-xs leading-tight">Potong<br>Piutang</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer relative group">
                            <input type="radio" name="refund_method" value="cash" x-model="refundMethod" class="peer sr-only">
                            <div class="py-3 px-2 text-center text-sm font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl transition-all duration-200 group-hover:bg-slate-100 group-active:scale-95 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-600 peer-checked:shadow-lg peer-checked:shadow-emerald-500/30">
                                <i class="bi bi-cash-stack text-lg block mb-1 peer-checked:hidden"></i>
                                <i class="bi bi-check-circle-fill text-lg block mb-1 hidden peer-checked:block"></i>
                                <span class="text-xs leading-tight">Tunai<br>(Cash)</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl shadow-xl text-white overflow-hidden relative">
                <!-- decorative background -->
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                
                <div class="p-5 sm:p-6 border-b border-slate-700/50 relative z-10">
                    <h6 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="bi bi-calculator text-primary-400"></i> Ringkasan Return
                    </h6>
                </div>
                
                <div class="p-5 sm:p-6 relative z-10 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Total Item</span>
                        <span class="font-semibold text-slate-200" x-text="validItemCount() + ' Item'"></span>
                    </div>
                    
                    <div class="pt-4 mt-2 border-t border-slate-700/50">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-slate-400">Total Nilai Return</span>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-indigo-400" x-text="formatRp(grandTotal())"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Action Button -->
            <div class="md:hidden">
                 <button type="submit" class="w-full btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-5 py-3.5 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center justify-center gap-2 text-base font-bold" :disabled="items.length === 0 || !saleId">
                    <i class="bi bi-check2-circle text-xl"></i> Simpan Return
                </button>
            </div>
        </div>
    </div>
</form>
</div>

@push('styles')
<style>
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        min-height: 42px;
        padding-top: 2px;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.2);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('returnForm', () => ({
        sales: {!! $salesJson !!},
        saleId: '',
        items: [],
        refundMethod: 'credit',
        updateTrigger: 0,

        loadItems() {
            if(!this.saleId) {
                this.items = [];
                return;
            }
            let sale = this.sales.find(s => s.id == this.saleId);
            if(sale) {
                this.items = sale.items.map(it => ({
                    product_id: it.product_id,
                    product_name: it.product_name,
                    unit_price: parseFloat(it.unit_price) || 0,
                    quantity: 1
                }));
            } else {
                this.items = [];
            }
            this.updateTrigger++;
        },

        validItemCount() {
            this.updateTrigger;
            return this.items.filter(it => it.quantity > 0).length;
        },

        grandTotal() {
            this.updateTrigger;
            return this.items.reduce((sum, it) => sum + ((parseFloat(it.quantity) || 0) * it.unit_price), 0);
        },

        formatRp(angka) {
            return 'Rp ' + Math.round(parseFloat(angka)).toLocaleString('id-ID');
        },

        handleSubmit(e) {
            if (this.items.length === 0) {
                if (typeof showToast === 'function') showToast('Pilih invoice terlebih dahulu', 'warning');
                return;
            }
            let hasValidItem = this.items.some(it => (parseFloat(it.quantity)||0) > 0);
            if (!hasValidItem) {
                if (typeof showToast === 'function') showToast('Minimal ada 1 item dengan qty lebih dari 0', 'warning');
                return;
            }
            
            let form = e.target;
            form.querySelectorAll('input[name^="items["]').forEach(el => el.remove());
            
            let idx = 0;
            this.items.forEach(it => {
                let qty = parseFloat(it.quantity) || 0;
                if (qty > 0) {
                    form.insertAdjacentHTML('beforeend',
                        `<input type="hidden" name="items[${idx}][product_id]" value="${it.product_id}">` +
                        `<input type="hidden" name="items[${idx}][quantity]" value="${qty}">` +
                        `<input type="hidden" name="items[${idx}][unit_price]" value="${it.unit_price}">`
                    );
                    idx++;
                }
            });
            form.submit();
        }
    }));
});

$(document).ready(function() { 
    if($.fn.select2) {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' }); 
        
        $('.select2').on('change', function(e) {
            let formElement = document.querySelector('[x-data="returnForm()"]');
            if(formElement) {
                let alpineData = Alpine.$data(formElement);
                if(alpineData) {
                    alpineData.saleId = $(this).val();
                    alpineData.loadItems();
                }
            }
        });
    }
});
</script>
@endpush
@endsection

@extends('layouts.app')
@section('title', 'Pembelian Langsung')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index') }}" class="text-slate-500 hover:text-slate-700">Pembelian</a></li>
    <li class="breadcrumb-item active text-slate-800 font-medium">Pembelian Langsung</li>
@endsection
@section('content')
<div x-data="directForm()" class="pb-24 lg:pb-8">
<form action="{{ route('transaksi.purchases.storeDirect') }}" method="POST" id="directForm" @submit.prevent="handleSubmit">
    @csrf
    
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Pembelian Langsung</h2>
            <p class="text-sm text-slate-500 mt-1">Catat transaksi pembelian produk langsung ke supplier secara instan.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Form Column -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Document Details Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="bi bi-file-earmark-text text-lg"></i>
                    </div>
                    <h3 class="text-base font-semibold text-slate-800">Detail Dokumen</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700">No. Dokumen <span class="text-red-500">*</span></label>
                            <input type="text" name="document_number" class="form-input w-full bg-slate-50 text-slate-500 cursor-not-allowed" value="{{ $documentNumber }}" readonly required>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700">Supplier <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select name="supplier_id" class="form-select w-full select2" required>
                                    <option value="">- Pilih Supplier -</option>
                                    @foreach($suppliers as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700">Tanggal Pembelian <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-calendar3 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 z-10 pointer-events-none"></i>
                                <input type="date" name="purchase_date" class="form-input w-full pl-10" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="space-y-1.5" x-show="remaining() > 0" x-cloak>
                            <label class="text-sm font-medium text-slate-700">Jatuh Tempo <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-calendar-check absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 z-10 pointer-events-none"></i>
                                <input type="date" name="due_date" class="form-input w-full pl-10" :required="remaining() > 0">
                            </div>
                        </div>
                        <div class="md:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium text-slate-700">Catatan <span class="text-slate-400 font-normal text-xs">(Opsional)</span></label>
                            <textarea name="notes" class="form-input w-full resize-none" rows="2" placeholder="Tambahkan catatan khusus untuk transaksi ini..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden flex flex-col" style="min-height: 400px;">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                            <i class="bi bi-box-seam text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-slate-800">Daftar Produk</h3>
                            <p class="text-xs text-slate-500" x-text="itemCount()"></p>
                        </div>
                    </div>
                    <button type="button" class="btn bg-slate-800 hover:bg-slate-900 text-white border-0 rounded-xl px-4 py-2 shadow-sm transition-all" @click="showModal = true">
                        <i class="bi bi-plus-lg mr-1.5"></i> Tambah Produk
                    </button>
                </div>
                
                <div class="p-6 flex-1 flex flex-col bg-slate-50/30">
                    <!-- Empty State -->
                    <div class="flex-1 flex flex-col items-center justify-center text-center py-12" x-show="Object.keys(selectedItems).length === 0" x-transition>
                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                            <i class="bi bi-cart-x text-4xl text-slate-300"></i>
                        </div>
                        <h4 class="text-lg font-medium text-slate-700 mb-1">Belum ada produk</h4>
                        <p class="text-sm text-slate-500 max-w-sm mb-6">Silakan klik tombol "Tambah Produk" di atas untuk memilih barang yang dibeli.</p>
                        <button type="button" class="btn btn-outline-primary rounded-xl px-5 py-2.5" @click="showModal = true">
                            <i class="bi bi-search mr-2"></i> Cari Produk
                        </button>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-4" x-show="Object.keys(selectedItems).length > 0" x-transition>
                        <template x-for="(it, id) in selectedItems" :key="id">
                            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:border-blue-300 hover:shadow-md transition-all duration-200 group relative">
                                <!-- Remove Button -->
                                <button type="button" class="absolute -top-3 -right-3 w-7 h-7 bg-white border border-red-200 text-red-500 rounded-full flex items-center justify-center shadow-sm hover:bg-red-50 hover:text-red-600 hover:border-red-300 transition-colors z-10 opacity-0 group-hover:opacity-100 focus:opacity-100" @click="delete selectedItems[id]; renderHidden()" title="Hapus item">
                                    <i class="bi bi-x text-lg"></i>
                                </button>
                                
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <!-- Image & Basic Info -->
                                    <div class="flex items-start gap-4 sm:w-1/3 border-b sm:border-b-0 sm:border-r border-slate-100 pb-4 sm:pb-0 sm:pr-4">
                                        <div class="w-16 h-16 rounded-lg bg-slate-100 border border-slate-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                            <img :src="it.photo" class="w-full h-full object-cover" x-show="it.photo">
                                            <i class="bi bi-image text-slate-300 text-2xl" x-show="!it.photo"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-semibold text-slate-800 line-clamp-2 leading-tight mb-1" x-text="it.name"></h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 mb-2" x-text="it.code"></span>
                                            
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px]">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-slate-400">Jual:</span>
                                                    <span class="font-medium text-emerald-600" x-text="formatRp(it.selling_price)"></span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <span class="text-slate-400">Reseller:</span>
                                                    <span class="font-medium text-amber-600" x-text="formatRp(it.wholesale_price)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Inputs & Total -->
                                    <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4 items-center">
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-medium text-slate-500 block">Kuantitas</label>
                                            <div class="relative">
                                                <input type="number" :value="it.qty" min="0.01" step="0.01" class="form-input w-full text-center text-sm font-medium focus:ring-blue-500 focus:border-blue-500 pr-8"
                                                       @change="updateItem(id, 'qty', $event.target.value)" @input="updateItem(id, 'qty', $event.target.value)">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none z-10">Qty</span>
                                            </div>
                                        </div>
                                        <div class="space-y-1 md:col-span-2">
                                            <div class="flex justify-between items-center">
                                                <label class="text-[11px] font-medium text-slate-500">Harga Beli & Diskon</label>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="relative flex-1">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 z-10 pointer-events-none">Rp</span>
                                                    <input type="text" :value="fmtNum(it.price)" class="form-input w-full pl-8 text-sm input-rupiah" placeholder="Harga"
                                                           @input="updateItem(id, 'price', $event.target.value); formatRupiahInput($event.target)">
                                                </div>
                                                <div class="relative w-24">
                                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-red-400 z-10 pointer-events-none"><i class="bi bi-tag"></i></span>
                                                    <input type="text" :value="it.discount ? fmtNum(it.discount) : '0'" class="form-input w-full pl-7 pr-2 text-sm input-rupiah text-red-500" placeholder="Diskon"
                                                           @input="updateItem(id, 'disc', $event.target.value); formatRupiahInput($event.target)" title="Diskon">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-1 text-right">
                                            <label class="text-[11px] font-medium text-slate-500 block">Subtotal Item</label>
                                            <div class="text-base font-bold text-blue-700 tracking-tight" x-text="lineTotal(it)"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Summary Column -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 sticky top-24 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
                    <h3 class="text-base font-semibold flex items-center gap-2">
                        <i class="bi bi-calculator text-blue-200"></i> Ringkasan Pembayaran
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Total Item</span>
                            <span class="font-semibold text-slate-800" x-text="itemCount()"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-semibold text-slate-800" x-text="formatRp(subtotal())"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Total Diskon</span>
                            <span class="font-semibold text-red-500" x-text="'- ' + formatRp(discountTotal())"></span>
                        </div>
                        
                        <div class="pt-4 border-t border-slate-200 border-dashed">
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold text-slate-700">TOTAL KESELURUHAN</span>
                                <div class="text-right">
                                    <span class="text-xs text-slate-400 block mb-0.5">IDR</span>
                                    <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600" x-text="formatRpNumber(grandTotal())"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                                <i class="bi bi-wallet2 text-slate-500"></i> Metode Pembayaran
                            </span>
                            <button type="button" @click="addPayment()" class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                                <i class="bi bi-plus-circle text-sm"></i> Tambah
                            </button>
                        </div>

                        <template x-for="(pmt, idx) in payments" :key="idx">
                            <div class="flex items-center gap-2 mb-2 p-2 bg-slate-50 rounded-lg border border-slate-100">
                                <select :name="'payments['+idx+'][cash_account_id]'" class="form-select text-xs py-1.5 flex-1" required>
                                    <option value="">- Pilih Kas/Bank -</option>
                                    @foreach($cashAccounts as $ca)
                                    <option value="{{ $ca->id }}">{{ $ca->name }} ({{ formatRupiah($ca->current_balance) }})</option>
                                    @endforeach
                                </select>
                                <div class="relative w-32">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 z-10">Rp</span>
                                    <input type="text" :value="pmt.amount" class="form-input text-xs py-1.5 pl-7 text-right" placeholder="0"
                                           @input="pmt.amount = $event.target.value.replace(/[^0-9]/g, '')"
                                           :name="'payments['+idx+'][amount]'" required>
                                </div>
                                <button type="button" @click="payments.splice(idx, 1)" class="text-red-400 hover:text-red-600 p-1 flex-shrink-0">
                                    <i class="bi bi-x-circle text-sm"></i>
                                </button>
                            </div>
                        </template>

                        <div x-show="payments.length === 0" class="text-center py-3 text-xs text-slate-400 bg-slate-50 rounded-lg">
                            Belum ada metode pembayaran. Klik <span class="text-blue-500 font-medium">Tambah</span>.
                        </div>

                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-100" x-show="payments.length > 0">
                            <span class="text-xs text-slate-500">Total Dibayar</span>
                            <span class="text-sm font-bold text-emerald-600" x-text="formatRp(paidTotal())"></span>
                        </div>
                        <div class="flex justify-between items-center mt-1" x-show="payments.length > 0 && remaining() > 0">
                            <span class="text-xs text-slate-500">Sisa Hutang</span>
                            <span class="text-sm font-bold text-red-500" x-text="formatRp(remaining())"></span>
                        </div>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-slate-100">
                        <button type="submit" class="btn w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-md hover:shadow-lg transition-all font-semibold flex items-center justify-center gap-2" :disabled="Object.keys(selectedItems).length === 0">
                            <i class="bi bi-check2-circle text-lg"></i>
                            Simpan Pembelian
                        </button>
                        <a href="{{ route('transaksi.purchases.index') }}" class="btn w-full py-2.5 bg-white border-2 border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300 rounded-xl transition-all font-medium flex items-center justify-center gap-2">
                            <i class="bi bi-x-lg"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Product Selection Modal (Glassmorphism) -->
<div x-show="showModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" @keydown.escape.window="showModal = false">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" x-show="showModal" x-transition.opacity @click="showModal = false"></div>
    
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden" 
         x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-8 scale-95"
         @click.stop>
         
        <!-- Modal Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 px-6 py-5 bg-white border-b border-slate-100 z-10 relative">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <i class="bi bi-box2 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Pilih Produk</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Klik pada produk untuk menambahkannya ke keranjang</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 z-10 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" @input="filterProducts()" class="form-input w-full pl-10 pr-4 py-2.5 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors" placeholder="Cari nama / kode produk...">
                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 z-10" x-show="searchQuery" @click="searchQuery = ''; filterProducts()">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
                <button @click="showModal = false" class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-colors flex-shrink-0">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        
        <!-- Modal Body (Product Grid) -->
        <div class="overflow-y-auto p-6 flex-1 bg-slate-50/50">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <template x-for="p in filteredProducts()" :key="p.id">
                    <div class="bg-white border border-slate-200 rounded-2xl p-3 cursor-pointer hover:border-indigo-400 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full relative overflow-hidden"
                         @click="addProduct(p); showToast(p.name + ' ditambahkan', 'success')">
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-indigo-600/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10"></div>
                        
                        <!-- Add Icon that appears on hover -->
                        <div class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-indigo-600 opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-300 z-20">
                            <i class="bi bi-plus-lg font-bold"></i>
                        </div>

                        <!-- Image -->
                        <div class="w-full aspect-[4/3] rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-3 overflow-hidden relative">
                            <img :src="p.photo" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" x-show="p.photo">
                            <i class="bi bi-image text-3xl text-slate-300" x-show="!p.photo"></i>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 flex flex-col">
                            <span class="text-[10px] font-semibold text-slate-400 mb-1 inline-block" x-text="p.code"></span>
                            <h5 class="text-xs font-bold text-slate-800 line-clamp-2 leading-tight mb-2 group-hover:text-indigo-700 transition-colors" x-text="p.name"></h5>
                            
                            <div class="mt-auto space-y-1.5 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] text-slate-500">Harga Beli</span>
                                    <span class="text-[11px] font-bold text-indigo-600" x-text="formatRp(p.price)"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-1 mt-1 bg-slate-50 rounded-lg p-1.5 border border-slate-100/50">
                                    <div class="text-center border-r border-slate-200">
                                        <div class="text-[9px] text-slate-400 mb-0.5">Jual</div>
                                        <div class="text-[10px] font-semibold text-emerald-600" x-text="formatRp(p.selling_price)"></div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-[9px] text-slate-400 mb-0.5">Grosir</div>
                                        <div class="text-[10px] font-semibold text-amber-600" x-text="formatRp(p.wholesale_price)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Empty Search Result -->
            <div class="flex flex-col items-center justify-center py-20 text-center" x-show="filteredProducts().length === 0">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-400">
                    <i class="bi bi-search text-3xl"></i>
                </div>
                <h4 class="text-lg font-medium text-slate-700 mb-1">Produk tidak ditemukan</h4>
                <p class="text-sm text-slate-500 max-w-sm">Coba gunakan kata kunci lain untuk mencari nama atau kode produk yang Anda inginkan.</p>
            </div>
        </div>
    </div>
</div>
</div>
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('directForm', () => ({
        selectedItems: {},
        payments: [],
        showModal: false,
        searchQuery: '',
        products: {!! $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'price' => (float) $p->purchase_price, 'selling_price' => (float) $p->selling_price, 'wholesale_price' => (float) $p->wholesale_price, 'photo' => $p->photo ? asset('storage/'.$p->photo) : ''])->values()->toJson() !!},

        itemCount() { let n = Object.keys(this.selectedItems).length; return n + ' item'; },
        subtotal() { return Object.values(this.selectedItems).reduce((s, it) => s + it.qty * it.price, 0); },
        discountTotal() { return Object.values(this.selectedItems).reduce((s, it) => s + (it.discount || 0), 0); },
        grandTotal() { return Math.max(0, this.subtotal() - this.discountTotal()); },
        lineTotal(it) { return this.formatRp(Math.max(0, it.qty * it.price - (it.discount || 0))); },
        paidTotal() { return this.payments.reduce((s, p) => s + (parseInt(p.amount) || 0), 0); },
        remaining() { return Math.max(0, this.grandTotal() - this.paidTotal()); },

        addPayment() { this.payments.push({ cash_account_id: '', amount: '' }); },

        parseRupiah(val) { return parseFloat((val || '0').replace(/\./g, '').replace(',', '.')) || 0; },
        fmtNum(val) { let n = parseFloat(val) || 0; return n % 1 === 0 ? n.toLocaleString('id-ID') : n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        formatRp(angka) { return 'Rp ' + Math.round(parseFloat(angka)).toLocaleString('id-ID'); },
        formatRpNumber(angka) { return Math.round(parseFloat(angka)).toLocaleString('id-ID'); },

        formatRupiahInput(el) {
            let cursor = el.selectionStart, raw = el.value.replace(/[^\d,]/g, '');
            let parts = raw.split(','); if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
            let intPart = parseInt(parts[0] || '0', 10), decPart = parts.length > 1 ? parts[1].substring(0, 2) : '';
            let val = intPart.toLocaleString('id-ID'); if (raw.includes(',')) val += ',' + decPart;
            let diff = el.value.length - val.length; el.value = val;
            el.setSelectionRange(Math.max(0, cursor - diff), Math.max(0, cursor - diff));
        },

        filteredProducts() {
            let q = this.searchQuery.toLowerCase();
            return this.products.filter(p => !q || p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q));
        },

        addProduct(p) {
            if (this.selectedItems[p.id]) {
                this.selectedItems[p.id].qty += 1;
            } else {
                this.selectedItems[p.id] = { ...p, qty: 1, discount: 0 };
            }
            // this.showModal = false; // Kept open so user can add multiple items easily
        },

        updateItem(id, field, val) {
            if (!this.selectedItems[id]) return;
            let it = this.selectedItems[id];
            if (field === 'qty') { let q = parseFloat(val) || 0; if (q <= 0) { delete this.selectedItems[id]; return; } it.qty = q; }
            else if (field === 'price') it.price = this.parseRupiah(val);
            else if (field === 'disc') it.discount = this.parseRupiah(val);
        },

        renderHidden() {},

        handleSubmit(e) {
            if (Object.keys(this.selectedItems).length === 0) { 
                if (typeof showToast === 'function') showToast('Pilih minimal 1 produk', 'warning'); 
                else alert('Pilih minimal 1 produk');
                return; 
            }
            let supplier = document.querySelector('[name="supplier_id"]');
            if (!supplier || !supplier.value) { 
                if (typeof showToast === 'function') showToast('Pilih supplier', 'warning'); 
                else alert('Pilih supplier');
                return; 
            }
            let form = e.target;
            form.querySelectorAll('input[name^="items["]').forEach(el => el.remove());
            let idx = 0;
            Object.entries(this.selectedItems).forEach(([id, it]) => {
                form.insertAdjacentHTML('beforeend',
                    `<input type="hidden" name="items[${idx}][product_id]" value="${id}">` +
                    `<input type="hidden" name="items[${idx}][quantity]" value="${it.qty}">` +
                    `<input type="hidden" name="items[${idx}][unit_price]" value="${it.price}">` +
                    `<input type="hidden" name="items[${idx}][discount]" value="${it.discount || 0}">`);
                idx++;
            });
            this.payments.forEach((p, i) => {
                if (p.cash_account_id && parseInt(p.amount) > 0) {
                    form.insertAdjacentHTML('beforeend',
                        `<input type="hidden" name="payments[${i}][cash_account_id]" value="${p.cash_account_id}">` +
                        `<input type="hidden" name="payments[${i}][amount]" value="${p.amount}">`);
                }
            });
            form.submit();
        }
    }));
});

$(document).ready(function() { 
    if($.fn.select2) {
        $('.select2').select2({ 
            theme: 'bootstrap-5', 
            width: '100%',
            placeholder: "- Pilih Supplier -" 
        });
        
        // Ensure AlpineJS state is aware of select2 changes (optional for this specific form if not tracked by Alpine, but good practice)
        $('.select2').on('select2:select', function (e) {
            // Document querySelector will handle validation on submit
        });
    }
});
</script>
@endpush
@endsection


@extends('layouts.app')
@section('title', 'Buat PO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.purchases.index') }}" class="text-slate-500 hover:text-primary-600 transition-colors">Pembelian</a></li>
    <li class="breadcrumb-item active text-slate-800 font-semibold" aria-current="page">Buat PO</li>
@endsection
@section('content')
<div x-data="poForm()" class="max-w-7xl mx-auto pb-10">
<form action="{{ route('transaksi.purchases.store') }}" method="POST" id="poForm" @submit.prevent="handleSubmit">
    @csrf

    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-bag-plus text-primary-600"></i> Buat Purchase Order
            </h2>
            <p class="text-sm text-slate-500 mt-1">Lengkapi form di bawah ini untuk membuat pesanan pembelian baru.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('transaksi.purchases.index') }}" class="btn btn-light rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm hover:bg-slate-100 transition-all">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-5 py-2 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center gap-2" :disabled="Object.keys(selectedItems).length === 0">
                <i class="bi bi-check2-circle text-lg"></i> Simpan PO
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
                            <input type="text" name="document_number" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all font-mono" value="{{ $documentNumber }}" readonly required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal</label>
                            <input type="date" name="purchase_date" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Supplier</label>
                            <div class="relative">
                                <select name="supplier_id" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm select2" required>
                                    <option value="">- Pilih Supplier -</option>
                                    @foreach($suppliers as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Catatan</label>
                            <textarea name="notes" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" rows="2" placeholder="Tambahkan catatan opsional..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item PO Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h6 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-box-seam text-indigo-500"></i> Daftar Item
                        </h6>
                        <p class="text-xs text-slate-500 mt-1">Pilih produk yang akan dipesan</p>
                    </div>
                    <button type="button" class="btn bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border-0 rounded-xl px-4 py-2 text-sm font-medium transition-all shadow-sm flex items-center gap-2 w-full sm:w-auto justify-center" @click="showModal = true">
                        <i class="bi bi-plus-lg"></i> Tambah Produk
                    </button>
                </div>
                
                <div class="p-0">
                    <!-- Empty State -->
                    <div class="text-center py-16" x-show="Object.keys(selectedItems).length === 0">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="bi bi-basket text-4xl text-slate-300"></i>
                        </div>
                        <h5 class="text-slate-600 font-medium mb-1">Belum ada item ditambahkan</h5>
                        <p class="text-sm text-slate-400">Silakan klik tombol tambah produk di atas.</p>
                    </div>

                    <!-- Item List -->
                    <div class="divide-y divide-slate-100" x-show="Object.keys(selectedItems).length > 0">
                        <template x-for="(it, id) in selectedItems" :key="id">
                            <div class="p-4 sm:p-5 hover:bg-slate-50 transition-colors group">
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <!-- Product Info -->
                                    <div class="flex items-start gap-3 sm:w-2/5">
                                        <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 shadow-sm flex-shrink-0 overflow-hidden flex items-center justify-center">
                                            <img :src="it.photo" class="w-full h-full object-cover" x-show="it.photo">
                                            <i class="bi bi-box-seam text-slate-300 text-2xl" x-show="!it.photo"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800 line-clamp-2" x-text="it.name"></p>
                                            <p class="text-xs text-slate-500 font-mono mt-1" x-text="it.code"></p>
                                            <button type="button" class="text-xs font-medium text-red-500 hover:text-red-700 mt-2 flex items-center gap-1 sm:hidden" @click="removeItem(id)">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Pricing Inputs -->
                                    <div class="flex-1 grid grid-cols-2 lg:grid-cols-4 gap-3 items-start">
                                        <div>
                                            <label class="block text-[10px] uppercase font-semibold text-slate-500 mb-1">Qty</label>
                                            <input type="number" :value="it.qty" min="0.01" step="0.01" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                                   @change="updateItem(id, 'qty', $event.target.value)" @input="updateItem(id, 'qty', $event.target.value)">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] uppercase font-semibold text-slate-500 mb-1">Harga Satuan</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rp</span>
                                                <input type="text" :value="fmtNum(it.price)" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 input-rupiah"
                                                       @input="updateItem(id, 'price', $event.target.value); formatRupiahInput($event.target)">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] uppercase font-semibold text-slate-500 mb-1">Diskon</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rp</span>
                                                <input type="text" :value="it.discount ? fmtNum(it.discount) : '0'" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 input-rupiah"
                                                       @input="updateItem(id, 'disc', $event.target.value); formatRupiahInput($event.target)">
                                            </div>
                                        </div>
                                        <div class="text-right flex flex-col justify-end h-full">
                                            <label class="block text-[10px] uppercase font-semibold text-slate-500 mb-1">Total</label>
                                            <span class="text-sm font-bold text-slate-800 bg-slate-50 px-2 py-1.5 rounded border border-slate-100" x-text="lineTotal(it)"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Button Desktop -->
                                    <div class="hidden sm:flex items-center justify-center pt-5">
                                        <button type="button" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm" @click="removeItem(id)" title="Hapus Item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary & Payment -->
        <div class="space-y-6">
            <!-- Payment Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                <div class="p-5 sm:p-6 border-b border-slate-50">
                    <h6 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-1">
                        <i class="bi bi-credit-card text-emerald-500"></i> Pembayaran
                    </h6>
                    <p class="text-xs text-slate-500">Atur status pembayaran PO</p>
                </div>
                
                <div class="p-5 sm:p-6 bg-slate-50/30">
                    <div class="flex gap-3 mb-5">
                        <label class="flex-1 cursor-pointer relative group">
                            <input type="radio" name="payment_status" value="credit" x-model="paymentStatus" class="peer sr-only">
                            <div class="py-3 px-4 text-sm font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl transition-all duration-200 group-hover:bg-slate-100 group-active:scale-95 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-600 peer-checked:shadow-lg peer-checked:shadow-emerald-500/30 flex items-center justify-center gap-2">
                                <i class="bi bi-hourglass-split text-lg" x-show="paymentStatus !== 'credit'"></i>
                                <i class="bi bi-check-circle-fill text-lg" x-show="paymentStatus === 'credit'" x-cloak></i>
                                <span>Hutang</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer relative group">
                            <input type="radio" name="payment_status" value="cash" x-model="paymentStatus" class="peer sr-only">
                            <div class="py-3 px-4 text-sm font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl transition-all duration-200 group-hover:bg-slate-100 group-active:scale-95 peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-600 peer-checked:shadow-lg peer-checked:shadow-emerald-500/30 flex items-center justify-center gap-2">
                                <i class="bi bi-cash-coin text-lg" x-show="paymentStatus !== 'cash'"></i>
                                <i class="bi bi-check-circle-fill text-lg" x-show="paymentStatus === 'cash'" x-cloak></i>
                                <span>Lunas / Tunai</span>
                            </div>
                        </label>
                    </div>

                    <!-- Hutang Fields -->
                    <div x-show="paymentStatus === 'credit'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Jatuh Tempo <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><i class="bi bi-calendar-x"></i></span>
                                <input type="date" name="due_date" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl pl-9 pr-4 py-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" :required="paymentStatus === 'credit'">
                            </div>
                        </div>
                        <div class="p-3 bg-amber-50 rounded-xl border border-amber-100/50 flex items-start gap-3">
                            <i class="bi bi-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <p class="text-xs text-amber-700 leading-relaxed">Pembayaran akan dicatat ke dalam hutang (Account Payable) dan dapat dibayar setelah PO diterima.</p>
                        </div>
                    </div>

                    <!-- Cash Fields -->
                    <template x-if="paymentStatus === 'cash'">
                        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-slate-600">Metode Pembayaran</label>
                                <button type="button" @click="addPayment()" class="text-xs bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-2 py-1 rounded-md font-medium transition-colors flex items-center gap-1">
                                    <i class="bi bi-plus"></i> Tambah
                                </button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(pmt, idx) in payments" :key="idx">
                                    <div class="flex items-center gap-2 bg-white p-2 rounded-xl border border-slate-200 shadow-sm">
                                        <select class="form-select text-xs sm:text-sm border-0 bg-transparent py-1.5 focus:ring-0 flex-1 px-1 font-medium text-slate-700" :class="'payment-select-'+idx" required>
                                            <option value="">- Akun -</option>
                                            @foreach($cashAccounts as $ca)
                                            <option value="{{ $ca->id }}">{{ $ca->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="w-px h-6 bg-slate-200"></div>
                                        <input type="text" x-model="pmt.amount" class="form-input text-sm text-right border-0 bg-transparent py-1.5 focus:ring-0 text-emerald-700 font-bold" placeholder="0" style="width:110px">
        <button type="button" @click="payments.splice(idx, 1); updateTrigger++" class="w-7 h-7 rounded bg-slate-100 hover:bg-red-100 text-slate-400 hover:text-red-500 flex items-center justify-center transition-colors">
                                            <i class="bi bi-x text-lg"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center justify-between mt-4" x-show="payments.length > 0">
                                <span class="text-xs font-semibold text-emerald-800">Total Dibayar</span>
                                <span class="text-sm font-bold text-emerald-700" x-text="formatRp(paidTotal())"></span>
                            </div>
                            
                            <div class="text-center py-4 text-slate-400 bg-white/50 rounded-xl border border-dashed border-slate-200" x-show="payments.length === 0">
                                <p class="text-xs">Klik tombol "Tambah" untuk mencatat pembayaran tunai/transfer saat PO ini dibuat.</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl shadow-xl text-white overflow-hidden relative">
                <!-- decorative background -->
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                
                <div class="p-5 sm:p-6 border-b border-slate-700/50 relative z-10">
                    <h6 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="bi bi-receipt text-primary-400"></i> Ringkasan Pesanan
                    </h6>
                </div>
                
                <div class="p-5 sm:p-6 relative z-10 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Total Item</span>
                        <span class="font-semibold text-slate-200" x-text="itemCount()"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Subtotal</span>
                        <span class="font-semibold text-slate-200" x-text="formatRp(subtotal())"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Diskon</span>
                        <span class="font-semibold text-red-400" x-text="formatRp(discountTotal())"></span>
                    </div>
                    
                    <div class="pt-4 mt-2 border-t border-slate-700/50">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-slate-400">Total Tagihan</span>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-indigo-400" x-text="formatRp(grandTotal())"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Action Button -->
            <div class="md:hidden">
                 <button type="submit" class="w-full btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-5 py-3.5 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center justify-center gap-2 text-base font-bold" :disabled="Object.keys(selectedItems).length === 0">
                    <i class="bi bi-check2-circle text-xl"></i> Simpan PO
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Product Modal -->
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-hidden flex items-center justify-center p-4 sm:p-6" @keydown.escape.window="showModal = false">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" x-show="showModal" x-transition.opacity @click="showModal = false"></div>
    
    <!-- Modal Panel -->
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh] overflow-hidden transform transition-all" x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.stop>
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white shrink-0">
            <div>
                <h5 class="text-lg font-bold text-slate-800">Pilih Produk</h5>
                <p class="text-xs text-slate-500">Klik pada produk untuk menambahkan ke daftar pesanan</p>
            </div>
            <button @click="showModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <!-- Search Bar -->
        <div class="px-6 py-3 bg-slate-50/50 border-b border-slate-100 shrink-0">
            <div class="relative max-w-md mx-auto">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" x-model="searchQuery" @input="filterProducts()" class="w-full bg-white border border-slate-200 text-slate-700 text-sm rounded-xl pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-sm" placeholder="Cari nama atau kode produk...">
            </div>
        </div>

        <!-- Product Grid -->
        <div class="overflow-y-auto p-6 flex-1 bg-slate-50/30">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <template x-for="p in filteredProducts()" :key="p.id">
                    <div class="group bg-white border border-slate-200 rounded-2xl p-3 cursor-pointer hover:border-primary-400 hover:shadow-lg hover:shadow-primary-500/10 transition-all duration-200"
                         @click="addProduct(p)">
                        <div class="w-full aspect-square rounded-xl bg-slate-50 flex items-center justify-center mb-3 overflow-hidden border border-slate-100 relative">
                            <img :src="p.photo" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" x-show="p.photo">
                            <i class="bi bi-box-seam text-3xl text-slate-300 group-hover:scale-110 transition-transform duration-300" x-show="!p.photo"></i>
                            
                            <!-- Hover Overlay Add -->
                            <div class="absolute inset-0 bg-primary-600/10 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="bg-primary-600 text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg transform scale-50 group-hover:scale-100 transition-all duration-300">
                                    <i class="bi bi-plus-lg"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs font-bold text-slate-800 line-clamp-2 leading-tight mb-1 group-hover:text-primary-600 transition-colors" x-text="p.name"></p>
                        <p class="text-[10px] text-slate-500 font-mono mb-2" x-text="p.code"></p>
                        <div class="flex items-center justify-between mt-auto">
                            <span class="text-xs font-extrabold text-primary-600" x-text="formatRp(p.price)"></span>
                        </div>
                    </div>
                </template>
            </div>
            
            <div class="text-center py-16" x-show="filteredProducts().length === 0">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-search text-3xl text-slate-400"></i>
                </div>
                <h5 class="text-slate-600 font-medium mb-1">Produk tidak ditemukan</h5>
                <p class="text-sm text-slate-400">Coba gunakan kata kunci pencarian yang lain.</p>
            </div>
        </div>
    </div>
</div>
</div>

@push('styles')
<style>
    /* Select2 Modernization */
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
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: #334155;
        font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('poForm', () => ({
        selectedItems: {},
        payments: [],
        paymentStatus: 'credit',
        showModal: false,
        searchQuery: '',
        updateTrigger: 0,
        products: {!! $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'price' => (float) $p->purchase_price, 'photo' => $p->photo ? asset('storage/'.$p->photo) : ''])->values()->toJson() !!},

        itemCount() { this.updateTrigger; return Object.keys(this.selectedItems).length; },
        subtotal() { this.updateTrigger; return Object.values(this.selectedItems).reduce((s, it) => s + it.qty * it.price, 0); },
        discountTotal() { this.updateTrigger; return Object.values(this.selectedItems).reduce((s, it) => s + (it.discount || 0), 0); },
        grandTotal() { this.updateTrigger; return Math.max(0, this.subtotal() - this.discountTotal()); },
        lineTotal(it) { this.updateTrigger; return this.formatRp(Math.max(0, it.qty * it.price - (it.discount || 0))); },
        paidTotal() { this.updateTrigger; return this.payments.reduce((s, p) => s + (parseInt(p.amount) || 0), 0); },

        addPayment() { this.payments.push({ amount: '' }); this.updateTrigger++; },

        parseRupiah(val) { return parseFloat((val || '0').replace(/\./g, '').replace(',', '.')) || 0; },
        fmtNum(val) { let n = parseFloat(val) || 0; return n % 1 === 0 ? n.toLocaleString('id-ID') : n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        formatRp(angka) { return 'Rp ' + Math.round(parseFloat(angka)).toLocaleString('id-ID'); },

        formatRupiahInput(el) {
            let cursor = el.selectionStart, raw = el.value.replace(/[^\d,]/g, '');
            let parts = raw.split(','); if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
            let intPart = parseInt(parts[0] || '0', 10), decPart = parts.length > 1 ? parts[1].substring(0, 2) : '';
            let val = intPart.toLocaleString('id-ID'); if (raw.includes(',')) val += ',' + decPart;
            let diff = el.value.length - val.length; el.value = val;
            el.setSelectionRange(Math.max(0, cursor - diff), Math.max(0, cursor - diff));
        },

        filterProducts() {
            let q = this.searchQuery.toLowerCase();
            return this.products.filter(p => !q || p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q));
        },

        filteredProducts() { return this.filterProducts(); },

        addProduct(p) {
            if (this.selectedItems[p.id]) {
                this.selectedItems[p.id].qty += 1;
            } else {
                this.selectedItems[p.id] = { ...p, qty: 1, discount: 0 };
            }
            this.updateTrigger++;
            if (typeof showToast === 'function') {
                showToast(p.name + ' ditambahkan', 'success');
            }
        },

        updateItem(id, field, val) {
            if (!this.selectedItems[id]) return;
            let it = this.selectedItems[id];
            if (field === 'qty') { 
                let q = parseFloat(val) || 0; 
                if (q <= 0) { this.removeItem(id); return; } 
                it.qty = q; 
            }
            else if (field === 'price') it.price = this.parseRupiah(val);
            else if (field === 'disc') it.discount = this.parseRupiah(val);
            
            this.updateTrigger++;
        },

        removeItem(id) {
            delete this.selectedItems[id];
            this.updateTrigger++;
        },

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
            if (this.paymentStatus === 'cash') {
                this.payments.forEach((p, i) => {
                    let sel = document.querySelector(`select.payment-select-${i}`);
                    if (sel && sel.value && parseInt(p.amount) > 0) {
                        form.insertAdjacentHTML('beforeend',
                            `<input type="hidden" name="payments[${i}][cash_account_id]" value="${sel.value}">` +
                            `<input type="hidden" name="payments[${i}][amount]" value="${p.amount}">`);
                    }
                });
            }
            form.submit();
        }
    }));
});

$(document).ready(function() { 
    if($.fn.select2) {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' }); 
    }
});
</script>
@endpush
@endsection

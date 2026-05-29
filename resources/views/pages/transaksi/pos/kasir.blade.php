@extends('layouts.pos')
@section('title', 'POS Kasir')
@section('content')
<div x-data="posKasir()" x-on:open-customer-modal="customerModal = true; customerForm = { name: '', phone: '', type: 'retail' }" id="posApp" class="h-full flex flex-col">
    <!-- Mini header -->
    <div class="flex items-center justify-between px-3 py-2 bg-white border-b border-slate-200 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <span class="font-bold text-slate-700 hidden sm:inline">POS Kasir</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative" x-data="{ custOpen: false, custSearch: '', selectedCust: null, custShowAll: false }" @click.outside="custOpen = false; custShowAll = false">
                <div class="flex items-center gap-1">
                    <i class="bi bi-person text-slate-400 text-sm"></i>
                    <input type="text"
                           x-model="custSearch"
                           @input="custOpen = true; custShowAll = false"
                           @focus="custOpen = true"
                           class="text-xs border-b border-slate-200 bg-transparent py-1 px-1 w-36 outline-none focus:border-primary-500"
                           placeholder="Walk-in..."
                           autocomplete="off">
                    <input type="hidden" name="customer_id" :value="selectedCust ? selectedCust.id : ''">
                    <button type="button" @click="custOpen = !custOpen; custShowAll = !custShowAll; custSearch = ''" class="text-slate-400 hover:text-slate-600 p-0.5" title="Lihat Semua Customer">
                        <i class="bi bi-chevron-down text-xs" :class="custShowAll && 'rotate-180'"></i>
                    </button>
                    <button type="button" @click="$dispatch('open-customer-modal')" class="text-primary-600 hover:text-primary-800 p-0.5" title="Kelola Customer">
                        <i class="bi bi-plus-circle-fill text-sm"></i>
                    </button>
                </div>
                <div x-show="custOpen && (custSearch.length >= 1 || custShowAll)" x-cloak x-transition.opacity
                     class="absolute z-30 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg w-64 max-h-48 overflow-y-auto">
                    <template x-for="c in custShowAll ? allCustomers : searchCustomers(custSearch)" :key="c.id">
                        <div @click="selectedCust=c; custSearch=c.name; custOpen=false"
                             class="px-3 py-2 text-sm hover:bg-primary-50 cursor-pointer flex items-center justify-between">
                            <span>
                                <span class="font-medium" x-text="c.name"></span>
                                <span class="text-slate-400 ml-1 text-xs" x-text="'['+c.code+']'"></span>
                            </span>
                        </div>
                    </template>
                    <div x-show="!custShowAll && searchCustomers(custSearch).length === 0"
                         class="px-3 py-2 text-sm text-slate-400 text-center">Tidak ditemukan</div>
                </div>
            </div>
            <span class="text-xs text-slate-400 hidden lg:inline">
                <i class="bi bi-calendar3 mr-1"></i> <span id="headerDateTime">{{ now()->isoFormat('dddd, D MMMM Y HH:mm') }}</span>
            </span>
            <button type="button" @click="historyModal=true; loadHistory()" class="btn btn-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Riwayat
            </button>
        </div>
    </div>

    <!-- Main POS content -->
    <div class="flex-1 flex flex-col lg:flex-row gap-3 p-3 min-h-0">
    <!-- LEFT: Product Grid -->
    <div class="flex-1 lg:w-[55%] min-w-0 flex flex-col">
        <div class="flex gap-2 mb-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" x-model="searchProduct" @input.debounce.100ms="filterProducts()"
                       class="form-input pl-10" placeholder="Cari produk (nama / kode / barcode)...">
            </div>
            <button type="button" @click="historyModal=true; loadHistory()" class="btn btn-secondary btn-sm whitespace-nowrap">
                <i class="bi bi-clock-history"></i> <span class="hidden sm:inline">Riwayat</span>
            </button>
        </div>

        <div class="flex gap-1.5 mb-3 overflow-x-auto pb-1" x-data="{ activeCat: 'all' }">
            <button @click="activeCat='all'; filterByCategory('all')"
                    :class="activeCat==='all' ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors shrink-0">
                <i class="bi bi-grid-fill mr-1"></i> Semua
            </button>
            @foreach($products->pluck('category.name', 'category_id')->unique()->filter() as $catId => $catName)
            <button @click="activeCat='{{ $catId }}'; filterByCategory('{{ $catId }}')"
                    :class="activeCat==='{{ $catId }}' ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors shrink-0">
                {{ $catName }}
            </button>
            @endforeach
        </div>

        <div class="flex-1 overflow-y-auto rounded-xl">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3" id="productGrid">
                @foreach($products as $product)
                <div class="product-card-item"
                     data-name="{{ strtolower($product->name) }}"
                     data-code="{{ strtolower($product->code) }}"
                     data-barcode="{{ strtolower($product->barcode ?? '') }}"
                     data-category="{{ $product->category_id }}">
                    <div class="pos-product-card group cursor-pointer"
                         @click="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->selling_price }}, {{ $product->stock }})">
                        <div class="relative">
                            @if($product->photo)
                                <img src="{{ asset('storage/'.$product->photo) }}" class="w-full h-24 object-cover">
                            @else
                                <div class="w-full h-24 bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                    <i class="bi bi-box-seam text-3xl text-slate-300"></i>
                                </div>
                            @endif
                            @if($product->stock <= 0)
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                    <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">HABIS</span>
                                </div>
                            @elseif($product->stock <= $product->min_stock)
                                <span class="absolute top-1.5 right-1.5 bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                                    {{ (int)$product->stock }}
                                </span>
                            @endif
                        </div>
                        <div class="p-2.5">
                            <p class="text-xs font-medium text-slate-800 line-clamp-2 leading-tight mb-1.5">{{ $product->name }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-primary-600">{{ formatRupiah($product->selling_price) }}</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium
                                    {{ $product->stock <= 0 ? 'bg-red-50 text-red-600' : ($product->stock <= $product->min_stock ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600') }}">
                                    {{ (int)$product->stock }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div x-show="filteredCount === 0" class="text-center py-12 text-slate-400" x-cloak>
                <i class="bi bi-search text-4xl block mb-3"></i>
                <p>Tidak ada produk ditemukan</p>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="w-full lg:w-[45%] lg:min-w-[480px] shrink-0 flex flex-col">
        <form action="{{ route('transaksi.sales.store') }}" method="POST" id="posForm" data-noloading="true" class="flex flex-col h-full">
            @csrf
            <div class="card flex flex-col h-full">
                <div class="card-header flex items-center justify-between bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-t-xl shrink-0">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-cart3 text-lg"></i>
                        <span class="font-bold">Keranjang</span>
                    </div>
                    <span class="bg-white/20 text-white text-xs px-2 py-1 rounded-full" x-text="cart.length + ' item'"></span>
                </div>

                <div class="overflow-y-auto flex-1 p-0">
                    <table class="table mb-0 w-full" id="cartTable">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="text-[11px]">Produk</th>
                                <th class="text-[11px]">Qty</th>
                                <th class="text-[11px]">Harga</th>
                                <th class="text-[11px]">Disc</th>
                                <th class="text-[11px] text-right">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div id="emptyCart" class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <i class="bi bi-basket text-4xl mb-3"></i>
                        <p class="text-sm">Keranjang kosong</p>
                        <p class="text-xs mt-1">Klik produk untuk menambahkan</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 space-y-2.5 shrink-0">
                    <input type="hidden" name="invoice_number" value="{{ $invoiceNumber }}">
                    <input type="hidden" name="sale_date" value="{{ now()->format('Y-m-d') }}">

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Pembayaran</label>
                            <select name="payment_method_id" class="form-select text-sm" id="paymentMethod" required>
                                @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" data-credit="{{ $pm->is_credit ? '1' : '0' }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Pajak</label>
                            <select name="tax_id" class="form-select text-sm" id="taxSelect" @change="recalcTax()">
                                <option value="">Tanpa Pajak</option>
                                @foreach($taxes as $tax)
                                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ isset($defaultTax) && $tax->id === $defaultTax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->rate, 1) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2" id="cashPanel">
                        <div>
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Diskon Tambahan</label>
                            <input type="number" x-model.number="additionalDiscount" class="form-input text-sm font-mono" value="0"
                                   @input="updateTotals()" />
                        </div>
                        <div>
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Bayar (Rp)</label>
                            <input type="text" name="paid_amount" id="paidAmount" class="form-input text-sm font-mono" value="0" inputmode="numeric"
                                   @input="updateChange()"
                                   @focus="$el.value = $el.value.replace(/\D/g, '')"
                                   @blur="$el.value = parseInt($el.value.replace(/\D/g, ''), 10).toLocaleString('id-ID')" />
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Catatan</label>
                        <textarea name="notes" class="form-input text-sm" rows="1" placeholder="Catatan tambahan... (opsional)"></textarea>
                    </div>

                    <div class="bg-white rounded-lg p-3 space-y-1.5 border border-slate-200">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-medium" id="cartSubtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Diskon Item</span>
                            <span class="text-red-500 font-medium" id="cartDiscount">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Pajak (<span id="taxLabel">0%</span>)</span>
                            <span class="font-medium text-amber-600" id="cartTax">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Diskon Tambahan</span>
                            <span class="text-red-500 font-medium" id="cartAddDisc">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t-2 border-slate-200">
                            <span class="text-base font-bold text-slate-800">TOTAL</span>
                            <span class="text-xl font-extrabold text-primary-700" id="cartTotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm" id="changeRow" style="display:none;">
                            <span class="text-slate-500">Kembalian</span>
                            <span class="font-medium text-emerald-600" id="changeAmount">Rp 0</span>
                        </div>
                    </div>
                    <input type="hidden" name="total_discount" id="totalAddDisc" value="0">

                    <div class="grid grid-cols-2 gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="btnPay" disabled>
                            <i class="bi bi-cash-coin text-lg"></i> Bayar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" @click="clearCart()" x-show="cart.length > 0">
                            <i class="bi bi-trash"></i> Kosongkan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Customer Modal -->
<div x-show="customerModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="customerModal=false" @keydown.escape.window="customerModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4 text-white flex items-center justify-between">
            <span class="font-bold">Tambah Customer</span>
            <button @click="customerModal=false" class="text-white/80 hover:text-white"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="p-5 space-y-3">
            <div>
                <label class="form-label text-sm">Nama <span class="text-red-500">*</span></label>
                <input type="text" x-model="customerForm.name" class="form-input text-sm" placeholder="Nama customer">
            </div>
            <div>
                <label class="form-label text-sm">No. Telepon</label>
                <input type="text" x-model="customerForm.phone" class="form-input text-sm" placeholder="0812...">
            </div>
            <div>
                <label class="form-label text-sm">Tipe</label>
                <select x-model="customerForm.type" class="form-select text-sm">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Grosir</option>
                </select>
            </div>
            <button type="button" @click="saveCustomer()" :disabled="customerFormLoading || !customerForm.name.trim()"
                    class="btn btn-primary btn-md w-full">
                <i class="bi bi-check-lg mr-1"></i> <span x-text="customerFormLoading ? 'Menyimpan...' : 'Simpan Customer'"></span>
            </button>
        </div>
    </div>
</div>

<!-- History Modal -->
<div x-show="historyModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="historyModal=false" @keydown.escape.window="historyModal=false">
    <div class="bg-white sm:rounded-2xl shadow-2xl w-full sm:max-w-xl max-h-[85vh] sm:max-h-[80vh] flex flex-col overflow-hidden rounded-t-2xl sm:rounded-2xl animate-slide-up">
        <div class="bg-white px-5 py-4 flex items-center justify-between shrink-0 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Riwayat Transaksi</h3>
                <p class="text-xs text-slate-400 mt-0.5">20 transaksi terakhir</p>
            </div>
            <button @click="historyModal=false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg text-slate-500 text-sm"></i>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 px-4 py-2">
            <div x-show="historyLoading" class="py-12 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-50 mb-3">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl text-primary-500"></i>
                </div>
                <p class="text-sm text-slate-400">Memuat transaksi...</p>
            </div>

            <div x-show="!historyLoading && historyList.length === 0" class="py-12 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-3">
                    <i class="bi bi-receipt text-2xl text-slate-300"></i>
                </div>
                <p class="text-sm text-slate-400">Belum ada transaksi</p>
            </div>

            <div class="space-y-2 pb-2">
                <template x-for="s in historyList" :key="s.id">
                    <div class="bg-slate-50 hover:bg-slate-100 rounded-xl p-4 transition-colors">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-semibold text-slate-800 truncate" x-text="s.invoice"></span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold shrink-0"
                                          :class="s.status==='paid'?'bg-emerald-100 text-emerald-700':(s.status==='partial'?'bg-amber-100 text-amber-700':'bg-red-100 text-red-700')"
                                          x-text="s.status==='paid'?'Lunas':(s.status==='partial'?'Sebagian':'Belum Bayar')"></span>
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                    <span x-text="s.date"></span>
                                    <span class="text-slate-300">·</span>
                                    <span class="px-2 py-0.5 bg-white rounded-md border border-slate-200 text-[11px] font-medium" x-text="s.method"></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-500 truncate" x-text="s.customer"></p>
                                <p class="text-lg font-bold text-slate-800 mt-0.5" x-text="s.total"></p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a :href="s.print_a4" target="_blank"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-600 hover:bg-primary-50 hover:border-primary-200 hover:text-primary-600 transition-colors">
                                    <i class="bi bi-printer"></i> A4
                                </a>
                                <a :href="s.print_thermal" target="_blank"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-600 hover:bg-primary-50 hover:border-primary-200 hover:text-primary-600 transition-colors">
                                    <i class="bi bi-receipt"></i> Thermal
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Print Modal -->
@if(session('last_sale_id'))
<div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden" @click.outside="open=false">
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4 text-white text-center">
            <i class="bi bi-check-circle-fill text-4xl block mb-2"></i>
            <p class="font-bold text-lg">Transaksi Berhasil!</p>
            <p class="text-sm text-blue-100">Pilih opsi cetak</p>
        </div>
        <div class="p-5 space-y-3">
            <a href="{{ route('pos.print-a4', session('last_sale_id')) }}" target="_blank"
               class="btn btn-primary btn-md w-full">
                <i class="bi bi-printer"></i> Cetak A4
            </a>
            <a href="{{ route('pos.print-thermal', session('last_sale_id')) }}" target="_blank"
               class="btn btn-secondary btn-md w-full">
                <i class="bi bi-receipt"></i> Cetak Thermal Printer
            </a>
            <button @click="open=false" class="btn btn-secondary btn-sm w-full">
                <i class="bi bi-x-lg"></i> Tutup
            </button>
        </div>
    </div>
</div>
@endif

<style>
@keyframes slide-up { from { transform: translateY(100%); } to { transform: translateY(0); } }
.animate-slide-up { animation: slide-up 0.3s ease-out; }
@media (min-width: 640px) { .animate-slide-up { animation: none; } }
</style>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('posKasir', () => ({
        cart: [],
        searchProduct: '',
        filteredCount: {{ $products->count() }},
        allCustomers: @json($customers->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])),
        customerModal: false,
        customerForm: { name: '', phone: '', type: 'retail' },
        customerFormLoading: false,
        additionalDiscount: 0,
        historyModal: false,
        historyList: [],
        historyLoading: false,

        init() {
            let self = this;
            window.posApp = this;
            this.$watch('cart', () => {
                let empty = document.getElementById('emptyCart');
                if (empty) empty.style.display = self.cart.length ? 'none' : 'flex';
                self.updateTotals();
            });
        },

        async saveCustomer() {
            if (!this.customerForm.name.trim()) return;
            this.customerFormLoading = true;
            try {
                let res = await fetch('{{ route('pos.customer-quick') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.customerForm)
                });
                let data = await res.json();
                if (data.success) {
                    this.allCustomers.push(data.customer);
                    this.customerModal = false;
                    showToast('Customer berhasil ditambahkan', 'success');
                }
            } catch(e) {
                showToast('Gagal menambah customer', 'error');
            }
            this.customerFormLoading = false;
        },

        async loadHistory() {
            this.historyLoading = true;
            try {
                let res = await fetch('{{ route('pos.recent') }}', { headers: { 'Accept': 'application/json' } });
                this.historyList = await res.json();
            } catch(e) {
                this.historyList = [];
            }
            this.historyLoading = false;
        },

        formatRupiah(angka) {
            let n = parseFloat(angka);
            if (isNaN(n)) n = 0;
            return 'Rp ' + n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        searchCustomers(query) {
            if (!query || query.length < 1) return [];
            const q = query.toLowerCase();
            return this.allCustomers.filter(c =>
                c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)
            ).slice(0, 10);
        },

        addToCart(id, name, price, stock) {
            if (stock <= 0) { showToast('Stok habis!', 'error'); return; }
            let existing = this.cart.find(c => c.id === id);
            if (existing) {
                if (existing.qty + 1 > stock) { showToast('Stok tidak cukup!', 'warning'); return; }
                existing.qty += 1;
            } else {
                this.cart.push({id, name, price, stock, qty: 1, discount: 0});
            }
            this.renderCart();
        },

        removeCart(idx) { this.cart.splice(idx, 1); this.renderCart(); },
        clearCart() { if (confirm('Kosongkan semua item?')) { this.cart = []; this.additionalDiscount = 0; this.renderCart(); } },

        renderCart() {
            let tbody = document.querySelector('#cartTable tbody');
            tbody.innerHTML = '';
            let subtotal = 0, discount = 0;
            this.cart.forEach((item, idx) => {
                let total = Math.max(0, item.qty * item.price - item.discount);
                subtotal += item.qty * item.price;
                discount += item.discount;
                let fmtPrice = item.price.toLocaleString('id-ID');
                let fmtDisc = item.discount.toLocaleString('id-ID');
                tbody.innerHTML += `<tr class="text-sm">
                    <td class="py-2.5"><input type="hidden" name="items[${idx}][product_id]" value="${item.id}"><span class="text-xs leading-tight line-clamp-2">${item.name}</span></td>
                    <td class="py-2.5"><input type="number" class="form-input text-xs py-1 px-1 text-center qty-input" data-idx="${idx}" value="${item.qty}" min="1" max="${item.stock}" name="items[${idx}][quantity]"></td>
                    <td class="py-2.5"><input type="text" class="form-input text-xs py-1 px-1 price-input" data-idx="${idx}" value="${fmtPrice}" inputmode="numeric" name="items[${idx}][unit_price]"></td>
                    <td class="py-2.5"><input type="text" class="form-input text-xs py-1 px-1 disc-input" data-idx="${idx}" value="${fmtDisc}" inputmode="numeric" name="items[${idx}][discount]"></td>
                    <td class="py-2.5 text-right text-xs font-semibold whitespace-nowrap">${this.formatRupiah(total)}</td>
                    <td class="py-2.5 w-0"><button type="button" class="text-red-400 hover:text-red-600 p-0.5" @click="removeCart(${idx})"><i class="bi bi-x-circle"></i></button></td>
                </tr>`;
            });
            document.getElementById('emptyCart').style.display = this.cart.length ? 'none' : 'flex';
            this.updateTotals();
        },

        recalcTax() { this.updateTotals(); },

        recalcTaxDisplay(taxable) {
            let sel = document.getElementById('taxSelect');
            let rate = 0;
            if (sel && sel.selectedIndex > 0) {
                let opt = sel.options[sel.selectedIndex];
                rate = parseFloat(opt.dataset.rate) || 0;
            }
            this._taxAmount = Math.round(taxable * rate / 100);
            document.getElementById('taxLabel').textContent = rate + '%';
            document.getElementById('cartTax').textContent = this.formatRupiah(this._taxAmount);
        },

        updateChange() {
            let total = parseInt(document.getElementById('cartTotal').textContent.replace(/\D/g, ''), 10) || 0;
            let paidEl = document.getElementById('paidAmount');
            let paid = parseInt((paidEl.value || '').replace(/\D/g, ''), 10) || 0;
            document.getElementById('changeAmount').textContent = this.formatRupiah(Math.max(0, paid - total));
        },

        updateTotals() {
            let subtotal = 0, itemDisc = 0;
            let rows = document.querySelectorAll('#cartTable tbody tr');
            this.cart.forEach((item, idx) => {
                let rowTotal = Math.max(0, item.qty * item.price - item.discount);
                subtotal += item.qty * item.price;
                itemDisc += item.discount;
                if (rows[idx]) {
                    let td = rows[idx].querySelector('td:nth-child(5)');
                    if (td) td.textContent = this.formatRupiah(rowTotal);
                }
            });
            let addDisc = parseInt((this.additionalDiscount || '').toString().replace(/\D/g, ''), 10) || 0;
            let taxable = Math.max(0, subtotal - itemDisc);
            this.recalcTaxDisplay(taxable);
            let total = taxable + (this._taxAmount || 0) - addDisc;
            let el;
            el = document.getElementById('cartSubtotal'); if (el) el.textContent = this.formatRupiah(subtotal);
            el = document.getElementById('cartDiscount'); if (el) { el.textContent = this.formatRupiah(itemDisc); el.parentElement.style.display = itemDisc > 0 ? '' : 'none'; }
            el = document.getElementById('cartAddDisc'); if (el) { el.textContent = this.formatRupiah(addDisc); el.parentElement.style.display = addDisc > 0 ? '' : 'none'; }
            el = document.getElementById('cartTotal'); if (el) el.textContent = this.formatRupiah(Math.max(0, total));
            el = document.getElementById('totalAddDisc'); if (el) el.value = addDisc;
            el = document.getElementById('btnPay'); if (el) el.disabled = this.cart.length === 0;
            this.updateChange();
        },

        _taxAmount: 0,

        filterProducts() {
            let val = this.searchProduct.toLowerCase();
            let count = 0;
            document.querySelectorAll('#productGrid .product-card-item').forEach(card => {
                let name = card.dataset.name, code = card.dataset.code, barcode = card.dataset.barcode;
                let show = !val || name.includes(val) || code.includes(val) || barcode.includes(val);
                card.style.display = show ? '' : 'none';
                if (show) count++;
            });
            this.filteredCount = count;
        },

        filterByCategory(catId) {
            document.querySelectorAll('#productGrid .product-card-item').forEach(card => {
                card.style.display = (catId === 'all' || card.dataset.category === catId) ? '' : 'none';
            });
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    let table = document.getElementById('cartTable');
    if (table) {
        table.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); e.target.blur(); }
        });
        table.addEventListener('input', function(e) {
            let el = e.target;
            let idx = parseInt(el.dataset.idx);
            if (!window.posApp) return;
            let cart = window.posApp.cart;
            if (!cart || idx >= cart.length) return;
            if (el.classList.contains('qty-input')) cart[idx].qty = parseFloat(el.value) || 1;
            if (el.classList.contains('price-input')) cart[idx].price = parseInt((el.value || '').replace(/\D/g, ''), 10) || 0;
            if (el.classList.contains('disc-input')) cart[idx].discount = parseInt((el.value || '').replace(/\D/g, ''), 10) || 0;
            window.posApp.updateTotals();
        });
        table.addEventListener('focusin', function(e) {
            let el = e.target;
            if (el.classList.contains('price-input') || el.classList.contains('disc-input')) {
                el.value = (el.value || '').replace(/\D/g, '');
            }
        });
        table.addEventListener('focusout', function(e) {
            let el = e.target;
            if (el.classList.contains('price-input') || el.classList.contains('disc-input')) {
                let raw = parseInt((el.value || '').replace(/\D/g, ''), 10) || 0;
                el.value = raw.toLocaleString('id-ID');
            }
        });
    }

    let paid = document.getElementById('paidAmount');
    if (paid) {
        paid.addEventListener('keydown', function(e) { if (e.key === 'Enter') e.preventDefault(); });
    }

    let pm = document.getElementById('paymentMethod');
    if (pm) {
        pm.addEventListener('change', function() {
            let selected = this.options[this.selectedIndex];
            let isCredit = selected && selected.dataset.credit === '1';
            let cash = document.getElementById('cashPanel');
            let changeRow = document.getElementById('changeRow');
            if (cash) cash.style.display = isCredit ? 'none' : '';
            if (changeRow) changeRow.style.display = isCredit ? 'none' : '';
        });
        pm.dispatchEvent(new Event('change'));
    }

    let form = document.getElementById('posForm');
    if (form) {
        form.addEventListener('keydown', function(e) { if (e.key === 'Enter') e.preventDefault(); });
        form.addEventListener('submit', function() {
            let p = document.getElementById('paidAmount');
            if (p) p.value = p.value.replace(/\D/g, '') || '0';
            document.querySelectorAll('#cartTable tbody .price-input, #cartTable tbody .disc-input').forEach(function(el) {
                el.value = el.value.replace(/\D/g, '') || '0';
            });
        });
    }
});
</script>
@endpush
@endsection

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
            <span class="text-xs text-slate-400 hidden lg:inline">
                <i class="bi bi-calendar3 mr-1"></i> <span id="headerDateTime">{{ now()->isoFormat('dddd, D MMMM Y HH:mm') }}</span>
            </span>
            <div class="flex items-center gap-1.5">
                <i class="bi bi-printer text-slate-400 text-sm"></i>
                <select x-model="printerType" class="form-select form-select-sm text-xs" style="min-width:130px" x-effect="localStorage.setItem('posPrinter', printerType)">
                    <option value="a4">Kertas A4</option>
                    <option value="thermal">Thermal 58mm</option>
                    <option value="none">Tanpa Cetak</option>
                </select>
            </div>
            <button type="button" @click="historyModal=true; loadHistory()" class="btn btn-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Riwayat
            </button>
        </div>
    </div>

    <!-- Main content: full-width product area -->
    <div class="flex-1 flex flex-col p-3 min-h-0">
        <!-- Search & filters -->
        <div class="flex gap-2 mb-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" x-model="searchProduct" @input.debounce.100ms="filterProducts()"
                       class="form-input pl-10" placeholder="Cari produk (nama / kode / barcode)...">
            </div>
        </div>

        <!-- Category pills -->
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

        <!-- Product grid -->
        <div class="flex-1 overflow-y-auto rounded-xl">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3" id="productGrid">
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

    <!-- Floating Cart Button -->
    <button type="button" @click="cartModal = true"
            class="fixed bottom-6 right-6 z-40 w-14 h-14 bg-primary-600 text-white rounded-full shadow-lg hover:bg-primary-700 hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center"
            x-show="!cartModal">
        <i class="bi bi-cart3 text-2xl"></i>
        <span x-show="cart.length > 0" x-text="cart.length"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"></span>
    </button>

    <!-- Cart Modal (fullscreen) -->
    <div x-show="cartModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex bg-white"
         @click.self="cartModal = false" @keydown.escape.window="cartModal = false">
        <div class="w-full h-full flex flex-col" @click.stop>
            <!-- Modal header -->
            <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white shrink-0">
                <div class="flex items-center gap-2">
                    <i class="bi bi-cart3 text-lg"></i>
                    <span class="font-bold">Keranjang</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-white/20 text-white text-xs px-2 py-1 rounded-full" x-text="cart.length + ' item'"></span>
                    <button @click="cartModal = false" class="text-white/80 hover:text-white"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>

            <form action="{{ route('transaksi.sales.store') }}" method="POST" id="posForm" data-noloading="true" class="flex flex-col flex-1 min-h-0">
                @csrf
                <div class="overflow-y-auto flex-1 p-0">
                    <table class="table mb-0 w-full" id="cartTable">
                        <thead class="sticky top-0 z-10 bg-slate-50">
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

                <div class="border-t border-slate-200 bg-slate-50/50 px-4 py-3 space-y-2.5 shrink-0">
                    <input type="hidden" name="invoice_number" value="{{ $invoiceNumber }}">
                    <input type="hidden" name="sale_date" value="{{ now()->format('Y-m-d') }}">

                    <div class="relative">
                        <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Customer</label>
                        <div class="flex items-center gap-1 mt-1">
                            <i class="bi bi-person text-slate-400 text-sm"></i>
                            <input type="text"
                                   x-model="custSearch"
                                   @input="custOpen = true; custShowAll = false"
                                   @focus="custOpen = true"
                                   @click.outside="custOpen = false; custShowAll = false"
                                   class="form-input form-input-sm text-sm flex-1"
                                   placeholder="Walk-in..."
                                   autocomplete="off">
                            <input type="hidden" name="customer_id" :value="selectedCust ? selectedCust.id : ''">
                            <button type="button" @click="custOpen = !custOpen; custShowAll = !custShowAll; custSearch = ''" class="text-slate-400 hover:text-slate-600 p-0.5" title="Semua Customer">
                                <i class="bi bi-chevron-down text-xs" :class="custShowAll && 'rotate-180'"></i>
                            </button>
                            <button type="button" @click="customerModal = true; customerForm = { name: '', phone: '', type: 'retail' }" class="btn btn-sm btn-outline-primary p-0 w-7 h-7 flex items-center justify-center" title="Tambah Customer">
                                <i class="bi bi-plus text-sm"></i>
                            </button>
                        </div>
                        <div x-show="custOpen && (custSearch.length >= 1 || custShowAll)" x-cloak x-transition.opacity
                             class="absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
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

                    <div>
                        <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Customer</label>
                        <div class="relative" @click.outside="custOpen = false; custShowAll = false">
                            <div class="flex items-center gap-1 mt-1">
                                <input type="text" x-model="custSearch"
                                       @input="custOpen = true; custShowAll = false"
                                       @focus="custOpen = true"
                                       class="form-input form-input-sm text-sm" placeholder="Walk-in / Umum..." autocomplete="off">
                                <input type="hidden" name="customer_id" :value="selectedCust ? selectedCust.id : ''">
                                <button type="button" @click="custOpen = !custOpen; custShowAll = !custShowAll; custSearch = ''" class="btn btn-sm btn-outline-secondary p-1 w-7 h-7 flex items-center justify-center" title="Semua Customer">
                                    <i class="bi bi-chevron-down text-xs" :class="custShowAll && 'rotate-180'"></i>
                                </button>
                                <button type="button" @click="customerModal = true; customerForm = { name: '', phone: '', type: 'retail' }" class="btn btn-sm btn-outline-primary p-1 w-7 h-7 flex items-center justify-center" title="Tambah Customer">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div x-show="custOpen && (custSearch.length >= 1 || custShowAll)" x-cloak x-transition.opacity
                                 class="absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                <template x-for="c in custShowAll ? allCustomers : searchCustomers(custSearch)" :key="c.id">
                                    <div @click="selectedCust=c; custSearch=c.name; custOpen=false"
                                         class="px-3 py-2 text-sm hover:bg-primary-50 cursor-pointer flex items-center justify-between">
                                        <span class="font-medium" x-text="c.name"></span>
                                        <span class="text-slate-400 text-xs" x-text="'['+c.code+']'"></span>
                                    </div>
                                </template>
                                <div x-show="!custShowAll && searchCustomers(custSearch).length === 0"
                                     class="px-3 py-2 text-sm text-slate-400 text-center">Tidak ditemukan</div>
                            </div>
                        </div>
                    </div>

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

                    <div id="accountPanel" style="display:none;">
                        <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Kas / Bank Tujuan</label>
                        <select name="cash_account_id" class="form-select text-sm mt-1">
                            @foreach($cashAccounts as $ca)
                            <option value="{{ $ca->id }}" {{ $ca->is_default ? 'selected' : '' }}>{{ $ca->name }} ({{ formatRupiah($ca->current_balance) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="total_discount" id="totalAddDisc" value="0">

                    <div class="grid grid-cols-2 gap-2">
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
                        <button type="button" class="btn btn-primary btn-lg" id="btnPay" disabled
                                @click="handleCheckout()" :disabled="cart.length === 0 || loading">
                            <span x-show="!loading"><i class="bi bi-cash-coin text-lg"></i> <span x-text="btnPayLabel"></span></span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <span class="spinner-border spinner-border-sm"></span> Memproses...
                            </span>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" @click="clearCart()" x-show="cart.length > 0">
                            <i class="bi bi-trash"></i> Kosongkan
                        </button>
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
    <div x-show="historyModal" x-cloak class="fixed inset-0 z-50 flex items-start sm:items-center justify-center bg-black/40 backdrop-blur-sm pt-4 sm:pt-0" @click.self="historyModal=false" @keydown.escape.window="historyModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden mx-4">
            <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-4 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <i class="bi bi-clock-history text-lg"></i>
                    <span class="font-bold">Riwayat Transaksi</span>
                </div>
                <button @click="historyModal=false" class="text-white/80 hover:text-white"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="overflow-y-auto p-5 flex-1 space-y-2" id="historyList">
                <div class="text-center py-8 text-slate-400">
                    <span class="loading loading-spinner loading-sm"></span> Memuat riwayat...
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
@php
$customersJson = $customers->map(function($c) { return ['id' => $c->id, 'name' => $c->name, 'code' => $c->code]; })->values()->toJson();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 30000);
});

function updateDateTime() {
    let el = document.getElementById('headerDateTime');
    if (!el) return;
    let d = new Date();
    let days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    let months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    el.textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ' ' +
        String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}

function posKasir() {
    return {
        cart: [],
            searchProduct: '',
            customerModal: false,
            cartModal: false,
            historyModal: false,
            customerFormLoading: false,
            loading: false,
            btnPayLabel: 'Bayar',
            printerType: localStorage.getItem('posPrinter') || 'a4',
            customerForm: { name: '', phone: '', type: 'retail' },
            selectedCust: null,
            custSearch: '',
            custOpen: false,
            custShowAll: false,
            additionalDiscount: 0,
        allCustomers: {!! $customersJson !!},
        filteredCount: {{ $products->count() }},

            init() {
                this.renderCart();
                this.filterProducts();
                this.updateTotals();
                let self = this;
                let accountPanel = document.getElementById('accountPanel');
                document.getElementById('paymentMethod').addEventListener('change', function() {
                    let isCredit = this.options[this.selectedIndex].dataset.credit === '1';
                    let isCash = this.options[this.selectedIndex].textContent.trim() === 'Tunai';
                    accountPanel.style.display = isCash ? 'none' : '';
                    if (isCredit) {
                        self.btnPayLabel = 'Proses Kredit';
                    } else {
                        self.btnPayLabel = 'Bayar';
                    }
                });
                document.getElementById('paymentMethod').dispatchEvent(new Event('change'));
            },

            filterProducts() {
                let search = this.searchProduct.toLowerCase();
                let count = 0;
                document.querySelectorAll('.product-card-item').forEach(el => {
                    let match = !search ||
                        el.dataset.name.includes(search) ||
                        el.dataset.code.includes(search) ||
                        (el.dataset.barcode && el.dataset.barcode.includes(search));
                    el.style.display = match ? '' : 'none';
                    if (match) count++;
                });
                this.filteredCount = count;
            },

            filterByCategory(catId) {
                document.querySelectorAll('.product-card-item').forEach(el => {
                    if (catId === 'all' || el.dataset.category === String(catId)) {
                        el.style.display = '';
                    } else {
                        el.style.display = 'none';
                    }
                });
            },

            searchCustomers(query) {
                if (!query) return [];
                let q = query.toLowerCase();
                return this.allCustomers.filter(c =>
                    c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)
                );
            },

            addToCart(id, name, price, stock) {
                if (stock <= 0) {
                    showToast('Stok habis', 'warning');
                    return;
                }
                let existing = this.cart.find(c => c.id === id);
                if (existing) {
                    if (existing.qty >= stock) {
                        showToast('Stok tidak mencukupi (max ' + stock + ')', 'warning');
                        return;
                    }
                    existing.qty++;
                    existing.total = existing.qty * existing.price - (existing.discount || 0);
                } else {
                    this.cart.push({ id, name, price, stock, qty: 1, discount: 0, total: price });
                }
                this.renderCart();
                this.updateTotals();
                showToast(name + ' ditambahkan', 'info');
            },

            updateQty(index, qty) {
                let item = this.cart[index];
                qty = parseFloat(qty) || 1;
                if (qty <= 0) { this.removeFromCart(index); return; }
                if (qty > item.stock) {
                    showToast('Stok tidak mencukupi (max ' + item.stock + ')', 'warning');
                    qty = item.stock;
                }
                item.qty = qty;
                item.total = item.qty * item.price - (item.discount || 0);
                this.renderCart();
                this.updateTotals();
            },

            updateDiscount(index, discount) {
                let item = this.cart[index];
                discount = parseFloat(discount) || 0;
                item.discount = discount;
                item.total = Math.max(0, item.qty * item.price - discount);
                this.renderCart();
                this.updateTotals();
            },

            removeFromCart(index) {
                this.cart.splice(index, 1);
                this.renderCart();
                this.updateTotals();
            },

            clearCart() {
                this.cart = [];
                this.renderCart();
                this.updateTotals();
                this.cartModal = false;
                showToast('Keranjang dikosongkan', 'info');
            },

            renderCart() {
                let tbody = document.querySelector('#cartTable tbody');
                let emptyEl = document.getElementById('emptyCart');
                if (!tbody) return;
                if (this.cart.length === 0) {
                    tbody.innerHTML = '';
                    if (emptyEl) emptyEl.style.display = '';
                    document.getElementById('btnPay').disabled = true;
                    return;
                }
                if (emptyEl) emptyEl.style.display = 'none';
                document.getElementById('btnPay').disabled = false;
                let rows = this.cart.map((item, idx) => `
                    <tr class="cart-row">
                        <td class="text-sm"><span class="font-medium">${item.name}</span><br><span class="text-[10px] text-slate-400">Rp ${item.price.toLocaleString('id-ID')}</span></td>
                        <td class="text-sm" style="width: 90px;">
                            <div class="flex items-center gap-0.5">
                                <button type="button" @@click="updateQty(${idx}, ${item.qty - 1})" class="btn btn-sm btn-outline-secondary p-0 w-6 h-6 flex items-center justify-center"><i class="bi bi-dash text-xs"></i></button>
                                <input type="number" value="${item.qty}" min="1" max="${item.stock}" step="1"
                                       @@change="updateQty(${idx}, $event.target.value)"
                                       class="form-input text-center text-sm" style="width: 48px; padding: 2px 4px;">
                                <button type="button" @@click="updateQty(${idx}, ${item.qty + 1})" class="btn btn-sm btn-outline-secondary p-0 w-6 h-6 flex items-center justify-center"><i class="bi bi-plus text-xs"></i></button>
                            </div>
                        </td>
                        <td class="text-sm">${item.price.toLocaleString('id-ID')}</td>
                        <td style="width: 70px;">
                            <input type="number" value="${item.discount || 0}" min="0"
                                   @@change="updateDiscount(${idx}, $event.target.value)"
                                   class="form-input text-center text-sm" style="width: 60px; padding: 2px 4px;">
                        </td>
                        <td class="text-sm text-right font-medium">${Math.max(0, item.total).toLocaleString('id-ID')}</td>
                        <td class="text-center" style="width: 36px;">
                            <button type="button" @@click="removeFromCart(${idx})" class="btn btn-sm btn-outline-danger p-0 w-7 h-7 flex items-center justify-center"><i class="bi bi-trash text-xs"></i></button>
                        </td>
                    </tr>
                `).join('');
                tbody.innerHTML = rows;
                this.recalcTax();
            },

            updateTotals() {
                let subtotal = this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
                let itemDiscount = this.cart.reduce((sum, item) => sum + (item.discount || 0), 0);
                let taxRate = parseFloat(document.getElementById('taxSelect')?.selectedOptions[0]?.dataset?.rate || 0);
                let taxable = Math.max(0, subtotal - itemDiscount);
                let tax = Math.round(taxable * taxRate / 100);
                let addDisc = parseFloat(this.additionalDiscount) || 0;
                let total = Math.max(0, taxable + tax - addDisc);

                document.getElementById('cartSubtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
                document.getElementById('cartDiscount').textContent = '-Rp ' + itemDiscount.toLocaleString('id-ID');
                document.getElementById('cartTax').textContent = 'Rp ' + tax.toLocaleString('id-ID');
                document.getElementById('cartAddDisc').textContent = '-Rp ' + addDisc.toLocaleString('id-ID');
                document.getElementById('cartTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
                document.getElementById('totalAddDisc').value = addDisc;
                this.updateChange();
            },

            recalcTax() {
                let taxSelect = document.getElementById('taxSelect');
                let rate = taxSelect?.selectedOptions[0]?.dataset?.rate || 0;
                document.getElementById('taxLabel').textContent = rate + '%';
                this.updateTotals();
            },

            updateChange() {
                let totalText = document.getElementById('cartTotal').textContent.replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.');
                let total = parseFloat(totalText) || 0;
                let paidEl = document.getElementById('paidAmount');
                let paid = 0;
                if (paidEl) {
                    paid = parseInt(paidEl.value.replace(/\D/g, ''), 10) || 0;
                }
                let change = Math.max(0, paid - total);
                let changeRow = document.getElementById('changeRow');
                let changeAmount = document.getElementById('changeAmount');
                if (paid > 0 && changeRow) {
                    changeRow.style.display = '';
                    if (changeAmount) changeAmount.textContent = 'Rp ' + change.toLocaleString('id-ID');
                } else if (changeRow) {
                    changeRow.style.display = 'none';
                }
            },

            handleCheckout() {
                if (this.cart.length === 0 || this.loading) return;
                this.loading = true;
                let form = document.getElementById('posForm');
                let fd = new FormData(form);
                let self = this;

                // Build items data
                this.cart.forEach((item, idx) => {
                    fd.append('items[' + idx + '][product_id]', item.id);
                    fd.append('items[' + idx + '][quantity]', item.qty);
                    fd.append('items[' + idx + '][unit_price]', item.price);
                    fd.append('items[' + idx + '][discount]', item.discount || 0);
                });

                // Clean paid_amount
                let paidEl = document.getElementById('paidAmount');
                if (paidEl) fd.set('paid_amount', paidEl.value.replace(/\D/g, '') || '0');

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: fd,
                })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (ok) {
                        showToast(data.message || 'Transaksi berhasil', 'success');
                        self.clearCart();
                        self.cartModal = false;
                        if (data.sale_id && self.printerType !== 'none') {
                            let printUrl = self.printerType === 'thermal'
                                ? '{{ url('pos/print-thermal') }}/' + data.sale_id
                                : '{{ url('pos/print-a4') }}/' + data.sale_id;
                            setTimeout(() => {
                                let iframe = document.createElement('iframe');
                                iframe.style.display = 'none';
                                iframe.src = printUrl;
                                iframe.onload = function() { this.contentWindow.print(); };
                                document.body.appendChild(iframe);
                                setTimeout(() => document.body.removeChild(iframe), 60000);
                            }, 300);
                        }
                    } else {
                        showToast(data.message || Object.values(data.errors || {}).flat().join('<br>') || 'Gagal', 'error');
                    }
                })
                .catch(() => showToast('Gagal memproses transaksi', 'error'))
                .finally(() => { self.loading = false; });
            },

            saveCustomer() {
                let self = this;
                if (!this.customerForm.name.trim()) return;
                this.customerFormLoading = true;
                fetch('{{ route('pos.customer-quick') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.customerForm),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        self.allCustomers.push(data.customer);
                        showToast('Customer berhasil ditambahkan', 'success');
                        self.customerModal = false;
                    } else {
                        showToast(data.message || 'Gagal menambah customer', 'error');
                    }
                })
                .catch(() => showToast('Gagal menambah customer', 'error'))
                .finally(() => { self.customerFormLoading = false; });
            },

            loadHistory() {
                let self = this;
                fetch('{{ route('pos.recent') }}')
                    .then(r => r.json())
                    .then(data => {
                        let list = document.getElementById('historyList');
                        if (!list) return;
                        if (!data.length) {
                            list.innerHTML = '<div class="text-center py-8 text-slate-400">Belum ada transaksi</div>';
                            return;
                        }
                        list.innerHTML = data.map(s => `
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                                <div>
                                    <span class="font-medium text-sm">${s.invoice}</span><br>
                                    <span class="text-xs text-slate-500">${s.customer} | ${s.date} | ${s.method}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm">${s.total}</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium ${s.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : s.status === 'partial' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600'}">${s.status === 'paid' ? 'Lunas' : s.status === 'partial' ? 'Sebagian' : 'Belum'}</span>
                                    <a href="${s.print_a4}" target="_blank" class="btn btn-sm p-1 w-7 h-7 flex items-center justify-center" title="Cetak A4"><i class="bi bi-printer text-xs"></i></a>
                                </div>
                            </div>
                        `).join('');
                    })
                    .catch(() => {
                        let list = document.getElementById('historyList');
                        if (list) list.innerHTML = '<div class="text-center py-8 text-slate-400">Gagal memuat riwayat</div>';
                    });
            },
        };
    }

    window.posKasir = posKasir;

    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('headerDateTime')) {
            updateDateTime();
            setInterval(updateDateTime, 30000);
        }
    });
</script>
@endpush
@endsection

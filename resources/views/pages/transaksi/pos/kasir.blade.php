@extends('layouts.pos')
@section('title', 'POS Kasir')
@section('content')
<div x-data="posKasir()" x-on:open-customer-modal="customerModal = true; customerForm = { name: '', phone: '', type: posMode === 'reseller' ? 'wholesale' : 'retail' }" id="posApp" class="h-full flex flex-col">
    <!-- Mini header -->
    <div class="flex items-center justify-between px-3 py-2 bg-white border-b border-slate-200 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.kasir') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Ganti Mode
            </a>
            <span class="font-bold text-slate-700 hidden sm:inline">POS Kasir</span>
            <span x-text="posMode === 'reseller' ? 'Reseller' : 'Toko'"
                  :class="posMode === 'reseller' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"></span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400 hidden lg:inline">
                <i class="bi bi-calendar3 mr-1"></i> <span id="headerDateTime">{{ now()->isoFormat('dddd, D MMMM Y HH:mm') }}</span>
            </span>
            <div class="flex items-center gap-1.5">
                <i class="bi bi-printer text-slate-400 text-sm"></i>
                <select x-model="printerType" class="form-select form-select-sm text-xs" style="min-width:160px" x-effect="localStorage.setItem('posPrinter', printerType)">
                    <option value="a4">Kertas A4</option>
                    <option value="thermal">Thermal 58mm</option>
                    <option value="epson">Epson L310 9.1x11</option>
                    <option value="none">Tanpa Cetak</option>
                </select>
            </div>
            <button type="button" @click="historyModal=true; loadHistory()" class="btn btn-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Riwayat
            </button>
        </div>
    </div>

    <!-- Main: split panel -->
    <div class="flex-1 flex flex-col md:flex-row min-h-0">
        <!-- Left: product grid -->
        <div class="flex-1 flex flex-col p-3 min-w-0 min-h-0">
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
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2" id="productGrid">
                    @foreach($products as $product)
                    <div class="product-card-item"
                         data-name="{{ strtolower($product->name) }}"
                         data-code="{{ strtolower($product->code) }}"
                         data-barcode="{{ strtolower($product->barcode ?? '') }}"
                         data-category="{{ $product->category_id }}">
                        <div class="pos-product-card group cursor-pointer"
                             @click="addToCart({{ $product->id }})">
                            <div class="relative">
                                @if($product->photo)
                                    <img src="{{ asset('storage/'.$product->photo) }}" class="w-full h-20 object-cover">
                                @else
                                    <div class="w-full h-20 bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                        <i class="bi bi-box-seam text-2xl text-slate-300"></i>
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
                            <div class="p-2">
                                <p class="text-xs font-medium text-slate-800 line-clamp-2 leading-tight mb-1">{{ $product->name }}</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-primary-600" x-text="formatPriceForProduct({{ $product->id }})"></span>
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

        <!-- Right: cart panel -->
        <div class="w-full md:w-[500px] lg:w-[600px] xl:w-[700px] h-[50vh] md:h-auto border-t md:border-t-0 md:border-l border-slate-200 bg-white flex flex-col shrink-0 min-h-0"
             x-show="true">
            @include('pages.transaksi.pos._cart_form')
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
                        <option value="wholesale">Reseller</option>
                    </select>
                </div>
                <button type="button" @click="saveCustomer()" :disabled="customerFormLoading || !customerForm.name.trim()"
                        class="btn btn-primary btn-md w-full">
                    <i class="bi bi-check-lg mr-1"></i> <span x-text="customerFormLoading ? 'Menyimpan...' : 'Simpan Customer'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Customer Picker Modal -->
    <div x-show="custPickerModal" x-cloak class="fixed inset-0 z-50 flex items-start sm:items-center justify-center bg-black/40 backdrop-blur-sm pt-4 sm:pt-0" @click.self="custPickerModal=false" @keydown.escape.window="custPickerModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col overflow-hidden mx-4">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-5 py-3 text-white flex items-center justify-between shrink-0">
                <span class="font-bold flex items-center gap-2"><i class="bi bi-person-lines-fill"></i> Pilih Customer</span>
                <button @click="custPickerModal=false" class="text-white/80 hover:text-white"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-4 border-b shrink-0">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" x-model="custPickerSearch" class="form-input pl-9 text-sm" placeholder="Cari nama atau kode customer..." autofocus>
                </div>
            </div>
            <div class="overflow-y-auto flex-1">
                <template x-if="filteredCustPicker().length === 0">
                    <div class="text-center py-12 text-slate-400">
                        <i class="bi bi-person-x text-3xl block mb-2"></i>
                        <p class="text-sm">Tidak ditemukan</p>
                    </div>
                </template>
                <template x-for="c in filteredCustPicker()" :key="c.id">
                    <div @click="selectedCust=c; custPickerModal=false"
                         :class="selectedCust && selectedCust.id === c.id ? 'bg-primary-50 border-l-4 border-primary-500' : 'hover:bg-slate-50 border-l-4 border-transparent'"
                         class="px-4 py-3 cursor-pointer flex items-center justify-between transition-colors">
                        <div>
                            <span class="font-medium text-sm" x-text="c.name"></span>
                            <span class="text-slate-400 text-xs ml-2" x-text="'['+c.code+']'"></span>
                        </div>
                        <i x-show="selectedCust && selectedCust.id === c.id" class="bi bi-check-circle-fill text-primary-600"></i>
                    </div>
                </template>
            </div>
            <div class="px-4 py-3 border-t bg-slate-50 shrink-0">
                <button type="button" @click="selectedCust = null; custPickerModal = false" class="btn btn-sm btn-outline-secondary w-full">
                    <i class="bi bi-person-x mr-1"></i> Walk-in / Umum (tanpa customer)
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
$customersJson = $customers->map(function($c) { return ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'type' => $c->type]; })->values()->toJson();
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
        printerType: localStorage.getItem('posPrinter') || 'epson',
        customerForm: { name: '', phone: '', type: 'retail' },
        selectedCust: null,
        custPickerModal: false,
        custPickerSearch: '',
        additionalDiscount: 0,
        creditTerm: '2',
        creditStartDate: '{{ now()->format('Y-m-d') }}',
        creditDueDate: '',
        posMode: '{{ request('mode', 'toko') }}',
        allCustomers: {!! $customersJson !!},
        allProducts: {!! $productsJson !!},
        filteredCount: {{ $products->count() }},

        init() {
            this.renderCart();
            this.filterProducts();
            this.updateTotals();
            let self = this;
            let accountPanel = document.getElementById('accountPanel');
            let tempoPanel = document.getElementById('tempoPanel');
            let pmEl = document.getElementById('paymentMethod');
            if (pmEl) {
                pmEl.addEventListener('change', function() {
                    let isCredit = this.options[this.selectedIndex].dataset.credit === '1';
                    if (accountPanel) accountPanel.style.display = isCredit ? 'none' : '';
                    if (tempoPanel) tempoPanel.style.display = isCredit ? '' : 'none';
                    if (tempoPanel) tempoPanel.style.display = isCredit ? '' : 'none';
                    if (isCredit) {
                        self.btnPayLabel = 'Proses Kredit';
                    } else {
                        self.btnPayLabel = 'Bayar';
                    }
                });
                pmEl.dispatchEvent(new Event('change'));
            }
            this.$watch('selectedCust', () => self.onCustomerChange());
        },

        formatPriceForProduct(id) {
            let p = this.allProducts.find(p => p.id === id);
            if (!p) return 'Rp 0';
            let price = this.posMode === 'reseller' ? p.wholesale_price : p.retail_price;
            return 'Rp ' + Number(price).toLocaleString('id-ID');
        },

        getProductPrice(id) {
            let product = this.allProducts.find(p => p.id === id);
            if (!product) return 0;
            return this.posMode === 'reseller' ? product.wholesale_price : product.retail_price;
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

        filteredCustPicker() {
            let q = (this.custPickerSearch || '').toLowerCase().trim();
            if (!q) return this.allCustomers;
            return this.allCustomers.filter(c =>
                c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)
            );
        },

        addToCart(id) {
            let product = this.allProducts.find(p => p.id === id);
            if (!product) return;
            if (product.stock <= 0) {
                showToast('Stok habis', 'warning');
                return;
            }
            let price = this.posMode === 'reseller' ? product.wholesale_price : product.retail_price;
            let existing = this.cart.find(c => c.id === id);
            if (existing) {
                if (existing.qty >= product.stock) {
                    showToast('Stok tidak mencukupi (max ' + product.stock + ')', 'warning');
                    return;
                }
                existing.qty++;
                existing.price = price;
                existing.total = existing.qty * price - (existing.discount || 0);
            } else {
                this.cart.push({ id, name: product.name, price, stock: product.stock, qty: 1, discount: 0, total: price });
            }
            this.renderCart();
            this.updateTotals();
            showToast(product.name + ' ditambahkan', 'info');
        },

        onCustomerChange() {
            if (this.cart.length === 0) return;
            this.cart.forEach(item => {
                let product = this.allProducts.find(p => p.id === item.id);
                if (product) {
                    item.price = this.posMode === 'reseller' ? product.wholesale_price : product.retail_price;
                    item.total = item.qty * item.price - (item.discount || 0);
                }
            });
            this.renderCart();
            this.updateTotals();
        },

        getComputedDueDate() {
            let weeks = parseInt(this.creditTerm);
            if (!weeks || weeks < 1) return '';
            let d = new Date();
            d.setDate(d.getDate() + weeks * 7);
            return d.toISOString().split('T')[0];
        },

        onCreditTermChange() {
            if (this.creditTerm !== 'custom') {
                this.creditDueDate = '';
            }
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

        updatePrice(index, price) {
            let item = this.cart[index];
            price = parseFloat(price) || 0;
            item.price = price;
            item.total = Math.max(0, item.qty * item.price - (item.discount || 0));
            this.renderCart();
            this.updateTotals();
        },

        formatInput(el, mode) {
            if (!el) return;
            let raw = el.value.replace(/\D/g, '');
            let num = parseInt(raw, 10) || 0;
            el.value = mode === 'raw' ? (raw === '0' ? '' : raw) : num.toLocaleString('id-ID');
            if (mode === 'currency' && el.value === '0') el.value = '';
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
                <tr class="cart-row border-b border-slate-100 last:border-0">
                    <td class="text-[11px] py-2 pr-2">
                        <span class="font-medium text-slate-800 line-clamp-2">${item.name}</span>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Rp ${item.price.toLocaleString('id-ID')}</span>
                    </td>
                    <td class="py-2 pr-2 align-top">
                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-md overflow-hidden">
                            <button type="button" @click="updateQty(${idx}, ${item.qty - 1})" class="px-1.5 py-1 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors"><i class="bi bi-dash"></i></button>
                            <input type="text" value="${item.qty.toLocaleString('id-ID')}" inputmode="numeric"
                                   @focus="formatInput($event.target, 'raw')"
                                   @blur="formatInput($event.target, 'currency')"
                                   @change="updateQty(${idx}, $event.target.value.replace(/\D/g, ''))"
                                   class="w-10 text-center text-[11px] font-medium bg-transparent border-0 focus:ring-0 p-0">
                            <button type="button" @click="updateQty(${idx}, ${item.qty + 1})" class="px-1.5 py-1 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors"><i class="bi bi-plus"></i></button>
                        </div>
                    </td>
                    <td class="py-2 pr-2 align-top">
                        <input type="text" value="${item.price.toLocaleString('id-ID')}" inputmode="numeric"
                               @focus="formatInput($event.target, 'raw')"
                               @blur="formatInput($event.target, 'currency')"
                               @change="updatePrice(${idx}, $event.target.value.replace(/\D/g, ''))"
                               class="form-input text-right text-[11px] w-full min-w-[90px] px-2 py-1 rounded-md border-slate-200 shadow-sm">
                    </td>
                    <td class="py-2 pr-2 align-top">
                        <input type="text" value="${(item.discount || 0).toLocaleString('id-ID')}" inputmode="numeric"
                               @focus="formatInput($event.target, 'raw')"
                               @blur="formatInput($event.target, 'currency')"
                               @change="updateDiscount(${idx}, $event.target.value.replace(/\D/g, ''))"
                               class="form-input text-right text-[11px] w-full min-w-[75px] px-2 py-1 rounded-md border-slate-200 shadow-sm text-red-500 font-medium">
                    </td>
                    <td class="text-[11px] py-2 pr-2 text-right font-bold text-slate-800 align-top pt-3">
                        ${Math.max(0, item.total).toLocaleString('id-ID')}
                    </td>
                    <td class="py-2 text-right align-top pt-2">
                        <button type="button" @click="removeFromCart(${idx})" class="text-red-400 hover:text-red-600 p-1.5 rounded-md hover:bg-red-50 transition-colors">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            tbody.innerHTML = rows;
            this.recalcTax();
        },

        updateTotals() {
            let subtotal = this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
            let itemDiscount = this.cart.reduce((sum, item) => sum + (item.discount || 0), 0);
            let taxSelect = document.getElementById('taxSelect');
            let taxRate = parseFloat(taxSelect ? (taxSelect.selectedOptions[0]?.dataset?.rate || 0) : 0);
            let taxable = Math.max(0, subtotal - itemDiscount);
            let tax = Math.round(taxable * taxRate / 100);
            let addDisc = parseFloat(this.additionalDiscount) || 0;
            let total = Math.max(0, taxable + tax - addDisc);

            let el;
            el = document.getElementById('cartSubtotal'); if (el) el.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            el = document.getElementById('cartDiscount'); if (el) el.textContent = '-Rp ' + itemDiscount.toLocaleString('id-ID');
            el = document.getElementById('cartTax'); if (el) el.textContent = 'Rp ' + tax.toLocaleString('id-ID');
            el = document.getElementById('cartAddDisc'); if (el) el.textContent = '-Rp ' + addDisc.toLocaleString('id-ID');
            el = document.getElementById('cartTotal'); if (el) el.textContent = 'Rp ' + total.toLocaleString('id-ID');
            el = document.getElementById('totalAddDisc'); if (el) el.value = addDisc;
            this.updateChange();
        },

        recalcTax() {
            let taxSelect = document.getElementById('taxSelect');
            let rate = taxSelect ? (taxSelect.selectedOptions[0]?.dataset?.rate || 0) : 0;
            let el = document.getElementById('taxLabel');
            if (el) el.textContent = rate + '%';
            this.updateTotals();
        },

        updateChange() {
            let cartTotalEl = document.getElementById('cartTotal');
            let totalText = cartTotalEl ? cartTotalEl.textContent.replace(/[^\d,-]/g, '').replace(/\./g, '').replace(',', '.') : '0';
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
            if (!form) { this.loading = false; return; }
            let fd = new FormData(form);
            let self = this;

            this.cart.forEach((item, idx) => {
                fd.append('items[' + idx + '][product_id]', item.id);
                fd.append('items[' + idx + '][quantity]', item.qty);
                fd.append('items[' + idx + '][unit_price]', item.price);
                fd.append('items[' + idx + '][discount]', item.discount || 0);
            });

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
                    if (data.sale_id && self.printerType !== 'none') {
                        let printUrl;
                        if (self.printerType === 'thermal') {
                            printUrl = '{{ url('pos/print-thermal') }}/' + data.sale_id;
                        } else if (self.printerType === 'epson') {
                            printUrl = '{{ url('pos/print-epson') }}/' + data.sale_id;
                        } else {
                            printUrl = '{{ url('pos/print-a4') }}/' + data.sale_id;
                        }
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

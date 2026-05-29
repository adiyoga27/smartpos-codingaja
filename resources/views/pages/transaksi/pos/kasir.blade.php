@extends('layouts.pos')
@section('title', 'POS Kasir')
@section('content')
<div x-data="posKasir()" class="h-full flex flex-col">
    <!-- Mini header -->
    <div class="flex items-center justify-between px-3 py-2 bg-white border-b border-slate-200 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <span class="font-bold text-slate-700 hidden sm:inline">POS Kasir</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative" x-data="{ custOpen: false, custSearch: '', selectedCust: null }">
                <div class="flex items-center gap-1">
                    <i class="bi bi-person text-slate-400 text-sm"></i>
                    <input type="text"
                           x-model="custSearch"
                           @input="custOpen = true"
                           @focus="custOpen = true"
                           @click.outside="custOpen = false"
                           class="text-xs border-b border-slate-200 bg-transparent py-1 px-1 w-36 outline-none focus:border-primary-500"
                           placeholder="Walk-in..."
                           autocomplete="off">
                    <input type="hidden" name="customer_id" :value="selectedCust ? selectedCust.id : ''">
                </div>
                <div x-show="custOpen && custSearch.length >= 1" x-cloak x-transition.opacity
                     class="absolute z-30 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg w-64 max-h-48 overflow-y-auto">
                    <template x-for="c in searchCustomers(custSearch)" :key="c.id">
                        <div @click="selectedCust=c; custSearch=c.name; custOpen=false"
                             class="px-3 py-2 text-sm hover:bg-primary-50 cursor-pointer flex items-center justify-between">
                            <span>
                                <span class="font-medium" x-text="c.name"></span>
                                <span class="text-slate-400 ml-1 text-xs" x-text="'['+c.code+']'"></span>
                            </span>
                        </div>
                    </template>
                    <div x-show="searchCustomers(custSearch).length === 0"
                         class="px-3 py-2 text-sm text-slate-400 text-center">Tidak ditemukan</div>
                </div>
            </div>
            <span class="text-xs text-slate-400 hidden lg:inline">
                <i class="bi bi-calendar3 mr-1"></i> <span id="headerDateTime">{{ now()->isoFormat('dddd, D MMMM Y HH:mm') }}</span>
            </span>
            <a href="{{ route('pos.riwayat') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-clock-history"></i> Riwayat
            </a>
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
            <a href="{{ route('pos.riwayat') }}" class="btn btn-secondary btn-sm whitespace-nowrap">
                <i class="bi bi-clock-history"></i> <span class="hidden sm:inline">Riwayat</span>
            </a>
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
                            <select name="payment_method" class="form-select text-sm" id="paymentMethod" required>
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="credit">Kredit (Hutang)</option>
                            </select>
                        </div>
                        <div id="bankPanel" style="display:none;">
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Akun Bank</label>
                            <select name="cash_account_id" class="form-select text-sm" id="cashAccountSelect">
                                @foreach($cashAccounts->where('type', 'bank') as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
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

                    <div class="grid grid-cols-2 gap-2" id="cashPanel">
                        <div>
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Bayar (Rp)</label>
                            <input type="text" name="paid_amount" id="paidAmount" class="form-input text-sm font-mono" value="0" inputmode="numeric">
                        </div>
                        <div id="cashPanelTotal">
                            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Kembalian</label>
                            <div class="form-input text-sm font-medium text-emerald-600 bg-emerald-50 flex items-center h-[42px]" id="changeAmount">Rp 0</div>
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
                        <div class="flex justify-between items-center pt-2 border-t-2 border-slate-200">
                            <span class="text-base font-bold text-slate-800">TOTAL</span>
                            <span class="text-xl font-extrabold text-primary-700" id="cartTotal">Rp 0</span>
                        </div>
                    </div>

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

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('posKasir', () => ({
        cart: [],
        searchProduct: '',
        filteredCount: {{ $products->count() }},
        allCustomers: @json($customers->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])),

        init() {
            this.$watch('cart', () => {
                let empty = document.getElementById('emptyCart');
                if (empty) empty.style.display = this.cart.length ? 'none' : 'flex';
            });
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
        clearCart() { if (confirm('Kosongkan semua item?')) { this.cart = []; this.renderCart(); } },

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
            document.getElementById('cartSubtotal').textContent = this.formatRupiah(subtotal);
            document.getElementById('cartDiscount').textContent = this.formatRupiah(discount);
            // Tax
            let taxable = Math.max(0, subtotal - discount);
            this.recalcTaxDisplay(taxable);
            document.getElementById('btnPay').disabled = this.cart.length === 0;
            this.updateChange();
        },

        recalcTax() { this.renderCart(); },

        recalcTaxDisplay(taxable) {
            let sel = document.getElementById('taxSelect');
            let rate = 0;
            if (sel && sel.selectedIndex > 0) {
                let opt = sel.options[sel.selectedIndex];
                rate = parseFloat(opt.dataset.rate) || 0;
            }
            let taxAmount = Math.round(taxable * rate / 100);
            document.getElementById('taxLabel').textContent = rate + '%';
            document.getElementById('cartTax').textContent = this.formatRupiah(taxAmount);
            document.getElementById('cartTotal').textContent = this.formatRupiah(taxable + taxAmount);
        },

        updateChange() {
            let total = parseInt(document.getElementById('cartTotal').textContent.replace(/\D/g, ''), 10) || 0;
            let paidEl = document.getElementById('paidAmount');
            let paid = parseInt((paidEl.value || '').replace(/\D/g, ''), 10) || 0;
            document.getElementById('changeAmount').textContent = this.formatRupiah(Math.max(0, paid - total));
        },

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
        table.addEventListener('input', function(e) {
            let el = e.target;
            let idx = parseInt(el.dataset.idx);
            let data = document.querySelector('[x-data]');
            if (!data || !data.__x) return;
            let cart = data.__x.$data.cart;
            if (!cart) return;
            if (el.classList.contains('qty-input')) cart[idx].qty = parseFloat(el.value) || 1;
            if (el.classList.contains('price-input')) cart[idx].price = parseInt((el.value || '').replace(/\D/g, ''), 10) || 0;
            if (el.classList.contains('disc-input')) cart[idx].discount = parseInt((el.value || '').replace(/\D/g, ''), 10) || 0;
            updateTotalsOnly(cart);
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
        paid.addEventListener('focus', function() { this.value = this.value.replace(/\D/g, ''); });
        paid.addEventListener('blur', function() {
            let raw = parseInt(this.value.replace(/\D/g, ''), 10) || 0;
            this.value = raw.toLocaleString('id-ID');
        });
        paid.addEventListener('input', function() {
            let data = document.querySelector('[x-data]');
            if (data && data.__x) data.__x.$data.updateChange();
        });
    }

    let pm = document.getElementById('paymentMethod');
    if (pm) {
        pm.addEventListener('change', function() {
            let cash = document.getElementById('cashPanel');
            let cashTotal = document.getElementById('cashPanelTotal');
            let bankPanel = document.getElementById('bankPanel');
            if (cash) cash.style.display = this.value === 'cash' ? '' : 'none';
            if (cashTotal) cashTotal.style.display = this.value === 'cash' ? '' : 'none';
            if (bankPanel) bankPanel.style.display = this.value === 'transfer' ? '' : 'none';
        });
        pm.dispatchEvent(new Event('change'));
    }

    let form = document.getElementById('posForm');
    if (form) {
        form.addEventListener('submit', function() {
            let p = document.getElementById('paidAmount');
            if (p) p.value = p.value.replace(/\D/g, '') || '0';
            document.querySelectorAll('#cartTable tbody .price-input, #cartTable tbody .disc-input').forEach(function(el) {
                el.value = el.value.replace(/\D/g, '') || '0';
            });
        });
    }
});

function updateTotalsOnly(cart) {
    if (!cart) return;
    let subtotal = 0, discount = 0;
    let fmt = function(a) { return 'Rp ' + a.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    cart.forEach((item, idx) => {
        subtotal += item.qty * item.price;
        discount += item.discount;
        let rowTotal = Math.max(0, item.qty * item.price - item.discount);
        let cells = document.querySelectorAll('#cartTable tbody tr');
        if (cells[idx]) {
            let td = cells[idx].querySelector('td:nth-child(5)');
            if (td) td.textContent = fmt(rowTotal);
        }
    });
    let taxable = Math.max(0, subtotal - discount);
    document.getElementById('cartSubtotal').textContent = fmt(subtotal);
    document.getElementById('cartDiscount').textContent = fmt(discount);

    let sel = document.getElementById('taxSelect');
    let rate = 0;
    if (sel && sel.selectedIndex > 0) {
        let opt = sel.options[sel.selectedIndex];
        rate = parseFloat(opt.dataset.rate) || 0;
    }
    let taxAmount = Math.round(taxable * rate / 100);
    document.getElementById('taxLabel').textContent = rate + '%';
    document.getElementById('cartTax').textContent = fmt(taxAmount);
    document.getElementById('cartTotal').textContent = fmt(taxable + taxAmount);

    document.getElementById('btnPay').disabled = cart.length === 0;
    let data = document.querySelector('[x-data]');
    if (data && data.__x) data.__x.$data.updateChange();
}
</script>
@endpush
@endsection

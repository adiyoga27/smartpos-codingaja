@extends('layouts.app')
@section('title', 'Buat SO')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transaksi.sales_orders.index') }}">Sales Order</a></li>
    <li class="breadcrumb-item active">Buat SO</li>
@endsection
@section('content')
<div x-data="soForm()">
<form action="{{ route('transaksi.sales_orders.store') }}" method="POST" id="soForm" @submit.prevent="handleSubmit">
    @csrf
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-cart3 text-rose-500 me-1"></i> Detail Sales Order</h6></div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="form-label text-xs">No. Dokumen</label>
                    <input type="text" name="document_number" class="form-input form-input-sm" value="{{ $documentNumber }}" readonly required>
                </div>
                <div>
                    <label class="form-label text-xs">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm select2" required id="soCustomer">
                        <option value="">- Pilih Customer -</option>
                        @foreach($customers as $c)<option value="{{ $c->id }}" data-type="{{ $c->type }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Tanggal</label>
                    <input type="date" name="order_date" class="form-input form-input-sm" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label text-xs">Jatuh Tempo</label>
                    <input type="date" name="due_date" class="form-input form-input-sm">
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label text-xs">Catatan</label>
                <textarea name="notes" class="form-input form-input-sm" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header flex flex-wrap items-center justify-between gap-2">
            <h6 class="mb-0"><i class="bi bi-list-check text-primary-500 me-1"></i> Item SO</h6>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5">
                    <button type="button" @click="posMode = 'toko'; onModeChange()"
                            :class="posMode === 'toko' ? 'bg-white shadow text-blue-700 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                            class="px-3 py-1 rounded-md text-xs transition-all duration-150">Toko</button>
                    <button type="button" @click="posMode = 'reseller'; onModeChange()"
                            :class="posMode === 'reseller' ? 'bg-white shadow text-purple-700 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                            class="px-3 py-1 rounded-md text-xs transition-all duration-150">Reseller</button>
                </div>
                <span class="text-xs text-slate-400" x-text="itemCount()"></span>
                <button type="button" class="btn btn-primary btn-sm rounded-full px-4 shadow-sm" @click="showModal = true">
                    <i class="bi bi-plus-lg"></i> Tambah Produk
                </button>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="text-center py-10 text-slate-400" x-show="Object.keys(selectedItems).length === 0">
                <i class="bi bi-inbox text-4xl block mb-3"></i>
                <span class="text-sm">Belum ada item</span>
                <p class="text-xs mt-1">Klik tombol "Tambah Produk" di atas</p>
            </div>
            <div class="space-y-2" x-show="Object.keys(selectedItems).length > 0">
                <template x-for="(it, id) in selectedItems" :key="id">
                    <div class="bg-white border border-slate-200 rounded-lg p-3">
                        <div class="flex items-start gap-3">
                            <div class="w-14 h-14 rounded-lg bg-slate-100 flex-shrink-0 overflow-hidden">
                                <img :src="it.photo" class="w-full h-full object-cover" x-show="it.photo">
                                <i class="bi bi-box-seam text-slate-300 flex items-center justify-center h-full w-full text-xl" x-show="!it.photo"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 line-clamp-1" x-text="it.name"></p>
                                <p class="text-[10px] text-slate-400" x-text="it.code"></p>
                                <div class="grid grid-cols-4 gap-2 mt-2">
                                    <div>
                                        <label class="text-[10px] text-slate-400">Qty</label>
                                        <input type="number" :value="it.qty" min="0.01" step="0.01" class="form-input form-input-sm text-sm"
                                               @change="updateItem(id, 'qty', $event.target.value)" @input="updateItem(id, 'qty', $event.target.value)">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400">Harga</label>
                                        <input type="text" :value="fmtNum(it.price)" class="form-input form-input-sm text-sm input-rupiah"
                                               @input="updateItem(id, 'price', $event.target.value); formatRupiahInput($event.target)">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400">Diskon</label>
                                        <input type="text" :value="it.discount ? fmtNum(it.discount) : '0'" class="form-input form-input-sm text-sm input-rupiah"
                                               @input="updateItem(id, 'disc', $event.target.value); formatRupiahInput($event.target)">
                                    </div>
                                    <div class="text-right">
                                        <label class="text-[10px] text-slate-400 d-block">Total</label>
                                        <span class="text-sm font-bold text-primary-600" x-text="lineTotal(it)"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger p-0 w-7 h-7 flex items-center justify-center flex-shrink-0" @click="delete selectedItems[id]; renderHidden()">
                                <i class="bi bi-x text-sm"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <div class="card-footer bg-slate-50/50 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <div></div>
            <div class="text-right">
                <div class="space-y-1 mb-2">
                    <div class="flex justify-between gap-6 text-sm"><span class="text-slate-500">Subtotal</span><span class="font-medium" x-text="formatRp(subtotal())"></span></div>
                    <div class="flex justify-between gap-6 text-sm"><span class="text-slate-500">Diskon</span><span class="text-red-500 font-medium" x-text="formatRp(discountTotal())"></span></div>
                    <div class="flex justify-between gap-6 items-center pt-1.5 border-t border-slate-200">
                        <span class="text-base font-bold">TOTAL</span>
                        <span class="text-xl font-extrabold text-primary-700" x-text="formatRp(grandTotal())"></span>
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <a href="{{ route('transaksi.sales_orders.index') }}" class="btn btn-outline-secondary rounded-full px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-full px-5 shadow-md" :disabled="Object.keys(selectedItems).length === 0">
                        <i class="bi bi-check-lg me-1"></i> Simpan SO
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Product Modal -->
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="showModal = false">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
    <div class="absolute inset-4 sm:inset-x-10 sm:inset-y-8 bg-white rounded-2xl shadow-2xl flex flex-col" @click.stop>
        <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-rose-500 to-pink-500 text-white rounded-t-2xl shrink-0">
            <h5 class="text-lg font-bold"><i class="bi bi-box-seam me-2"></i>Pilih Produk</h5>
            <div class="flex items-center gap-3">
                <input type="text" x-model="searchQuery" @input="filterProducts()" class="form-input form-input-sm text-slate-700" placeholder="Cari nama / kode..." style="min-width:200px">
                <button @click="showModal = false" class="text-white/80 hover:text-white"><i class="bi bi-x-lg text-xl"></i></button>
            </div>
        </div>
        <div class="overflow-y-auto p-3 flex-1">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2">
                <template x-for="p in filteredProducts()" :key="p.id">
                    <div class="border border-slate-200 rounded-xl p-2.5 cursor-pointer hover:border-rose-400 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150"
                         @click="addProduct(p); $el.style.transform='scale(0.95)'; setTimeout(() => $el.style.transform='', 100)">
                        <div class="w-full aspect-[4/3] rounded-lg bg-slate-100 flex items-center justify-center mb-2 overflow-hidden">
                            <img :src="p.photo" class="w-full h-full object-cover" x-show="p.photo">
                            <i class="bi bi-box-seam text-2xl text-slate-300" x-show="!p.photo"></i>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-700 line-clamp-2 leading-tight mb-1" x-text="p.name"></p>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400" x-text="p.code"></span>
                            <span class="text-[11px] font-bold text-primary-600" x-text="formatRp(getPrice(p))"></span>
                        </div>
                    </div>
                </template>
            </div>
            <div class="text-center py-10 text-slate-400" x-show="filteredProducts().length === 0">
                <i class="bi bi-search text-3xl block mb-2"></i>
                <span class="text-sm">Produk tidak ditemukan</span>
            </div>
        </div>
    </div>
</div>
</div>
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('soForm', () => ({
        selectedItems: {},
        showModal: false,
        searchQuery: '',
        posMode: 'toko',
        products: {!! $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'retail_price' => (float) $p->selling_price, 'wholesale_price' => (float) $p->wholesale_price, 'photo' => $p->photo ? asset('storage/'.$p->photo) : ''])->values()->toJson() !!},

        init() {
            this.filterCustomersByMode();
        },

        filterCustomersByMode() {
            let targetType = this.posMode === 'reseller' ? 'wholesale' : 'retail';
            let sel = document.getElementById('soCustomer');
            if (!sel) return;
            let currentVal = sel.value;
            Array.from(sel.options).forEach(opt => {
                if (!opt.value) return;
                opt.style.display = opt.dataset.type === targetType ? '' : 'none';
            });
            if (currentVal) {
                let stillVisible = Array.from(sel.options).some(o => o.value === currentVal && o.style.display !== 'none');
                if (!stillVisible) sel.value = '';
            }
        },

        getPrice(product) {
            return this.posMode === 'reseller' ? product.wholesale_price : product.retail_price;
        },

        onModeChange() {
            this.filterCustomersByMode();
            Object.values(this.selectedItems).forEach(it => {
                let prod = this.products.find(p => p.id === it.id);
                if (prod) it.price = this.getPrice(prod);
            });
        },

        itemCount() { let n = Object.keys(this.selectedItems).length; return n + ' item'; },
        subtotal() { return Object.values(this.selectedItems).reduce((s, it) => s + it.qty * it.price, 0); },
        discountTotal() { return Object.values(this.selectedItems).reduce((s, it) => s + (it.discount || 0), 0); },
        grandTotal() { return Math.max(0, this.subtotal() - this.discountTotal()); },
        lineTotal(it) { return this.formatRp(Math.max(0, it.qty * it.price - (it.discount || 0))); },

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
            let price = this.getPrice(p);
            if (this.selectedItems[p.id]) {
                this.selectedItems[p.id].qty += 1;
                this.selectedItems[p.id].price = price;
            } else {
                this.selectedItems[p.id] = { id: p.id, name: p.name, code: p.code, photo: p.photo, price: price, qty: 1, discount: 0 };
            }
            this.showModal = false;
        },

        updateItem(id, field, val) {
            if (!this.selectedItems[id]) return;
            let it = this.selectedItems[id];
            if (field === 'qty') { let q = parseFloat(val) || 0; if (q <= 0) { delete this.selectedItems[id]; return; } it.qty = q; }
            else if (field === 'price') it.price = this.parseRupiah(val);
            else if (field === 'disc') it.discount = this.parseRupiah(val);
        },

        renderHidden() { /* triggers Alpine re-render */ },

        handleSubmit(e) {
            if (Object.keys(this.selectedItems).length === 0) { showToast('Pilih minimal 1 produk', 'warning'); return; }
            let customer = document.querySelector('[name="customer_id"]');
            if (!customer || !customer.value) { showToast('Pilih customer', 'warning'); return; }
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
            form.submit();
        }
    }));
});

$(document).ready(function() { $('.select2').select2({ theme: 'bootstrap-5', width: '100%' }); });
</script>
@endpush
@endsection

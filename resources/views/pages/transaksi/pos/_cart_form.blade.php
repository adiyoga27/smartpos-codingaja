<form action="{{ route('transaksi.sales.store') }}" method="POST" id="posForm" data-noloading="true" class="flex flex-col flex-1 min-h-0 w-full overflow-hidden">
    @csrf
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-2.5 text-white shrink-0 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="bi bi-cart3"></i>
            <span class="font-bold text-sm">Keranjang</span>
        </div>
        <span class="bg-white/20 text-white text-[10px] px-2 py-0.5 rounded-full" x-text="cart.length + ' item'"></span>
    </div>
    <div class="overflow-y-auto overflow-x-auto flex-1 flex flex-col custom-scrollbar">
        <div class="flex flex-col flex-1 w-full min-w-[500px] pb-4">
            <div class="flex-1 w-full">
                <table class="table mb-0 w-full" id="cartTable">
                <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200 shadow-sm">
                    <tr>
                        <th class="text-[11px] py-2 px-1 text-slate-500 font-bold tracking-wider">Produk</th>
                        <th class="text-[11px] py-2 px-1 text-slate-500 font-bold tracking-wider">Qty</th>
                        <th class="text-[11px] py-2 px-1 text-slate-500 font-bold tracking-wider">Harga</th>
                        <th class="text-[11px] py-2 px-1 text-slate-500 font-bold tracking-wider">Disc</th>
                        <th class="text-[11px] py-2 px-1 text-right text-slate-500 font-bold tracking-wider">Total</th>
                        <th class="py-2 px-1"></th>
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

        <div class="border-t border-slate-200 bg-slate-50/70 px-4 py-2.5 space-y-2 shrink-0 mt-auto">
            <input type="hidden" name="invoice_number" value="{{ $invoiceNumber }}">
        <input type="hidden" name="sale_date" value="{{ now()->format('Y-m-d') }}">

        <div>
            <label class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">Customer</label>
            <div class="flex items-center gap-1 mt-0.5">
                <button type="button" @click="custPickerModal = true; custPickerSearch = ''"
                        class="form-input form-input-sm text-xs flex-1 text-left flex items-center gap-2">
                    <i class="bi bi-person text-slate-400"></i>
                    <span x-text="selectedCust ? selectedCust.name + ' [' + selectedCust.code + ']' : 'Walk-in / Umum'" class="truncate"></span>
                </button>
                <input type="hidden" name="customer_id" :value="selectedCust ? selectedCust.id : ''">
                <button type="button" @click="selectedCust = null" x-show="selectedCust" class="text-red-400 hover:text-red-600 p-0.5" title="Hapus customer">
                    <i class="bi bi-x-circle text-xs"></i>
                </button>
                <button type="button" @click="customerModal = true; customerForm = { name: '', phone: '', type: posMode === 'reseller' ? 'wholesale' : 'retail' }" class="btn btn-sm btn-primary p-0 w-6 h-6 flex items-center justify-center rounded-full" title="Tambah Customer">
                    <i class="bi bi-plus text-xs"></i>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Pembayaran</label>
                <select name="payment_method_id" class="form-select text-xs py-1.5" id="paymentMethod" required>
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}" data-credit="{{ $pm->is_credit ? '1' : '0' }}">{{ $pm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Pajak</label>
                <select name="tax_id" class="form-select text-xs py-1.5" id="taxSelect" @change="recalcTax()">
                    <option value="">Tanpa Pajak</option>
                    @foreach($taxes as $tax)
                    <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ isset($defaultTax) && $tax->id === $defaultTax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->rate, 1) }}%)</option>
                    @endforeach
                </select>
            </div>
          
        </div>

        <div id="accountPanel" style="display:none;">
            <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Kas / Bank Tujuan</label>
            <select name="cash_account_id" class="form-select text-xs py-1.5 mt-0.5">
                @foreach($cashAccounts as $ca)
                <option value="{{ $ca->id }}" {{ $ca->is_default ? 'selected' : '' }}>{{ $ca->name }} ({{ formatRupiah($ca->current_balance) }})</option>
                @endforeach
            </select>
        </div>

        <div id="tempoPanel" style="display:none;" class="space-y-1.5">
            <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Jatuh Tempo</label>
            <select x-model="creditTerm" @change="onCreditTermChange()" class="form-select text-xs py-1.5">
                <option value="1">1 Minggu</option>
                <option value="2">2 Minggu</option>
                <option value="3">3 Minggu</option>
                <option value="4">4 Minggu</option>
                <option value="custom">Custom</option>
            </select>
            <div x-show="creditTerm === 'custom'" class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] text-slate-400">Tgl Mulai</label>
                    <div class="relative">
                        <input type="date" x-model="creditStartDate" class="form-input text-xs py-1.5" value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div>
                    <label class="text-[10px] text-slate-400">Tgl Jatuh Tempo</label>
                    <div class="relative">
                        <input type="date" x-model="creditDueDate" class="form-input text-xs py-1.5">
                    </div>
                </div>
            </div>
            <input type="hidden" name="due_date" :value="creditTerm !== 'custom' ? getComputedDueDate() : creditDueDate">
        </div>

 <div>
    <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">
        Diskon / Catatan
    </label>

    <div class="flex flex-col gap-2 mt-0.5">
        <input
            type="number"
            x-model.number="additionalDiscount"
            class="form-input text-xs font-mono py-1.5 w-20"
            value="0"
            @input="updateTotals()"
            placeholder="Diskon"
        />

        <textarea
            name="notes"
            class="form-input text-xs py-1.5"
            rows="2"
            placeholder="Catatan..."
        ></textarea>
    </div>
    
</div>
<div>
                <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Bayar (Rp)</label>
                <input type="text" name="paid_amount" id="paidAmount" class="form-input text-xs font-mono py-1.5" value="0" inputmode="numeric"
                       @input="updateChange()"
                       @focus="$el.value = $el.value.replace(/\D/g, '')"
                       @blur="$el.value = parseInt($el.value.replace(/\D/g, ''), 10).toLocaleString('id-ID')" />
            </div>

        <div class="bg-white rounded-lg p-2.5 space-y-1 border border-slate-200">
            <div class="flex justify-between text-[11px]">
                <span class="text-slate-500">Subtotal</span>
                <span class="font-medium" id="cartSubtotal">Rp 0</span>
            </div>
            <div class="flex justify-between text-[11px]">
                <span class="text-slate-500">Diskon Item</span>
                <span class="text-red-500 font-medium" id="cartDiscount">Rp 0</span>
            </div>
            <div class="flex justify-between text-[11px]">
                <span class="text-slate-500">Pajak (<span id="taxLabel">0%</span>)</span>
                <span class="font-medium text-amber-600" id="cartTax">Rp 0</span>
            </div>
            <div class="flex justify-between text-[11px]">
                <span class="text-slate-500">Diskon Tambahan</span>
                <span class="text-red-500 font-medium" id="cartAddDisc">Rp 0</span>
            </div>
            </div>
            <input type="hidden" name="total_discount" id="totalAddDisc" value="0">
        </div>
    </div>
    </div>

    <div class="border-t border-slate-200 bg-white p-3 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-20 relative">
        <div class="flex justify-between items-center mb-1">
            <span class="text-sm font-bold text-slate-800">TOTAL</span>
            <span class="text-xl font-extrabold text-primary-700" id="cartTotal">Rp 0</span>
        </div>
        <div class="flex justify-between text-xs mb-2" id="changeRow" style="display:none;">
            <span class="text-slate-500 font-medium">Kembalian</span>
            <span class="font-bold text-emerald-600" id="changeAmount">Rp 0</span>
        </div>
        <div class="flex gap-2">
            <button type="button" class="btn btn-primary btn-md flex-1 text-sm" id="btnPay" disabled
                    @click="handleCheckout()" :disabled="cart.length === 0 || loading">
                <span x-show="!loading"><i class="bi bi-cash-coin"></i> <span x-text="btnPayLabel"></span></span>
                <span x-show="loading" class="flex items-center gap-2 justify-center">
                    <span class="spinner-border spinner-border-sm"></span> Memproses...
                </span>
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" @click="clearCart()" x-show="cart.length > 0">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</form>

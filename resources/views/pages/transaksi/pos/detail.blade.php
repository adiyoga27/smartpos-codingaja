@extends('layouts.app')
@section('title', 'Detail Transaksi - '.$sale->invoice_number)
@section('breadcrumb')
    <a href="{{ route('pos.kasir') }}" class="text-slate-400 hover:text-slate-600 transition-colors">POS</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('pos.riwayat') }}" class="text-slate-400 hover:text-slate-600 transition-colors">Riwayat</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600 font-medium">Detail</span>
@endsection
@section('content')
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h4 class="text-2xl font-bold text-slate-800 mb-1 flex items-center gap-3">
            Transaksi
            <span class="bg-primary-100 text-primary-700 text-sm font-mono px-3 py-1 rounded-full border border-primary-200">
                {{ $sale->invoice_number }}
            </span>
        </h4>
        <p class="text-sm text-slate-500 flex items-center gap-2">
            <i class="bi bi-calendar3"></i> {{ $sale->sale_date->isoFormat('dddd, D MMMM Y - HH:mm') }}
        </p>
    </div>
    <div class="flex gap-2 w-full sm:w-auto">
        <a href="{{ route('pos.print-epson', $sale) }}" target="_blank" class="w-full sm:w-auto btn bg-gradient-to-r from-primary-600 to-indigo-600 hover:from-primary-700 hover:to-indigo-700 text-white border-0 shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
            <i class="bi bi-printer"></i> Cetak Struk
        </a>
        <a href="{{ route('pos.riwayat') }}" class="w-full sm:w-auto btn bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-sm transition-all flex items-center justify-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<!-- Info Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Customer Card -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
            <i class="bi bi-person text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-0.5">Customer</p>
            <p class="text-sm font-semibold text-slate-800 line-clamp-1">{{ $sale->customer?->name ?? $sale->customer_name ?? 'Pelanggan Umum' }}</p>
        </div>
    </div>
    
    <!-- Payment Method Card -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
            <i class="bi bi-wallet2 text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-0.5">Metode Bayar</p>
            <p class="text-sm font-semibold text-slate-800 line-clamp-1">{{ $sale->paymentMethod?->name ?? '-' }}</p>
        </div>
    </div>
    
    <!-- Status Card -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        @php
            $statusBg = 'bg-slate-50';
            $statusText = 'text-slate-600';
            $statusIcon = 'bi-info-circle';
            $statusLabel = 'Batal';
            
            if($sale->status === 'paid') {
                $statusBg = 'bg-success-50';
                $statusText = 'text-success-600';
                $statusIcon = 'bi-check-circle';
                $statusLabel = 'Lunas';
            } elseif($sale->status === 'partial') {
                $statusBg = 'bg-warning-50';
                $statusText = 'text-warning-600';
                $statusIcon = 'bi-exclamation-circle';
                $statusLabel = 'Sebagian';
            } elseif($sale->status === 'unpaid') {
                $statusBg = 'bg-danger-50';
                $statusText = 'text-danger-600';
                $statusIcon = 'bi-x-circle';
                $statusLabel = 'Belum Bayar';
            }
        @endphp
        <div class="w-12 h-12 rounded-full {{ $statusBg }} flex items-center justify-center {{ $statusText }} flex-shrink-0">
            <i class="bi {{ $statusIcon }} text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-0.5">Status</p>
            <p class="text-sm font-bold {{ $statusText }} line-clamp-1">{{ $statusLabel }}</p>
        </div>
    </div>
    
    <!-- Cashier Card -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 flex-shrink-0">
            <i class="bi bi-person-badge text-xl"></i>
        </div>
        <div>
            <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-0.5">Kasir</p>
            <p class="text-sm font-semibold text-slate-800 line-clamp-1">{{ $sale->creator?->name ?? '-' }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Item List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h5 class="font-semibold text-slate-800 mb-0">Item Transaksi</h5>
                <span class="badge bg-slate-200 text-slate-700">{{ $sale->items->count() }} Item</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-white">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Qty</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Harga</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Diskon</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sale->items as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded bg-slate-100 flex items-center justify-center text-slate-400 shrink-0">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800 mb-0.5">{{ $item->product?->name ?? 'Produk Dihapus' }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ $item->product?->code ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md text-xs font-medium">{{ formatQty($item->quantity) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-600">
                                {{ formatRupiah($item->unit_price) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                @if($item->discount > 0)
                                    <span class="text-red-500 font-medium">-{{ formatRupiah($item->discount) }}</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-slate-800">
                                {{ formatRupiah($item->total) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i class="bi bi-inbox text-4xl mb-3 text-slate-300"></i>
                                    <p>Tidak ada item dalam transaksi ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($sale->notes)
        <div class="mt-6 bg-amber-50 rounded-2xl p-5 border border-amber-100 flex gap-4">
            <i class="bi bi-journal-text text-amber-500 text-xl mt-0.5"></i>
            <div>
                <h6 class="text-amber-800 font-semibold mb-1 text-sm">Catatan Transaksi</h6>
                <p class="text-sm text-amber-700/80 mb-0">{{ $sale->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column: Summary & Payment -->
    <div class="space-y-6">
        <!-- Rincian Pembayaran -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h5 class="font-semibold text-slate-800 mb-0">Rincian Pembayaran</h5>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Subtotal</span>
                    <span class="font-medium text-slate-800">{{ formatRupiah($sale->subtotal) }}</span>
                </div>
                
                @if($sale->item_discount > 0)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Diskon Item</span>
                    <span class="font-medium text-red-500">-{{ formatRupiah($sale->item_discount) }}</span>
                </div>
                @endif
                
                @if($sale->total_discount > 0)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">Diskon Tambahan</span>
                    <span class="font-medium text-red-500">-{{ formatRupiah($sale->total_discount) }}</span>
                </div>
                @endif
                
                @if($sale->tax > 0)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500 flex items-center gap-1">Pajak <i class="bi bi-info-circle text-xs" title="Sesuai tarif pajak berlaku"></i></span>
                    <span class="font-medium text-slate-800">+{{ formatRupiah($sale->tax) }}</span>
                </div>
                @endif
                
                <hr class="border-dashed border-slate-200">
                
                <div class="bg-gradient-to-br from-primary-50 to-indigo-50 rounded-xl p-4 border border-primary-100/50">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-primary-800 font-semibold text-sm">Total Tagihan</span>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-primary-700">{{ formatRupiah($sale->total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Pembayaran -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h5 class="font-semibold text-slate-800 mb-0">Status Pembayaran</h5>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Nominal Dibayar</span>
                    <span class="text-sm font-semibold text-emerald-600">{{ formatRupiah($sale->paid_amount) }}</span>
                </div>
                
                @if($sale->change_amount > 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Kembalian</span>
                    <span class="text-sm font-medium text-slate-700">{{ formatRupiah($sale->change_amount) }}</span>
                </div>
                @endif
                
                @php $sisa = max(0, $sale->total - $sale->paid_amount); @endphp
                @if($sisa > 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Sisa Tagihan</span>
                    <span class="text-sm font-bold text-red-600">{{ formatRupiah($sisa) }}</span>
                </div>
                
                @if($sale->due_date)
                <div class="mt-3 bg-red-50 text-red-700 p-3 rounded-lg text-xs flex items-start gap-2 border border-red-100">
                    <i class="bi bi-clock-history mt-0.5"></i>
                    <div>
                        <strong>Jatuh Tempo:</strong> {{ $sale->due_date->isoFormat('D MMMM Y') }}<br>
                        <span class="opacity-80">Harap lakukan pelunasan sebelum tanggal tersebut.</span>
                    </div>
                </div>
                @endif
                
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

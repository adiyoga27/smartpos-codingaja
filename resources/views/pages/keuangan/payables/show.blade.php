@extends('layouts.app')
@section('title', 'Detail Hutang')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('keuangan.payables.index') }}" class="text-slate-500 hover:text-primary-600 transition-colors">Hutang</a></li>
    <li class="breadcrumb-item active text-slate-800 font-semibold" aria-current="page">Detail</li>
@endsection

@section('content')
<div class="max-w-4xl mx-auto pb-10">
    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-file-earmark-text text-primary-600"></i> Detail Hutang
            </h2>
            <p class="text-sm text-slate-500 mt-1">Informasi lengkap rincian hutang dan riwayat pembayaran.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('keuangan.payables.index') }}" class="btn btn-light rounded-xl px-4 py-2 flex items-center gap-2 shadow-sm hover:bg-slate-100 transition-all">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            @if($payable->remaining_amount > 0)
            <a href="{{ route('keuangan.payables.pay', $payable) }}" class="btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-5 py-2 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center gap-2">
                <i class="bi bi-cash text-lg"></i> Bayar
            </a>
            @endif
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Dokumen Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-full -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center border border-slate-100 shadow-sm z-10">
                <i class="bi bi-hash text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-0.5">No. Dokumen</p>
                <p class="text-sm font-bold text-slate-800">{{ $payable->document_number }}</p>
            </div>
        </div>
        
        <!-- Supplier Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-16 h-16 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center border border-indigo-100 shadow-sm z-10">
                <i class="bi bi-shop text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-0.5">Supplier</p>
                <p class="text-sm font-bold text-slate-800">{{ $payable->supplier?->name ?? 'Umum' }}</p>
            </div>
        </div>

        <!-- Status Card -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-16 h-16 bg-sky-50 rounded-bl-full -mr-4 -mt-4 z-0"></div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-500 flex items-center justify-center border border-sky-100 shadow-sm z-10">
                <i class="bi bi-flag text-xl"></i>
            </div>
            <div class="z-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-0.5">Status</p>
                @if($payable->status === 'paid')
                    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-md text-xs font-bold">Lunas</span>
                @elseif($payable->status === 'partial')
                    <span class="bg-warning-100 text-warning-700 px-2.5 py-1 rounded-md text-xs font-bold">Sebagian</span>
                @else
                    <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md text-xs font-bold">Belum Dibayar</span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Rincian -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl shadow-xl text-white overflow-hidden relative">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                
                <div class="p-5 sm:p-6 border-b border-slate-700/50 relative z-10">
                    <h6 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="bi bi-calculator text-primary-400"></i> Rincian Hutang
                    </h6>
                </div>
                
                <div class="p-5 sm:p-6 relative z-10 space-y-4">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Total Hutang Awal</span>
                        <span class="font-semibold text-slate-200">{{ formatRupiah($payable->amount) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Telah Dibayar</span>
                        <span class="font-semibold text-emerald-400">{{ formatRupiah($payable->paid_amount) }}</span>
                    </div>
                    
                    <div class="pt-4 mt-2 border-t border-slate-700/50">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-slate-400">Sisa Tagihan</span>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-indigo-400">{{ formatRupiah($payable->remaining_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative h-full">
                <div class="absolute top-0 left-0 w-1 h-full bg-primary-500"></div>
                <div class="p-5 sm:p-6 border-b border-slate-50">
                    <h6 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-1">
                        <i class="bi bi-clock-history text-primary-500"></i> Riwayat Pembayaran
                    </h6>
                </div>
                <div class="p-0">
                    @if($payable->payments->isEmpty())
                        <div class="text-center py-16">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="bi bi-receipt text-2xl text-slate-300"></i>
                            </div>
                            <h5 class="text-slate-600 font-medium text-sm">Belum Ada Pembayaran</h5>
                            <p class="text-xs text-slate-400 mt-1">Hutang ini belum memiliki riwayat cicilan atau pelunasan.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase text-slate-500 font-bold tracking-wider">
                                    <tr>
                                        <th class="px-5 py-3">Tanggal</th>
                                        <th class="px-5 py-3">Akun Kas/Bank</th>
                                        <th class="px-5 py-3">Catatan</th>
                                        <th class="px-5 py-3 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($payable->payments as $payment)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-5 py-3 whitespace-nowrap text-xs font-medium">{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td class="px-5 py-3 font-medium text-slate-800 text-xs">
                                            <span class="bg-slate-100 px-2 py-1 rounded-md">{{ $payment->cashAccount?->name ?? '-' }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-xs">{{ $payment->notes ?: '-' }}</td>
                                        <td class="px-5 py-3 text-right font-bold text-emerald-600">{{ formatRupiah($payment->amount) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

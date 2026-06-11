@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Dashboard</span>
@endsection
@section('content')

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Sales Today -->
    <div class="stat-card">
        <div class="flex items-center gap-4">
            <div class="stat-icon bg-blue-50 text-blue-600">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Penjualan Hari Ini</p>
                <p class="text-xl font-bold text-slate-800 mt-0.5">{{ formatRupiah($salesToday) }}</p>
            </div>
        </div>
    </div>

    <!-- Purchases This Month -->
    <div class="stat-card">
        <div class="flex items-center gap-4">
            <div class="stat-icon bg-amber-50 text-amber-600">
                <i class="bi bi-cart"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Pembelian Bulan Ini</p>
                <p class="text-xl font-bold text-slate-800 mt-0.5">{{ formatRupiah($purchasesThisMonth) }}</p>
            </div>
        </div>
    </div>

    <!-- Total Receivable -->
    <div class="stat-card">
        <div class="flex items-center gap-4">
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <i class="bi bi-arrow-down-circle"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Piutang</p>
                <p class="text-xl font-bold text-slate-800 mt-0.5">{{ formatRupiah($totalReceivable) }}</p>
            </div>
        </div>
    </div>

    <!-- Total Payable -->
    <div class="stat-card">
        <div class="flex items-center gap-4">
            <div class="stat-icon bg-red-50 text-red-500">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Hutang</p>
                <p class="text-xl font-bold text-slate-800 mt-0.5">{{ formatRupiah($totalPayable) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Alerts -->
@if($overduePayables > 0 || $overdueReceivables > 0)
<div class="alert alert-warning mb-6">
    <i class="bi bi-exclamation-triangle-fill text-lg"></i>
    <div>
        @if($overduePayables > 0) <strong>{{ $overduePayables }}</strong> hutang jatuh tempo. @endif
        @if($overdueReceivables > 0) <strong>{{ $overdueReceivables }}</strong> piutang jatuh tempo. @endif
    </div>
</div>
@endif

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <!-- Sales Chart -->
    <div class="card lg:col-span-2">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i class="bi bi-graph-up text-blue-600"></i>
                Penjualan 7 Hari Terakhir
            </span>
            <span class="text-xs text-slate-400 font-normal">
                <i class="bi bi-calendar3 me-1"></i> Minggu ini
            </span>
        </div>
        <div class="card-body">
            <div id="salesChart" style="height:300px;"></div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="card">
        <div class="card-header flex items-center gap-2">
            <i class="bi bi-trophy text-amber-500"></i>
            Top 5 Produk
        </div>
        <div class="card-body">
            <div id="topProductsChart" style="height:300px;"></div>
        </div>
    </div>
</div>

<!-- Recent Data Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i class="bi bi-clock-history text-blue-600"></i>
                Transaksi Penjualan Terbaru
            </span>
            <span class="badge badge-primary">5 Terakhir</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                    <tr>
                        <td><span class="text-primary-600 font-medium text-xs">{{ $sale->invoice_number }}</span></td>
                        <td>{{ $sale->customer?->name ?? $sale->customer_name ?? 'Umum' }}</td>
                        <td class="font-medium">{{ formatRupiah($sale->total) }}</td>
                        <td class="text-slate-500">{{ $sale->sale_date->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-6">
                            <i class="bi bi-inbox text-2xl block mb-2"></i>
                            Belum ada transaksi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i class="bi bi-exclamation-diamond text-red-500"></i>
                Produk Stok Menipis
            </span>
            <span class="badge badge-danger">{{ count($lowStockProducts) }} Produk</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Min</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowStockProducts as $product)
                    <tr>
                        <td class="font-medium">{{ $product->name }}</td>
                        <td class="text-slate-500">{{ $product->category?->name ?? '-' }}</td>
                        <td><span class="badge badge-danger">{{ $product->stock }}</span></td>
                        <td class="text-slate-500">{{ $product->min_stock }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-6">
                            <i class="bi bi-check-circle text-2xl text-emerald-400 block mb-2"></i>
                            Semua stok aman
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upcoming Payments Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
    <!-- Upcoming Payables -->
    <div class="card border-t-4 border-t-red-500">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2 font-bold text-slate-800">
                <i class="bi bi-calendar-x text-red-500"></i>
                Hutang Jatuh Tempo (7 Hari Kedepan)
            </span>
            <span class="badge badge-danger">{{ count($upcomingPayables) }} Tagihan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>Supplier</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Sisa Hutang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingPayables as $payable)
                    <tr>
                        <td>
                            <a href="{{ route('keuangan.payables.pay', $payable) }}" class="text-primary-600 font-medium text-xs hover:underline">
                                {{ $payable->document_number }}
                            </a>
                        </td>
                        <td>{{ $payable->supplier?->name ?? 'Umum' }}</td>
                        <td>
                            <span class="text-xs font-medium px-2 py-1 rounded-md {{ $payable->due_date < now() ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                                {{ $payable->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-right font-bold text-slate-800">{{ formatRupiah($payable->remaining_amount) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-6">
                            <i class="bi bi-emoji-smile text-2xl text-emerald-400 block mb-2"></i>
                            Tidak ada hutang jatuh tempo dalam 7 hari kedepan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upcoming Receivables -->
    <div class="card border-t-4 border-t-emerald-500">
        <div class="card-header flex items-center justify-between">
            <span class="flex items-center gap-2 font-bold text-slate-800">
                <i class="bi bi-calendar-check text-emerald-500"></i>
                Piutang Jatuh Tempo (7 Hari Kedepan)
            </span>
            <span class="badge bg-emerald-100 text-emerald-600">{{ count($upcomingReceivables) }} Tagihan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>Customer</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Sisa Piutang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingReceivables as $receivable)
                    <tr>
                        <td>
                            <a href="{{ route('keuangan.receivables.receive', $receivable) }}" class="text-primary-600 font-medium text-xs hover:underline">
                                {{ $receivable->document_number }}
                            </a>
                        </td>
                        <td>{{ $receivable->customer?->name ?? 'Umum' }}</td>
                        <td>
                            <span class="text-xs font-medium px-2 py-1 rounded-md {{ $receivable->due_date < now() ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                                {{ $receivable->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-right font-bold text-slate-800">{{ formatRupiah($receivable->remaining_amount) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-6">
                            <i class="bi bi-emoji-smile text-2xl text-emerald-400 block mb-2"></i>
                            Tidak ada piutang jatuh tempo dalam 7 hari kedepan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    var salesData = @json($sales7Days->map(fn($s)=> ['x'=>$s->date, 'y'=>(float)$s->total]));
    new ApexCharts(document.querySelector("#salesChart"), {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif' },
        series: [{ name: 'Penjualan', data: salesData.map(s => s.y) }],
        xaxis: { categories: salesData.map(s => s.x), labels: { style: { colors: '#94a3b8' } } },
        colors: ['#3b82f6'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        yaxis: { labels: { formatter: (val) => 'Rp ' + val.toLocaleString('id-ID'), style: { colors: '#94a3b8' } } }
    }).render();

    var topData = @json($topProducts->map(fn($p)=> ['x'=>$p->product->name ?? '-', 'y'=>(float)$p->total_qty]));
    new ApexCharts(document.querySelector("#topProductsChart"), {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif' },
        series: [{ name: 'Terjual', data: topData.map(t => t.y) }],
        xaxis: { categories: topData.map(t => t.x), labels: { style: { colors: '#94a3b8' } } },
        colors: ['#10b981'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        yaxis: { labels: { style: { colors: '#94a3b8' } } }
    }).render();
</script>
@endsection

@extends('layouts.app')
@section('title', 'Riwayat Penjualan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pos.kasir') }}" class="text-slate-500 hover:text-primary-600 transition-colors">POS</a></li>
    <li class="breadcrumb-item active text-slate-800 font-semibold" aria-current="page">Riwayat</li>
@endsection
@section('content')
<div class="max-w-7xl mx-auto pb-10">
    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-clock-history text-primary-600"></i> Riwayat Penjualan
            </h2>
            <p class="text-sm text-slate-500 mt-1">Daftar semua transaksi penjualan yang telah dilakukan melalui Kasir (POS).</p>
        </div>
        <div>
            <a href="{{ route('pos.kasir') }}" class="btn btn-primary bg-gradient-to-r from-primary-600 to-primary-500 rounded-xl px-5 py-2.5 shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 transition-all border-0 flex items-center gap-2 font-bold">
                <i class="bi bi-cart-plus"></i> Transaksi Baru
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <form id="filter-form" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Mulai</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="bi bi-calendar text-slate-400"></i>
                    </div>
                    <input type="date" id="from" name="from" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl pl-10 px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all" value="{{ request('from') }}">
                </div>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Akhir</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="bi bi-calendar-check text-slate-400"></i>
                    </div>
                    <input type="date" id="to" name="to" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl pl-10 px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all" value="{{ request('to') }}">
                </div>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Metode Pembayaran</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="bi bi-wallet2 text-slate-400"></i>
                    </div>
                    <select id="payment_method" name="payment_method_id" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl pl-10 px-4 py-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all appearance-none">
                        <option value="">Semua Metode</option>
                        @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->id }}" {{ request('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <button type="button" id="btn-filter" class="bg-primary-50 text-primary-600 hover:bg-primary-600 hover:text-white rounded-xl px-5 py-2.5 font-bold transition-all shadow-sm border border-primary-100 hover:border-primary-600 flex items-center gap-2">
                    <i class="bi bi-funnel"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-1 h-full bg-primary-500"></div>
        <div class="p-0">
            <div class="overflow-x-auto p-5">
                <table class="table table-striped w-full text-left text-sm text-slate-600" id="sales-history-table">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 rounded-tl-lg">Invoice</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Metode</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center rounded-tr-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Data loaded by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#sales-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('pos.riwayat') }}',
            data: function(d) {
                d.from = $('#from').val();
                d.to = $('#to').val();
                d.payment_method_id = $('#payment_method').val();
            }
        },
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-clipboard"></i>' },
            { extend: 'csv', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-filetype-csv"></i>' },
            { extend: 'excel', className: 'btn btn-success btn-sm', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm', text: '<i class="bi bi-file-earmark-pdf"></i>' },
            { extend: 'print', className: 'btn btn-secondary btn-sm', text: '<i class="bi bi-printer"></i>' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        columnDefs: [
            { orderable: false, targets: [5, 6] }
        ]
    });
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete-sale', function() {
        var btn = $(this);
        var id = btn.data('id');
        var invoice = btn.data('invoice');
        if (confirm('Hapus transaksi ' + invoice + '?\n\nStok akan dikembalikan dan semua data terkait akan dihapus.')) {
            $.ajax({
                url: '{{ url('pos/riwayat') }}/' + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(res) {
                    alert(res.message);
                    table.ajax.reload();
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || 'Gagal menghapus transaksi.';
                    alert(msg);
                }
            });
        }
    });
});

function printReceipt(url) {
    let iframe = document.getElementById('print-iframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'print-iframe';
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
    }
    iframe.onload = function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
    iframe.src = url;
}
</script>
@endpush

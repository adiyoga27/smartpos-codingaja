@extends('layouts.app')
@section('title', 'Riwayat Penjualan')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('pos.kasir') }}">POS</a></li>
    <li class="breadcrumb-item active">Riwayat</li>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Riwayat Penjualan</h4>
    <form id="filter-form" class="flex gap-2">
        <input type="date" id="from" name="from" class="form-input" value="{{ request('from') }}">
        <input type="date" id="to" name="to" class="form-input" value="{{ request('to') }}">
        <select id="payment_method" name="payment_method_id" class="form-select">
            <option value="">Semua</option>
            @foreach($paymentMethods as $pm)
            <option value="{{ $pm->id }}" {{ request('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
            @endforeach
        </select>
        <button type="button" id="btn-filter" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    </form>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-striped mb-0" id="sales-history-table" style="width:100%">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Tanggal</th><th>Metode</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div><small class="text-slate-400">Invoice</small><div class="font-medium" id="detailInvoice">-</div></div>
                    <div><small class="text-slate-400">Customer</small><div class="font-medium" id="detailCustomer">-</div></div>
                    <div><small class="text-slate-400">Tanggal</small><div class="font-medium" id="detailDate">-</div></div>
                    <div><small class="text-slate-400">Metode</small><div class="font-medium" id="detailMethod">-</div></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                    <div><small class="text-slate-400">Subtotal</small><div class="font-medium" id="detailSubtotal">-</div></div>
                    <div><small class="text-slate-400">Diskon</small><div class="font-medium text-red-500" id="detailDiscount">-</div></div>
                    <div><small class="text-slate-400">Pajak</small><div class="font-medium" id="detailTax">-</div></div>
                    <div><small class="text-slate-400">Total</small><div class="font-bold text-primary-600" id="detailTotal">-</div></div>
                    <div><small class="text-slate-400">Status</small><div id="detailStatus">-</div></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div><small class="text-slate-400">Dibayar</small><div class="font-medium text-emerald-600" id="detailPaid">-</div></div>
                    <div><small class="text-slate-400">Catatan</small><div class="font-medium" id="detailNotes">-</div></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Produk</th><th class="text-center">Qty</th><th>Harga</th><th>Diskon</th><th class="text-right">Total</th></tr></thead>
                        <tbody id="detailItems"></tbody>
                    </table>
                </div>
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

    // Detail modal
    $('#sales-history-table').on('click', '.btn-detail', function() {
        let id = $(this).data('id');
        $.get('{{ route('pos.riwayat') }}/' + id, function(res) {
            let p = res.data;
            $('#detailInvoice').text(p.invoice);
            $('#detailCustomer').text(p.customer);
            $('#detailDate').text(p.date);
            $('#detailMethod').text(p.method);
            $('#detailStatus').html(p.status_badge);
            $('#detailSubtotal').text(p.subtotal);
            $('#detailDiscount').text(p.discount);
            $('#detailTax').text(p.tax);
            $('#detailTotal').text(p.total);
            $('#detailPaid').text(p.paid);
            $('#detailNotes').text(p.notes || '-');
            let itemsHtml = '';
            p.items.forEach(function(i) {
                itemsHtml += '<tr><td>'+i.name+'</td><td class="text-center">'+i.qty+'</td><td>'+i.price+'</td><td>'+i.disc+'</td><td class="text-right">'+i.total+'</td></tr>';
            });
            $('#detailItems').html(itemsHtml);
            $('#detailModal').modal('show');
        });
    });
});
</script>
@endpush

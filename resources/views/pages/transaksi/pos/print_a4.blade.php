<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; padding: 30px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #2563eb; }
        .header .company { font-size: 12px; color: #666; }
        .header .invoice { text-align: right; font-size: 12px; }
        .header .invoice h2 { font-size: 18px; color: #2563eb; }
        .info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
        .info div { line-height: 1.8; }
        .info strong { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px 8px; font-size: 11px; text-transform: uppercase; text-align: left; }
        tbody td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 300px; margin-left: auto; font-size: 13px; }
        .totals .row { display: flex; justify-content: space-between; padding: 5px 0; }
        .totals .row.total { border-top: 2px solid #1a1a1a; padding-top: 8px; margin-top: 5px; font-size: 16px; font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
        .note { font-size: 12px; color: #888; margin-top: 10px; }
        @media print {
            body { padding: 10px; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <h1>{{ $company->name ?? config('app.name') }}</h1>
            <p>{{ $company->address ?? '' }}</p>
            <p>{{ $company->phone ?? '' }}</p>
        </div>
        <div class="invoice">
            <h2>INVOICE</h2>
            <p>{{ $sale->invoice_number }}</p>
        </div>
    </div>

    <div class="info">
        <div>
            <strong>Customer:</strong> {{ $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in' }}<br>
            <strong>Tanggal:</strong> {{ $sale->sale_date->isoFormat('dddd, D MMMM Y') }}<br>
            <strong>Metode:</strong> {{ $sale->paymentMethod?->name ?? '-' }}
        </div>
        <div>
            <strong>Status:</strong> {{ $sale->status === 'paid' ? 'LUNAS' : strtoupper($sale->status) }}<br>
            @if($sale->created_by)<strong>Kasir:</strong> {{ $sale->creator?->name }}<br>@endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product?->name ?? '-' }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->discount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal</span><span>{{ formatRupiah($sale->subtotal) }}</span></div>
        @if($sale->item_discount > 0)
        <div class="row"><span>Diskon Item</span><span>-{{ formatRupiah($sale->item_discount) }}</span></div>
        @endif
        @if($sale->tax_amount > 0)
        <div class="row"><span>Pajak</span><span>{{ formatRupiah($sale->tax_amount) }}</span></div>
        @endif
        <div class="row total"><span>TOTAL</span><span>{{ formatRupiah($sale->total) }}</span></div>
        @if($sale->paid_amount > 0)
        <div class="row"><span>Bayar</span><span>{{ formatRupiah($sale->paid_amount) }}</span></div>
        <div class="row"><span>Kembalian</span><span>{{ formatRupiah($sale->change_amount) }}</span></div>
        @endif
    </div>

    @if($sale->notes)
    <div class="note"><strong>Catatan:</strong> {{ $sale->notes }}</div>
    @endif

    <div class="footer">
        <p>Terima kasih telah berbelanja &mdash; {{ $company->name ?? config('app.name') }}</p>
        <button onclick="window.print()" style="margin-top:10px;padding:8px 20px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Cetak</button>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $sale->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 8px; width: 9.1cm; font-size: 11px; color: #000; }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .line-double { border-top: 2px solid #000; margin: 6px 0; }
        h2 { font-size: 13px; margin: 4px 0; }
        p { margin: 2px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; font-size: 10px; vertical-align: top; }
        .item-name { font-size: 10px; font-weight: bold; }
        .item-detail { font-size: 10px; }
        .total-row td { font-weight: bold; font-size: 12px; padding-top: 4px; }
        .btn-print { margin-top: 12px; padding: 6px 16px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px; display: block; width: 100%; }
        @media print {
            body { width: 9.1cm; }
            .btn-print { display: none; }
            @page { size: 9.1cm 11cm; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="center">
        <h2>{{ $company->name ?? config('app.name', 'SmartPOS') }}</h2>
        <p>{{ $company->address ?? '' }}</p>
        <p>{{ $company->phone ?? '' }}</p>
    </div>
    <div class="line"></div>
    <table>
        <tr><td><strong>{{ $sale->invoice_number }}</strong></td><td class="right">{{ $sale->sale_date->format('d/m/Y H:i') }}</td></tr>
        <tr><td colspan="2">Customer: {{ $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in' }}</td></tr>
        <tr><td colspan="2">Pembayaran: {{ $sale->paymentMethod?->name ?? '-' }}</td></tr>
    </table>
    <div class="line"></div>
    <table>
        @foreach($sale->items as $item)
        <tr>
            <td class="item-name" colspan="2">{{ $item->product?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="item-detail">{{ number_format($item->quantity, 0) }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="right item-detail">{{ number_format($item->total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr class="total-row"><td>TOTAL</td><td class="right">{{ formatRupiah($sale->total) }}</td></tr>
        @if($sale->paid_amount > 0)
        <tr><td>BAYAR</td><td class="right">{{ formatRupiah($sale->paid_amount) }}</td></tr>
        <tr><td>KEMBALI</td><td class="right">{{ formatRupiah($sale->change_amount) }}</td></tr>
        @endif
    </table>
    <div class="line-double"></div>
    <p class="center"><strong>=== TERIMA KASIH ===</strong></p>
    @if($sale->notes)
    <p class="center" style="font-size:9px;">{{ $sale->notes }}</p>
    @endif
    <p class="center" style="font-size:9px;">{{ now()->format('d/m/Y H:i:s') }}</p>

    <button class="btn-print" onclick="window.print()">Cetak</button>
</body>
</html>

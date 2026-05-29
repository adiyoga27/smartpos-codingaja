<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $sale->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; padding: 5px; width: 80mm; font-size: 11px; color: #000; }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .line-double { border-top: 2px solid #000; margin: 5px 0; }
        h2 { font-size: 14px; margin: 5px 0; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; font-size: 11px; vertical-align: top; }
        .item-name { font-size: 11px; }
        .item-qty { width: 25px; text-align: center; }
        .item-price { width: 60px; text-align: right; }
        .total-row td { font-weight: bold; font-size: 13px; padding-top: 5px; }
        .btn-print { margin-top: 15px; padding: 8px 20px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; display: block; width: 100%; }
        @media print {
            body { width: 80mm; }
            .btn-print { display: none; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="center">
        <h2>{{ $company->name ?? config('app.name') }}</h2>
        <p>{{ $company->address ?? '' }}</p>
        <p>{{ $company->phone ?? '' }}</p>
    </div>
    <div class="line"></div>
    <table>
        <tr><td>{{ $sale->invoice_number }}</td><td class="right">{{ $sale->sale_date->format('d/m/Y H:i') }}</td></tr>
        <tr><td colspan="2">{{ $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in' }}</td></tr>
        <tr><td colspan="2">{{ $sale->paymentMethod?->name ?? '-' }}</td></tr>
    </table>
    <div class="line"></div>
    <table>
        @foreach($sale->items as $item)
        <tr>
            <td class="item-name">{{ $item->product?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>{{ number_format($item->quantity, 0) }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($item->total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td>TOTAL</td><td class="right" style="font-size:14px;font-weight:bold;">{{ formatRupiah($sale->total) }}</td></tr>
        @if($sale->paid_amount > 0)
        <tr><td>BAYAR</td><td class="right">{{ formatRupiah($sale->paid_amount) }}</td></tr>
        <tr><td>KEMBALI</td><td class="right">{{ formatRupiah($sale->change_amount) }}</td></tr>
        @endif
    </table>
    <div class="line-double"></div>
    <p class="center">=== TERIMA KASIH ===</p>
    @if($sale->notes)
    <p class="center" style="font-size:10px;">{{ $sale->notes }}</p>
    @endif
    <p class="center" style="font-size:10px;">{{ now()->format('d/m/Y H:i:s') }}</p>

    <button class="btn-print" onclick="window.print()">Cetak</button>
</body>
</html>

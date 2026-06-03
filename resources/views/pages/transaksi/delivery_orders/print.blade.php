<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>DO {{ $deliveryOrder->document_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; padding: 30px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #059669; }
        .header .company { font-size: 12px; color: #666; }
        .header .doc { text-align: right; font-size: 12px; }
        .header .doc h2 { font-size: 18px; color: #059669; }
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
        <div class="doc">
            <h2>DELIVERY ORDER</h2>
            <p>{{ $deliveryOrder->document_number }}</p>
        </div>
    </div>

    <div class="info">
        <div>
            <strong>Customer:</strong> {{ $deliveryOrder->customer?->name ?? '-' }}<br>
            <strong>Tanggal Kirim:</strong> {{ $deliveryOrder->delivery_date->isoFormat('dddd, D MMMM Y') }}<br>
            <strong>No. SO:</strong> {{ $deliveryOrder->salesOrder?->document_number ?? '-' }}<br>
        </div>
        <div>
            @if($deliveryOrder->creator)
            <strong>Dibuat Oleh:</strong> {{ $deliveryOrder->creator?->name }}<br>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th class="text-center">Qty Dikirim</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryOrder->items as $i => $item)
            @php $soItem = $item->salesOrderItem;
                $disc = $soItem ? ($soItem->discount / max(1, $soItem->quantity)) * $item->quantity : 0;
                $sub = $soItem ? ($item->quantity * $soItem->unit_price) - $disc : 0;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $soItem?->product?->name ?? $item->product?->name ?? '-' }}</td>
                <td class="text-center">{{ formatQty($item->quantity) }}</td>
                <td class="text-right">{{ $soItem ? number_format($soItem->unit_price, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($disc, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format(max(0, $sub), 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row total"><span>TOTAL</span><span>{{ formatRupiah($deliveryOrder->total) }}</span></div>
    </div>

    @if($deliveryOrder->notes)
    <div class="note"><strong>Catatan:</strong> {{ $deliveryOrder->notes }}</div>
    @endif

    <div class="footer">
        <p>{{ $company->name ?? config('app.name') }}</p>
        <button onclick="window.print()" style="margin-top:10px;padding:8px 20px;background:#059669;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Cetak</button>
    </div>
</body>
</html>

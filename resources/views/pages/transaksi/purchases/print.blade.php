<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PO {{ $purchase->document_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; padding: 30px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #d97706; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #d97706; }
        .header .company { font-size: 12px; color: #666; }
        .header .doc { text-align: right; font-size: 12px; }
        .header .doc h2 { font-size: 18px; color: #d97706; }
        .info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
        .info div { line-height: 1.8; }
        .info strong { color: #555; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .status.draft { background: #e2e8f0; color: #475569; }
        .status.partial { background: #fef3c7; color: #92400e; }
        .status.completed { background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px 8px; font-size: 11px; text-transform: uppercase; text-align: left; }
        tbody td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 320px; margin-left: auto; font-size: 13px; }
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
            <h2>PURCHASE ORDER</h2>
            <p>{{ $purchase->document_number }}</p>
        </div>
    </div>

    <div class="info">
        <div>
            <strong>Supplier:</strong> {{ $purchase->supplier?->name ?? '-' }}<br>
            <strong>Tanggal:</strong> {{ $purchase->purchase_date->isoFormat('dddd, D MMMM Y') }}<br>
            @if($purchase->due_date)
            <strong>Jatuh Tempo:</strong> {{ $purchase->due_date->isoFormat('D MMMM Y') }}<br>
            @endif
        </div>
        <div>
            <strong>Status:</strong>
            <span class="status {{ $purchase->status }}">{{ strtoupper($purchase->status) }}</span><br>
            @if($purchase->creator)
            <strong>Dibuat Oleh:</strong> {{ $purchase->creator?->name }}<br>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Diterima</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product?->name ?? '-' }}</td>
                <td class="text-center">{{ formatQty($item->quantity) }}</td>
                <td class="text-center">{{ formatQty($item->received_quantity) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->discount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal</span><span>{{ formatRupiah($purchase->subtotal) }}</span></div>
        @if($purchase->discount > 0)
        <div class="row"><span>Diskon</span><span>-{{ formatRupiah($purchase->discount) }}</span></div>
        @endif
        @if($purchase->tax > 0)
        <div class="row"><span>PPN</span><span>{{ formatRupiah($purchase->tax) }}</span></div>
        @endif
        <div class="row total"><span>TOTAL</span><span>{{ formatRupiah($purchase->total) }}</span></div>
    </div>

    @if($purchase->notes)
    <div class="note"><strong>Catatan:</strong> {{ $purchase->notes }}</div>
    @endif

    <div class="footer">
        <p>{{ $company->name ?? config('app.name') }}</p>
        <button onclick="window.print()" style="margin-top:10px;padding:8px 20px;background:#d97706;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Cetak</button>
    </div>
</body>
</html>

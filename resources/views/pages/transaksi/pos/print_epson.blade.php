<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur {{ $sale->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            padding: 20px; 
            width: 9.5in; 
            font-size: 14px; 
            color: #000; 
            background: #fff;
            margin: auto;
        }
        .container {
            width: 100%;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .header-left, .header-right {
            width: 50%;
        }
        .header-right {
            text-align: right;
            padding-right: 20px;
        }
        .title {
            font-size: 16px;
            margin-bottom: 10px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px;
            table-layout: fixed;
        }
        thead {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        tbody {
            border-bottom: 1px dashed #000;
        }
        th, td { 
            padding: 4px 0; 
            vertical-align: top;
            font-weight: normal;
        }
        th {
            text-align: left;
        }
        .col-no { width: 5%; }
        .col-name { width: 40%; }
        .col-qty { width: 12%; }
        .col-price { width: 15%; text-align: right; }
        .col-disc { width: 10%; text-align: right; }
        .col-total { width: 18%; text-align: right; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 10px;
        }
        .terbilang-box {
            width: 70%;
        }
        .terbilang-text {
            margin-bottom: 15px;
        }
        .total-box {
            width: 30%;
            text-align: right;
        }
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }
        .signature-box {
            width: 33%;
            text-align: center;
        }
        .btn-print { 
            margin-top: 30px; 
            padding: 8px 16px; 
            background: #2563eb; 
            color: #fff; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px; 
            display: block; 
        }
        @media print {
            body { 
                width: 100%; 
                padding: 0;
            }
            .btn-print { display: none; }
            @page { 
                size: 9.5in 11in; 
                margin: 0.5in; 
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div>{{ $company->name ?? 'HA()' }}</div>
                <div>{{ $company->address ?? 'Jl. Tumpang Sari Cakranegara' }}</div>
                <br>
                <div>Tanggal : {{ $sale->sale_date->format('d-m-Y') }}</div>
                <div>Metode  : {{ $sale->paymentMethod?->name ?? '-' }}</div>
                @if($sale->paymentMethod?->is_credit && $sale->due_date)
                <div>Tgl Mulai : {{ $sale->sale_date->format('d-m-Y') }}</div>
                <div>Tgl Tempo : {{ \Carbon\Carbon::parse($sale->due_date)->format('d-m-Y') }}</div>
                @endif
                <div>Kasir   : {{ $sale->creator->name ?? 'KASIR 1' }}</div>
                <div>No.     : {{ $sale->invoice_number }}</div>
            </div>
            <div class="header-right">
                <div class="title">FAKTUR {{ strtoupper($sale->paymentMethod?->is_credit ? 'PENJUALAN KREDIT' : 'PENJUALAN') }}</div>
                <br>
                <div>Kepada Yth.</div>
                <div>{{ strtoupper($sale->customer?->name ?? $sale->customer_name ?? 'UMUM') }}</div>
                @if($sale->customer?->city)
                <div>{{ strtoupper($sale->customer->city) }}</div>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-name"><span style="float:left">:</span> Kode/Nama Produk</th>
                    <th class="col-qty"><span style="float:left">:</span> QTY</th>
                    <th class="col-price text-right"><span style="float:left">:</span> HARGA</th>
                    <th class="col-disc text-right"><span style="float:left">:</span> DISKON</th>
                    <th class="col-total text-right"><span style="float:left">:</span> JUMLAH</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><span style="float:left">:</span> {{ strtoupper($item->product?->name ?? '-') }}</td>
                    <td><span style="float:left">:</span> {{ formatQty($item->quantity) }} PCS</td>
                    <td class="text-right"><span style="float:left">:</span> {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right"><span style="float:left">:</span> {{ $item->discount > 0 ? number_format($item->discount, 0, ',', '.') : '' }}</td>
                    <td class="text-right"><span style="float:left">:</span> {{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-container">
            <div class="terbilang-box">
                <div class="terbilang-text">{{ trim(terbilang($sale->total)) }} rupiah</div>
                <div>Terima kasih</div>
                <div>PEMBAYARAN NOTA HARAP DISELESAIKAN PER NOTA SAJA</div>
                <div class="signature-area">
                    <div class="signature-box">Penerima,</div>
                    <div class="signature-box">Gudang,</div>
                    <div class="signature-box">Hormat Kami,</div>
                </div>
            </div>
            <div class="total-box">
                Jumlah Rp. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ number_format($sale->total, 0, ',', '.') }}
            </div>
        </div>

        <button class="btn-print" onclick="window.print()">Cetak</button>
    </div>
</body>
</html>

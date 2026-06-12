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
            font-size: 14px; 
            color: #000; 
            background: #fff;
        }
        pre {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px; /* Adjust size if needed so it doesn't wrap on browser view */
            line-height: 1.2;
            white-space: pre;
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
            display: inline-block; 
        }
        @media print {
            body { 
                padding: 0;
            }
            .btn-print { display: none; }
            @page { 
                margin: 0; 
            }
        }
    </style>
</head>
<body>
<?php
    // --- HELPER FUNCTIONS UNTUK FORMATTING TEKS ---
    function padRight($string, $length) {
        return str_pad(substr((string)$string, 0, $length), $length, " ", STR_PAD_RIGHT);
    }
    
    function padLeft($string, $length) {
        return str_pad(substr((string)$string, 0, $length), $length, " ", STR_PAD_LEFT);
    }

    function padCenter($string, $length) {
        return str_pad(substr((string)$string, 0, $length), $length, " ", STR_PAD_BOTH);
    }

    // --- LEBAR KERTAS (Karakter) ---
    // Epson LX-310 kertas letter/A4 continous form biasanya 80 karakter (10 cpi)
    $w = 80; 

    // --- MENGUMPULKAN DATA ---
    $companyName = $company->name ?? 'HA()';
    $companyAddr = $company->address ?? 'Jl. Tumpang Sari Cakranegara';
    $fakturTitle = 'FAKTUR ' . strtoupper($sale->paymentMethod?->is_credit ? 'PENJUALAN KREDIT' : 'PENJUALAN');
    
    $customerName = strtoupper($sale->customer?->name ?? $sale->customer_name ?? 'UMUM');
    $customerCity = $sale->customer?->city ? strtoupper($sale->customer->city) : "";
    $methodName   = $sale->paymentMethod?->name ?? '-';
    $cashierName  = $sale->creator->name ?? 'KASIR 1';
    
    $tglMulai = $sale->paymentMethod?->is_credit && $sale->due_date ? "Tgl Mulai : " . $sale->sale_date->format('d-m-Y') : "";
    $tglTempo = $sale->paymentMethod?->is_credit && $sale->due_date ? "Tgl Tempo : " . $sale->due_date->format('d-m-Y') : "";

    // --- HEADER ---
    $output  = "";
    $output .= padRight($companyName, 45) . padRight($fakturTitle, 35) . "\n";
    $output .= padRight($companyAddr, 45) . padRight("", 35) . "\n";
    $output .= padRight("", 45) . padRight("Kepada Yth.", 35) . "\n";
    
    $output .= padRight("Tanggal : " . $sale->sale_date->format('d-m-Y'), 45) . padRight($customerName, 35) . "\n";
    $output .= padRight("Metode  : " . $methodName, 45) . padRight($customerCity, 35) . "\n";
    
    $output .= padRight("Kasir   : " . $cashierName, 45) . padRight($tglMulai, 35) . "\n";
    $output .= padRight("No.     : " . $sale->invoice_number, 45) . padRight($tglTempo, 35) . "\n";
    
    $output .= "\n";
    
    // --- TABEL BARANG ---
    // Kolom: No(4) Nama(32) QTY(10) HARGA(10) DISKON(10) JUMLAH(14)
    $separator = str_repeat("-", $w) . "\n";
    $output .= $separator;
    $output .= padRight("No.", 4) . padRight("Kode/Nama Produk", 31) . padRight("QTY", 11) . padLeft("HARGA", 10) . padLeft("DISKON", 10) . padLeft("JUMLAH", 14) . "\n";
    $output .= $separator;

    foreach($sale->items as $index => $item) {
        $no = $index + 1;
        $nama = strtoupper($item->product?->name ?? '-');
        
        // Memotong nama jika lebih dari 30 karakter agar tidak merusak spasi
        if(strlen($nama) > 30) {
            $nama = substr($nama, 0, 30); 
        }
        
        $qty = formatQty($item->quantity) . " PCS";
        $harga = number_format($item->unit_price, 0, ',', '.');
        $diskon = $item->discount > 0 ? number_format($item->discount, 0, ',', '.') : '';
        $jumlah = number_format($item->total, 0, ',', '.');

        $output .= padRight($no, 4) . padRight($nama, 31) . padRight($qty, 11) . padLeft($harga, 10) . padLeft($diskon, 10) . padLeft($jumlah, 14) . "\n";
    }
    
    $output .= $separator;

    // --- FOOTER & TOTAL ---
    $terbilangText = trim(terbilang($sale->total)) . " rupiah";
    $totalText = number_format($sale->total, 0, ',', '.');
    
    // Jika terbilang sangat panjang, potong jadi 2 baris (opsional, tapi diasumsikan muat 50 karakter)
    $output .= padRight($terbilangText, 55) . padLeft("Jumlah Rp. " . padLeft($totalText, 14), 25) . "\n";
    $output .= "\n";
    
    $output .= "Terima kasih\n";
    $output .= "PEMBAYARAN NOTA HARAP DISELESAIKAN PER NOTA SAJA\n";
    $output .= "\n";
    
    $output .= padCenter("Penerima,", 26) . padCenter("Gudang,", 26) . padCenter("Hormat Kami,", 28) . "\n";
    $output .= "\n\n\n";
    $output .= padCenter("(............)", 26) . padCenter("(............)", 26) . padCenter("(............)", 28) . "\n";
?>
    <div>
        @if(!isset($isPdf) && !request()->has('preview'))
        <button class="btn-print no-print" onclick="window.print()">Cetak (Raw)</button>
        <br><br>
        @endif
        
        <pre>{{ $output }}</pre>
    </div>

    <script>
        // Opsional: otomatis print jika tidak dalam mode preview
        @if(!isset($isPdf) && !request()->has('preview'))
            // window.onload = function() { window.print(); }
        @endif
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panduan Penggunaan SmartPOS</title>
    <style>
        @media print { body { margin: 0 15mm; } .no-print { display: none; } }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #1e293b; line-height: 1.7; max-width: 900px; margin: 20px auto; padding: 0 20px; }
        h1 { font-size: 28px; text-align: center; margin: 30px 0 5px; color: #1e40af; }
        .subtitle { text-align: center; color: #64748b; margin-bottom: 35px; font-size: 14px; }
        h2 { font-size: 20px; margin: 35px 0 12px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; color: #1e40af; }
        h3 { font-size: 16px; margin: 20px 0 6px; color: #334155; }
        p, li { font-size: 14px; color: #475569; }
        ul, ol { margin: 6px 0 12px 24px; }
        li { margin: 3px 0; }
        blockquote { background: #f0fdf4; border-left: 4px solid #22c55e; margin: 12px 0; padding: 10px 16px; border-radius: 0 6px 6px 0; font-size: 13px; color: #166534; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; font-size: 13px; }
        th { background: #f1f5f9; text-align: left; padding: 8px 12px; border: 1px solid #e2e8f0; font-weight: 600; }
        td { padding: 8px 12px; border: 1px solid #e2e8f0; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        strong { color: #0f172a; }
        .no-print { text-align: center; margin: 25px 0; }
        .no-print button { background: #1e40af; color: #fff; border: none; padding: 10px 30px; border-radius: 8px; font-size: 15px; cursor: pointer; }
        .no-print button:hover { background: #1e3a8a; }
        .footer { text-align: center; margin-top: 40px; padding-top: 15px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 13px; }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Cetak / Save as PDF</button></div>

    <h1>Panduan Penggunaan SmartPOS</h1>
    <p class="subtitle">Aplikasi Point of Sale &amp; Manajemen Bisnis Terintegrasi &mdash; v1.0.0</p>

    <h2>1. Dashboard</h2>
    <p>Halaman utama menampilkan ringkasan bisnis Anda: statistik penjualan hari ini, produk dengan stok menipis, dan transaksi terbaru.</p>

    <h2>2. Data Master</h2>
    <p>Menu untuk mengelola data dasar aplikasi.</p>

    <h3>Kategori</h3>
    <p>Mengelompokkan produk. Contoh: Obat, Vitamin, Pakan. Klik <strong>Data Master &gt; Kategori &gt; Tambah</strong> untuk membuat kategori baru.</p>

    <h3>Produk</h3>
    <p>Data seluruh produk yang dijual.</p>
    <ul>
        <li><strong>Kode</strong>: kode unik produk (otomatis, bisa diedit)</li>
        <li><strong>Nama</strong>: nama produk</li>
        <li><strong>Harga Beli</strong>: harga dari supplier</li>
        <li><strong>Harga Toko</strong>: harga jual eceran</li>
        <li><strong>Harga Reseller</strong>: harga jual grosir/reseller</li>
        <li><strong>Stok</strong>: jumlah barang tersedia</li>
        <li><strong>Min Stok</strong>: batas minimal stok (muncul peringatan jika di bawah ini)</li>
    </ul>

    <h3>Supplier</h3>
    <p>Data pemasok barang. Digunakan saat membuat Purchase Order.</p>

    <h3>Customer</h3>
    <p>Data pelanggan. Digunakan saat transaksi POS.</p>

    <h3>Akun Biaya (COA)</h3>
    <p>Chart of Accounts &mdash; daftar akun akuntansi untuk jurnal.</p>

    <h3>Metode Pembayaran</h3>
    <p>Jenis pembayaran yang tersedia di POS: Tunai, Transfer, QRIS, dll. Centang <strong>Kredit</strong> jika metode ini bersifat hutang.</p>

    <h3>Pajak</h3>
    <p>Daftar tarif pajak yang bisa diterapkan di transaksi.</p>

    <h2>3. Pembelian</h2>

    <h3>Purchase Order (PO)</h3>
    <p>Membuat pesanan ke supplier yang bisa diterima bertahap.</p>
    <p><strong>Membuat PO:</strong></p>
    <ol>
        <li>Klik <strong>Pembelian &gt; Purchase Order &gt; Buat PO</strong></li>
        <li>Pilih <strong>Supplier</strong></li>
        <li>Pilih status: <strong>Hutang</strong> (isi jatuh tempo) atau <strong>Lunas/Tunai</strong> (isi metode pembayaran)</li>
        <li>Klik <strong>Tambah Produk</strong> untuk memilih barang</li>
        <li>Isi <strong>Qty</strong> dan <strong>Harga Beli</strong> untuk setiap produk</li>
        <li>Klik <strong>Simpan PO</strong></li>
    </ol>
    <p><strong>Menerima Barang:</strong></p>
    <ol>
        <li>Buka detail PO &gt; klik <strong>Terima</strong></li>
        <li>Isi <strong>Qty Terima</strong> untuk barang yang datang</li>
        <li>Klik <strong>Simpan Penerimaan</strong></li>
    </ol>
    <p><strong>Membayar PO:</strong></p>
    <ol>
        <li>Buka detail PO &gt; klik <strong>Bayar</strong></li>
        <li>Tambahkan metode pembayaran (bisa &gt;1: kas, transfer, dll)</li>
        <li>Isi jumlah pembayaran</li>
        <li>Klik <strong>Bayar</strong></li>
    </ol>
    <blockquote>Anda bisa menerima barang dan membayar secara bertahap (cicil). History penerimaan dan pembayaran tercatat lengkap.</blockquote>

    <h3>Pembelian Langsung</h3>
    <p>Pembelian instan tanpa PO &mdash; stok langsung masuk dan pembayaran langsung.</p>
    <ol>
        <li>Klik <strong>Pembelian &gt; Pembelian Langsung &gt; Buat Pembelian Langsung</strong></li>
        <li>Pilih <strong>Supplier</strong></li>
        <li>Tambahkan produk, isi qty &amp; harga beli</li>
        <li>Tambahkan <strong>metode pembayaran</strong> (wajib, bisa multiple)</li>
        <li>Klik <strong>Simpan Pembelian</strong></li>
    </ol>

    <h2>4. Penjualan</h2>

    <h3>POS Kasir</h3>
    <p>Halaman kasir untuk transaksi langsung.</p>
    <p><strong>Mode Toko vs Reseller:</strong></p>
    <ul>
        <li><strong>Toko</strong>: harga jual retail (Harga Toko)</li>
        <li><strong>Reseller</strong>: harga jual grosir (Harga Reseller)</li>
    </ul>
    <p><strong>Melakukan Transaksi:</strong></p>
    <ol>
        <li>Pilih mode (Toko/Reseller)</li>
        <li>Klik produk di grid kiri untuk menambah ke keranjang</li>
        <li>Pilih <strong>Customer</strong> (opsional, default: Walk-in/Umum)</li>
        <li>Edit qty, harga, diskon per item jika perlu</li>
        <li>Pilih <strong>Metode Pembayaran</strong></li>
        <li>Isi <strong>Jumlah Bayar</strong></li>
        <li>Klik <strong>Bayar</strong></li>
        <li>Struk otomatis tercetak (sesuai printer dipilih)</li>
    </ol>
    <p><strong>Cetak Ulang:</strong> Pilih tipe printer (A4/Thermal/Epson) sebelum bayar, atau cetak dari riwayat.</p>

    <h3>Riwayat Penjualan</h3>
    <p>Melihat dan mencari transaksi yang sudah dilakukan. Bisa cetak ulang struk.</p>

    <h3>Retur Penjualan</h3>
    <p>Mengembalikan barang dari customer. Stok otomatis kembali.</p>

    <h2>5. Hutang &amp; Piutang</h2>

    <h3>Bayar Hutang</h3>
    <p>Daftar hutang ke supplier dari PO. Klik <strong>Bayar</strong> untuk mencatat pembayaran.</p>

    <h3>Terima Piutang</h3>
    <p>Daftar piutang dari customer (pembayaran kredit). Klik <strong>Terima</strong> untuk mencatat pelunasan.</p>
    <blockquote>Hutang &amp; Piutang otomatis terbuat dari transaksi Pembelian dan POS.</blockquote>

    <h2>6. Alur Kas &amp; Bank</h2>

    <h3>Akun Kas/Bank</h3>
    <p>Mengelola akun kas (tunai) dan rekening bank.</p>
    <p><strong>Kelola Akun:</strong></p>
    <ol>
        <li>Klik ikon <strong>gear</strong> pada akun</li>
        <li>Lihat riwayat transaksi akun tersebut</li>
        <li><strong>Top-Up</strong>: menambah saldo (contoh: setor tunai)</li>
        <li><strong>Tarik</strong>: mengurangi saldo (contoh: biaya operasional)</li>
    </ol>

    <h3>Transaksi Kas</h3>
    <p>Riwayat semua transaksi kas: masuk, keluar, transfer antar akun.</p>

    <h2>7. Akuntansi</h2>

    <h3>Jurnal Umum</h3>
    <p>Semua jurnal otomatis dari transaksi (penjualan, pembelian, pembayaran).</p>

    <h3>Buku Besar</h3>
    <p>Laporan per akun &mdash; melihat semua debit/kredit pada akun tertentu.</p>

    <h3>Neraca</h3>
    <p>Laporan posisi keuangan: Aset = Liabilitas + Ekuitas.</p>

    <h3>Laba Rugi</h3>
    <p>Laporan pendapatan dan beban dalam periode tertentu.</p>

    <h2>8. Stok Kontrol</h2>

    <h3>Kartu Stok</h3>
    <p>Menampilkan stok seluruh produk beserta total barang masuk &amp; keluar.</p>
    <ul>
        <li>Centang <strong>Tampilkan stok dibawah minimal</strong> untuk filter produk menipis</li>
        <li>Klik <strong>Detail</strong> untuk melihat history mutasi per produk</li>
    </ul>

    <h3>Stock Opname</h3>
    <p>Menyesuaikan stok fisik dengan stok sistem:</p>
    <ol>
        <li>Klik <strong>Stock Opname</strong></li>
        <li>Pilih produk yang akan dihitung</li>
        <li>Isi <strong>Qty Fisik</strong> (hasil hitungan)</li>
        <li>Sistem otomatis mencatat selisih</li>
    </ol>

    <h2>9. Laporan</h2>
    <ul>
        <li><strong>Stok</strong>: laporan stok semua produk (bisa export Excel/PDF)</li>
        <li><strong>Pembelian</strong>: laporan transaksi pembelian</li>
        <li><strong>Penjualan</strong>: laporan transaksi penjualan</li>
        <li><strong>Hutang</strong>: laporan hutang ke supplier</li>
        <li><strong>Piutang</strong>: laporan piutang dari customer</li>
        <li><strong>Arus Kas</strong>: ringkasan kas masuk, kas keluar, dan saldo bersih</li>
    </ul>

    <h2>10. Pengaturan</h2>
    <ul>
        <li><strong>Profil Perusahaan</strong>: nama, logo, alamat, prefix dokumen (INV, PO)</li>
        <li><strong>Manajemen User</strong>: tambah/edit/hapus pengguna</li>
        <li><strong>Role &amp; Permission</strong>: atur hak akses per role</li>
    </ul>

    <h2>Tips &amp; Shortcut</h2>
    <table>
        <tr><th>Fitur</th><th>Cara Cepat</th></tr>
        <tr><td>Cari produk di POS</td><td>Ketik nama/kode/barcode di search bar</td></tr>
        <tr><td>Tambah customer baru</td><td>Klik <strong>+</strong> di samping dropdown customer</td></tr>
        <tr><td>Cetak ulang struk</td><td>Buka riwayat penjualan &gt; klik ikon printer</td></tr>
        <tr><td>Export data</td><td>Semua tabel punya tombol Excel/PDF/Print</td></tr>
        <tr><td>Filter stok menipis</td><td>Centang checkbox di halaman Kartu Stok</td></tr>
        <tr><td>Pembayaran cicil</td><td>Bisa bayar PO berkali-kali sampai lunas</td></tr>
    </table>

    <p class="footer">&copy; 2026 SmartPOS &mdash; Panduan Penggunaan v1.0.0</p>

    <div class="no-print"><button onclick="window.print()" style="margin-bottom:30px">Cetak / Save as PDF</button></div>
</body>
</html>

# Panduan Penggunaan SmartPOS

Aplikasi Point of Sale & Manajemen Bisnis Terintegrasi

---

## Daftar Isi

1. [Dashboard](#1-dashboard)
2. [Data Master](#2-data-master)
3. [Pembelian](#3-pembelian)
4. [Penjualan](#4-penjualan)
5. [Hutang & Piutang](#5-hutang--piutang)
6. [Alur Kas & Bank](#6-alur-kas--bank)
7. [Akuntansi](#7-akuntansi)
8. [Stok Kontrol](#8-stok-kontrol)
9. [Laporan](#9-laporan)
10. [Pengaturan](#10-pengaturan)

---

## 1. Dashboard

Halaman utama menampilkan ringkasan bisnis Anda:
- Statistik penjualan hari ini
- Produk dengan stok menipis
- Transaksi terbaru

---

## 2. Data Master

Menu untuk mengelola data dasar aplikasi.

### Kategori
Mengelompokkan produk. Contoh: Obat, Vitamin, Pakan.
- Klik **Data Master > Kategori** > **Tambah** untuk membuat kategori baru.

### Produk
Data seluruh produk yang dijual.
- **Kode**: kode unik produk (otomatis, bisa diedit)
- **Nama**: nama produk
- **Harga Beli**: harga dari supplier
- **Harga Toko**: harga jual eceran
- **Harga Reseller**: harga jual grosir/reseller
- **Stok**: jumlah barang tersedia
- **Min Stok**: batas minimal stok (muncul peringatan jika di bawah ini)

### Supplier
Data pemasok barang. Digunakan saat membuat Purchase Order.

### Customer
Data pelanggan. Digunakan saat transaksi POS.

### Akun Biaya (COA)
Chart of Accounts — daftar akun akuntansi untuk jurnal.

### Metode Pembayaran
Jenis pembayaran yang tersedia di POS: Tunai, Transfer, QRIS, dll.
- **Kredit**: centang jika metode ini bersifat hutang.

### Pajak
Daftar tarif pajak yang bisa diterapkan di transaksi.

---

## 3. Pembelian

### Purchase Order (PO)
Membuat pesanan ke supplier yang bisa diterima bertahap.

**Membuat PO:**
1. Klik **Pembelian > Purchase Order > Buat PO**
2. Pilih **Supplier**
3. Pilih status: **Hutang** (isi jatuh tempo) atau **Lunas/Tunai** (isi metode pembayaran)
4. Klik **Tambah Produk** untuk memilih barang
5. Isi **Qty** dan **Harga Beli** untuk setiap produk
6. Klik **Simpan PO**

**Menerima Barang:**
1. Buka detail PO > klik **Terima**
2. Isi **Qty Terima** untuk barang yang datang
3. Klik **Simpan Penerimaan**

**Membayar PO:**
1. Buka detail PO > klik **Bayar**
2. Tambahkan metode pembayaran (bisa >1: kas, transfer, dll)
3. Isi jumlah pembayaran
4. Klik **Bayar**

> Anda bisa menerima barang dan membayar secara bertahap (cicil). History penerimaan dan pembayaran tercatat lengkap.

### Pembelian Langsung
Pembelian instan tanpa PO — stok langsung masuk dan pembayaran langsung.

1. Klik **Pembelian > Pembelian Langsung > Buat Pembelian Langsung**
2. Pilih **Supplier**
3. Tambahkan produk, isi qty & harga beli
4. Tambahkan **metode pembayaran** (wajib, bisa multiple)
5. Klik **Simpan Pembelian**

---

## 4. Penjualan

### POS Kasir
Halaman kasir untuk transaksi langsung.

**Mode Toko vs Reseller:**
- **Toko**: harga jual retail (Harga Toko)
- **Reseller**: harga jual grosir (Harga Reseller)

**Melakukan Transaksi:**
1. Pilih mode (Toko/Reseller)
2. Klik produk di grid kiri untuk menambah ke keranjang
3. Pilih **Customer** (opsional, default: Walk-in/Umum)
4. Edit qty, harga, diskon per item jika perlu
5. Pilih **Metode Pembayaran**
6. Isi **Jumlah Bayar**
7. Klik **Bayar**
8. Struk otomatis tercetak (sesuai printer dipilih)

**Cetak Ulang:** Pilih tipe printer (A4/Thermal/Epson) sebelum bayar, atau cetak dari riwayat.

### Riwayat Penjualan
Melihat dan mencari transaksi yang sudah dilakukan. Bisa cetak ulang struk.

### Retur Penjualan
Mengembalikan barang dari customer. Stok otomatis kembali.

---

## 5. Hutang & Piutang

### Bayar Hutang
Daftar hutang ke supplier dari PO. Klik **Bayar** untuk mencatat pembayaran.

### Terima Piutang
Daftar piutang dari customer (pembayaran kredit). Klik **Terima** untuk mencatat pelunasan.

> Hutang & Piutang otomatis terbuat dari transaksi Pembelian dan POS.

---

## 6. Alur Kas & Bank

### Akun Kas/Bank
Mengelola akun kas (tunai) dan rekening bank.

**Kelola Akun:**
1. Klik ikon **gear** pada akun
2. Lihat riwayat transaksi akun tersebut
3. **Top-Up**: menambah saldo (contoh: setor tunai)
4. **Tarik**: mengurangi saldo (contoh: biaya operasional)

### Transaksi Kas
Riwayat semua transaksi kas: masuk, keluar, transfer antar akun.

---

## 7. Akuntansi

### Jurnal Umum
Semua jurnal otomatis dari transaksi (penjualan, pembelian, pembayaran).

### Buku Besar
Laporan per akun — melihat semua debit/kredit pada akun tertentu.

### Neraca
Laporan posisi keuangan: Aset = Liabilitas + Ekuitas.

### Laba Rugi
Laporan pendapatan dan beban dalam periode tertentu.

---

## 8. Stok Kontrol

### Kartu Stok
Menampilkan stok seluruh produk beserta total barang masuk & keluar.

- Centang **Tampilkan stok dibawah minimal** untuk filter produk menipis
- Klik **Detail** untuk melihat history mutasi per produk

### Stock Opname
Menyesuaikan stok fisik dengan stok sistem:
1. Klik **Stock Opname**
2. Pilih produk yang akan dihitung
3. Isi **Qty Fisik** (hasil hitungan)
4. Sistem otomatis mencatat selisih

---

## 9. Laporan

- **Stok**: laporan stok semua produk (bisa export Excel/PDF)
- **Pembelian**: laporan transaksi pembelian
- **Penjualan**: laporan transaksi penjualan
- **Hutang**: laporan hutang ke supplier
- **Piutang**: laporan piutang dari customer
- **Arus Kas**: ringkasan kas masuk, kas keluar, dan saldo bersih

---

## 10. Pengaturan

- **Profil Perusahaan**: nama, logo, alamat, prefix dokumen (INV, PO)
- **Manajemen User**: tambah/edit/hapus pengguna
- **Role & Permission**: atur hak akses per role

---

## Tips & Shortcut

| Fitur | Cara Cepat |
|-------|-----------|
| Cari produk di POS | Ketik nama/kode/barcode di search bar |
| Tambah customer baru | Klik **+** di samping dropdown customer |
| Cetak ulang struk | Buka riwayat penjualan > klik ikon printer |
| Export data | Semua tabel punya tombol Excel/PDF/Print |
| Filter stok menipis | Centang checkbox di halaman Kartu Stok |
| Pembayaran cicil | Bisa bayar PO berkali-kali sampai lunas |

---

*Versi 1.0.0 &mdash; SmartPOS*

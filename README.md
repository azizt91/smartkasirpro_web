# SmartKasir Pro - Aplikasi Penjualan (Barang & Jasa)

SmartKasir Pro adalah aplikasi Point of Sale (POS) berbasis web yang modern dan ramah pengguna, dirancang untuk membantu mengelola transaksi penjualan, produk, dan jasa dengan efisien.

## Fitur Utama

- **Sistem Kasir (POS):** Antarmuka kasir yang cepat dan mudah digunakan
- **Manajemen Produk & Kategori:** Kelola produk dengan mudah
- **Produk Varian:** Dukungan untuk produk dengan varian (Warna/Ukuran) dengan harga dan stok berbeda
- **Multi Metode Pembayaran:** Tunai, Utang, Kartu, E-Wallet, Transfer, QRIS
- **Payment Gateway (Tripay, Duitku, Midtrans):** Pembayaran digital otomatis via QRIS, Virtual Account, dan E-Wallet dengan verifikasi webhook, QR Code popup, dan auto-polling status
- **Manajemen Piutang:** Pencatatan nama customer dan tandai lunas
- **Laporan:** Penjualan, stok, produk, piutang, shift, log aktivitas, dan komisi
- **Cetak Struk & Barcode:** Cetak struk transaksi dan label barcode
- **Multi User:** Admin dan Kasir dengan hak akses berbeda
- **Manajemen Supplier:** Kelola data supplier dan kontak
- **Manajemen Pelanggan:** Database pelanggan untuk layanan personal
- **Loyalty Points:** Sistem poin pelanggan — earn & redeem otomatis saat transaksi
- **Pembelian Stok (Restok):** Catat pembelian barang masuk dan update stok otomatis
- **Biaya Operasional:** Catat pengeluaran toko (listrik, gaji, dll)
- **Akuntansi (Buku Besar & Laba Rugi):** Jurnal umum otomatis, Chart of Accounts, dan laporan validasi pendapatan bersih
- **Riwayat Transaksi:** Lihat dan kelola histori penjualan (termasuk fitur Void/Batal)
- **Support Printer:** Cetak struk via USB (WebUSB), Bluetooth, dan Browser Dialog
- **Barang & Jasa:** Mendukung penjualan produk fisik maupun layanan non-fisik (Jasa) tanpa memotong stok
- **Sistem Komisi Pegawai:** Hitung komisi pegawai per layanan secara otomatis (Nominal Tetap / Persentase)
- **Batch Payment Komisi:** Checkbox per baris, pilih sumber dana (Tunai/Bank), otomatis catat sebagai Pengeluaran
- **Manajemen Shift Kasir:** Validasi buka/tutup shift kasir, input modal awal, dan pencocokan uang fisik dengan X-Report
- **Log Aktivitas (Audit):** Pantau penambahan produk, penghapusan transaksi, atau settlement komisi (Data Lama vs Baru)
- **WhatsApp Gateway:** Kirim notifikasi transaksi via WA (menggunakan API Fonnte) untuk nota pelanggan
- **Import Produk via Excel:** Tambah data produk massal menggunakan template Excel/CSV yang sudah disediakan
- **Pengaturan Toko:** Nama toko, alamat, logo, dan lainnya

## Panduan WhatsApp Gateway (Fonnte)
Aplikasi ini terintegrasi dengan [Fonnte](https://fonnte.com/) untuk pengiriman notifikasi WhatsApp otomatis saat ada transaksi berhasil.
1. Daftar atau login di dashboard Fonnte.
2. Dapatkan API Token.
3. Di SmartKasir, masuk ke menu **Pengaturan**, tab **Sistem**.
4. Masukkan **Fonnte Token** dan aktifkan fitur WhatsApp Notification.
5. Anda dapat menguji pengaturan menggunakan tombol **Test Koneksi API Fonnte**.

## Panduan Printer USB

Aplikasi ini mendukung pencetakan struk menggunakan Printer Thermal USB (ESC/POS) secara langsung dari browser menggunakan teknologi **WebUSB**.

**Persyaratan:**
1.  **Browser:** Gunakan Google Chrome, Microsoft Edge, atau Opera (Chromium-based).
2.  **Koneksi:** Pastikan printer USB terhubung dan menyala.
3.  **HTTPS:** Fitur WebUSB *hanya* berfungsi pada protokol **https://** atau **http://localhost**. Jika di-hosting di server publik (non-localhost), wajib menggunakan SSL/HTTPS.

**Cara Menggunakan:**
1.  Masuk ke menu **Pengaturan (Settings)**.
2.  Gulir ke bagian **Printer Struk**.
3.  Klik tombol **Connect USB Printer**.
4.  Pilih printer thermal Anda dari daftar popup browser, lalu klik **Connect**.
5.  Status akan berubah menjadi "Terhubung".
6.  Gunakan tombol **Test Print USB** untuk mencoba mencetak.

## Teknologi

- **Backend:** Laravel 11
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Database:** MySQL / MariaDB

## Prasyarat

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / MariaDB

## Instalasi

### 1. Extract File

Extract file `kasir-app.zip` ke folder htdocs (XAMPP) atau www (Laragon):

```
C:\xampp\htdocs\kasir-app\
```

### 2. Install Dependencies

Buka terminal/command prompt, masuk ke folder project:

```bash
cd C:\xampp\htdocs\kasir-app
composer install
npm install
```

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kasir_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate App Key

```bash
php artisan key:generate
```

### 5. Migrasi Database

Buat database baru di phpMyAdmin dengan nama `kasir_db`, lalu jalankan:

```bash
php artisan migrate --seed
```

### 6. Build Assets

```bash
npm run build
```

### 7. Jalankan Aplikasi

```bash
php artisan serve
```

Buka browser dan akses: `http://127.0.0.1:8000`

## Akun Demo

| Role  | Email                    | Password |
|-------|--------------------------|----------|
| Admin | admin@smartkasir.com     | password |
| Kasir | kasir1@smartkasir.com    | password |
| Kasir | kasir2@smartkasir.com    | password |

## Catatan Penting

- Pastikan XAMPP/Laragon sudah running (Apache & MySQL)
- Jika menggunakan XAMPP, bisa langsung akses via `http://localhost/kasir-app/public`
- Untuk production, arahkan document root ke folder `public/`

---

© 2026 SmartKasir Pro

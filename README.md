# Kasir App - Aplikasi Kasir Berbasis Web

Kasir App adalah aplikasi Point of Sale (POS) berbasis web yang modern dan ramah pengguna, dirancang untuk membantu mengelola transaksi penjualan, produk, dan stok dengan efisien.

## Fitur Utama

- **Sistem Kasir (POS):** Antarmuka kasir yang cepat dan mudah digunakan
- **Manajemen Produk & Kategori:** Kelola produk dengan mudah
- **Multi Metode Pembayaran:** Tunai, Utang, Kartu, E-Wallet, Transfer
- **Manajemen Piutang:** Pencatatan nama customer dan tandai lunas
- **Laporan:** Penjualan, stok, produk, dan piutang
- **Cetak Struk & Barcode:** Cetak struk transaksi dan label barcode
- **Multi User:** Admin dan Kasir dengan hak akses berbeda
- **Manajemen Supplier:** Kelola data supplier dan kontak
- **Manajemen Pelanggan:** Database pelanggan untuk layanan personal
- **Pembelian Stok (Restok):** Catat pembelian barang masuk dan update stok otomatis
- **Biaya Operasional:** Catat pengeluaran toko (listrik, gaji, dll)
- **Riwayat Transaksi:** Lihat dan kelola histori penjualan (termasuk fitur Void/Batal)
- **Pengaturan Toko:** Nama toko, alamat, logo, dan lainnya

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
| Admin | admin@minimarket.com     | password |
| Kasir | kasir1@minimarket.com    | password |

## Catatan Penting

- Pastikan XAMPP/Laragon sudah running (Apache & MySQL)
- Jika menggunakan XAMPP, bisa langsung akses via `http://localhost/kasir-app/public`
- Untuk production, arahkan document root ke folder `public/`

---

© 2025 Kasir App

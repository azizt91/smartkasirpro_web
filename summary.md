# Ringkasan Implementasi Mode Kafe/Resto & Self-Ordering

Dokumen ini berisi rangkuman seluruh fitur Mode Kafe/Resto yang telah diimplementasikan pada aplikasi basis Web. Ringkasan ini dibuat sebagai acuan utama untuk pengerjaan sinkronisasi fitur pada **Mobile App** (Flutter) di tahap selanjutnya.

---

## 1. Perubahan Struktur Database
- **Tabel `tables`**: Tabel baru untuk master data Meja, berisi [id](file:///c:/xampp/htdocs/kasir-app/app/Services/PaymentGatewayService.php#334-343), `nama_meja`, `status` (available/used), dan `hash` (unik untuk QR Code).
- **Tabel `transactions`**:
  - Kolom baru: `table_id` (relasi ke tabel meja).
  - Kolom baru: `is_self_order` (boolean, true jika pelanggan pesan mandiri via QR).
  - Kolom baru: `customer_phone` (nomor HP pelanggan).
  - Kolom baru: `order_status` (status alur proses dapur: `pending`, `processing`, `completed`, `cancelled`).
  - Kolom baru: `payment_status` (status pembayaran: `unpaid`, `paid`).
- **Tabel `settings`**: Penambahan konfigurasi `business_mode` dengan pengaturan default `retail` atau `resto`.

## 2. Sistem Public Order (Self-Ordering via QR)
- **Akses QR Code**: Pelanggan memindai QR code di meja (`/order/{hash}`).
- **UI Menu**: Tampilan katalog produk lengkap dengan keranjang belanja interaktif berbasis Alpine.js.
- **Data Pelanggan**: Nama & Nomor HP yang dimasukkan pelanggan saat checkout mandiri akan otomatis disimpan/diperbarui ke tabel Master `customers`.
- **Integrasi Payment Gateway**: 
  - Layar checkout publik terhubung langsung ke Payment Gateway (opsi QRIS, E-Wallet, Transfer Bank).
  - Pemilihan channel provider Bank/E-Wallet dari Grid View telah dipindah menggunakan native **Dropdown** agar lebih hemat tempat.
  - Transaksi publik awalnya berstatus `payment_status = unpaid` dan `order_status = pending`.
- **Mode Retail / Kafe**: Integrasi ini bisa dinyalakan atau dimatikan (sebagai fallback transaksi langsung).

## 3. Fitur Point of Sale (POS Kasir Web)
- **Hide Fitur Hutang**: Opsi pembayaran "Utang (Piutang)" otomatis disembunyikan dari POS apabila `business_mode` adalah 'resto'.
- **Fitur Opsional Meja**: Jika ada pelanggan walk-in (pesan langsung ke kasir tapi makan di tempat), kasir bisa menyertakan nomor meja melalui input opsional `table_id` di modal pembayaran POS.
- **Antrean Pesanan Masuk (Self-Order Queue)**:
  - Terdapat tombol notifikasi khusus dengan badge angka (bulat) berwarna merah jika ada orderan publik baru.
  - Menampilkan transaksi `is_self_order` yang `payment_status`-nya masih **Unpaid** ATAU `order_status`-nya masih **Pending / Processing** oleh dapur.
  - Kasir dapat menekan tombol **"Buka & Proses"**; aksi ini akan menyedot orderan pelanggan langsung ke dalam keranjang POS, sekaligus auto-fill informasi Nama Pelanggan dan Nomor Mejanya.
  - Opsi penolakan ("Tolak") hanya dikontrol penuh oleh Kasir. Pesanan yang ditolak akan memiliki status `cancelled` secara keseluruhan sistem dan hilang dari antrean.
- **Fallback Payment Gateway**: Bila Payment Gateway tiba-tiba di-disable dari pengaturan admin, pesanan digital dari POS atau Public akan terganti aman otomatis menjadi **Tunai/Cash** tanpa error.

## 4. Kitchen View (Layar Dapur)
- **Akses & UI**: Diakses di `/pos/kitchen`, dibuat menggunakan Alpine.js dengan antarmuka Grid yang *auto-refresh* setiap 10 detik.
- **Alur Kerja Dapur**:
  - Status `pending` (Antrean Baru): Muncul dengan warna merah. Dapur bisa menekan **"MASAK"** mengubah status ke `processing`.
  - Status `processing` (Sedang Dimasaka): Muncul dengan warna kuning/amber. Dapur menekan **"SELESAI"** mengubah status ke `completed` dan memberitahu kasir.
  - Tombol **Batal / Tolak** sengaja dihilangkan dari layar dapur, karena hak istimewa pembatalan/penolakan dipusatkan hanya di sisi front-Kasir.

## 5. Riwayat Histori Transaksi
- View histori transaksi (`/transactions`) sudah di-patch agar dapat membaca akurat kondisi Non-Tunai dan Self-Ordering.
- Status menampilkan:
  - *"⏳ Belum Bayar"* untuk transaksi QRIS/Transfer yang sukses order publik tapi belum discan pembayarannya.
  - *"Expired / Ditolak"* untuk transaksi yang digagalkan.
  - *"Sukses"* untuk yang sudah sah terbayarkan secara nominal.

---

### *Todo untuk Integrasi Mobile App (Flutter) Selanjutnya:*
1. **Routing UI**: Pastikan Flutter Mobile membaca flag `business_mode`. Jika 'resto' nyala, maka UI mobile perlu menonaktifkan flow piutang.
2. **Sinkronisasi Antrean Kasir**: Sediakan modal/bottom-sheet *"Pesanan Masuk"* di interface POS mobile agar mem-fetch data transaksi yang sama ([getPendingOrders](file:///c:/xampp/htdocs/kasir-app/app/Http/Controllers/PosController.php#250-290)).
3. **Kitchen Display System UI**: Buat halaman baru di Flutter khusus Layar Kitchen View yang bisa me-listen status `pending`/`processing`.
4. **Pembayaran Transversal (Self-Order to Kasir):** Replikasi sistem *"Buka & Proses"* di tablet Kasir agar bisa menghisap data [items](file:///c:/xampp/htdocs/kasir-app/app/Models/Transaction.php#121-128) pada payload transaksi *self-order* menjadi state *cart* interaktif.

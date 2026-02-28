# Panduan Instalasi Lengkap: Web Admin (Laravel) & Mobile App (Flutter)

Aplikasi ini terdiri dari dua bagian utama:
1. **Web Admin (Backend)** menggunakan Framework Laravel 11.
2. **Mobile App (Android POS)** menggunakan Framework Flutter.

Berikut adalah panduan langkah demi langkah untuk menginstal dan mengkonfigurasi kedua sistem tersebut agar dapat tersinkronisasi.

---

## BAGIAN 1: Instalasi Web Admin (Laravel)

### Prasyarat:
- Web Server lokal (XAMPP / Laragon) atau Hosting cPanel.
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / MariaDB

### Langkah-langkah Instalasi Lokal (PC/Laptop)

**1. Extract File**
Extract file `Source_Code_Kasir_Pro.zip` ke folder `htdocs` (XAMPP) atau `www` (Laragon).
Contoh: `C:\xampp\htdocs\kasir-app\`

**2. Install Dependencies**
Buka terminal/command prompt, arahkan ke folder web admin:
```bash
cd C:\xampp\htdocs\kasir-app
composer install
npm install
```

**3. Konfigurasi Environment (.env)**
Salin file `.env.example` menjadi `.env`.
Sesuaikan pengaturan database Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kasir_db
DB_USERNAME=root
DB_PASSWORD=
```

**4. Generate App Key & Migrasi Database**
Buat database baru di phpMyAdmin (misal: `kasir_db`), lalu jalankan:
```bash
php artisan key:generate
php artisan migrate --seed
```

**5. Build Assets & Jalankan Aplikasi**
```bash
npm run build
php artisan serve
```
Aplikasi Web Admin sekarang bisa diakses di browser pada: `http://127.0.0.1:8000`

*(Catatan: Jika di-hosting di cPanel internet, pastikan arahkan Document Root domain Anda ke folder `public/` dan atur database seperti instalasi Laravel pada umumnya).*

---

## BAGIAN 2: Instalasi Mobile App Android (Flutter)

Untuk dapat menggunakan aplikasi kasir Android dan menghubungkannya dengan Web Admin milik Anda sendiri, perhatikan langkah berikut.

### Prasyarat:
- Instalasi Flutter SDK versi 3.27 atau lebih baru.
- Android Studio / VS Code (untuk edit kode dan build APK).
- Web Admin Anda **sudah harus online** di internet (Hosting publik atau IP public), atau gunakan jaringan WiFi yang sama (Local IP) jika untuk testing.

### Langkah-langkah Build APK

**1. Buka Project Flutter**
Buka folder `mobile_app` menggunakan VS Code atau Android Studio.

**2. Ubah Alamat Server (API URL)**
Buka file bernama `.env` di dalam folder root `mobile_app`. 
Anda akan melihat baris konfigurasinya:
```env
# Ganti dengan URL Hosting Web Admin Anda (Jangan lupa tambahkan /api di belakangnya)
API_URL=https://namatokoanda.com/api
```
*Contoh jika masih testing di komputer lokal (satu WiFi dengan HP):*
`API_URL=http://192.168.1.5:8000/api` (Ganti dengan IP Local komputer Anda).

**3. Ganti Nama Aplikasi & Logo (Opsional)**
- Untuk nama aplikasi: Edit file `android/app/src/main/AndroidManifest.xml` pada tag `android:label="Nama Toko Anda"`.
- Untuk logo: Timpa gambar logo lama yang ada di folder `android/app/src/main/res/`.

**4. Download Package/Dependency**
Buka terminal di dalam folder `mobile_app` lalu ketik:
```bash
flutter pub get
```

**5. Build File APK**
Untuk menghasilkan file installer Android (.apk), jalankan perintah berikut di terminal:
```bash
flutter build apk --release
```
Tunggu prosesnya (sekitar 2-5 menit). Jika berhasil, file APK siap pakai Anda bisa ditemukan di dalam folder:
`mobile_app/build/app/outputs/flutter-apk/app-release.apk`

Pindahkan file `.apk` tersebut ke HP Android kasir Anda dan siap digunakan! 🎉

---

### Info Akun Login Demo (Bawaan Database)
Anda dapat menggunakan akun ini untuk login pertama kali di Web maupun di Mobile App:
- **Admin**: admin@smartkasir.com / Password: `password`
- **Kasir**: kasir1@smartkasir.com / Password: `password`
- **Kasir**: kasir2@smartkasir.com / Password: `password`

*(Pastikan ganti password ini demi keamanan jika sudah dironline-kan!).*

---
© 2026 SmartKasir Pro - Hak Cipta Dilindungi

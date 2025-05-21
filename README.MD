# Kelompok 5
Nama Anggota Kelompok:
- Muhamad Azka Atqiya (NPM.2271020039)
- Nadhif Luqyana (NPM.2271020042)
- Nadia Hilwana (NPM.2271020043)

# Danis Printing
Danis Printing adalah sebuah platform web yang dirancang untuk memudahkan pelanggan dalam memesan produk percetakan seperti banner, spanduk, dan berbagai kebutuhan promosi lainnya. Website ini menyediakan layanan yang cepat, mudah, dan terintegrasi dengan berbagai fitur modern untuk mendukung pengalaman pengguna.

## Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB
- Composer
- XAMPP (atau server web lainnya)
- Browser modern yang mendukung JavaScript

## Fitur Utama
- **Pesanan Online**: Pelanggan dapat memesan produk secara online dengan mudah.
- **Desain AI**: Fitur generator desain berbasis AI untuk membantu pelanggan membuat desain yang menarik.
- **Cek Pesanan**: Pelanggan dapat memantau status pesanan mereka secara real-time.
- **Integrasi Pembayaran**: Sistem pembayaran terintegrasi dengan Midtrans untuk transaksi yang aman dan cepat.
- **Manajemen Admin**: Dashboard admin untuk mengelola pesanan, produk, dan pelanggan.

## Dependensi
Project ini menggunakan beberapa library eksternal yang dikelola menggunakan Composer:
- PHPMailer - untuk mengirim email notifikasi
- Midtrans PHP Client - untuk integrasi pembayaran
- GuzzleHTTP - untuk HTTP client

## Teknologi yang Digunakan
- **Frontend**: 
  - HTML, CSS, JavaScript
  - Bootstrap 5
- **Backend**:
  - PHP
  - MySQL
- **API**:
  - Midtrans Payment Gateway
  - OpenAI API (untuk generator desain AI)
- **Tools**:
  - XAMPP untuk pengembangan lokal
  - Composer untuk manajemen dependensi PHP

## Struktur Direktori
printproject/
├── admin/                 # Panel admin
├── user/                  # Area customer
├── api/                   # API endpoints
├── assets/               # Asset statis
├── vendor/               # Dependensi Composer
├── config.php            # Konfigurasi utama
└── README.md             # Dokumentasi

## Instalasi
1. Clone repository ini ke direktori web server Anda:
```bash
    git clone https://github.com/kzxazka/BannerPrinting_kel5.git
2. Masuk ke direktori proyek:
cd danis-printing
3. Install dependensi Composer:
   composer install
4. Konfigurasi database MySQL:
Buat database baru di MySQL/MariaDB.
Buka file config.php dan sesuaikan konfigurasi database.
5. Import database:
php import-database.php
6. Jalankan server web:
Untuk pengembangan lokal, gunakan XAMPP.
Buka browser dan akses
Untuk pengembangan lokal, gunakan XAMPP.
Buka browser dan akses
Buka browser dan akses URL_ADDRESS/danis-printing/user/index-update.php untuk melihat website
Buka browser dan akses URL_ADDRESS/danis-printing/user/index-update.php untuk melihat website.

## Kontribusi
Kontribusi sangat diterima! Jika Anda ingin berkontribusi pada proyek ini, silakan buka issue atau submit pull request.


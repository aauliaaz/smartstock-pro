# Troubleshooting Guide - SmartStock Pro

## 1. Masalah Login
- **Gejala:** Muncul pesan "The provided credentials do not match our records."
- **Solusi:** Periksa kembali email dan password. Pastikan Caps Lock tidak aktif. Jika lupa password, hubungi Admin.

## 2. Gambar Produk Tidak Muncul
- **Gejala:** Gambar muncul sebagai placeholder atau icon pecah.
- **Solusi:** Jalankan perintah `php artisan storage:link` pada server untuk menghubungkan folder storage ke folder publik.

## 3. Proses Transfer Tertahan di "APPROVED"
- **Gejala:** Status tidak berubah menjadi "COMPLETED".
- **Solusi:** Pastikan antrian (queue worker) berjalan. Cek di terminal server: `php artisan queue:work`.

## 4. Error 500 Internal Server Error
- **Gejala:** Halaman blank atau muncul pesan error sistem.
- **Solusi:** Buka menu **System Logs** -> **Error Logs** untuk melihat detail penyebab kesalahan. Biasanya disebabkan oleh koneksi database yang terputus.

## 5. Performa Lambat
- **Gejala:** Loading dashboard memakan waktu lama.
- **Solusi:** Bersihkan cache aplikasi dengan `php artisan cache:clear`. Pastikan koneksi internet stabil.

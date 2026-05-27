# SmartStock Pro - Sistem Manajemen Inventaris

SmartStock Pro adalah aplikasi web Sistem Manajemen Inventaris yang dibangun dengan arsitektur modern untuk membantu pengelolaan stok barang di berbagai lokasi gudang secara efisien dan akurat.

## Tech Stack
- **Backend**: Laravel 11 (PHP 8.2)
- **Frontend**: React + Vite + TypeScript
- **Styling**: Tailwind CSS + shadcn/ui
- **Database**: SQLite
- **Auth**: Laravel Sanctum (Session based)

## Fitur Utama
- Dashboard Interaktif & Peta Gudang
- Manajemen Katalog Produk & Kategori
- Multi-Warehouse Support
- Transaksi Barang Masuk & Keluar
- Transfer Stok Antar Gudang dengan Approval
- Notifikasi Stok Kritis
- Audit Log & Error Monitoring
- Import Data Masal & Laporan PDF

## Cara Instalasi

### 1. Backend (Laravel)
```bash
# Clone repository
# Pindah ke root folder
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### 2. Frontend (React)
```bash
cd smartstock-web
npm install
# Pastikan VITE_API_URL di .env sudah benar
npm run dev
```

## Akun Demo
- **Admin**: admin@smartstock.id / Admin@123
- **Manajer**: manager@smartstock.id / Manager@123
- **Staf**: staff@smartstock.id / Staff@123
- **Viewer**: viewer@smartstock.id / Viewer@123

## Cara Menjalankan Full Stack
1. Jalankan backend: `php artisan serve` (port 8000)
2. Jalankan frontend: `npm run dev` (port 5173)
3. Buka browser ke `http://localhost:5173`
4. Login menggunakan akun demo.

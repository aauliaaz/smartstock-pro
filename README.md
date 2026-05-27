# SmartStock Pro
<img width="1887" height="898" alt="image" src="https://github.com/user-attachments/assets/a4da5897-df8d-48a7-b9e0-2117fa8ba29d" />

SmartStock Pro adalah aplikasi web manajemen inventaris multi-gudang untuk membantu pencatatan stok, pengelolaan produk, transaksi stok masuk/keluar, transfer antar gudang, monitoring dashboard, notifikasi, audit log, error log, dan laporan PDF.

Project ini dibuat sebagai studi kasus skema **Web Developer** dengan arsitektur frontend dan backend terpisah:

- Backend utama: Laravel di root repository
- Frontend: React + Vite + TypeScript di folder `smartstock-web`
- API v1 opsional/eksperimental: Laravel terpisah di folder `smartstock-api`

## Fitur Utama

- Autentikasi pengguna berbasis Laravel session
- Role pengguna: Admin, Manajer, Staf, dan Viewer
- Dashboard inventaris dengan kartu ringkasan, grafik, dan peta gudang
- CRUD produk, kategori, gudang, dan supplier
- Transaksi stok masuk dan stok keluar
- Transfer stok antar gudang dengan proses queue
- Notifikasi sistem dan low stock alert
- Audit log aktivitas pengguna
- Error log untuk membantu debugging
- Import produk berbasis background job dasar
- Laporan produk dalam format PDF menggunakan DomPDF
- Frontend responsif dengan Tailwind CSS dan komponen UI modern

## Tech Stack

| Layer | Teknologi |
| --- | --- |
| Frontend | React 19, Vite 8, TypeScript 6 |
| Styling | Tailwind CSS, Radix UI, shadcn-style components |
| Chart & Map | Recharts, Leaflet, React Leaflet |
| Backend Utama | Laravel 11, PHP 8.2+ |
| Database | SQLite |
| Queue / Cache | Database driver |
| PDF | barryvdh/laravel-dompdf |
| Testing | PHPUnit, ESLint, TypeScript build |

## Struktur Folder

```text
.
|-- app/                  # Backend Laravel utama
|-- database/             # Migration, factory, seeder, SQLite database
|-- routes/               # Route web dan API backend utama
|-- resources/            # Blade template dan aset Laravel
|-- smartstock-web/       # Frontend React + Vite + TypeScript
|-- smartstock-api/       # API v1 opsional berbasis Laravel + Sanctum
|-- tests/                # Test backend utama
|-- composer.json
`-- README.md
```

## Kebutuhan Sistem

Pastikan perangkat sudah memiliki:

- PHP 8.2 atau lebih baru
- Composer
- Node.js 22 atau versi LTS yang kompatibel
- npm
- SQLite

Untuk Windows + XAMPP, pastikan command `php` sudah mengarah ke PHP XAMPP atau PHP yang memiliki extension SQLite aktif.

## Instalasi Backend Utama

Jalankan dari root repository:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Jika file `database/database.sqlite` belum ada, buat terlebih dahulu:

```bash
type nul > database/database.sqlite
```

Untuk Linux/macOS:

```bash
touch database/database.sqlite
```

## Instalasi Frontend

Masuk ke folder frontend:

```bash
cd smartstock-web
npm install
```

Pastikan file `smartstock-web/.env` mengarah ke backend yang sedang berjalan:

```env
VITE_API_URL=http://localhost:8000
```

Jika port `8000` sedang dipakai aplikasi lain, jalankan backend di port lain dan sesuaikan `VITE_API_URL`, misalnya:

```env
VITE_API_URL=http://localhost:8001
```

## Menjalankan Aplikasi

Terminal 1, jalankan backend utama:

```bash
php artisan serve
```

Secara default backend berjalan di:

```text
http://localhost:8000
```

Terminal 2, jalankan frontend:

```bash
cd smartstock-web
npm run dev
```

Frontend berjalan di:

```text
http://localhost:5173
```

Buka `http://localhost:5173` di browser.

## Akun Demo

Setelah menjalankan `php artisan migrate --seed`, gunakan akun berikut:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@smartstock.id` | `Admin@123` |
| Manajer | `manager@smartstock.id` | `Manager@123` |
| Staf | `staff@smartstock.id` | `Staff@123` |
| Viewer | `viewer@smartstock.id` | `Viewer@123` |

## Endpoint API Backend Utama

Prefix endpoint utama adalah `/api`.

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| POST | `/api/login` | Login pengguna |
| POST | `/api/logout` | Logout pengguna |
| GET | `/api/me` | Data pengguna aktif |
| GET | `/api/dashboard` | Data dashboard |
| GET | `/api/products` | Daftar produk |
| POST | `/api/products` | Tambah produk |
| PUT/PATCH | `/api/products/{id}` | Update produk |
| DELETE | `/api/products/{id}` | Hapus produk |
| GET | `/api/categories` | Daftar kategori |
| GET | `/api/warehouses` | Daftar gudang |
| GET | `/api/suppliers` | Daftar supplier |
| GET | `/api/stock-movements` | Riwayat stok masuk/keluar |
| POST | `/api/stock-movements` | Catat stok masuk/keluar |
| GET | `/api/transfers` | Daftar transfer gudang |
| POST | `/api/transfers` | Ajukan transfer gudang |
| PATCH | `/api/transfers/{id}/approve` | Setujui transfer |
| GET | `/api/notifications` | Daftar notifikasi |
| GET | `/api/audit-logs` | Audit log |
| GET | `/api/error-logs` | Error log |
| GET | `/api/reports/products-pdf` | Download laporan produk PDF |
| GET | `/api/system/stats` | Data monitoring server demo |

## API v1 Opsional

Folder `smartstock-api` berisi backend API terpisah dengan Laravel, Sanctum, endpoint `/api/v1/*`, dan struktur yang lebih modular.

Backend yang dipakai oleh frontend utama repository ini adalah backend Laravel di root repository. Gunakan `smartstock-api` hanya jika ingin mengembangkan versi API v1 secara terpisah.

## Menjalankan Queue Worker

Beberapa proses seperti transfer stok dan import dasar menggunakan queue database. Jalankan worker pada terminal terpisah:

```bash
php artisan queue:work
```

Untuk kebutuhan demo kecil, proses tertentu tetap dapat terlihat dari request utama, tetapi worker diperlukan agar job queue berjalan normal.

## Testing dan Quality Check

Backend utama:

```bash
php artisan test
```

Frontend:

```bash
cd smartstock-web
npm run lint
npm run build
```

API v1 opsional:

```bash
cd smartstock-api
php artisan test
```

## Troubleshooting

### Gagal Login

Pastikan database sudah di-seed:

```bash
php artisan migrate:fresh --seed
```

Lalu coba login ulang menggunakan akun demo.

### Frontend Tidak Terhubung ke Backend

Cek `smartstock-web/.env`:

```env
VITE_API_URL=http://localhost:8000
```

Sesuaikan port dengan backend yang sedang berjalan, lalu restart Vite:

```bash
npm run dev
```

### Port 8000 Sudah Dipakai

Jalankan backend pada port lain:

```bash
php artisan serve --port=8001
```

Lalu ubah `smartstock-web/.env`:

```env
VITE_API_URL=http://localhost:8001
```

### Job Queue Tidak Jalan

Jalankan worker:

```bash
php artisan queue:work
```

Pastikan tabel queue sudah ada dengan menjalankan migration.

### PDF Tidak Terunduh

Pastikan backend berjalan dan browser tidak memblokir download/pop-up. Endpoint laporan produk:

```text
/api/reports/products-pdf
```

## Catatan Pengembangan

Beberapa fitur masih dapat dikembangkan lebih lanjut:

- RoleMiddleware backend untuk pembatasan akses yang lebih ketat
- Import CSV/Excel yang benar-benar membaca file upload pengguna
- Penyesuaian tipe notifikasi low stock agar konsisten dengan enum database
- Laporan PDF dengan logo, grafik, dan ringkasan statistik
- Migrasi database ke MySQL/PostgreSQL untuk produksi
- Redis untuk queue/cache pada beban lebih besar
- Notifikasi real-time berbasis WebSocket
- Reset password dan autentikasi dua faktor

## Lisensi

Project ini dibuat untuk kebutuhan pembelajaran/studi kasus. Sesuaikan lisensi sebelum digunakan untuk kebutuhan produksi atau distribusi publik.

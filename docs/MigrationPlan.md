# Rencana Migrasi & Cutover - SmartStock Pro

## 1. Strategi Migrasi Data
Migrasi dari sistem lama (Spreadsheet) ke SmartStock Pro menggunakan proses ETL (Extract, Transform, Load).

### Mapping Field (Contoh)
| Spreadsheet Field | Database Table | Database Field |
| :--- | :--- | :--- |
| Kode Barang | products | sku |
| Nama Elektronik | products | name |
| Kategori Produk | categories | name (Lookup ID) |
| Stok Saat Ini | stock_movements | quantity (Type: IN) |
| Harga Beli | products | unit_price |

## 2. Validasi Pasca-Migrasi
- **Cek Total Record:** Memastikan jumlah baris di spreadsheet sama dengan jumlah baris di tabel `products`.
- **Cek Saldo Stok:** Melakukan query `SUM(quantity)` dan membandingkannya dengan total stok di spreadsheet.
- **Spot Check:** Mengambil 5 item secara acak dan memverifikasi detailnya.

## 3. Cutover Plan (Langkah-Langkah Go-Live)
1. **D-Day (22:00):** Matikan akses edit pada spreadsheet lama.
2. **22:15:** Ekspor data terakhir dari spreadsheet.
3. **22:30:** Jalankan script import/batch job pada SmartStock Pro.
4. **23:15:** Lakukan validasi data oleh tim internal.
5. **23:45:** Update DNS/Domain ke server baru.
6. **00:00:** Sistem resmi dapat digunakan oleh user.

## 4. Rollback Plan
Jika terjadi kegagalan kritis saat cutover:
1. Kembalikan akses edit pada spreadsheet lama.
2. Turunkan layanan SmartStock Pro sementara (Maintenance Mode).
3. Analisis log kesalahan dan jadwalkan ulang cutover.

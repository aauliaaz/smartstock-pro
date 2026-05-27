# Dokumentasi API - SmartStock Pro

## 1. Autentikasi
Semua API endpoint menggunakan session-based auth atau Sanctum Token.

### Login
- **URL:** `/login`
- **Method:** `POST`
- **Payload:** `{ "email": "...", "password": "..." }`

## 2. Inventaris

### List Produk
- **URL:** `/api/products`
- **Method:** `GET`
- **Params:** `search`, `category_id`, `page`

### Tambah Produk
- **URL:** `/api/products`
- **Method:** `POST`
- **Payload:** Multipart/form-data (inc. image)

## 3. Transaksi

### Record Stock Movement
- **URL:** `/api/stock-movements`
- **Method:** `POST`
- **Payload:** `{ "product_id": 1, "warehouse_id": 1, "quantity": 10, "type": "IN/OUT" }`

### Request Transfer
- **URL:** `/api/transfers`
- **Method:** `POST`
- **Payload:** `{ "from_warehouse_id": 1, "to_warehouse_id": 2, "items": [...] }`

## 4. Sistem

### Dashboard Stats
- **URL:** `/api/dashboard`
- **Method:** `GET`

### Server Health
- **URL:** `/api/system/stats`
- **Method:** `GET`

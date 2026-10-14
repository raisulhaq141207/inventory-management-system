# Mini Project 3 – Inventory App (PHP Native + MySQL)

Aplikasi manajemen stok barang dengan login role **admin/staff**, modul kategori & produk,
search/filter/sorting/pagination, barang masuk/keluar berbasis **database transaction**, dan dashboard.

- Mata kuliah: Relational Database + Advanced PHP (Pertemuan 4)
- Nama / NIM: _isi nama dan NIM Anda_

## Stack
PHP 8.1+ (PDO), MySQL/MariaDB (InnoDB), HTML + CSS sederhana. Tanpa framework.

## Cara Menjalankan
1. Letakkan folder di `htdocs` (XAMPP) / `www` (Laragon) dengan nama **inventory-app**
   (jika nama beda, ubah `BASE_URL` di `includes/auth.php`).
2. Import schema: `mysql -u root -p < database/schema.sql` (atau import lewat phpMyAdmin).
3. Sesuaikan kredensial di `config/database.php`.
4. Buat akun awal: `php database/seed.php`
5. Buka `http://localhost/inventory-app/login.php`

| Role  | Username | Password |
|-------|----------|----------|
| Admin | admin    | admin123 |
| Staff | staff    | staff123 |

## Struktur
```
config/database.php     koneksi PDO
includes/               auth, session, CSRF, header/footer
categories/             CRUD kategori (admin)
products/               list+search/filter/sort/pagination, CRUD (admin)
stock/                  in.php, out.php (transaction), history.php
database/schema.sql     skema + data awal   |  seed.php  akun awal
docs/ERD.md             ERD (Mermaid, tampil otomatis di GitHub)
screenshots/            bukti fitur
```

## ERD
Lihat [docs/ERD.md](docs/ERD.md).

## Hak Akses
| Fitur | Admin | Staff |
|---|:--:|:--:|
| Login, dashboard, lihat produk/kategori/riwayat | ✅ | ✅ |
| Barang masuk / keluar | ✅ | ✅ |
| Tambah/ubah/hapus kategori & produk | ✅ | ❌ (403) |

## Business Rule
1. **Autentikasi**: password disimpan dengan `password_hash()` (bcrypt) dan dicek dengan `password_verify()`. Session di-regenerate saat login.
2. **Otorisasi**: halaman form kategori/produk dan aksi hapus hanya untuk admin. Staff yang mengakses mendapat **HTTP 403**. Pengecekan dilakukan di sisi server, bukan hanya menyembunyikan tombol.
3. **Kategori**: nama wajib & unik. Kategori yang masih dipakai produk **tidak boleh dihapus**.
4. **Produk**: SKU wajib (3–30 karakter huruf/angka/-) dan unik; harga ≥ 0; stok awal & stok minimum bilangan bulat ≥ 0. Produk yang sudah punya riwayat transaksi tidak boleh dihapus.
5. **Stok tidak diedit langsung**: nilai `products.stock` hanya berubah lewat transaksi barang masuk/keluar (kecuali stok awal saat produk dibuat).
6. **Barang masuk**: jumlah ≥ 1; stok bertambah.
7. **Barang keluar**: jumlah ≥ 1 dan **tidak boleh melebihi stok** (stok tidak boleh negatif).
8. **Transaction (ACID)**: tiap transaksi stok menjalankan `BEGIN → SELECT ... FOR UPDATE → INSERT stock_transactions → UPDATE products.stock → COMMIT`. Jika ada error/validasi gagal, `ROLLBACK` sehingga riwayat dan stok selalu konsisten. `FOR UPDATE` mengunci baris produk agar dua request bersamaan tidak membuat stok minus.
9. **Stok menipis**: produk dengan `stock <= min_stock` ditandai merah dan tampil di dashboard.
10. **Keamanan**: prepared statement (anti SQL injection), `htmlspecialchars` (anti XSS), token CSRF pada semua form POST, kolom sorting memakai whitelist.

## Search, Filter, Sorting, Pagination (halaman Produk)
- Search: nama atau SKU (`LIKE`)
- Filter: kategori & status stok (menipis/aman)
- Sorting: klik header kolom (SKU, Nama, Kategori, Harga, Stok), toggle ASC/DESC
- Pagination: 5 data per halaman, query string dipertahankan

## Pengujian
**1. Validation error**
- Login → Barang Keluar → pilih "Buku Tulis A5" (stok 3), jumlah `10` → muncul *"Stok tidak cukup..."*, stok tidak berubah.
- Variasi lain: jumlah `0` / `abc` → *"Jumlah harus bilangan bulat minimal 1"*; tambah produk dengan SKU duplikat → *"SKU sudah dipakai"*.

**2. Akses terlarang**
- Login sebagai `staff`, buka `/inventory-app/products/form.php` atau `/categories/form.php` → halaman **403 Akses Ditolak**.
- Tanpa login, buka halaman apa pun → redirect ke `login.php`.

**3. Transaction**
- Barang masuk 10 unit pada "Mouse Wireless" → stok 20 → 30 dan 1 baris muncul di Riwayat.

## Screenshot
Simpan di folder `screenshots/` lalu tampilkan di sini:

| Fitur | File |
|---|---|
| Login | `screenshots/01-login.png` |
| Dashboard | `screenshots/02-dashboard.png` |
| Produk (search/filter/sort/pagination) | `screenshots/03-products.png` |
| Kategori | `screenshots/04-categories.png` |
| Barang masuk | `screenshots/05-stock-in.png` |
| Barang keluar | `screenshots/06-stock-out.png` |
| Validation error (stok tidak cukup) | `screenshots/07-validation-error.png` |
| Akses terlarang (403) | `screenshots/08-forbidden.png` |

<!-- Contoh: ![Dashboard](screenshots/02-dashboard.png) -->

# SIMATEK — Sistem Informasi Apotek

Monorepo untuk sistem inventaris dan operasional Apotek Salam Sehat serta Apotek Badan Sehat:

- `frontend/`: Next.js, TypeScript, App Router.
- `backend/`: Laravel 12, REST API, Sanctum session authentication.

## Kebutuhan

- PHP 8.2+, Composer
- Node.js 20+, npm
- MySQL 8+ / MariaDB (XAMPP menyertakan MySQL dan phpMyAdmin)

## Menjalankan di Windows

Terminal pertama:

```powershell
cd backend
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=localhost --port=8010
```

Sebelum migrasi pertama, nyalakan MySQL pada XAMPP lalu buka `http://localhost/phpmyadmin`. Buat database `simatek_apotek` melalui tab **SQL**:

```sql
CREATE DATABASE simatek_apotek
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Pastikan pengaturan `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `backend/.env` sesuai akun MySQL lokal Anda. Migrasi Laravel membuat seluruh tabel, kemudian `--seed` menambahkan data awal kedua apotek. Jangan jalankan `migrate:fresh` pada database yang berisi data karena perintah tersebut menghapus semua tabel.

Untuk database `simatek_apotek` yang sudah berisi data, jalankan `php artisan migrate` untuk menerapkan skema tambahan; jalankan `php artisan db:seed --class=FoundationDataSeeder` bila katalog kategori, satuan, metode pembayaran, dan kode cabang belum terisi.

Terminal kedua:

```powershell
cd frontend
if (-not (Test-Path .env.local)) { Copy-Item .env.example .env.local }
npm install
npm run dev
```

Buka `http://localhost:3000`. Pada penggunaan pertama, buat akun owner melalui halaman setup. API menggunakan port `8010` agar tidak bertabrakan dengan layanan Laravel lain yang sudah berjalan di port standar `8000`. Gunakan host `localhost` pada frontend dan backend secara konsisten agar cookie sesi Sanctum dapat digunakan.

## Role dan akses awal

| Role | Cakupan |
|---|---|
| Owner | Ringkasan dua apotek, laporan penjualan, supplier, dan CRUD pengguna |
| Admin Salam Sehat | Operasional dan laporan Apotek Salam Sehat; tanpa mutasi stok |
| Admin Badan Sehat | Operasional dan laporan Apotek Badan Sehat; tanpa mutasi stok |
| Kasir | Transaksi, pelanggan, dan pencatatan permintaan obat pada apotek yang ditugaskan |
| Admin Gudang | Persediaan dan mutasi stok terpusat; tanpa menu pembelian atau aktivitas cabang |
| Supplier | Profil mitra dan katalog obat yang terkait dengan supplier tersebut |

Role admin apotek otomatis dikaitkan ke apotek yang sesuai; akun kasir juga harus ditugaskan ke satu apotek oleh owner atau admin cabangnya. API memvalidasi cakupan tersebut di backend; pembatasan antarmuka frontend bukan satu-satunya pengaman.

## Fitur yang tersedia

- Setup owner pertama, login, logout, dan pemulihan sesi dengan cookie Sanctum.
- Dashboard dan navigasi terpisah sesuai role, termasuk meja kasir khusus dan tombol logout yang selalu terlihat.
- Dashboard memperbarui ringkasan berkala dan menampilkan peringatan batch kedaluwarsa sesuai cabang.
- CRUD pengguna dengan batasan role/cakupan apotek.
- Daftar dan pencarian inventaris berdasarkan nama, SKU, atau barcode; pembuatan obat, stok awal, serta pencatatan barang masuk/keluar secara transaksional.
- CRUD supplier dan akses supplier yang hanya menampilkan produk terkait.
- Data pelanggan per cabang dengan pencarian dan kaitan opsional ke transaksi penjualan.
- Kasir multi-item: pembayaran tunai/kartu/transfer/QRIS, pencatatan pelanggan opsional, perhitungan kembalian tunai, serta pengurangan stok atomik.
- Permintaan obat pelanggan dari meja kasir untuk obat yang belum tersedia; admin apotek dapat menindaklanjuti status hingga siap diambil atau selesai.
- Purchase order per apotek oleh owner/admin apotek, penerimaan parsial dengan batch/kedaluwarsa, dan mutasi stok yang idempoten.
- Riwayat mutasi stok gudang dengan pencarian server dan paginasi 10 data per halaman.
- Laporan penjualan menampilkan grafik penjualan harian dan daftar produk terlaris sesuai hak akses.
- Ekspor laporan melalui dialog cetak browser (termasuk opsi simpan sebagai PDF).
- Jejak audit aktivitas penting dengan cakupan akses owner, admin apotek, dan admin gudang.
- Data demo apotek dan satu produk per apotek melalui `php artisan migrate --seed`.

Data Apotek Salam Sehat dan Apotek Badan Sehat berada dalam satu database MySQL, dipisahkan berdasarkan `pharmacy_id`. API membatasi admin/kasir apotek pada lokasi masing-masing. Admin gudang memiliki tampilan persediaan dan riwayat mutasi terpusat untuk pencarian stok, tetapi tidak memiliki akses ke purchase order; admin apotek juga tidak memiliki akses mutasi stok. phpMyAdmin dapat digunakan untuk melihat dan mengelola database `simatek_apotek`.

Migrasi fondasi menambahkan katalog kategori/satuan, kode cabang, batch kedaluwarsa, pelanggan, pesanan pembelian, metode dan catatan pembayaran, serta audit log. Migrasi permintaan obat pelanggan juga diterapkan secara tambahan pada database lokal yang sudah berisi data; jumlah pengguna, apotek, supplier, dan produk yang ada tetap terjaga.

Diskon, pajak, retur, dan aturan laporan lanjutan perlu divalidasi bersama mitra sebelum ditambahkan.

## Pemeriksaan

```powershell
cd backend
php artisan test

cd ..\frontend
npm run lint
npm run build
```

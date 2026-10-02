# Task Board Capstone — Sistem Informasi Apotek

**Tanggal snapshot:** 2 Oktober 2026  
**Sumber acuan:** [PRD](./PRD_Capstone_Apotek.md), [Workflow 12 Minggu](./workflow_capstone_apotek_12_minggu.md), source code `backend/` dan `frontend/`, serta [README](./README.md).  
**Tujuan:** mencatat pekerjaan yang sudah tampak pada project, pekerjaan yang masih parsial, dan pekerjaan yang belum memiliki bukti penyelesaian.

> **Cara membaca status:** board ini dibuat dari file dan kode yang tersedia di folder project, bukan dari konfirmasi anggota tim atau mitra. “Terimplementasi” berarti ada bukti implementasi di source code; status tersebut **tidak otomatis berarti sudah diuji di lingkungan mitra, disetujui mitra, atau siap rilis**. Status “Belum ada bukti” berarti deliverable/hasilnya tidak ditemukan atau belum dapat dipastikan dari folder ini.

## Ringkasan status

| Area | Status snapshot | Catatan |
|---|---|---|
| Fondasi web, backend API, database, dan autentikasi | **Terimplementasi** | Next.js + TypeScript dan Laravel 12; API menggunakan Sanctum. |
| Data dua apotek dan pembatasan akses | **Terimplementasi; perlu verifikasi release** | Implementasi dan test feature untuk cakupan apotek tersedia. Hasil test terakhir belum dicatat di board ini. |
| Inventaris dan mutasi stok | **Terimplementasi** | Produk, kategori/satuan, stok, batch/kedaluwarsa, dan mutasi tersedia. |
| Kasir, transaksi multi-item, dan perubahan stok | **Terimplementasi; perlu tutup acceptance** | Endpoint transaksi dan test stok/batch tersedia; validasi bisnis final serta hasil UAT belum terlihat. |
| Riwayat dan laporan | **Terimplementasi sebagian** | Riwayat penjualan dan ringkasan/grafik laporan ada; cakupan laporan final tetap perlu disepakati. |
| Fitur tambahan | **Sebagian sudah dibuat** | Supplier, purchase order, pelanggan, permintaan obat, audit log, serta konsultasi/triase muncul di aplikasi. Sebagian belum ditetapkan sebagai MVP PRD. |
| Observasi mitra, baseline SRS, UML/ERD, dan traceability | **Belum ada bukti yang cukup** | PRD dan workflow tersedia; keluaran analisis/desain lain tidak ditemukan pada daftar file project yang ditinjau. |
| Pengujian formal, UAT, deployment, dan final release | **Belum terkonfirmasi** | Test suite tersedia, tetapi hasil eksekusi, UAT mitra, hosting, dan release evidence belum tampak. |

**Kesimpulan singkat:** secara implementasi, project sudah memiliki bagian besar dari MVP inti dan beberapa fitur ekstra. Fokus kerja berikutnya sebaiknya bukan menambah fitur baru, melainkan mengunci kebutuhan bisnis, menutup celah penerimaan, menjalankan dan mendokumentasikan pengujian, melakukan UAT, lalu memastikan deployment dan dokumen final.

## Progress snapshot

> Persentase berikut adalah **estimasi progres berdasarkan bukti yang ditemukan pada kode/dokumen per tanggal snapshot**, bukan hasil pelaporan tim atau persetujuan mitra. Fitur yang sudah dikodekan tetapi belum memiliki bukti pengujian/UAT/deployment belum diberi 100%.

| Area progres | Estimasi | Loading |
|---|---:|---|
| Implementasi fitur inti MVP | **80%** | `[########--]` |
| Verifikasi dan acceptance fitur inti | **55%** | `[######----]` |
| Kesiapan release (UAT, deployment, backup, dokumen final) | **25%** | `[###-------]` |
| Deliverable workflow/akademik (validasi, SRS final, UML/ERD, test report) | **35%** | `[####------]` |

**Interpretasi:** kode aplikasi sudah cukup jauh, tetapi kemajuan penyelesaian capstone secara keseluruhan masih tertahan oleh validasi kebutuhan, bukti pengujian/UAT, dan release. Angka per area memakai estimasi terpisah, bukan rata-rata matematis dari seluruh baris task.

## Status yang digunakan

- **DONE — kode tersedia:** fitur/artefak tampak di source atau dokumen. Masih perlu acceptance/test bila belum ada buktinya.
- **PARTIAL — sebagian:** implementasi ada tetapi cakupan atau penerimaannya belum lengkap/terkonfirmasi.
- **TODO — belum terbukti:** hasil yang diperlukan tidak ditemukan atau belum dapat dipastikan dari project.
- **BACKLOG — bukan Must Have saat ini:** hanya dikerjakan setelah scope dikonfirmasi dan tidak mengganggu MVP.

## Board kebutuhan produk (PRD)

### Must Have — MVP

| ID / pekerjaan | Status | Progres | Bukti saat ini | Sisa pekerjaan / kriteria selesai |
|---|---|---:|---|---|
| AUTH-01 Login, setup owner pertama, session, logout | **DONE — kode tersedia** | `[########--] 80%` | API setup/login/logout, frontend auth screen, session Sanctum. | Jalankan test login valid/invalid, session, logout, serta smoke test pada browser target; simpan hasilnya. |
| AUTH-02 Akun terhubung ke apotek | **DONE — kode tersedia** | `[#######---] 70%` | User menyimpan cakupan apotek; setup menyiapkan Salam Sehat dan Badan Sehat. | Pastikan seluruh akun demo/operasional memiliki apotek yang benar; dokumentasikan aturan provisioning akun. |
| AUTH-03 Isolasi data dua apotek | **DONE — implementasi + test tersedia** | `[########--] 80%` | Scope diterapkan pada produk, penjualan, pelanggan, permintaan, laporan, dan data lain; `ApiAccessTest`/`CashierAuthorizationTest` berisi skenario lintas apotek. | Jalankan test suite dan simpan hasil; lakukan test negatif pada endpoint baca **dan** mutasi dengan akun kedua apotek sebelum release. |
| AUTH-04/05 Proteksi halaman/API dan logout | **DONE — kode tersedia** | `[########--] 80%` | Middleware `auth:sanctum`, middleware user aktif, endpoint logout. | Verifikasi akses tanpa sesi dan sesi setelah logout pada test/browser; dokumentasikan bukti. |
| INV-01–06 Master produk/barang: daftar, cari, tambah, ubah, hapus, lihat stok | **DONE — kode tersedia** | `[########--] 80%` | API resource produk dan layar inventaris; pencarian berdasarkan nama/SKU/barcode disebut README. | Pastikan acceptance CRUD termasuk aturan hapus dan pencarian di test/UAT; validasi field master obat dengan mitra. |
| STK-01 Stok per apotek | **DONE — kode tersedia** | `[########--] 80%` | Produk memiliki `pharmacy_id`; endpoint membatasi produk berdasar role/cabang. | Lakukan smoke test data kedua apotek dan catat hasilnya. |
| STK-02/03 Stok berubah hanya saat transaksi sukses | **DONE — kode + test tersedia** | `[########--] 80%` | Checkout transaksional; test checkout sukses dan gagal tanpa partial writes. | Jalankan test terbaru dan lampirkan output pada test report. |
| STK-04 Penjualan melebihi stok ditolak | **DONE — kode + test tersedia** | `[########--] 80%` | Skenario stok tidak cukup ada di `ApiAccessTest` dan `BatchAwareCheckoutTest`. | Pastikan pesan validasi dipahami kasir dan skenario diuji pada UI end-to-end. |
| STK-05 Multi-item konsisten | **DONE — implementasi tersedia; acceptance perlu verifikasi** | `[#######---] 70%` | Keranjang multi-item, checkout, pencatatan item dan mutasi stok. | Uji satu transaksi beberapa item: satu item invalid/stok kurang harus membatalkan seluruh transaksi tanpa stok parsial. Simpan hasil. |
| SAL-01–04 Kasir, pencarian barang, jumlah, transaksi multi-item | **DONE — kode tersedia** | `[########--] 80%` | Layar kasir dan API penjualan; beberapa item dapat disimpan dalam satu checkout. | Verifikasi langkah lengkap kasir pada browser dengan data yang disepakati. |
| SAL-05/06 Subtotal dan total sesuai aturan bisnis | **PARTIAL — implementasi ada, aturan final belum terbukti** | `[#####-----] 50%` | Harga, pembayaran, dan kembalian tersedia; ada beberapa metode pembayaran. | Mitra harus menetapkan aturan eceran/satuan, pembulatan, pajak, diskon, serta nominal pembayaran. Cocokkan formula UI dan backend dengan keputusan tertulis. |
| SAL-07/08 Header/detail transaksi dan waktu | **DONE — kode tersedia** | `[########--] 80%` | Model/API penjualan menyimpan transaksi dan item; riwayat penjualan tersedia. | Pastikan nomor/format struk atau informasi wajib tidak diasumsikan; dokumentasikan acceptance final. |
| SAL-09 Stok dikurangi sesuai transaksi | **DONE — kode + test tersedia** | `[########--] 80%` | Test transaksi dan batch memeriksa jumlah stok sebelum/sesudah checkout. | Jalankan regression test dan tambahkan bukti end-to-end transaksi → stok → riwayat. |
| RETAIL Penjualan obat eceran | **PARTIAL — alur kuantitas tersedia** | `[#####-----] 50%` | Kasir menerima quantity dan item memiliki unit; test checkout berlaku untuk quantity. | Konfirmasi dengan mitra apakah satuan eceran, pecahan kemasan, dan konversi satuan diperlukan. Test tepat sesuai aturan final. |
| REP-01 Riwayat transaksi | **DONE — kode tersedia** | `[#######---] 70%` | API daftar/detail penjualan dan tampilan kasir/riwayat. | Buktikan filter, detail yang dibutuhkan, dan isolasi data melalui test/UAT. |
| REP-03/04 Laporan inventaris dan penjualan | **PARTIAL — ringkasan penjualan/inventaris tersedia** | `[######----] 60%` | API laporan memberikan rekap penjualan harian, produk terlaris, nilai inventaris, stok rendah; frontend menampilkan grafik/laporan. | Tetapkan jenis laporan, periode/filter, format, dan angka yang dianggap wajib oleh mitra. Cocokkan angka report terhadap data uji. |
| VAL Error handling dan validasi input | **PARTIAL — ada di endpoint/form** | `[######----] 60%` | Validasi Laravel dan validasi frontend terlihat di beberapa modul. | Buat matriks kasus normal/negatif untuk semua Must Have; verifikasi error dapat dipahami dan tidak ada penulisan data parsial. |

### Nonfungsional, tambahan, dan keputusan scope

| Pekerjaan | Status | Rincian |
|---|---|---|
| Keamanan password/session | **DONE — implementasi tersedia** | Login/session Sanctum dan hashing Laravel digunakan. Verifikasi akhir tetap menjadi bagian test release. |
| Batas akses lintas-apotek | **DONE — implementasi + beberapa test tersedia** | Harus diuji pada semua endpoint yang membaca/mengubah data, bukan hanya navigasi UI. |
| Integritas transaksi dan stok | **DONE — implementasi + beberapa test tersedia** | Checkout memakai transaksi database dan test menolak penjualan yang tidak valid. Bukti eksekusi terkini masih harus direkam. |
| Performa pada data dan hosting nyata | **TODO** | Belum ada benchmark target atau hasil ukur. Tentukan volume data dan lingkungan target bersama mitra, kemudian ukur halaman/pencarian/laporan. |
| Backup dan pemulihan | **TODO** | README memuat setup lokal, tetapi prosedur backup/restore untuk environment target belum terkonfirmasi. |
| Role/akses sesuai PRD | **PARTIAL — perlu rekonsiliasi** | PRD menggambarkan role pengguna yang seragam berdasarkan apotek; aplikasi memiliki Owner, Admin cabang, Kasir, Admin Gudang, dan Supplier. Minta persetujuan mitra atas matriks role dan perbarui PRD/SRS serta test. |
| Supplier, purchase order, penerimaan batch | **DONE — fitur ekstra di luar baseline MVP** | Modul dan test purchase/batch tersedia. Pertahankan bila dibutuhkan mitra; pastikan tidak menunda penerimaan MVP. |
| Pelanggan dan kaitan pelanggan ke transaksi | **DONE — implementasi tersedia; keputusan data perlu dicatat** | Modul pelanggan dan checkout pelanggan ada. Konfirmasi data yang benar-benar diperlukan dan siapa yang berhak melihatnya. |
| Permintaan obat pelanggan | **DONE — fitur tambahan** | Permintaan bisa dicatat kasir dan status diproses admin; test workflow tersedia. Putuskan apakah bagian MVP atau tambahan versi berikutnya. |
| Member/loyalty/rekomendasi customer | **PARTIAL / perlu keputusan** | Pelanggan dan konsultasi ada, namun itu belum membuktikan skema member/loyalty sesuai usulan PRD. Tetapkan keputusan scope, data, akses, dan kriteria sebelum menyebut requirement ini selesai. |
| Konsultasi/triase berbantuan AI | **BACKLOG / perlu persetujuan scope** | Endpoint/layar konsultasi tersedia, tetapi tidak dirinci sebagai Must Have pada PRD; informasi kesehatan sensitif perlu tujuan, consent, batas penggunaan, dan persetujuan mitra yang eksplisit. |
| Payment gateway, BPJS, resep digital, pemesanan otomatis pemasok | **BACKLOG / OUT OF SCOPE** | Tidak perlu dikerjakan untuk MVP sesuai PRD, kecuali ada change request yang disetujui. |

## Task board mengikuti Workflow 12 Minggu

> Minggu di bawah mengikuti urutan workflow. Tanggal migrasi September/Oktober dan tanggal snapshot saja tidak cukup untuk menentukan minggu proyek saat ini.

| Minggu | Fokus dan deliverable workflow | Status dari bukti project | Progres | Task tersisa / kriteria penutupan |
|---|---|---|---:|---|
| **1 — Observasi dan validasi mitra** | Catatan observasi dua apotek, wawancara, As-Is, masalah dan kebutuhan tervalidasi. | **TODO — bukti tidak ditemukan** | `[----------] 0%` | Lampirkan catatan/tanggal observasi, narasumber, alur transaksi/stok nyata, dan daftar keputusan/pertanyaan. Jika sudah dilakukan, masukkan evidence ke dokumentasi proyek. |
| **2 — Requirement dan scope baseline** | SRS disetujui, requirement list, prioritas, acceptance criteria, scope freeze awal. | **PARTIAL** — PRD tersedia dan cukup rinci, tetapi status draft dan belum ada bukti approval/SRS final. | `[#####-----] 50%` | Validasi requirement dengan mitra; buat atau finalisasi SRS; catat sign-off dan keputusan formula transaksi, role, laporan, data historis, member, dan eceran. |
| **3 — Analisis dan desain** | UML, ERD, wireframe, user flow, arsitektur dan spesifikasi teknis. | **PARTIAL / bukti dokumen tidak ditemukan** — implementasi memberi gambaran rancangan, tetapi bukan pengganti deliverable desain. | `[##--------] 20%` | Tambahkan Use Case, Activity/Sequence yang relevan, ERD, arsitektur, wireframe, dan traceability; samakan dengan sistem aktual. |
| **4 — Setup proyek dan fondasi** | Repository, environment, database awal, skeleton, autentikasi dasar. | **DONE — implementasi tersedia** | `[########--] 80%` | README menyediakan langkah setup lokal; konfirmasi semua anggota dapat menjalankan di environment bersih dan dokumentasikan versi/prasyarat serta hasil onboarding. |
| **5 — Akun, apotek, inventaris** | Pengguna/apotek, pemisahan akses, CRUD produk, stok, validasi. | **DONE — implementasi tersedia** | `[########--] 80%` | Tutup verifikasi per-role, uji silang dua apotek, validasi katalog/field produk bersama mitra, serta lampirkan hasil test. |
| **6 — Kasir dan transaksi** | Pencarian/pemilihan produk, keranjang, total, penyimpanan transaksi/detail. | **DONE — implementasi tersedia; aturan final terbuka** | `[########--] 80%` | Dapatkan keputusan tertulis untuk cara bayar, jumlah eceran, pajak/diskon/pembulatan, lalu jalankan skenario kasir end-to-end. |
| **7 — Integrasi transaksi dengan stok** | Core flow login → kasir → transaksi → stok → riwayat berjalan konsisten. | **DONE — implementasi dan skenario test tersedia** | `[########--] 80%` | Jalankan suite; lakukan demo/test dua apotek dan uji transaksi gagal, stok kurang, double submit, serta semua item transaksi secara atomik. Catat bukti milestone. |
| **8 — Riwayat dan laporan** | Riwayat, laporan inventaris/penjualan sesuai kebutuhan, angka terverifikasi. | **PARTIAL** — implementasi laporan dan riwayat terlihat, tetapi definisi laporan belum tervalidasi. | `[#######---] 70%` | Minta contoh laporan yang dibutuhkan mitra; buat data uji yang dapat dihitung manual; cocokkan setiap ringkasan dan filter terhadap hasil yang diharapkan. |
| **9 — Hardening dan feature freeze** | Bug/validasi/akses/usability diperbaiki; scope MVP dibekukan. | **PARTIAL / belum ada bukti gate** — fitur tambahan dan beberapa kontrol telah dikembangkan, tetapi tidak ada catatan feature-freeze. | `[######----] 60%` | Buat daftar Must/Should/Backlog yang disetujui, hentikan penambahan fitur besar, prioritaskan defect inti dan dokumentasikan keputusan freeze. |
| **10 — System test dan UAT** | Test case, hasil functional/integration/access/regression, UAT dan daftar bug. | **PARTIAL** — test feature backend tersedia; hasil run, test report, dan UAT mitra tidak ditemukan. | `[####------] 40%` | Jalankan backend test dan pemeriksaan frontend; buat test report per requirement; lakukan UAT dengan perwakilan mitra, catat feedback, severity, PIC, dan status perbaikan. |
| **11 — Bug fix, deployment, dokumentasi** | Critical/High defect ditutup, Release Candidate terpasang, user/technical/test docs. | **TODO — deployment tidak terkonfirmasi** | `[##--------] 20%` | Tentukan hosting/domain/DB, secret dan konfigurasi aman, backup/restore, deploy staging/production, smoke test pada Chrome/Windows dan buat panduan pengguna/teknis. |
| **12 — Finalisasi, demo, dan presentasi** | Final regression, release, dokumen akademik, demo, presentasi, backup. | **TODO — bukti release belum ditemukan** | `[#---------] 10%` | Selesaikan checklist final, catat versi rilis, hasil smoke/regression, skenario demo, slide/laporan, backup source/database, dan persetujuan serah-terima. |

## Pekerjaan prioritas berikutnya

| Prioritas | Task | Definisi selesai |
|---|---|---|
| **P0** | Validasi scope dan aturan bisnis bersama mitra | Keputusan tertulis untuk role, transaksi eceran/satuan, formula total, metode bayar, laporan, pelanggan/member, dan kebutuhan stok masuk tersedia; PRD/SRS diperbarui. |
| **P0** | Menyusun matriks traceability PRD → implementasi → test | Setiap FR Must Have memiliki lokasi implementasi, test case, hasil terakhir, dan status; gap yang belum ada diberi PIC. |
| **P0** | Menjalankan dan merekam test suite terkini | Backend test dan lint/build frontend berhasil atau seluruh kegagalan tercatat sebagai bug; bukti masuk test report. |
| **P0** | Uji penerimaan alur inti pada dua apotek | Login/akses, CRUD barang, transaksi multi-item, stok kurang, kegagalan transaksi, riwayat, serta isolasi data lulus di lingkungan yang menyerupai target. |
| **P1** | Menutup kebutuhan laporan dan acceptance report | Metrik/format laporan disetujui mitra dan angka diuji dengan dataset terkontrol. |
| **P1** | Menyiapkan deployment dan backup/restore | Release candidate dapat dibuka pada hosting target, smoke test lulus, dan langkah backup/restore berhasil diuji. |
| **P1** | Melengkapi dokumentasi desain dan operasional | SRS, UML, ERD, panduan pengguna, dokumentasi teknis, test report, UAT report, dan catatan deployment sesuai dengan versi implementasi. |
| **P2** | Tinjau fitur ekstra terhadap scope dan data sensitif | Supplier/PO, permintaan obat, member, serta konsultasi/triase diberi keputusan keep/defer; perubahan requirement disetujui sebelum pengembangan lanjutan. |

## Bukti implementasi yang ditemukan

- Backend Laravel API mencakup autentikasi, user, produk, sales, laporan, supplier, customer, purchase, mutasi stok, permintaan obat, konsultasi, dan audit log.
- Frontend Next.js memiliki layar autentikasi, dashboard, inventaris, kasir, laporan, manajemen pengguna, supplier, pembelian, pelanggan, mutasi stok, permintaan obat, serta audit.
- Migrasi membentuk data apotek, produk, transaksi, batch inventaris, pelanggan, purchase order, metode/pencatatan pembayaran, audit log, dan permintaan obat.
- Test feature mencakup isolasi data, otorisasi kasir, checkout dan stok, batch/kedaluwarsa, pembelian, pelanggan/pembayaran, audit trail, data fondasi, permintaan obat, serta konsultasi.
- README menjelaskan cara menjalankan aplikasi **secara lokal** dan daftar role/fitur. Instruksi lokal bukan bukti bahwa aplikasi telah dideploy ke hosting target.

## Catatan penting untuk update board

1. Ganti status **TODO / belum ada bukti** apabila tim memiliki bukti yang belum disimpan di folder project; tambahkan tautan atau lokasi evidence.
2. Setelah test dijalankan, isi tanggal, commit/versi yang diuji, jumlah lulus/gagal, dan link test report.
3. Beri setiap task terbuka **PIC dan due date** pada board kerja tim. PIC tidak ditentukan di sini karena tidak ada penugasan aktual yang dapat diverifikasi.
4. Jika hasil validasi mengubah scope, perbarui PRD/SRS dan traceability terlebih dahulu; jangan hanya mengubah UI/kode.
5. Jangan menyatakan MVP/final release selesai sebelum Must Have lulus acceptance, isolasi apotek dibuktikan, UAT/deployment terdokumentasi, dan tidak ada defect kritis yang terbuka.

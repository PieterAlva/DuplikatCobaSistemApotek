# Workflow Pengerjaan Capstone Project
## Optimasi dan Digitalisasi Penerapan Sistem Inventaris dan Kasir pada Apotek

**Durasi:** ±12 minggu / 1 semester  
**Tim:** 4 mahasiswa  
**Mitra:** PT. Indah Berkat Usaha  
**Produk:** Sistem berbasis web untuk pengelolaan inventaris dan transaksi penjualan pada dua apotek milik mitra.

---

## 1. Dasar Perencanaan

Workflow ini disusun berdasarkan proposal/SRS yang saat ini dimiliki tim. Proposal menetapkan bahwa sistem berfokus pada pengelolaan inventaris, stok, transaksi kasir/penjualan termasuk penjualan obat eceran, pengelolaan dua apotek dalam satu platform, serta pemisahan data berdasarkan apotek dan akun pengguna. Proposal juga membatasi sistem agar tidak mengelola distribusi obat ke apotek lain atau seluruh operasional perusahaan. [1]

Proposal mencatat dua apotek milik mitra, yaitu **Apotek Badan Sehat** dan **Apotek Salam Sehat**, serta hasil wawancara awal yang menunjukkan bahwa kebutuhan utama berkaitan dengan manajemen stok dan transaksi, khususnya transaksi obat secara eceran. Mitra juga mengusulkan fitur member/rekomendasi obat. Karena fitur tersebut masih berupa usulan dan berpotensi menambah kompleksitas, keputusan memasukkannya sebagai fitur inti harus dilakukan setelah observasi dan validasi kebutuhan. [1]

### Prinsip pengerjaan

1. **Requirement terlebih dahulu, coding kemudian.**
2. **Observasi mitra menjadi dasar validasi kebutuhan.**
3. **MVP diprioritaskan** agar sistem realistis selesai dalam 12 minggu.
4. **Semua anggota bekerja paralel setelah requirement stabil**, tetapi setiap pekerjaan tetap memiliki PIC yang jelas.
5. **Testing dilakukan sepanjang proses**, bukan hanya pada minggu terakhir.
6. **Integrasi dilakukan bertahap**, bukan menunggu semua modul selesai.
7. Setiap perubahan requirement harus dicatat agar **scope creep** dapat dikendalikan.
8. Setiap fitur harus dapat ditelusuri dari **masalah → kebutuhan → requirement → implementasi → test case**.

---

# 2. Target MVP

Target minimum yang harus selesai sebelum akhir semester:

- Login dan autentikasi pengguna.
- Pengaitan akun dengan salah satu apotek.
- Pembatasan akses data berdasarkan apotek.
- Pengelolaan data obat/barang.
- Pengelolaan stok.
- Transaksi penjualan melalui kasir.
- Transaksi obat eceran.
- Perhitungan transaksi sesuai aturan bisnis yang disepakati.
- Pengurangan/pembaruan stok setelah transaksi berhasil.
- Riwayat transaksi.
- Laporan inventaris dan transaksi sesuai kebutuhan mitra.
- Pengelolaan dua apotek dalam satu platform dengan data tetap terpisah.
- Validasi input dan penanganan kondisi transaksi yang tidak valid.
- Pengujian sistem dan perbaikan bug.
- Deployment/presentasi versi yang dapat didemonstrasikan.

### Fitur yang belum otomatis menjadi MVP

Fitur **member/rekomendasi obat** merupakan usulan mitra yang tercatat dalam proposal. Fitur ini jangan langsung dikembangkan sebelum observasi menentukan kebutuhan, aturan bisnis, data yang diperlukan, dan kelayakannya dalam waktu proyek. [1]

Fitur seperti payment gateway, integrasi BPJS, validasi resep digital, atau pemesanan otomatis ke pemasok juga tidak boleh tiba-tiba masuk ke MVP tanpa keputusan scope yang jelas. Proposal saat ini memang mencantumkan beberapa fitur tersebut sebagai fitur yang ditiadakan. [1]

---

# 3. Pembagian Peran Tim

Pembagian mengikuti jobdesk pada proposal, dengan penyesuaian workflow agar pekerjaan tidak terlalu terkotak-kotak.

| Anggota | Tanggung Jawab Utama | Tanggung Jawab Pendukung |
|---|---|---|
| **Alexander Arthur Bimo Satriaji** | Project Manager + Backend Developer | Database, integrasi backend, koordinasi teknis |
| **Pieter Alva Pradana** | Frontend Developer + UML | Integrasi UI dengan backend, dokumentasi analisis/desain |
| **Roman Adi Surya** | Frontend Developer + UI/UX | Design system, usability, integrasi frontend |
| **Samuel Latuihamallo** | Support UI/UX | Dokumentasi, support frontend, data uji, testing support |
| **Seluruh anggota** | Testing | Code review, bug fixing, dokumentasi, presentasi |

Pembagian ini tetap fleksibel. Pada fase analisis, testing, integrasi, dan deployment, pekerjaan sebaiknya dilakukan bersama agar pengetahuan sistem tidak hanya dimiliki oleh satu orang.

---

# 4. Workflow 12 Minggu

## Minggu 1 — Validasi Masalah dan Observasi Mitra

### Tujuan
Memastikan tim memahami kondisi nyata operasional kedua apotek sebelum requirement dikunci.

### Aktivitas
- Melaksanakan observasi ke **Apotek Badan Sehat** dan **Apotek Salam Sehat**.
- Mengamati alur kerja pengelolaan barang/stok.
- Mengamati proses transaksi penjualan/kasir, terutama transaksi eceran.
- Mengidentifikasi bagaimana stok dicatat, diperbarui, dan diperiksa.
- Mengidentifikasi dokumen/data yang digunakan.
- Mengidentifikasi siapa yang melakukan setiap aktivitas.
- Menggali kebutuhan terkait laporan.
- Mengklarifikasi kebutuhan pemisahan data kedua apotek.
- Mengklarifikasi usulan fitur member/rekomendasi.
- Mencatat masalah aktual, bukan asumsi tim.

### Output
- Catatan observasi.
- Hasil wawancara.
- Flow proses bisnis berjalan (**As-Is**).
- Daftar masalah.
- Daftar kebutuhan awal.
- Daftar pertanyaan/kebutuhan yang masih belum jelas.

### Gate
**Jangan mengunci database dan coding utama sebelum masalah dan alur bisnis utama tervalidasi.**

---

## Minggu 2 — Analisis Kebutuhan dan Finalisasi Scope

### Tujuan
Mengubah hasil observasi menjadi requirement yang jelas dan terukur.

### Aktivitas
- Menyusun kebutuhan fungsional.
- Menyusun kebutuhan nonfungsional.
- Menentukan aktor/pengguna.
- Menentukan hak akses berdasarkan akun dan apotek.
- Menentukan data yang harus dipisahkan antar-apotek.
- Menentukan alur transaksi.
- Menentukan aturan perubahan stok.
- Menentukan kebutuhan laporan.
- Menentukan apakah member/rekomendasi masuk MVP, fitur tambahan, atau ditunda.
- Merevisi SRS berdasarkan hasil observasi.
- Menyusun prioritas:
  - **Must Have**
  - **Should Have**
  - **Could Have**
  - **Out of Scope**
- Membuat traceability awal: masalah → kebutuhan → fitur.

### Output
- SRS versi final/approved untuk implementasi.
- Requirement List.
- Prioritas fitur.
- Scope baseline.
- Acceptance criteria awal.

### Gate
**Setelah minggu 2, perubahan fitur baru harus melalui persetujuan tim dan dicatat sebagai change request.**

---

## Minggu 3 — Analisis Sistem dan Perancangan

### Tujuan
Menerjemahkan requirement menjadi rancangan sistem yang siap diimplementasikan.

### Aktivitas
**Pieter**
- Use Case Diagram.
- Activity Diagram proses utama.
- Class Diagram awal.
- Sequence Diagram untuk proses penting.

**Alexander**
- Perancangan arsitektur aplikasi.
- Struktur backend.
- Perancangan database.
- Relasi data pengguna, apotek, barang, stok, transaksi, dan detail transaksi.

**Roman**
- User flow.
- Wireframe.
- Desain halaman utama.
- Desain kasir.
- Desain inventaris.
- Desain laporan.

**Samuel**
- Review usability wireframe.
- Menyiapkan struktur dokumentasi.
- Membantu penyusunan skenario penggunaan dan data uji.

### Output
- UML.
- ERD/database design.
- Wireframe/prototype.
- Arsitektur sistem.
- User flow.
- Spesifikasi teknis awal.

### Gate
Review bersama: **apakah desain benar-benar menjawab requirement minggu 2?**

---

## Minggu 4 — Setup Proyek dan Fondasi Sistem

### Tujuan
Menyediakan fondasi teknis agar pengembangan modul dapat dilakukan paralel.

### Aktivitas
- Membuat repository dan struktur project.
- Menentukan branching/workflow Git.
- Menyiapkan database development.
- Membuat struktur tabel berdasarkan desain yang telah disetujui.
- Membuat koneksi database.
- Menyiapkan autentikasi dasar.
- Menyiapkan struktur backend.
- Menyiapkan layout/frontend dasar.
- Menyiapkan komponen UI.
- Menyiapkan data dummy.
- Menetapkan standar coding dan penamaan.
- Menentukan mekanisme pencatatan issue/bug.

### Output
- Repository aktif.
- Database awal.
- Skeleton aplikasi.
- Login dasar.
- Template frontend.
- Development environment siap.

### Gate
Aplikasi minimal dapat dijalankan oleh seluruh anggota tim dari environment masing-masing.

---

## Minggu 5 — Modul Pengguna, Apotek, dan Inventaris

### Tujuan
Menyelesaikan fondasi data dan pengelolaan inventaris.

### Aktivitas
- Implementasi data pengguna.
- Implementasi relasi akun dengan apotek.
- Implementasi pembatasan akses berdasarkan apotek.
- CRUD data obat/barang.
- Validasi data obat.
- Tampilan daftar barang.
- Pencarian/filter jika termasuk requirement.
- Pengelolaan stok.
- Validasi stok.
- Integrasi frontend dan backend.

### Output
- Login berjalan.
- User dapat masuk sesuai akun.
- Data apotek dapat dibedakan.
- CRUD barang berjalan.
- Stok dapat dilihat dan dikelola.
- Akses antar-apotek terisolasi.

### Testing
- Unit test/functional test modul.
- Test akses akun Apotek A terhadap data Apotek B.
- Test validasi input.

---

## Minggu 6 — Modul Kasir dan Transaksi Penjualan

### Tujuan
Membangun proses transaksi inti.

### Aktivitas
- Pencarian/pemilihan barang.
- Input jumlah barang.
- Keranjang transaksi.
- Perhitungan subtotal.
- Perhitungan pajak/komponen harga sesuai hasil observasi dan requirement.
- Perhitungan total.
- Validasi jumlah terhadap stok.
- Penyimpanan transaksi.
- Penyimpanan detail transaksi.
- Penomoran transaksi jika diperlukan.
- Pencatatan tanggal/waktu transaksi.

### Output
- Halaman kasir berjalan.
- Transaksi dapat dibuat.
- Multi-item transaction berjalan jika memang dibutuhkan.
- Detail transaksi tersimpan.

### Testing
- Transaksi satu barang.
- Transaksi beberapa barang.
- Jumlah > 1.
- Jumlah melebihi stok.
- Barang tidak ditemukan.
- Input tidak valid.
- Pembatalan transaksi.
- Transaksi berhasil tersimpan.

---

## Minggu 7 — Integrasi Transaksi dengan Stok

### Tujuan
Memastikan transaksi dan inventaris menjadi satu alur bisnis yang konsisten.

### Aktivitas
- Menghubungkan transaksi dengan pengurangan stok.
- Memastikan stok hanya berubah setelah transaksi berhasil.
- Memastikan transaksi gagal tidak menyebabkan stok berkurang.
- Menguji transaksi dari dua apotek.
- Memastikan transaksi Apotek A hanya memengaruhi stok Apotek A.
- Memastikan transaksi Apotek B hanya memengaruhi stok Apotek B.
- Menangani transaksi bersamaan/validasi stok sesuai kemampuan sistem.
- Review database dan backend.

### Output
**End-to-end flow pertama:**

`Login → Pilih/Cari Barang → Kasir → Transaksi Berhasil → Stok Berkurang → Riwayat Transaksi`

### Gate
Alur utama harus stabil sebelum tim menambah fitur noninti.

---

## Minggu 8 — Modul Riwayat dan Laporan

### Tujuan
Menyediakan informasi yang dibutuhkan untuk monitoring operasional.

### Aktivitas
- Menentukan jenis laporan berdasarkan requirement hasil observasi.
- Laporan inventaris/stok.
- Riwayat transaksi.
- Rekap transaksi sesuai kebutuhan mitra.
- Filter berdasarkan periode jika diperlukan.
- Filter berdasarkan apotek jika sesuai hak akses.
- Menentukan format tampilan laporan.
- Menguji kesesuaian angka laporan dengan database.

### Output
- Modul laporan.
- Riwayat transaksi.
- Laporan inventaris.
- Data laporan konsisten dengan transaksi dan stok.

### Testing
Bandingkan hasil laporan dengan data transaksi yang sengaja dibuat untuk pengujian.

---

## Minggu 9 — Penyelesaian Fitur Tambahan Prioritas + Hardening

### Tujuan
Menyelesaikan fitur **Should Have/Could Have** yang masih realistis tanpa mengganggu MVP.

### Aktivitas
- Review backlog.
- Memutuskan fitur tambahan yang benar-benar aman untuk dikerjakan.
- Jika fitur member/rekomendasi telah divalidasi dan masuk scope, mulai implementasi versi minimum.
- Jika belum tervalidasi, **jangan memaksakan implementasi**; gunakan waktu untuk meningkatkan kualitas MVP.
- Perbaikan validasi input.
- Perbaikan error handling.
- Perbaikan hak akses.
- Perbaikan usability.
- Perbaikan performa query sederhana bila diperlukan.

### Output
- MVP feature-complete.
- Fitur tambahan yang disetujui selesai atau backlog ditutup.
- Security/validation baseline.

### Gate
**Akhir minggu 9 = feature freeze untuk MVP.**

Setelah feature freeze, fokus utama bergeser dari menambah fitur ke **testing, stabilisasi, dokumentasi, dan deployment**.

---

## Minggu 10 — System Testing dan User Acceptance Test

### Tujuan
Menguji sistem secara menyeluruh dan memastikan sistem sesuai kebutuhan mitra.

### Aktivitas
- Menyusun test case berdasarkan requirement.
- Functional testing.
- Integration testing.
- System testing.
- Access control testing.
- Boundary/negative testing.
- Regression testing.
- Pengujian pada dua apotek/data scope.
- Simulasi alur kerja kasir.
- Simulasi pengelolaan inventaris.
- UAT dengan pihak mitra jika memungkinkan.
- Mencatat seluruh bug dan feedback.

### Output
- Test case.
- Test result.
- Bug list.
- UAT feedback.
- Daftar perbaikan prioritas.

### Prioritas bug
1. **Critical:** sistem tidak dapat digunakan / data rusak.
2. **High:** fungsi utama salah.
3. **Medium:** fungsi masih berjalan tetapi ada masalah.
4. **Low:** masalah minor pada UI/dokumentasi.

---

## Minggu 11 — Bug Fixing, Deployment, dan Dokumentasi

### Tujuan
Menyiapkan versi sistem yang stabil untuk demonstrasi/presentasi.

### Aktivitas
- Memperbaiki bug Critical dan High.
- Regression testing setelah setiap perbaikan penting.
- Menyiapkan server hosting.
- Konfigurasi database production.
- Deployment aplikasi.
- Pengujian pada environment production.
- Menyiapkan data demo.
- Menyusun panduan penggunaan.
- Menyusun dokumentasi teknis.
- Finalisasi UML dan SRS agar sesuai implementasi.
- Menyusun dokumentasi pengujian.

### Output
- Release Candidate.
- Sistem dapat diakses pada environment deployment.
- Database production siap.
- Dokumentasi pengguna.
- Dokumentasi teknis.
- Dokumentasi testing.

### Gate
**Tidak boleh ada Critical Bug yang diketahui pada Release Candidate.**

---

## Minggu 12 — Finalisasi, Demo, Evaluasi, dan Presentasi

### Tujuan
Menutup proyek dengan sistem, dokumentasi, dan presentasi yang konsisten.

### Aktivitas
- Final regression testing.
- Smoke test sebelum demo.
- Verifikasi semua fungsi MVP.
- Verifikasi data dua apotek.
- Verifikasi login dan pembatasan akses.
- Verifikasi transaksi → stok.
- Verifikasi laporan.
- Finalisasi SRS.
- Finalisasi UML.
- Finalisasi laporan proyek.
- Menyiapkan slide presentasi.
- Menyiapkan skenario demo.
- Menyiapkan pembagian presentasi 4 anggota.
- Simulasi presentasi.
- Menyiapkan backup database dan source code.
- Evaluasi hasil terhadap kebutuhan awal mitra.

### Output
- Final Release.
- SRS final.
- UML final.
- Dokumentasi testing.
- User guide.
- Laporan Capstone.
- Slide presentasi.
- Demo scenario.
- Backup source code dan database.

---

# 5. Timeline Ringkas

| Minggu | Fokus | Milestone |
|---|---|---|
| **1** | Observasi & validasi masalah | As-Is + daftar masalah |
| **2** | Requirement & scope | SRS baseline |
| **3** | Analisis & desain | UML + ERD + UI/UX |
| **4** | Setup & fondasi | Skeleton + DB + login dasar |
| **5** | User + inventaris | Modul inventaris selesai |
| **6** | Kasir + transaksi | Modul transaksi selesai |
| **7** | Integrasi stok | End-to-end transaksi → stok |
| **8** | Riwayat + laporan | Modul laporan selesai |
| **9** | Fitur tambahan + hardening | **Feature Freeze / MVP complete** |
| **10** | Testing + UAT | Bug list + feedback mitra |
| **11** | Bug fix + deployment | Release Candidate |
| **12** | Finalisasi + presentasi | **Final Release** |

---

# 6. Pola Kerja Mingguan Tim

Setiap minggu sebaiknya menggunakan siklus yang sama:

```text
PLAN
  ↓
DEFINE TASK
  ↓
IMPLEMENT / ANALYZE
  ↓
INTEGRATE
  ↓
TEST
  ↓
REVIEW
  ↓
DOCUMENT
  ↓
NEXT WEEK
```

### Awal minggu
- Meeting singkat 30–60 menit.
- Review target minggu tersebut.
- Membagi task.
- Menentukan PIC dan deadline.
- Memeriksa dependency antaranggota.

### Tengah minggu
- Masing-masing mengerjakan task.
- Commit secara berkala.
- Melaporkan blocker.
- Tidak menunggu sampai akhir minggu untuk memberi tahu masalah.

### Akhir minggu
- Merge/integrasi.
- Testing.
- Review hasil terhadap target.
- Mencatat bug.
- Memperbarui dokumentasi.
- Menentukan status:
  - Done
  - In Progress
  - Blocked
  - Deferred

---

# 7. Aturan Git dan Integrasi yang Disarankan

Gunakan repository bersama dengan workflow sederhana:

```text
main
  │
  ├── develop
  │     ├── feature/backend-...
  │     ├── feature/frontend-...
  │     ├── feature/uiux-...
  │     └── feature/testing-...
  │
  └── release
```

### Aturan dasar
- `main` hanya berisi versi yang stabil.
- Setiap fitur dikerjakan pada branch sendiri.
- Jangan langsung melakukan perubahan besar pada `main`.
- Pull/update repository sebelum mulai bekerja.
- Commit harus menjelaskan perubahan.
- Sebelum merge, lakukan pengecekan dan testing.
- Setelah merge fitur besar, anggota lain melakukan pull dan menguji ulang.

---

# 8. Mekanisme Pengendalian Scope

Karena proyek hanya ±12 minggu dan dikerjakan 4 mahasiswa, scope harus dikendalikan secara ketat.

Gunakan aturan:

```text
Ada permintaan fitur baru
        ↓
Apakah kebutuhan tersebut tervalidasi?
        ↓
      Ya / Tidak
        ↓
Apakah termasuk MVP?
        ↓
      Ya → Prioritaskan
        ↓
Tidak → Apakah waktu dan resource mencukupi?
        ↓
     Ya → Backlog / Could Have
     Tidak → Out of Scope
```

### Jangan menerima fitur baru hanya karena:
- terlihat menarik,
- mudah dibuat,
- diminta secara spontan tanpa analisis,
- ingin membuat sistem terlihat lebih banyak fitur.

Setiap fitur baru harus menjawab:
1. Masalah apa yang diselesaikan?
2. Siapa penggunanya?
3. Apa alur bisnisnya?
4. Data apa yang diperlukan?
5. Apakah ada dampak terhadap database?
6. Apakah ada dampak terhadap hak akses?
7. Berapa estimasi waktu pengerjaannya?
8. Apakah mengancam penyelesaian MVP?

---

# 9. Traceability yang Harus Dijaga

Buat satu tabel yang terus diperbarui:

| ID Masalah | Masalah | Requirement | Fitur | Test Case | Status |
|---|---|---|---|---|---|
| P-01 | Pencatatan stok tidak memadai | FR-01 | Modul Inventaris | TC-01 | |
| P-02 | Transaksi eceran masih manual | FR-02 | Modul Kasir | TC-02 | |
| P-03 | Stok harus berubah setelah transaksi | FR-03 | Integrasi Transaksi-Stok | TC-03 | |
| P-04 | Dua apotek harus tetap terpisah | FR-04 | Pharmacy Data Scope | TC-04 | |
| P-05 | Membutuhkan informasi transaksi | FR-05 | Riwayat/Laporan | TC-05 | |

**Catatan:** ID dan isi final harus disesuaikan kembali dengan hasil observasi minggu 1 dan requirement final minggu 2.

---

# 10. Risiko Utama dan Mitigasinya

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Requirement berubah setelah coding | Tinggi | Finalisasi baseline pada minggu 2 |
| Scope terlalu besar | Tinggi | MVP + feature freeze minggu 9 |
| Observasi kurang lengkap | Tinggi | Dokumentasikan pertanyaan dan lakukan klarifikasi |
| Backend dan frontend tidak sinkron | Tinggi | Tentukan kontrak data/API lebih awal |
| Bug ditemukan terlalu akhir | Tinggi | Testing mulai sejak modul pertama selesai |
| Integrasi gagal | Tinggi | Integrasi bertahap mulai minggu 5–7 |
| Data mitra belum siap | Sedang | Gunakan data dummy untuk development; finalisasi data bersama mitra |
| Fitur member/rekomendasi terlalu kompleks | Tinggi | Validasi dahulu; jadikan optional/backlog bila belum jelas |
| Satu anggota menjadi bottleneck | Tinggi | Pair work, dokumentasi, dan knowledge sharing |
| Deployment bermasalah | Sedang | Mulai persiapan deployment sebelum minggu 11 |
| Dokumentasi tertunda | Sedang | Dokumentasi dilakukan bersamaan dengan pengerjaan |

---

# 11. Definition of Done

Sebuah fitur dianggap **Done** jika:

- [ ] Requirement fitur sudah jelas.
- [ ] UI sudah dibuat jika diperlukan.
- [ ] Backend sudah diimplementasikan jika diperlukan.
- [ ] Database sudah terintegrasi jika diperlukan.
- [ ] Validasi input tersedia.
- [ ] Error handling dasar tersedia.
- [ ] Frontend dan backend sudah terintegrasi.
- [ ] Fitur sudah diuji.
- [ ] Tidak menimbulkan bug pada fitur utama lain.
- [ ] Code sudah masuk repository utama melalui proses integrasi.
- [ ] Dokumentasi terkait sudah diperbarui.

---

# 12. Definition of Done untuk Final Project

Proyek dianggap siap final apabila:

- [ ] Login berhasil.
- [ ] Akun terasosiasi dengan apotek yang benar.
- [ ] Data Apotek Badan Sehat dan Apotek Salam Sehat tetap terpisah.
- [ ] Pengguna tidak dapat mengakses data apotek yang bukan cakupan akunnya.
- [ ] Data obat/barang dapat dikelola.
- [ ] Stok dapat dipantau.
- [ ] Transaksi kasir dapat dilakukan.
- [ ] Transaksi eceran dapat diproses.
- [ ] Stok berubah sesuai transaksi yang berhasil.
- [ ] Transaksi gagal tidak menyebabkan perubahan stok yang tidak semestinya.
- [ ] Riwayat transaksi tersedia.
- [ ] Laporan utama tersedia sesuai requirement final.
- [ ] Validasi dan error handling utama berjalan.
- [ ] Tidak terdapat Critical Bug.
- [ ] Sistem sudah dideploy pada environment yang ditentukan.
- [ ] SRS sesuai dengan sistem yang benar-benar dibangun.
- [ ] UML sesuai dengan implementasi final.
- [ ] Test case dan hasil testing terdokumentasi.
- [ ] User guide tersedia.
- [ ] Tim siap melakukan demo/presentasi.

---

# 13. Milestone Kritis

Ada lima titik kontrol yang harus dijaga:

### Milestone 1 — Akhir Minggu 1
**"Kami sudah memahami masalah sebenarnya."**

### Milestone 2 — Akhir Minggu 2
**"Kami tahu persis apa yang akan dibangun."**

### Milestone 3 — Akhir Minggu 7
**"Core business flow sudah berjalan end-to-end."**

### Milestone 4 — Akhir Minggu 9
**"MVP sudah lengkap dan tidak ada lagi penambahan fitur besar."**

### Milestone 5 — Akhir Minggu 12
**"Sistem stabil, terdokumentasi, dapat didemokan, dan sesuai requirement."**

---

# 14. Catatan Strategis untuk Tim

Proposal saat ini masih memiliki beberapa hal yang perlu dikonfirmasi melalui observasi sebelum dianggap final, terutama detail proses bisnis aktual, kebutuhan laporan, aturan transaksi dan stok, serta kelayakan fitur member/rekomendasi. Proposal juga menyatakan bahwa kedua apotek menggunakan satu platform tetapi data inventaris dan transaksi dipisahkan berdasarkan akun/apotek. Hal ini harus menjadi perhatian utama dalam desain database, backend, dan testing. [1]

Jangan menggunakan minggu 10–12 untuk baru mulai membuat sistem. Pada saat tersebut sistem seharusnya sudah selesai secara fungsional dan tim hanya melakukan **testing → fixing → deployment → documentation → presentation**.

Prioritas proyek adalah:

```text
REQUIREMENT
    ↓
DESIGN
    ↓
CORE MVP
    ↓
INTEGRATION
    ↓
TESTING
    ↓
FIXING
    ↓
DEPLOYMENT
    ↓
DOCUMENTATION
    ↓
PRESENTATION
```

Dengan 4 anggota dan waktu ±12 minggu, strategi yang paling realistis bukan membuat sebanyak mungkin fitur, tetapi memastikan **fungsi inti inventaris + kasir + transaksi eceran + pemisahan data dua apotek benar-benar berjalan stabil dan dapat dibuktikan melalui testing.**

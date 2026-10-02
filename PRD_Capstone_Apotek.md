# Product Requirements Document (PRD)
## Optimasi dan Digitalisasi Penerapan Sistem Inventaris dan Kasir pada Apotek

**Mitra:** PT. Indah Berkat Usaha  
**Produk:** Sistem Inventaris dan Kasir Berbasis Web  
**Target penggunaan:** Apotek Badan Sehat dan Apotek Salam Sehat  
**Durasi pengembangan:** ±12 minggu / 1 semester  
**Tim:** 4 mahasiswa  
**Status dokumen:** Draft PRD untuk baseline pengembangan  
**Tanggal:** September 2026

---

## 1. Ringkasan Eksekutif

Product Requirements Document (PRD) ini mendefinisikan kebutuhan produk, tujuan, ruang lingkup, pengguna, prioritas fitur, kebutuhan fungsional dan nonfungsional, indikator keberhasilan, serta kriteria penerimaan untuk sistem **Optimasi dan Digitalisasi Penerapan Sistem Inventaris dan Kasir pada Apotek**.

Produk yang direncanakan merupakan sistem berbasis web yang digunakan oleh dua apotek milik PT. Indah Berkat Usaha dalam satu platform. Sistem berfokus pada pengelolaan data obat/barang, stok inventaris, transaksi penjualan melalui kasir, serta transaksi obat secara eceran. Kedua apotek menggunakan sistem yang sama, tetapi data inventaris dan transaksi tetap terpisah berdasarkan apotek yang terasosiasi dengan akun pengguna.

PRD ini disusun berdasarkan proposal proyek/SRS yang telah dibuat sebelumnya dan workflow pengembangan 12 minggu. PRD berfungsi sebagai jembatan antara permasalahan mitra, kebutuhan pengguna, ruang lingkup produk, implementasi, dan pengujian. Detail teknis seperti struktur tabel database, endpoint API, bahasa pemrograman, dan desain kode tidak ditetapkan secara berlebihan di PRD karena akan ditentukan pada tahap desain teknis.

> **Catatan validasi:** Beberapa kebutuhan, terutama detail alur operasional, aturan transaksi, laporan yang benar-benar dibutuhkan, serta fitur member/rekomendasi, harus dikonfirmasi melalui hasil observasi dan wawancara mitra sebelum dianggap sebagai requirement final.

---

## 2. Latar Belakang dan Permasalahan

PT. Indah Berkat Usaha merupakan perusahaan yang bergerak di bidang farmasi dan mengelola dua apotek, yaitu **Apotek Badan Sehat** dan **Apotek Salam Sehat**. Berdasarkan proposal, kedua apotek belum memiliki sistem manajemen stok dan transaksi yang memadai, terutama untuk melayani pembelian obat secara eceran. Mitra juga telah menyampaikan usulan mengenai sistem member/rekomendasi bagi customer yang kembali berobat atau mencari obat tertentu.

Mitra telah memiliki sistem lokal untuk manajemen stok, inventaris, dan karyawan pada tingkat tertentu. Oleh karena itu, produk capstone ini tidak dimaksudkan untuk mengelola seluruh aktivitas PT. Indah Berkat Usaha, melainkan difokuskan pada kebutuhan operasional inventaris dan transaksi penjualan kedua apotek yang menjadi objek proyek.

### 2.1 Masalah Utama

| ID | Masalah | Dampak yang Perlu Ditangani |
|---|---|---|
| P-01 | Pengelolaan stok dan inventaris belum memadai | Informasi stok sulit dikelola dan dipantau secara terstruktur |
| P-02 | Transaksi penjualan, terutama obat eceran, masih membutuhkan proses manual | Risiko kesalahan dan beban pencatatan meningkat |
| P-03 | Dua apotek membutuhkan satu platform tetapi data operasional harus tetap terpisah | Dibutuhkan pemisahan data berdasarkan apotek |
| P-04 | Perubahan stok perlu tercermin berdasarkan transaksi | Dibutuhkan mekanisme pembaruan stok yang konsisten |
| P-05 | Mitra mengusulkan member/rekomendasi customer | Kebutuhan perlu divalidasi sebelum masuk MVP |

**Sumber dasar:** proposal menyatakan bahwa kedua apotek belum memiliki sistem stok/transaksi yang memadai, khususnya transaksi obat eceran, serta menyebut usulan sistem member/rekomendasi dari mitra.

---

## 3. Tujuan Produk

### 3.1 Tujuan Utama

Produk harus:

1. Mendigitalisasi pengelolaan inventaris dan stok obat pada dua apotek.
2. Mendukung pencatatan transaksi penjualan melalui kasir.
3. Mendukung transaksi obat secara eceran.
4. Memperbarui stok berdasarkan transaksi yang berhasil.
5. Menyediakan satu platform untuk kedua apotek.
6. Menjaga pemisahan data inventaris dan transaksi antar-apotek.
7. Mengurangi ketergantungan pada pencatatan manual dalam ruang lingkup produk.
8. Menyediakan informasi dan riwayat yang membantu pengguna memantau inventaris dan transaksi.

### 3.2 Tujuan Produk yang Terukur

Keberhasilan MVP ditentukan melalui indikator berikut:

| ID | Indikator | Target Penerimaan |
|---|---|---|
| KPI-01 | Pemisahan data apotek | 100% data inventaris/transaksi yang ditampilkan kepada pengguna mengikuti apotek akun tersebut |
| KPI-02 | Pencatatan transaksi | 100% transaksi valid yang diselesaikan tersimpan dalam sistem |
| KPI-03 | Pembaruan stok | Setiap transaksi penjualan yang berhasil menyebabkan perubahan stok sesuai jumlah yang terjual |
| KPI-04 | Pencegahan stok tidak mencukupi | Transaksi tidak dapat diselesaikan apabila jumlah yang dijual melebihi stok tersedia |
| KPI-05 | Konsistensi perhitungan | Total transaksi pada sistem sesuai dengan aturan perhitungan yang telah disepakati |
| KPI-06 | Fitur inti | Seluruh requirement Must Have lulus pengujian penerimaan sebelum release final |
| KPI-07 | Pengujian | Seluruh test case kritis untuk login, akses data, inventaris, transaksi, dan stok lulus |
| KPI-08 | Deployment | Sistem dapat diakses melalui browser pada lingkungan target yang telah disepakati dengan mitra |

> Target numerik di atas merupakan **target produk/proyek yang diusulkan untuk pengujian**, bukan hasil pengukuran kondisi awal mitra.

---

## 4. Sasaran Pengguna dan Stakeholder

### 4.1 Pengguna Utama

Pengguna sistem adalah karyawan/admin dari masing-masing apotek yang menjalankan aktivitas pengelolaan inventaris dan transaksi penjualan.

Kedua kelompok pengguna memiliki **peran dan pola akses yang sama**. Perbedaannya berada pada **cakupan data apotek yang terasosiasi dengan akun**, bukan pada role.

| Pengguna | Role | Cakupan Data |
|---|---|---|
| Karyawan/Admin Apotek Badan Sehat | Pengguna sistem | Data Apotek Badan Sehat |
| Karyawan/Admin Apotek Salam Sehat | Pengguna sistem | Data Apotek Salam Sehat |

### 4.2 Stakeholder

| Stakeholder | Kepentingan |
|---|---|
| PT. Indah Berkat Usaha | Memastikan solusi sesuai kebutuhan bisnis apotek |
| Karyawan/Admin Apotek | Menggunakan sistem untuk aktivitas operasional |
| Tim Capstone | Menganalisis, merancang, membangun, menguji, dan mendokumentasikan produk |
| Dosen/Pembimbing | Memantau kualitas akademik dan proses pengembangan |

---

## 5. Prinsip Produk

Produk harus mengikuti prinsip berikut:

1. **MVP-first** — fungsi inti harus selesai dan stabil sebelum fitur tambahan.
2. **Pharmacy data isolation** — data antar-apotek tidak boleh tercampur.
3. **Requirement-driven** — fitur harus memiliki dasar kebutuhan atau hasil validasi.
4. **Simple for operational users** — alur utama harus mudah dipahami oleh pengguna dengan kemampuan dasar komputer/web.
5. **Transaction integrity** — transaksi dan perubahan stok harus konsisten.
6. **Traceable** — setiap requirement penting dapat ditelusuri ke implementasi dan test case.
7. **Scope controlled** — fitur baru tidak otomatis masuk hanya karena menarik untuk dikembangkan.
8. **Deployable** — hasil akhir harus dapat dijalankan pada lingkungan target yang realistis.

---

# 6. Ruang Lingkup Produk

## 6.1 In Scope

### A. Autentikasi dan Akses

- Login menggunakan kredensial yang ditetapkan sistem.
- Akun pengguna terasosiasi dengan satu apotek.
- Sistem menentukan cakupan data berdasarkan apotek akun.
- Pengguna hanya dapat mengakses data apotek yang menjadi hak aksesnya.
- Logout/session management.

### B. Master Data Obat/Barang

- Menambah data obat/barang.
- Melihat data obat/barang.
- Mengubah data obat/barang.
- Menghapus data obat/barang sesuai aturan bisnis.
- Menyimpan informasi yang diperlukan untuk inventaris, seperti identitas barang, kategori, harga, stok, dan informasi relevan lain yang telah disepakati.

### C. Inventaris dan Stok

- Menampilkan stok barang.
- Mencatat/mengelola perubahan stok yang termasuk dalam ruang lingkup.
- Menghubungkan stok dengan apotek terkait.
- Memperbarui stok setelah transaksi penjualan berhasil.
- Menolak penjualan apabila stok tidak mencukupi.
- Menampilkan informasi stok untuk membantu pengguna melakukan pemantauan.

### D. Kasir dan Transaksi Penjualan

- Memilih barang yang akan dijual.
- Menentukan jumlah barang.
- Mendukung transaksi lebih dari satu item.
- Mendukung transaksi obat secara eceran.
- Menghitung subtotal.
- Menghitung komponen pajak apabila memang menjadi aturan bisnis final.
- Menghitung total transaksi.
- Menyimpan transaksi yang berhasil.
- Menyimpan detail item transaksi.
- Mengurangi stok sesuai jumlah penjualan.
- Menampilkan/menyediakan riwayat transaksi.

### E. Laporan

- Laporan/rekap inventaris.
- Laporan/rekap transaksi.
- Filter atau periode laporan sesuai kebutuhan yang divalidasi bersama mitra.

### F. Multi-Apotek

- Dua apotek berada dalam satu aplikasi/platform.
- Setiap akun terkait dengan satu apotek.
- Data inventaris dan transaksi tetap terisolasi antar-apotek.
- Operasi pengguna hanya berlaku pada cakupan apotek akun tersebut.

### G. Validasi dan Error Handling

- Validasi input wajib.
- Validasi jumlah penjualan.
- Penanganan barang tidak ditemukan.
- Penanganan stok tidak mencukupi.
- Penanganan login gagal.
- Penanganan kegagalan penyimpanan transaksi.
- Pesan kesalahan yang dapat dipahami pengguna.

---

## 6.2 Out of Scope

Fitur berikut tidak menjadi bagian MVP:

- Distribusi obat PT. Indah Berkat Usaha ke apotek lain.
- Produksi obat.
- Akuntansi perusahaan secara keseluruhan.
- Penggajian karyawan.
- Pengelolaan seluruh operasional PT. Indah Berkat Usaha di luar dua apotek.
- Validasi resep digital.
- Pemesanan otomatis ke pemasok.
- Payment gateway.
- Integrasi BPJS.
- Integrasi sistem eksternal yang tidak menyediakan akses/API/izin yang diperlukan.
- Aplikasi mobile native, kecuali ruang lingkup diubah melalui keputusan proyek.
- Penggantian atau upgrade perangkat keras mitra sebagai bagian dari produk.

---

# 7. Prioritas Fitur

Prioritas menggunakan MoSCoW.

| Prioritas | Makna |
|---|---|
| Must Have | Wajib tersedia agar MVP dapat dianggap berhasil |
| Should Have | Penting, tetapi dapat ditunda jika terdapat kendala waktu |
| Could Have | Nilai tambah jika kapasitas memungkinkan |
| Won't Have | Tidak dikembangkan dalam scope saat ini |

| Fitur | Prioritas |
|---|---|
| Login dan session | Must Have |
| Association akun → apotek | Must Have |
| Pembatasan akses data antar-apotek | Must Have |
| CRUD data obat/barang | Must Have |
| Pengelolaan/monitoring stok | Must Have |
| Kasir multi-item | Must Have |
| Transaksi eceran | Must Have |
| Perhitungan transaksi | Must Have |
| Penyimpanan transaksi dan detail | Must Have |
| Pengurangan stok setelah transaksi | Must Have |
| Validasi stok | Must Have |
| Riwayat transaksi | Must Have |
| Laporan inventaris/transaksi | Should Have |
| Penyempurnaan UX | Should Have |
| Bulk upload data awal | Should Have, tergantung validasi kebutuhan dan kesiapan data |
| Member/customer | Could Have |
| Rekomendasi customer/obat | Could Have |
| Payment gateway | Won't Have |
| BPJS | Won't Have |
| Validasi resep digital | Won't Have |
| Pemesanan otomatis supplier | Won't Have |

### 7.1 Aturan Feature Freeze

Pada akhir **minggu ke-9**, tim harus menetapkan feature freeze. Setelah titik tersebut:

- Tidak ada penambahan fitur baru tanpa alasan yang kuat.
- Perubahan harus dinilai berdasarkan dampaknya terhadap requirement inti, testing, dan deadline.
- Bug pada fitur Must Have memiliki prioritas lebih tinggi daripada fitur Could Have.

---

# 8. User Journey Utama

## 8.1 Login

```text
Pengguna membuka sistem
        ↓
Memasukkan username + password
        ↓
Sistem melakukan autentikasi
        ↓
Valid?
 ┌──────┴──────┐
Tidak          Ya
 ↓              ↓
Pesan error     Sistem membaca apotek akun
                ↓
             Dashboard
```

## 8.2 Pengelolaan Inventaris

```text
Login
  ↓
Dashboard
  ↓
Menu Inventaris
  ↓
Melihat / mencari barang
  ↓
Tambah / ubah / hapus data
  ↓
Validasi
  ↓
Simpan
  ↓
Data inventaris diperbarui
```

## 8.3 Transaksi Penjualan

```text
Login
  ↓
Menu Kasir
  ↓
Cari/pilih barang
  ↓
Masukkan jumlah
  ↓
Validasi stok
  ↓
Tambahkan ke keranjang
  ↓
Hitung subtotal + komponen transaksi
  ↓
Konfirmasi transaksi
  ↓
Simpan transaksi + detail
  ↓
Kurangi stok
  ↓
Tampilkan hasil transaksi
```

## 8.4 Pemisahan Data Apotek

```text
Login User A
     ↓
Account.pharmacy_id = A
     ↓
Query/filter data dengan pharmacy_id = A
     ↓
User A hanya melihat/mengelola data A

Login User B
     ↓
Account.pharmacy_id = B
     ↓
Query/filter data dengan pharmacy_id = B
     ↓
User B hanya melihat/mengelola data B
```

Pemisahan harus diterapkan pada seluruh fungsi yang membaca atau mengubah data terkait apotek, bukan hanya pada tampilan antarmuka.

---

# 9. Functional Requirements

## 9.1 Modul Autentikasi dan Akses

| ID | Requirement | Acceptance Criteria | Prioritas |
|---|---|---|---|
| FR-AUTH-01 | Sistem harus menyediakan login pengguna | Kredensial valid membawa pengguna ke sistem; kredensial tidak valid ditolak | Must |
| FR-AUTH-02 | Akun harus terasosiasi dengan satu apotek | Setiap akun aktif memiliki identitas apotek yang valid | Must |
| FR-AUTH-03 | Sistem harus membatasi data berdasarkan apotek akun | User A tidak dapat melihat/mengelola data User B | Must |
| FR-AUTH-04 | Sistem harus mencegah akses tanpa autentikasi | Halaman internal tidak dapat digunakan tanpa session valid | Must |
| FR-AUTH-05 | Sistem harus menyediakan logout | Session pengguna berakhir setelah logout | Must |

## 9.2 Modul Data Obat/Barang

| ID | Requirement | Acceptance Criteria | Prioritas |
|---|---|---|---|
| FR-INV-01 | Sistem harus menampilkan daftar barang | Daftar barang sesuai apotek pengguna tampil | Must |
| FR-INV-02 | Sistem harus menyediakan pencarian barang | Barang yang sesuai kata kunci dapat ditemukan | Must |
| FR-INV-03 | Sistem harus menambah barang | Data valid tersimpan dan dapat ditampilkan kembali | Must |
| FR-INV-04 | Sistem harus mengubah data barang | Perubahan tersimpan dan terlihat pada data terbaru | Must |
| FR-INV-05 | Sistem harus menghapus barang sesuai aturan bisnis | Barang hanya dapat dihapus jika memenuhi aturan yang ditetapkan | Must |
| FR-INV-06 | Sistem harus menampilkan stok | Jumlah stok barang dapat dilihat oleh pengguna terkait | Must |

## 9.3 Modul Stok

| ID | Requirement | Acceptance Criteria | Prioritas |
|---|---|---|---|
| FR-STK-01 | Sistem harus menyimpan stok per apotek | Stok memiliki cakupan apotek yang jelas | Must |
| FR-STK-02 | Sistem harus memperbarui stok setelah transaksi berhasil | Stok berkurang sebesar jumlah item yang terjual | Must |
| FR-STK-03 | Sistem tidak boleh mengurangi stok jika transaksi gagal | Stok tetap sebelum transaksi berhasil | Must |
| FR-STK-04 | Sistem harus menolak penjualan melebihi stok | User mendapat pesan dan transaksi tidak dapat diselesaikan | Must |
| FR-STK-05 | Sistem harus menangani transaksi multi-item secara konsisten | Semua item valid diproses sesuai jumlahnya atau transaksi gagal secara aman | Must |

## 9.4 Modul Kasir

| ID | Requirement | Acceptance Criteria | Prioritas |
|---|---|---|---|
| FR-SAL-01 | Sistem harus menyediakan halaman kasir | User dapat membuka fungsi transaksi | Must |
| FR-SAL-02 | User dapat mencari dan memilih barang | Barang tersedia dapat ditambahkan ke transaksi | Must |
| FR-SAL-03 | User dapat memasukkan jumlah item | Quantity valid diterima | Must |
| FR-SAL-04 | Sistem harus mendukung beberapa item dalam satu transaksi | Keranjang dapat berisi lebih dari satu barang | Must |
| FR-SAL-05 | Sistem harus menghitung subtotal | Subtotal sesuai jumlah × harga setiap item | Must |
| FR-SAL-06 | Sistem harus menghitung total sesuai aturan bisnis final | Hasil perhitungan sesuai formula yang disepakati | Must |
| FR-SAL-07 | Sistem harus menyimpan transaksi | Header transaksi dan detail item tersimpan | Must |
| FR-SAL-08 | Sistem harus mencatat waktu transaksi | Transaksi menyimpan tanggal/waktu sesuai aturan sistem | Must |
| FR-SAL-09 | Sistem harus mengurangi stok setelah transaksi berhasil | Stok setiap item berubah sesuai quantity terjual | Must |

> **Catatan:** Formula pajak, metode pembayaran, pembulatan, pembatalan transaksi, retur, diskon, dan cetak struk harus dikonfirmasi melalui requirement bisnis mitra. Jangan mengunci detail tersebut hanya berdasarkan asumsi teknis.

## 9.5 Modul Riwayat dan Laporan

| ID | Requirement | Acceptance Criteria | Prioritas |
|---|---|---|---|
| FR-REP-01 | Sistem harus menyediakan riwayat transaksi | User dapat melihat transaksi pada apoteknya | Must |
| FR-REP-02 | Sistem harus menyediakan informasi transaksi yang relevan | Informasi transaksi dapat ditelusuri kembali | Should |
| FR-REP-03 | Sistem harus menyediakan laporan inventaris | Rekap data inventaris dapat ditampilkan | Should |
| FR-REP-04 | Sistem harus menyediakan laporan transaksi | Rekap transaksi dapat ditampilkan | Should |
| FR-REP-05 | Laporan tidak boleh mencampurkan data apotek | User hanya memperoleh data sesuai cakupan akunnya | Must |

---

# 10. Kebutuhan Nonfungsional

## 10.1 Security

| ID | Requirement | Target |
|---|---|---|
| NFR-SEC-01 | Password tidak disimpan dalam plaintext | Password disimpan menggunakan mekanisme hashing yang sesuai |
| NFR-SEC-02 | Akses data harus mengikuti apotek akun | Tidak ada akses lintas-apotek melalui URL, request, atau manipulasi parameter |
| NFR-SEC-03 | Input pengguna harus divalidasi | Input invalid ditolak sebelum diproses |
| NFR-SEC-04 | Query database harus menggunakan mekanisme aman | Implementasi mencegah SQL Injection |
| NFR-SEC-05 | Session harus dikelola dengan benar | User yang logout tidak dapat mengakses halaman terlindungi tanpa login kembali |

## 10.2 Usability

- Antarmuka menggunakan istilah yang mudah dipahami pengguna apotek.
- Alur kasir dibuat sesingkat mungkin untuk aktivitas transaksi rutin.
- Form memberikan validasi yang jelas.
- Pesan error menjelaskan tindakan yang perlu dilakukan.
- Navigasi antar modul konsisten.

## 10.3 Performance

Untuk MVP, sistem harus memiliki performa yang memadai pada lingkungan target mitra.

Target pengujian yang diusulkan:

- Halaman utama/modul utama dapat dimuat tanpa error pada lingkungan target.
- Pencarian barang dan penambahan item ke transaksi memberikan respons yang layak untuk penggunaan operasional.
- Transaksi tidak menghasilkan duplikasi penyimpanan akibat satu kali aksi pengguna.

Nilai benchmark waktu respons final perlu ditentukan setelah lingkungan deployment dan karakteristik data uji diketahui.

## 10.4 Reliability dan Data Integrity

- Transaksi yang gagal tidak boleh menyebabkan stok berkurang sebagian.
- Transaksi berhasil harus memiliki detail yang sesuai.
- Stok dan detail transaksi harus konsisten.
- Operasi kritis harus diuji pada kondisi normal dan kondisi gagal.
- Backup database harus tersedia sebelum demonstrasi/release apabila fasilitas deployment memungkinkan.

## 10.5 Compatibility

Lingkungan target proposal:

- Platform: Web.
- Perangkat pengguna: PC/desktop atau laptop.
- Sistem operasi: Windows.
- Browser target: Chrome.
- Server: hosting server.
- Database: MySQL.
- Jaringan: internet.
- Lingkungan pengembangan: laptop, Visual Studio Code, internet/Wi-Fi.

Detail spesifikasi server/hosting belum ditetapkan dalam proposal dan perlu dikonfirmasi sebelum deployment final.

---

# 11. Data Requirements

## 11.1 Entitas Utama

Secara konseptual, sistem minimal membutuhkan data:

- **Apotek**
- **Pengguna/Akun**
- **Obat/Barang**
- **Stok**
- **Transaksi Penjualan**
- **Detail Transaksi**

Entitas tambahan hanya dibuat apabila terdapat kebutuhan bisnis yang tervalidasi.

## 11.2 Prinsip Relasi Data

1. Setiap akun terkait dengan satu apotek.
2. Data operasional yang dimiliki apotek harus memiliki hubungan yang dapat menentukan pemilik/cakupan apotek.
3. Transaksi harus terkait dengan apotek.
4. Detail transaksi harus terkait dengan transaksi dan barang.
5. Perubahan stok akibat transaksi harus dapat ditelusuri secara logis.
6. Data kedua apotek boleh berada dalam database/platform yang sama, tetapi tidak boleh tercampur pada level akses pengguna.

---

# 12. Business Rules

| ID | Business Rule |
|---|---|
| BR-01 | Satu akun pengguna hanya memiliki satu cakupan apotek. |
| BR-02 | Pengguna hanya dapat mengakses data apotek yang terkait dengan akunnya. |
| BR-03 | Data transaksi harus terkait dengan apotek tempat transaksi dilakukan. |
| BR-04 | Barang yang dijual harus tersedia dalam data apotek pengguna. |
| BR-05 | Quantity penjualan harus bernilai valid dan tidak melebihi stok tersedia. |
| BR-06 | Stok berkurang hanya setelah transaksi berhasil disimpan/ditetapkan berhasil. |
| BR-07 | Jika transaksi gagal, perubahan stok yang terkait tidak boleh tertinggal secara tidak konsisten. |
| BR-08 | Transaksi multi-item harus menjaga konsistensi seluruh detail dan stok. |
| BR-09 | Formula harga, pajak, diskon, pembulatan, dan pembayaran mengikuti aturan bisnis yang telah disetujui mitra. |
| BR-10 | Fitur yang tidak memiliki dasar kebutuhan atau validasi tidak boleh menggeser fitur Must Have selama periode MVP. |

---

# 13. Requirements yang Masih Harus Divalidasi

Bagian ini sengaja dipisahkan agar tim tidak mengubah asumsi menjadi requirement tanpa bukti.

| ID | Hal yang Perlu Divalidasi | Keputusan yang Dibutuhkan |
|---|---|---|
| VAL-01 | Alur transaksi aktual di masing-masing apotek | As-Is process dan To-Be process |
| VAL-02 | Struktur data obat yang benar-benar digunakan | Field master obat |
| VAL-03 | Cara stok masuk/bertambah | Apakah hanya melalui input manual, pembelian, atau mekanisme lain |
| VAL-04 | Aturan transaksi eceran | Satuan, quantity, harga, dan stok |
| VAL-05 | Pajak | Apakah pajak diterapkan dan formula finalnya |
| VAL-06 | Metode pembayaran | Tunai saja atau ada metode lain |
| VAL-07 | Diskon | Apakah diperlukan |
| VAL-08 | Retur/pembatalan transaksi | Apakah termasuk scope |
| VAL-09 | Laporan | Jenis laporan dan periode yang dibutuhkan |
| VAL-10 | Data historis | Apakah perlu migrasi dan dalam format apa |
| VAL-11 | Bulk upload | Format dan siapa yang bertanggung jawab terhadap data awal |
| VAL-12 | Member/rekomendasi | Apakah menjadi fitur MVP, versi berikutnya, atau tidak dilanjutkan |
| VAL-13 | Pengguna lintas-apotek | Apakah benar tidak ada akun yang perlu melihat kedua apotek |
| VAL-14 | Deployment | Hosting, domain, kapasitas, backup, dan akses jaringan |
| VAL-15 | Perangkat operasional | Kondisi PC/laptop dan browser aktual di kedua lokasi |

---

# 14. Member dan Rekomendasi Customer

Proposal mencatat adanya usulan dari mitra mengenai sistem member/rekomendasi customer berdasarkan kebutuhan dan ketersediaan obat.

Namun, fitur ini **belum otomatis menjadi bagian MVP**.

Alasannya:

1. Proposal belum memberikan spesifikasi proses dan data secara lengkap.
2. Fitur dapat membutuhkan data customer/pasien yang lebih sensitif.
3. Alur rekomendasi harus didefinisikan dengan hati-hati dan tidak boleh diasumsikan sebagai rekomendasi medis otomatis.
4. Waktu proyek hanya ±12 minggu dengan tim 4 orang.
5. Fitur inti inventaris dan kasir memiliki prioritas lebih tinggi.

### Gate Keputusan

Pada minggu 1–2, tim harus meminta konfirmasi mitra:

- Apa tujuan member?
- Data apa yang perlu disimpan?
- Siapa yang boleh mengakses?
- Apa yang dimaksud dengan rekomendasi?
- Apakah rekomendasi hanya berupa riwayat/informasi pencarian atau memiliki aturan tertentu?
- Apakah fitur wajib untuk MVP?

Jika tidak tervalidasi sebagai kebutuhan prioritas, fitur ditempatkan di backlog dan tidak boleh mengganggu penyelesaian MVP.

---

# 15. Traceability: Problem → Requirement → Feature → Test

| Problem | Requirement | Feature | Test |
|---|---|---|---|
| Pencatatan stok belum memadai | FR-STK-01 | Modul stok | TC-STK-01 |
| Penjualan eceran masih manual | FR-SAL-01 s.d. FR-SAL-09 | Kasir | TC-SAL-01 s.d. TC-SAL-09 |
| Risiko stok tidak sesuai transaksi | FR-STK-02/03/04 | Stock update | TC-STK-02 s.d. TC-STK-04 |
| Dua apotek memakai satu platform | FR-AUTH-02/03 | Account-pharmacy isolation | TC-AUTH-02/03 |
| Perlu pemantauan transaksi | FR-REP-01/03/04 | Riwayat & laporan | TC-REP-01 dst. |

**Aturan:** setiap Must Have requirement wajib memiliki minimal satu test case yang dapat membuktikan requirement tersebut terpenuhi.

---

# 16. Acceptance Criteria MVP

MVP dianggap **release candidate** apabila seluruh kondisi berikut terpenuhi:

### A. Akses
- [ ] User dapat login menggunakan akun valid.
- [ ] Login invalid ditolak.
- [ ] Akun memiliki cakupan satu apotek.
- [ ] User tidak dapat membaca/mengubah data apotek lain.
- [ ] Logout bekerja.

### B. Inventaris
- [ ] Data barang dapat dibuat.
- [ ] Data barang dapat dilihat.
- [ ] Data barang dapat dicari.
- [ ] Data barang dapat diubah.
- [ ] Stok tampil sesuai data.
- [ ] Validasi input berjalan.

### C. Kasir
- [ ] Barang dapat dipilih.
- [ ] Quantity dapat dimasukkan.
- [ ] Multi-item transaction berjalan.
- [ ] Stok tidak cukup ditolak.
- [ ] Subtotal benar.
- [ ] Formula transaksi sesuai aturan bisnis final.
- [ ] Transaksi tersimpan.
- [ ] Detail transaksi tersimpan.

### D. Stok
- [ ] Stok berkurang setelah transaksi berhasil.
- [ ] Stok tidak berubah jika transaksi gagal.
- [ ] Stok tidak menjadi negatif akibat transaksi normal.
- [ ] Data stok sesuai dengan transaksi yang berhasil.

### E. Riwayat/Laporan
- [ ] User dapat melihat transaksi apoteknya.
- [ ] Data transaksi antar-apotek tidak tercampur.
- [ ] Laporan Must/Should Have yang disepakati tersedia.

### F. Kualitas
- [ ] Test case kritis lulus.
- [ ] Tidak terdapat defect kritis yang diketahui.
- [ ] Sistem dapat di-deploy pada lingkungan target.
- [ ] Dokumentasi penggunaan dan teknis minimum tersedia.

---

# 17. Testing Strategy

Pengujian dilakukan oleh seluruh anggota tim.

## 17.1 Level Pengujian

1. **Unit/Component Test** — menguji fungsi individual yang relevan.
2. **Integration Test** — menguji hubungan frontend, backend, database, inventaris, dan transaksi.
3. **System Test** — menguji alur sistem secara utuh.
4. **Security/Access Test** — terutama pemisahan data antar-apotek.
5. **User Acceptance Test (UAT)** — validasi terhadap kebutuhan mitra.
6. **Regression Test** — dilakukan setelah bug diperbaiki.

## 17.2 Test Scenario Kritis

Minimal:

- Login valid/invalid.
- Akses halaman tanpa login.
- User Apotek A mencoba mengakses data Apotek B.
- User Apotek B mencoba mengakses data Apotek A.
- Tambah/edit/hapus barang.
- Penjualan satu item.
- Penjualan multi-item.
- Quantity = 1.
- Quantity > 1.
- Quantity melebihi stok.
- Transaksi gagal.
- Stok setelah transaksi berhasil.
- Riwayat transaksi.
- Data dua apotek secara bersamaan.
- Refresh/reload setelah transaksi.
- Double submit transaksi untuk mendeteksi duplikasi.

---

# 18. Product Metrics

| Metric | Cara Ukur | Target |
|---|---|---|
| Requirement completion | Must Have yang selesai / seluruh Must Have | 100% sebelum release |
| Critical test pass rate | Test kritis lulus / seluruh test kritis | 100% |
| Cross-pharmacy isolation | Skenario akses lintas-apotek yang gagal diblokir / seluruh skenario | 100% |
| Transaction accuracy | Transaksi uji dengan total benar / seluruh transaksi uji | 100% |
| Stock accuracy | Transaksi uji dengan perubahan stok benar / seluruh transaksi stok | 100% |
| Critical defects | Defect severity kritis yang terbuka saat release | 0 |
| Deployment availability | Skenario akses lingkungan target yang berhasil / seluruh skenario deployment | 100% |

---

# 19. Roadmap Produk 12 Minggu

Roadmap mengikuti workflow pengembangan yang telah dibuat untuk proyek.

| Minggu | Fokus | Output Utama |
|---|---|---|
| 1 | Observasi & validasi masalah | As-Is process, masalah tervalidasi, kebutuhan awal |
| 2 | Requirement & scope | PRD/SRS baseline, priority, acceptance criteria |
| 3 | Analisis & desain | UML, ERD, wireframe, architecture |
| 4 | Project foundation | Repository, database awal, skeleton aplikasi, auth foundation |
| 5 | Inventaris | Modul akun/apotek + master barang + stok |
| 6 | Kasir | Modul transaksi dan keranjang |
| 7 | Integrasi core | Transaksi ↔ stok + isolation test |
| 8 | Riwayat & laporan | History/report + penyempurnaan |
| 9 | Hardening & fitur prioritas | Bug fixing + optional feature yang tervalidasi; feature freeze |
| 10 | System testing & UAT | Test report, UAT feedback |
| 11 | Stabilization & deployment | Release candidate, deployment, dokumentasi |
| 12 | Finalization | Regression, final deployment, demo, presentasi, backup |

### Milestone Kritis

- **End W1:** masalah dan proses aktual dipahami.
- **End W2:** scope dan requirement baseline disepakati.
- **End W7:** core flow inventaris → kasir → stok berjalan end-to-end.
- **End W9:** feature freeze.
- **End W12:** release final dan dokumentasi selesai.

---

# 20. Pembagian Tanggung Jawab Tim

Pembagian mengikuti proposal, tetapi workflow menggunakan pendekatan kolaboratif agar tidak terjadi ketergantungan pada satu anggota.

| Anggota | Tanggung Jawab Utama | Tanggung Jawab Pendukung |
|---|---|---|
| Alexander Arthur Bimo Satriaji | Project Manager, Backend Developer | Database, integration, technical coordination |
| Pieter Alva Pradana | Frontend Developer, UML | Integrasi UI dan requirement traceability |
| Roman Adi Surya | Frontend Developer, UI/UX Developer | Design system, usability |
| Samuel Latuihamallo | Support UI/UX | Dokumentasi, test support, data preparation |
| Seluruh anggota | Testing, review, bug fixing, dokumentasi, presentasi | — |

### Prinsip Pembagian

Tidak ada anggota yang bekerja sepenuhnya terisolasi. Setiap modul penting harus memiliki:

- owner,
- reviewer,
- test scenario,
- dokumentasi,
- dan status yang dapat dilacak.

---

# 21. Development Workflow

Workflow mingguan:

```text
PLAN
 ↓
DEFINE TASK
 ↓
DESIGN / IMPLEMENT
 ↓
INTEGRATE
 ↓
TEST
 ↓
CODE/REQUIREMENT REVIEW
 ↓
DOCUMENT
 ↓
WEEKLY REVIEW
```

### Git/Repository

Struktur workflow yang disarankan:

```text
main
 └── develop
      ├── feature/auth
      ├── feature/inventory
      ├── feature/cashier
      ├── feature/report
      └── fix/...
```

Prinsip:

- Jangan mengembangkan langsung di `main`.
- Satu perubahan besar = satu branch.
- Pull/merge harus diperiksa anggota lain.
- Commit harus menjelaskan perubahan.
- `main` hanya menerima versi yang sudah cukup stabil.

---

# 22. Scope Control

Setiap permintaan fitur baru harus dinilai dengan pertanyaan:

1. Apakah kebutuhan tersebut berasal dari mitra?
2. Apakah sudah divalidasi?
3. Apakah masuk ruang lingkup proposal?
4. Apakah termasuk Must Have?
5. Berapa estimasi waktu implementasi?
6. Apa dampaknya terhadap testing?
7. Apa fitur yang harus ditunda jika fitur baru diterima?
8. Apakah fitur tersebut membutuhkan API/integrasi eksternal?
9. Apakah terdapat risiko data atau regulasi?
10. Apakah fitur dapat diselesaikan tanpa mengganggu milestone minggu ke-12?

Jika jawaban menunjukkan risiko tinggi terhadap MVP, fitur dimasukkan ke backlog.

---

# 23. Risiko Produk dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Requirement berubah setelah coding | Tinggi | Baseline requirement pada minggu 2 |
| Scope creep | Tinggi | MoSCoW + feature freeze minggu 9 |
| Data mitra belum siap | Tinggi | Validasi dan data preparation sejak awal |
| Kesalahan pemisahan data apotek | Sangat tinggi | Security/access test khusus |
| Stok tidak sinkron dengan transaksi | Sangat tinggi | Integration test + transaction integrity |
| Fitur terlalu banyak | Tinggi | Prioritaskan Must Have |
| Ketergantungan pada satu anggota | Sedang | Pair review dan dokumentasi |
| Masalah deployment | Sedang | Deployment trial sebelum minggu 11 |
| Fitur member/rekomendasi tidak jelas | Sedang | Jadikan conditional backlog |
| Koneksi internet tidak stabil | Sedang | Uji lingkungan target dan siapkan prosedur operasional yang disepakati |

---

# 24. Definition of Done

## 24.1 Definition of Done untuk Feature

Sebuah fitur dianggap selesai apabila:

- [ ] Requirement-nya jelas.
- [ ] UI/UX telah ditentukan.
- [ ] Implementasi selesai.
- [ ] Validasi/error handling tersedia.
- [ ] Terintegrasi dengan komponen yang diperlukan.
- [ ] Test case tersedia.
- [ ] Test berhasil.
- [ ] Tidak menimbulkan regression pada fitur inti.
- [ ] Code telah direview.
- [ ] Dokumentasi yang diperlukan diperbarui.

## 24.2 Definition of Done untuk MVP

MVP dianggap selesai apabila:

- [ ] Semua Must Have selesai.
- [ ] Acceptance criteria Must Have terpenuhi.
- [ ] Test kritis lulus.
- [ ] Pemisahan data antar-apotek teruji.
- [ ] Alur transaksi dan perubahan stok teruji end-to-end.
- [ ] Tidak ada defect kritis terbuka.
- [ ] Sistem dapat diakses pada environment target.
- [ ] UAT mitra telah dilakukan atau seluruh feedback yang disepakati telah ditangani.
- [ ] Dokumentasi final tersedia.
- [ ] Database/application backup tersedia sesuai kemampuan environment.
- [ ] Tim siap melakukan demonstrasi.

---

# 25. Deliverables

## Product Deliverables

1. Sistem web inventory dan kasir.
2. Database sistem.
3. Modul autentikasi dan akses apotek.
4. Modul inventaris.
5. Modul stok.
6. Modul kasir/transaksi.
7. Modul riwayat/laporan sesuai scope.
8. Dokumentasi penggunaan minimum.
9. Dokumentasi teknis minimum.
10. Test report/UAT evidence.
11. Deployment/release candidate.
12. Final release.

## Project/Academic Deliverables

1. PRD.
2. SRS.
3. UML.
4. ERD/database design.
5. UI/UX design.
6. Source code.
7. Test documentation.
8. Dokumentasi deployment.
9. Dokumentasi/presentasi Capstone.

---

# 26. Definition of Success

Proyek dianggap berhasil apabila pada akhir periode capstone:

1. Dua apotek dapat menggunakan satu sistem berbasis web.
2. Setiap pengguna hanya mengakses data apotek yang terasosiasi dengan akunnya.
3. Data obat/barang dapat dikelola.
4. Stok dapat dipantau dan diperbarui secara konsisten.
5. Transaksi penjualan, termasuk transaksi eceran yang menjadi fokus masalah mitra, dapat dicatat melalui kasir.
6. Transaksi berhasil menghasilkan perubahan stok yang benar.
7. Transaksi dengan stok tidak mencukupi ditolak.
8. Riwayat/laporan yang masuk scope dapat digunakan.
9. Sistem lulus pengujian kritis.
10. Sistem dapat didemonstrasikan dan dijalankan pada environment target.
11. Solusi tetap berada dalam batas waktu dan sumber daya tim 4 orang.

---

# 27. Decisions Log

| Keputusan | Status |
|---|---|
| Satu platform untuk dua apotek | Disepakati pada proposal |
| Data antar-apotek terpisah | Disepakati |
| Akun terasosiasi dengan satu apotek | Disepakati |
| Role A dan B tidak dibedakan | Disepakati secara konseptual |
| Fokus utama inventory + cashier | Disepakati |
| Transaksi eceran menjadi fokus | Didukung proposal |
| Payment gateway | Out of scope |
| BPJS | Out of scope |
| Validasi resep digital | Out of scope |
| Pemesanan supplier otomatis | Out of scope |
| Member/rekomendasi | Menunggu validasi kebutuhan |
| Laporan | Termasuk scope, detail perlu divalidasi |
| Detail formula pajak | Perlu validasi aturan bisnis |
| Detail retur/diskon/metode pembayaran | Perlu validasi |
| Deployment hosting | Direncanakan, detail perlu dikonfirmasi |

---

# 28. Catatan Konsistensi dengan SRS

PRD ini sengaja mempertahankan keputusan penting dari proposal:

- Produk adalah sistem berbasis web.
- Objek utama adalah dua apotek milik PT. Indah Berkat Usaha.
- Sistem mengelola inventaris dan transaksi penjualan.
- Transaksi eceran menjadi salah satu fokus utama.
- Kedua apotek menggunakan satu platform.
- Data tetap dipisahkan berdasarkan apotek.
- Pengguna memiliki akun yang terasosiasi dengan satu apotek.
- Role pengguna tidak dibedakan hanya berdasarkan lokasi apotek.
- Distribusi, produksi, akuntansi perusahaan secara keseluruhan, penggajian, dan operasional PT di luar kedua apotek berada di luar scope.
- Pengembangan dibatasi oleh waktu ±12 minggu dan tim 4 orang.

Proposal juga menyebut lingkungan operasi berupa web, PC/laptop, Windows, Chrome, hosting server, MySQL, dan internet. Detail implementasi akhir tetap harus mengikuti hasil validasi lingkungan mitra.

---

# 29. Catatan Penting untuk Tahap Berikutnya

PRD ini **belum seharusnya langsung dianggap sebagai spesifikasi database atau kode**.

Urutan kerja yang disarankan setelah PRD:

```text
PRD
 ↓
Validasi Observasi/Wawancara
 ↓
Finalisasi Requirement
 ↓
SRS
 ↓
Use Case + Activity Diagram
 ↓
ERD + Database Design
 ↓
Wireframe/UI Design
 ↓
Architecture & Technical Design
 ↓
Implementation
 ↓
Integration
 ↓
Testing
 ↓
UAT
 ↓
Deployment
 ↓
Final Release
```

Jika hasil observasi mengubah requirement, PRD/SRS harus diperbarui terlebih dahulu sebelum tim mengunci desain teknis.

---

## 30. Ringkasan Product Baseline

**Produk:** Sistem Inventaris dan Kasir Berbasis Web  
**Pengguna:** Karyawan/Admin dua apotek  
**Objek:** Apotek Badan Sehat dan Apotek Salam Sehat  
**Platform:** Web  
**Core value:** Digitalisasi pengelolaan inventaris, stok, dan transaksi penjualan/eceran dalam satu platform dengan pemisahan data antar-apotek.

### MVP wajib:

```text
Authentication
      +
Account → Pharmacy
      +
Pharmacy Data Isolation
      +
Medicine/Item Management
      +
Inventory & Stock
      +
Cashier
      +
Retail Sales
      +
Transaction Recording
      +
Automatic Stock Update
      +
Validation & Error Handling
      +
Transaction History
      +
Testing & Deployment
```

### Prioritas proyek

> **Stabilitas dan kebenaran alur Inventory → Cashier → Transaction → Stock lebih penting daripada jumlah fitur.**

Dengan tim 4 orang dan waktu sekitar 12 minggu, keberhasilan proyek sebaiknya diukur dari kemampuan menghasilkan MVP yang **benar, teruji, dapat digunakan, dan sesuai kebutuhan mitra**, bukan dari banyaknya fitur yang berhasil ditambahkan.

# SOPAN — OVERNIGHT DEEP AUDIT REPORT
## Full-Project Cross-Module, Security, Database Integrity & Reliability Audit
**Aplikasi:** SOPAN (Sistem Operasional Presensi & Agenda Mengajar Guru) — SMK Negeri 1 Ciamis  
**Repository:** `c:\laragon\www\agenda-mengajar-guru`  
**Remote Git:** `https://github.com/PradiptaPPLG/agenda-mengajar-guru.git`  
**Commit Baseline:** `9061d12c8dbd2c0104e84a2b637985bf1160225d` (`main`)  
**Status Mode:** READ-ONLY AUDIT (Tidak ada source code, migrasi, atau database yang diubah)  
**Waktu Audit:** 13 Oktober 2026  

---

## 1. Executive Summary

Audit teknis mendalam (*overnight deep audit*) telah dilaksanakan secara komprehensif terhadap seluruh arsitektur, basis kode, migrasi, alur bisnis, dan suite pengujian aplikasi **SOPAN SMK Negeri 1 Ciamis**.

Hasil audit mengonfirmasi bahwa fondasi aplikasi telah memiliki perlindungan dasar yang sangat baik (seperti mitigasi IDOR pada alur presensi siswa dan jurnal guru, sanitasi parameter SQL, isolasi database testing, serta integrasi kurikulum merdeka PPLG). Namun demikian, penelusuran menyeluruh mengungkap **beberapa cacat kritis dan risiko tinggi** yang selama ini tersembunyi:

1. **[CRITICAL] Bahaya Kehilangan Data Permanen (Data Loss) pada Impor Jadwal:** Fitur *clear before import* pada [AdminJadwalController](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Admin/JadwalController.php#L301-L306) melakukan *hard-delete* non-transaksional yang secara permanen memicu penghapusan berantai (*cascade on delete*) terhadap seluruh riwayat pertemuan, presensi guru, presensi siswa, dan foto bukti satu semester jika proses impor berkas Excel gagal di tengah jalan (**DATA-001**).
2. **[HIGH] Eskalasi Hak Akses (Privilege Escalation):** Administrator biasa (`role: admin`) dapat menaikkan hak akses dirinya atau pengguna lain menjadi `super_admin`, serta mengubah password atau menghapus akun `super_admin` melalui antarmuka manajemen pengguna (**SEC-001**).
3. **[MEDIUM] Ketiadaan Rate Limiting:** Endpoint autentikasi `/login`, endpoint unggah presensi, dan endpoint unduh arsip ZIP foto massal tidak diproteksi middleware *throttle*, membuka celah *brute-force* dan kehabisan memori server (**SEC-003**).
4. **[MEDIUM] Ketidakkonsistenan Rekap Laporan PDF Kepala Sekolah:** Status kehadiran siswa `terlambat` tidak diakomodasi dalam kalkulasi rekap cetak PDF [PdfController](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/KepalaSekolah/PdfController.php#L145-L155), menyebabkan total akumulasi sesi tidak sinkron dengan rincian pada dokumen cetak resmi (**LOGIC-001**).

---

## 2. Audit Scope dan Batasan

### 2.1 Ruang Lingkup yang Diperiksa
- 39 Controller (Admin, Guru, Siswa, Piket, TU, Kepala Sekolah, Super Admin, Publik).
- 17 Model Eloquent dan relasi antar-tabel.
- 48 Migration database dari inisialisasi hingga migrasi kurikulum terkini.
- 23 File Feature Test (119 kasus uji) dalam environment terisolasi.
- Seluruh rute HTTP pada [routes/web.php](file:///c:/laragon/www/agenda-mengajar-guru/routes/web.php) dan Artisan commands pada [app/Console/Commands](file:///c:/laragon/www/agenda-mengajar-guru/app/Console/Commands).
- Mekanisme kompresi gambar, penanganan file storage, dan pembuatan arsip ZIP.

### 2.2 Batasan Operasional
- **Prinsip Read-Only:** Audit tidak mengubah source code, tidak mengubah skema basis data, dan tidak melakukan deployment ke server `agenda-guru.smkn1ciamis.id`.
- **Database Produksi Tidak Diakses:** Kondisi riil data server produksi (kuota penyimpanan, log server produksi) diverifikasi berdasarkan artefak lokal dan ditandai *Unverified* untuk lingkungan remote.

---

## 3. Repository dan Runtime Baseline

* **Branch Git:** `main` (Sinkron dengan `origin/main`).
* **HEAD Commit:** `9061d12` (*test(safety): tambahkan suite pengujian regresi soft delete dan unifikasi pplg*).
* **Working Tree State:** Bersih (hanya terdapat file dokumentasi untracked `PROJECT_DISCOVERY_REPORT.md`).
* **PHP CLI Runtime:** `PHP 8.3.26 (cli) (ZTS Visual C++ 2019 x64)` (Kompatibel dengan batasan `composer.json` `^8.3`).
* **Framework:** Laravel Framework `13.17.0`.
* **Testing Engine:** PHPUnit `12.5.35` dengan koneksi database SQLite In-Memory (`DB_DATABASE=:memory:`).

---

## 4. Hasil Verifikasi Discovery Report

| Komponen yang Diklaim di Discovery | Hasil Verifikasi Aktual | Catatan Lapangan |
|---|:---:|---|
| **Ketiadaan API Routes (`routes/api.php`)** | **CONFIRMED** | Seluruh rute murni berada di `routes/web.php` dan `routes/console.php`. |
| **Spatie Roles vs Role Column** | **CONFIRMED** | Rute dilindungi kolom `users.role` via `RoleMiddleware`. Spatie roles terpasang di database namun tidak terikat ke Laravel Gates. |
| **IDOR Protection pada Siswa & Guru** | **CONFIRMED** | `CaptureController` dan `PertemuanController` memvalidasi kepemilikan kelas/jadwal dengan ketat. |
| **Pembersihan Presensi pada HTTP GET** | **CONFIRMED FIXED** | Query `delete()` pada `PertemuanController::show()` telah dihilangkan sepenuhnya pada commit sebelumnya. |
| **Pemisahan Peran Admin vs Super Admin** | **DISPROVED / VULNERABLE** | Discovery mengasumsikan pemisahan peran aman, namun audit menemukan bahwa `admin` dapat memanipulasi `super_admin` pada `Admin\UserController`. |

---

## 5. Temuan Critical & High Severity

---

### [DATA-001] Catastrophic Cascading Historical Data Loss pada Impor Jadwal Pelajaran
- **Severity:** **CRITICAL** (P0)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [app/Http/Controllers/Admin/JadwalController.php:L301-L308](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Admin/JadwalController.php#L301-L308)
- **Bukti Kode:**
  ```php
  // JadwalController.php line 301-308
  if ($request->boolean('clear_before_import')) {
      DB::table('jadwal_pelajarans')
          ->where('tahun_ajaran', $tahunAjaran)
          ->where('semester', $semester)
          ->delete();
  }

  Excel::import(new JadwalImport($tahunAjaran, $semester), $request->file('file'));
  ```
- **Analisis & Pembuktian Masalah:**
  1. `DB::table('jadwal_pelajarans')->delete()` mem-bypass model Eloquent, sehingga mengeksekusi **HARD DELETE** di MySQL/MariaDB (bukan *SoftDeletes*).
  2. Tabel `pertemuans` memiliki foreign key constraint:  
     `$table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();` ([2026_09_18_013749_create_pertemuans_table.php:L13](file:///c:/laragon/www/agenda-mengajar-guru/database/migrations/2026_09_18_013749_create_pertemuans_table.php#L13)).
  3. Tabel `kehadiran_siswas`, `kehadiran_gurus`, dan `foto_buktis` semuanya memiliki foreign key `pertemuan_id` dengan aturan `cascadeOnDelete()`.
  4. Eksekusi `clear_before_import` dijalankan **DI LUAR TRANSAKSI BASIS DATA** (`DB::transaction`).
- **Dampak Nyata:** Jika seorang operator mencentang opsi *"Hapus jadwal lama"* dan berkas Excel yang diunggah mengalami galat (misal format kolom salah, nama guru tidak terdaftar, atau server PHP timeout), **seluruh jadwal lama sudah terhapus permanen**, dan mekanisme *foreign key cascade* otomatis **MENGHAPUS BERSIH SELURUH RIWAYAT KBM, JURNAL GURU, ABSENSI SISWA, DAN FOTO BUKTI SATU SEMESTER PENUH TANPA BISA DI-ROLLBACK**.
- **Rekomendasi Perbaikan:**
  1. Bungkus seluruh proses impor dan pembersihan jadwal ke dalam satu transaksi atomik `DB::transaction()`.
  2. Ganti `DB::table()->delete()` dengan *soft delete* via Eloquent (`JadwalPelajaran::where(...)->delete()`).
  3. Lepas aturan `cascadeOnDelete()` pada `pertemuans.jadwal_id` dan ganti dengan `restrictOnDelete()` atau `nullOnDelete()` untuk mencegah hilangnya data riwayat historis akademik.

---

### [SEC-001] Privilege Escalation & Akun Hijacking dari Role Admin ke Super Admin
- **Severity:** **HIGH** (P0)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [app/Http/Controllers/Admin/UserController.php:L244, L365, L548](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Admin/UserController.php#L244)
- **Bukti Kode:**
  ```php
  // UserController.php line 244 (store) & line 365 (update)
  'role' => ['required', 'in:guru,siswa,piket,tu,kepala_sekolah,pengawas,admin,super_admin'],
  ```
  ```php
  // UserController.php line 548-552 (destroy)
  public function destroy(User $user): RedirectResponse
  {
      if ($user->id === auth()->id()) {
          return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
      }
      // Tidak ada pengecekan apakah target user adalah Super Admin!
      $user->delete();
  }
  ```
- **Analisis & Pembuktian Masalah:**
  1. Rute `/admin/users` dilindungi middleware `role:admin,super_admin` ([routes/web.php:L104](file:///c:/laragon/www/agenda-mengajar-guru/routes/web.php#L104)).
  2. Pengguna dengan peran `admin` memiliki akses penuh ke form `create`, `store`, `edit`, `update`, dan `destroy`.
  3. Pada method `store()` dan `update()`, nilai `'role' => 'super_admin'` diterima tanpa memvalidasi apakah pengguna yang sedang login memiliki hak istimewa Super Admin (`isSuperAdmin()`).
  4. Pengguna dengan peran `admin` dapat mengedit akun miliknya sendiri atau akun orang lain menjadi `super_admin`, mengubah password akun `super_admin` yang sudah ada, atau menghapus akun `super_admin`.
- **Dampak Nyata:** Pelanggaran batas otorisasi hierarkis (*Broken Access Control*). Staf operator dengan akun admin biasa dapat mengambil alih (*takeover*) hak akses konfigurasi sistem sekolah di `/super-admin/settings`.
- **Rekomendasi Perbaikan:**
  1. Cegah pemilihan role `super_admin` jika `!auth()->user()->isSuperAdmin()`.
  2. Tambahkan pengecekan otorisasi pada `edit()`, `update()`, dan `destroy()` agar pengguna non-super_admin dilarang mengubah atau menghapus akun bertipe `super_admin`.

---

## 6. Temuan Medium & Low Severity

---

### [SEC-002] Ketiadaan Kewajiban Rotasi Password Default Siswa dan Guru
- **Severity:** **MEDIUM** (P1)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [app/Http/Controllers/Admin/UserController.php:L915](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Admin/UserController.php#L915), [SiswaController.php:L336](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Admin/SiswaController.php#L336), [User.php](file:///c:/laragon/www/agenda-mengajar-guru/app/Models/User.php)
- **Uraian:** Fitur reset password massal menyetel password guru menjadi NIP dan siswa menjadi NIS. NIP dan NIS merupakan identitas semi-publik yang tertera pada kartu pelajar dan daftar hadir fisik. Tabel `users` tidak memiliki flag `must_change_password`. Siswa dapat terus menggunakan NIS sebagai password tanpa batas waktu, membuka celah impersonasi akun untuk manipulasi foto kehadiran KBM.
- **Rekomendasi:** Tambahkan kolom boolean `must_change_password` pada `users`. Jika bernilai `true`, paksa pengalihan (*force redirect*) ke form pergantian password sebelum siswa/guru dapat mengakses menu dashboard.

---

### [SEC-003] Ketiadaan Rate Limiting pada Rute Kritis (Brute-Force & DoS)
- **Severity:** **MEDIUM** (P1)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [routes/web.php:L45, L69, L97](file:///c:/laragon/www/agenda-mengajar-guru/routes/web.php#L45)
- **Uraian:** Rute `POST /login`, `POST /siswa/jadwal/{id}/capture/{tgl}`, dan `GET /guru/pertemuan/{id}/download-foto-all` tidak menerapkan middleware `throttle:60,1` atau `throttle:login`. Ini memungkinkan serangan *credential stuffing* pada form login dan eksploitasi pembuatan berkas ZIP foto secara repetitif yang dapat menguras I/O disk dan CPU server.
- **Rekomendasi:** Pasang `throttle:10,1` pada endpoint login dan `throttle:6,1` pada endpoint pembuatan arsip ZIP foto.

---

### [LOGIC-001] Status Siswa Terlambat Dikecualikan dari Laporan Rekap PDF Kepala Sekolah
- **Severity:** **MEDIUM** (P1)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [app/Http/Controllers/KepalaSekolah/PdfController.php:L145-L155](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/KepalaSekolah/PdfController.php#L145-L155), [resources/views/kepala-sekolah/pdf/siswa.blade.php:L226-L233](file:///c:/laragon/www/agenda-mengajar-guru/resources/views/kepala-sekolah/pdf/siswa.blade.php#L226-L233)
- **Bukti Kode:**
  ```php
  // PdfController.php line 145-155
  $summary = $kehadiranData->groupBy('siswa_id')->map(function ($items) {
      return [
          'siswa' => $items->first()->siswa,
          'hadir' => $items->where('status', 'hadir')->count(),
          'sakit' => $items->where('status', 'sakit')->count(),
          'izin' => $items->where('status', 'izin')->count(),
          'alpa' => $items->where('status', 'alpa')->count(),
          'dispensasi' => $items->where('status', 'dispensasi')->count(),
          'total' => $items->count(), // Total menghitung seluruh baris termasuk status 'terlambat'
      ];
  });
  ```
- **Dampak Nyata:** Ketika ada siswa yang berstatus `terlambat`, sesi tersebut dihitung ke dalam kolom `Total`, namun tidak masuk ke dalam kategori `Hadir`, `Sakit`, `Izin`, `Alpa`, maupun `Dispensasi`. Akibatnya, pada dokumen cetak PDF resmi kepala sekolah, **jumlah rincian per kolom tidak sama dengan kolom Total** (`Hadir + Sakit + Izin + Alpa + Disp != Total`), menimbulkan keraguan akuntabilitas pelaporan dinas.
- **Rekomendasi:** Tambahkan kolom status `Terlambat` pada laporan PDF atau masukkan hitungan `terlambat` ke dalam kategori kehadiran sah siswa (sama seperti logika persentase kehadiran di `WaliKelasController`).

---

### [LOGIC-002] Ketidaksinkronan Kunci Pengaturan Kepala Sekolah pada Dokumen Cetak
- **Severity:** **LOW** (P2)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [resources/views/kepala-sekolah/pdf/siswa.blade.php:L271-L272](file:///c:/laragon/www/agenda-mengajar-guru/resources/views/kepala-sekolah/pdf/siswa.blade.php#L271-L272), [resources/views/kepala-sekolah/pdf/guru.blade.php:L331-L332](file:///c:/laragon/www/agenda-mengajar-guru/resources/views/kepala-sekolah/pdf/guru.blade.php#L331-L332), [SaSettingsController.php:L19](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/SuperAdmin/SettingsController.php#L19)
- **Uraian:** Template cetak PDF memanggil kunci `Setting::get('headmaster_name')` dan `Setting::get('headmaster_nip')`. Namun, form pengaturan Super Admin di [SaSettingsController.php](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/SuperAdmin/SettingsController.php) hanya menyimpan kunci `'principal_name'` dan tidak menyediakan isian NIP Kepala Sekolah. Akibatnya, nama kepala sekolah yang diperbarui oleh admin tidak pernah muncul pada cetak PDF dan selalu menampilkan teks bawaan *placeholder* (*Drs. H. Kepala Sekolah, M.Pd.*).
- **Rekomendasi:** Selaraskan kunci konfigurasi menjadi `principal_name` dan tambahkan field `principal_nip` serta `school_city` pada menu pengaturan Super Admin.

---

### [PERF-001] Agregasi Berat Tanpa Cache pada Rute Publik Digital Signage (`/`)
- **Severity:** **MEDIUM** (P2)
- **Confidence:** High
- **Status:** **CONFIRMED**
- **Lokasi Kode:** [app/Http/Controllers/PublicDashboardController.php:L226-L266](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/PublicDashboardController.php#L226-L266)
- **Uraian:** Halaman root publik `/` mengeksekusi lebih dari 35 query database setiap kali dimuat, termasuk perulangan `for ($i = 6; $i >= 0; $i--)` yang menjalankan query agregasi berulang dengan klausa `whereHas('pertemuan')`. Karena halaman ini ditampilkan pada monitor lobi TV sekolah yang me-refresh secara terus menerus, ini dapat menjadi sumber beban CPU database yang tinggi.
- **Rekomendasi:** Terapkan caching ringan `Cache::remember('public_dashboard_stats', 60, ...)` dengan durasi 30-60 detik untuk agregasi data statistik 7 hari.

---

## 7. Cross-Module Dependency Matrix

```
┌─────────────────────┐       ┌──────────────────────┐       ┌────────────────────────┐
│      User Model     │ ───►  │   JadwalPelajaran    │ ───►  │    Pertemuan Model     │
│   (Guru / Siswa)    │       │     (Master KBM)     │       │    (Sesi Pembelajaran) │
└─────────────────────┘       └──────────────────────┘       └────────────────────────┘
          │                              │                                │
          │                              │ Cascade on Delete              │ Cascade on Delete
          ▼                              ▼                                ▼
┌─────────────────────┐       ┌──────────────────────┐       ┌────────────────────────┐
│    SiswaProfile     │       │     Kelas Model      │       │ KehadiranGuru / Siswa  │
│   (NIS & Kelas ID)  │       │ (Blok, PKL, Rombel)  │       │ & FotoBukti (Histori)  │
└─────────────────────┘       └──────────────────────┘       └────────────────────────┘
```

* **Dampak Silang Impor Jadwal:** Modifikasi jadwal pelajaran berdampak langsung pada *PublicDashboard*, *PertemuanController*, *CaptureController Siswa*, dan rekap bulanan Wali Kelas/BK.
* **Dampak Silang Status Siswa:** Menonaktifkan akun siswa di modul Wali Kelas secara langsung memblokir hak akses upload foto di [CaptureController::show()](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Siswa/CaptureController.php#L26).

---

## 8. Security & Authorization Matrix

| Rute / Modul | Autentikasi | Middleware Otorisasi | Validasi Kepemilikan (Ownership) | Status Risiko Keamanan |
|---|:---:|:---:|:---:|:---:|
| `POST /login` | Publik | Tidak Ada | N/A | ⚠️ Tanpa Rate Limit (Brute Force) |
| `/siswa/capture/*` | Wajib | `role:siswa` | ✅ `$jadwal->kelas_id === $kelas->id` | Aman dari IDOR |
| `/guru/pertemuan/*` | Wajib | `role:guru` | ✅ `$jadwal->guru_id === Auth::id()` | Aman dari IDOR |
| `/guru/pertemuan/*/download-foto-all` | Wajib | `role:guru` | ✅ Pemilik Jadwal / Hak Akses | ⚠️ Tanpa Rate Limit (Arsip ZIP) |
| `/guru/wali-kelas/siswa/*/toggle` | Wajib | `role:guru` | ✅ Siswa berada dalam binaan kelas wali | Aman dari IDOR |
| `/admin/users/*` | Wajib | `role:admin,super_admin` | ❌ Tidak ada proteksi hierarki role | 🚨 **Vulnerable (SEC-001)** |
| `GET /` (Public Dashboard) | Publik | Tidak Ada | N/A | ⚠️ Uncached Heavy Aggregation |

---

## 9. Database & Migration Safety Review

1. **Integritas Constraint Foreign Key:**  
   Pola `cascadeOnDelete()` pada tabel `pertemuans` terhadap `jadwal_pelajarans` merupakan risiko arsitektur nomor satu. Selama jadwal dihapus melalui Eloquent *SoftDeletes*, histori aman. Namun, penggunaan query mentah `DB::table('jadwal_pelajarans')->delete()` memicu penghapusan permanen ke data anak.
2. **Migrasi RPL &rarr; PPLG (`2026_10_13_080000_unify_rpl_to_pplg_data_and_schema.php`):**  
   Telah diaudit dan terverifikasi aman:
   - Bersifat defensif terhadap benturan nama ganda.
   - Mengalihkan foreign key relasi siswa dan jadwal sebelum menghapus baris duplikat.
   - Mendukung *rollback down()* penuh tanpa memutus relasi integer `kelas_id`.

---

## 10. Business Workflow Integrity

1. **Alur Presensi Visual (Dual-Capture):**  
   - Integritas alur sangat tinggi: Waktu toleransi keterlambatan dihitung otomatis berdasarkan jam pertama (10 menit) vs jam lanjutan (15 menit).  
   - Validasi batas maksimal 7 hari ke belakang mencegah manipulasi absensi lampau.
2. **Alur Bypass PKL Kelas 12:**  
   - Berfungsi konsisten: Selama rentang tanggal PKL, rute upload siswa diblokir dan jam mengajar guru tidak dihitung alpa di rekapitulasi.
3. **Alur Kenaikan Kelas & Retensi:**  
   - Menggunakan transaksi atomik `DB::transaction()`. Siswa tinggal kelas dipertahankan di rombel asal dan siswa kelas 12 dinonaktifkan tanpa menghapus rekap kehadiran historis.

---

## 11. Performance & Operational Reliability

1. **Penggunaan Memori Generator PDF (`DomPDF`):**  
   Pada filter semester dengan ribuan sesi pertemuan siswa, `DomPDF` memuat seluruh struktur HTML ke memori. Disarankan mengekspor rekap per rombel/kelas (bukan seluruh siswa sekolah sekaligus) untuk mencegah lonjakan memori PHP-FPM di atas 256MB.
2. **Penyimpanan Berkas Foto WebP:**  
   Kompresi WebP client-side dan server-side berhasil mereduksi ukuran file dari 5MB-15MB menjadi rata-rata 100KB-250KB per foto. Namun, sekolah dengan 36 rombel x 8 JP x 2 foto = ~576 foto/hari (~80MB/hari atau ~9.6GB/semester). Dibutuhkan strategi *pruning* foto semester lampau.

---

## 12. Test Suite Verification

Audit menjalankan seluruh pengujian otomatis lokal secara independen:
* **Perintah:** `vendor/bin/phpunit --colors=never`
* **Hasil Pengujian:**
  - **Total Tests:** 119
  - **Passed:** 118
  - **Failed:** 0
  - **Skipped:** 1 (`ExampleTest`)
  - **Total Assertions:** 639
  - **Durasi Eksekusi:** 21.67 detik
* **Validasi Independen:** Pengujian membuktikan bahwa perbaikan soft-delete sebelumnya tidak merusak fungsionalitas lain.

---

## 13. Test Coverage Gaps (Kekosongan Pengujian)

Area penting yang saat ini **belum memiliki pengujian otomatis (test gap)**:
1. **Privilege Escalation Test:** Belum ada test yang memverifikasi bahwa akun `admin` ditolak saat mencoba membuat akun `super_admin` atau mengubah password `super_admin`.
2. **Import Failure Transaction Rollback:** Belum ada test yang menguji apakah jadwal lama tetap utuh jika berkas Excel yang diunggah mengalami galat di baris ke-50.
3. **Rate Limiting Tests:** Belum ada pengujian batas request pada endpoint autentikasi.
4. **PDF Late Attendance Consistency Test:** Belum ada assertion yang memeriksa kesesuaian angka status `terlambat` pada output laporan PDF Kepala Sekolah.

---

## 14. Prioritized Remediation Roadmap

### Prioritas P0 — Kritis & Keamanan Mendesak
1. **Perbaikan DATA-001 (Impor Jadwal Transaksional):** Bungkus penghapusan jadwal ke dalam `DB::transaction()` dan hindari hard-delete non-transaksional agar riwayat KBM tidak musnah saat impor gagal.
2. **Perbaikan SEC-001 (Privilege Escalation Guard):** Tambahkan otorisasi hierarkis pada [UserController](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/Admin/UserController.php) agar hanya `super_admin` yang dapat membuat, mengedit, atau menghapus pengguna ber-role `super_admin`.

### Prioritas P1 — Integritas Bisnis & Pelaporan
3. **Perbaikan LOGIC-001 (Presensi Terlambat di PDF):** Masukkan status `terlambat` ke dalam summary dan tabel laporan PDF Kepala Sekolah di [PdfController.php](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/KepalaSekolah/PdfController.php).
4. **Perbaikan SEC-003 (Rate Limiting):** Pasang middleware `throttle:10,1` pada rute `POST /login` dan batasi endpoint unduh arsip ZIP.

### Prioritas P2 — Reliabilitas & Performa
5. **Perbaikan LOGIC-002 (Kunci Pengaturan Kepala Sekolah):** Selaraskan kunci `principal_name` di view PDF dan lengkapi input NIP di pengaturan Super Admin.
6. **Perbaikan PERF-001 (Cache Monitor Publik):** Pasang cache 30-60 detik untuk kueri agregasi 7 hari di [PublicDashboardController.php](file:///c:/laragon/www/agenda-mengajar-guru/app/Http/Controllers/PublicDashboardController.php).

### Prioritas P3 — Hardening Lanjutan
7. **Perbaikan SEC-002 (Rotasi Password):** Implementasikan flag `must_change_password` untuk akun yang menggunakan password default NIS/NIP.

---

## 15. Deployment Preconditions & Rollback Considerations

Sebelum melakukan rilis pembaruan ke server produksi `agenda-guru.smkn1ciamis.id`:
1. **Prasyarat Dump Database:** Wajib melakukan dump SQL lengkap (`mysqldump`) sesaat sebelum eksekusi `git pull` atau `php artisan migrate`.
2. **Symlink Storage:** Verifikasi `public/storage` mengarah secara valid ke `storage/app/public` (`php artisan storage:link`).
3. **Build Aset Frontend:** Jalankan `npm run build` jika terdapat perubahan pada antarmuka Blade atau CSS Tailwind v4.
4. **Rollback Strategy:** Jika migrasi kurikulum mengalami kendala, file migrasi [2026_10_13_080000_unify_rpl_to_pplg_data_and_schema.php](file:///c:/laragon/www/agenda-mengajar-guru/database/migrations/2026_10_13_080000_unify_rpl_to_pplg_data_and_schema.php) telah memiliki method `down()` yang teruji mengembalikan penamaan ke format RPL.

---

## 16. Hal yang Belum Dapat Diverifikasi (Unverified)

1. **Konfigurasi PHP-FPM Server Produksi:** Batas `max_execution_time` dan `memory_limit` pada web server produksi `agenda-guru.smkn1ciamis.id` tidak dapat dibaca dari lingkungan lokal.
2. **Sisa Kapasitas Disk Produksi:** Sisa ruang penyimpanan untuk direktori `storage/app/public/foto-bukti` di server sekolah.

---

## 17. Ringkasan File yang Diperiksa

- **Controllers:** `AuthController`, `ProfileController`, `PublicDashboardController`, `CaptureController`, `PertemuanController`, `WaliKelasController`, `BkController`, `KaprogController`, `Piket\DashboardController`, `TeguranController`, `Admin\UserController`, `Admin\SiswaController`, `Admin\KelasController`, `Admin\JadwalController`, `Admin\KelasPklController`, `Admin\KalenderBlokController`, `Admin\HariLiburController`, `Admin\PenggunaController`, `KepalaSekolah\PdfController`, `SuperAdmin\SettingsController`.
- **Models:** `User`, `Kelas`, `MataPelajaran`, `JadwalPelajaran`, `Pertemuan`, `KehadiranGuru`, `KehadiranSiswa`, `FotoBukti`, `KelasPkl`, `KalenderBlokMinggu`, `Setting`, `HariLibur`, `GuruProfile`, `SiswaProfile`.
- **Services & Imports:** `ImageCompressor`, `JadwalBlokResolverService`, `JadwalImport`.
- **Views:** Template PDF Kepala Sekolah (`guru.blade.php`, `siswa.blade.php`), views capture siswa, show pertemuan guru, index admin.
- **Konfigurasi & Migrasi:** `config/auth.php`, `config/session.php`, `config/filesystems.php`, `phpunit.xml`, serta 48 file migrasi database.

---
*Laporan ini dihasilkan secara objektif oleh Senior Laravel Software Architect & Application Security Auditor berdasarkan bukti kode riil.*

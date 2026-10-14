# LAPORAN PERUBAHAN & HASIL PENGETESAN SISTEM (CHANGELOG & AUDIT REPORT)

**Tanggal:** 14 Oktober 2026  
**Aplikasi:** Agenda Mengajar Guru (SOPAN - SMKN 1 Ciamis)  
**Framework:** Laravel 11 / PHP 8.5  
**Status Audit & Build:** ✅ **PASSED (Aman untuk Production Server)**

---

## 1. Ringkasan Eksekutif

Sesi pengembangan dan audit ini mencakup perombakan besar untuk modul **Super Admin**, peningkatan laporan visual **Kepala Sekolah** (Siswa & Guru), optimasi akses guru dengan peran ganda (**Guru BK & Kaprog**), serta perbaikan antarmuka mobile (**PWA Sidebar & Touch**).

Seluruh perubahan telah di-commit secara terpisah dan terstruktur ke dalam **11 commit atomik** berstandar *Conventional Commits*, serta divalidasi melalui pengujian unit/fitur otomatis (**PHPUnit**), pemformatan kode (**Laravel Pint**), dan inspeksi antarmuka browser (**Browser Automation**).

---

## 2. Rincian Perubahan per Modul (11 Unit Atomik)

### Commit 1: `fix(layout): resolve mobile sidebar open on page load and improve touch dismissal`
- **File:** `resources/views/components/layouts/{admin,guru,kepala-sekolah,app}.blade.php`
- **Perubahan:**
  - Memperbaiki bug pada perangkat seluler (PWA di ponsel) di mana sidebar selalu otomatis terbuka saat berpindah halaman dan sulit ditutup.
  - Memperbaiki event handler tombol hamburger, backdrop blur, tombol close `(X)`, dan area luar sidebar agar responsif terhadap sentuhan layar tanpa *touch conflict*.

### Commit 2: `feat(guru-bk): add configurable kelas binaan in teacher profile with dedicated access`
- **File:** `app/Http/Controllers/ProfileController.php`, `app/Http/Controllers/Guru/BkController.php`, `resources/views/profile/index.blade.php`, `resources/views/guru/bk/index.blade.php`, `tests/Feature/GuruBkKelasBinaanTest.php`
- **Perubahan:**
  - Menyediakan fitur konfigurasi mandiri kelas binaan bagi guru yang merangkap sebagai Guru Bimbingan Konseling (BK) langsung pada halaman profil pribadi (`/profile`).
  - Guru BK dapat memilih rombel kelas binaan (misal: hanya kelas 10 atau kelas tertentu), dan sistem otomatis membatasi akses monitoring kasus siswa hanya pada kelas binaannya.

### Commit 3: `feat(wali-kelas): enhance attendance recap dashboard with monthly breakdown and pdf export`
- **File:** `app/Http/Controllers/Guru/WaliKelasController.php`, `resources/views/guru/wali-kelas/index.blade.php`, `resources/views/guru/wali-kelas/pdf-bulanan.blade.php`
- **Perubahan:**
  - Merombak dashboard rekapitulasi kehadiran wali kelas agar transparan dan mudah dipahami.
  - Memisahkan agregasi kehadiran menjadi rekapan semester dan rekapan per bulan dengan badge persentase kehadiran individual.
  - Menyediakan fitur cetak laporan ke PDF per bulan untuk arsip administrasi wali kelas.

### Commit 4: `feat(admin): add tingkat and jurusan filters to class and student management`
- **File:** `app/Http/Controllers/Admin/KelasController.php`, `app/Http/Controllers/Admin/SiswaController.php`, `resources/views/admin/kelas/index.blade.php`, `resources/views/admin/siswa/index.blade.php`
- **Perubahan:**
  - Menambahkan filter dropdown berdasarkan **Tingkat Kelas** (10, 11, 12) dan **Jurusan / Konsentrasi Keahlian** pada Manajemen Kelas dan Manajemen Siswa.
  - Menghilangkan beban scroll panjang bagi staf administrasi saat mencari rombel kelas tertentu.

### Commit 5: `feat(kaprog): add program head vocational department mapping and badges in user management`
- **File:** `app/Http/Controllers/Admin/PenggunaController.php`, `app/Http/Controllers/Guru/KaprogController.php`, `resources/views/admin/pengguna/{create,edit,index}.blade.php`, `resources/views/admin/users/index.blade.php`, `database/migrations/2026_10_14_100000_seed_kaprog_jurusan_data.php`
- **Perubahan:**
  - Menghubungkan akun Kepala Program Keahlian (Kaprog) ke jurusan binaan masing-masing di SMKN 1 Ciamis (Kuliner, PPLG, DKV, AKL, Pemasaran, MPLB, Perhotelan).
  - Menampilkan badge jabatan Kaprog beserta nama jurusannya di seluruh daftar pengguna admin.

### Commit 6: `feat(super-admin): implement comprehensive audit trail observer and activity log viewer`
- **File:** `database/migrations/2026_10_14_101821_create_activity_logs_table.php`, `app/Models/ActivityLog.php`, `app/Services/ActivityLogger.php`, `app/Observers/AuditTrailObserver.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/SuperAdmin/ActivityLogController.php`, `resources/views/super-admin/activity-logs/index.blade.php`
- **Perubahan:**
  - Mencatat rekam jejak (*Audit Trail*) secara otomatis untuk aksi mutasi penting (`CREATE`, `UPDATE`, `DELETE`, `RESTORE`, `LOGIN`, `IMPERSONATE`) pada data Master Siswa, Guru, Kelas, Jadwal, dan Agenda KBM.
  - Menyimpan perbedaan data lama vs baru (`old_values` vs `new_values` JSON), alamat IP, dan User Agent.
  - Antarmuka peninjauan log interaktif dengan modal JSON inspector.

### Commit 7: `feat(super-admin): add secure user impersonation mechanism with banner controls`
- **File:** `app/Http/Controllers/SuperAdmin/ImpersonateController.php`
- **Perubahan:**
  - Fitur "Masuk Sebagai" (*Login as User*) eksklusif bagi Super Admin untuk melakukan audit atau investigasi kendala guru/siswa tanpa memerlukan kata sandi mereka.
  - Dilengkapi banner oranye persisten di bagian atas layar dengan tombol instan untuk kembali ke sesi Super Admin asli.
  - Diproteksi agar Super Admin tidak dapat menyamar sebagai sesama akun Super Admin.

### Commit 8: `feat(super-admin): add academic year lifecycle management with cut-off mutation locks`
- **File:** `database/migrations/2026_10_14_102644_create_academic_years_table.php`, `app/Models/AcademicYear.php`, `app/Http/Middleware/CheckAcademicPeriodLock.php`, `app/Http/Controllers/SuperAdmin/AcademicYearController.php`, `resources/views/super-admin/academic-years/index.blade.php`
- **Perubahan:**
  - Pengelolaan siklus tahun ajaran & semester dengan status Aktif/Nonaktif.
  - Fitur **Kunci Periode (Cut-Off: Read-Only)**: Saat semester ditutup, seluruh pengisian presensi dan agenda mengajar terkunci menjadi hanya baca (*Read-Only*) bagi guru, sedangkan Super Admin tetap memiliki hak akses koreksi (*bypass*).
  - Mekanisme *self-healing fallback* otomatis membuat record awal jika tabel database kosong.

### Commit 9: `feat(super-admin): add central trash manager with batch restore and permanent wipe`
- **File:** `database/migrations/2026_10_14_103127_add_soft_deletes_to_pertemuans_table.php`, `app/Models/Pertemuan.php`, `app/Http/Controllers/SuperAdmin/TrashController.php`, `resources/views/super-admin/trash/index.blade.php`
- **Perubahan:**
  - Pusat pemulihan data master (*Trash Manager*) terpusat untuk Data Guru, Siswa, Kelas, Jadwal, Mapel, dan Agenda KBM yang terhapus sementara (*Soft Deletes*).
  - Menyediakan tombol pemulihan per entri, pulihkan semua data, serta pembersihan permanen (*Empty Trash*).

### Commit 10: `feat(super-admin): introduce system settings and lockout-proof maintenance mode`
- **File:** `app/Http/Controllers/SuperAdmin/SettingsController.php`, `app/Http/Controllers/SuperAdmin/DashboardController.php`, `resources/views/super-admin/settings.blade.php`, `resources/views/super-admin/dashboard.blade.php`, `app/Http/Middleware/CheckMaintenanceMode.php`, `resources/views/errors/maintenance.blade.php`, `app/Http/Controllers/AuthController.php`, `bootstrap/app.php`, `routes/web.php`, `tests/Feature/SuperAdminSuiteTest.php`
- **Perubahan:**
  - Pengaturan global: toleransi keterlambatan presensi, aturan backdate pengisian agenda guru, radius geofencing sekolah, reset password massal siswa ke NIS, dan mode pemeliharaan (*Maintenance Mode*).
  - Arsitektur middleware anti-terkunci (*lockout-proof*): rute `/login` tetap terbuka bagi Admin/Super Admin saat maintenance mode aktif, sementara guru/siswa menerima pesan informatif saat mencoba masuk.

### Commit 11: `feat(kepala-sekolah): add attendance percentage distribution pivot analysis and pie charts for teachers and students`
- **File:** `app/Http/Controllers/KepalaSekolah/ReportController.php`, `app/Http/Controllers/KepalaSekolah/PdfController.php`, `resources/views/kepala-sekolah/report/siswa.blade.php`, `resources/views/kepala-sekolah/report/guru.blade.php`, `resources/views/kepala-sekolah/dashboard.blade.php`, `tests/Feature/KepalaSekolahReportGuruTest.php`, `tests/Feature/KepalaSekolahReportSiswaTest.php`
- **Perubahan:**
  - **Modul Kehadiran Siswa & Guru di Kepala Sekolah:**
    - Panel Distribusi Persentase Kehadiran gaya Pivot Excel: rincian keterangan kehadiran (Hadir `-`, Sakit klinis, Izin, Alpa, Dinas Luar, Cuti, dsb) lengkap dengan *Count of Status*, *Persentase desimal 2 digit*, dan *Bar Distribusi*.
    - Visualisasi **Pie Chart interaktif (Chart.js)** dengan palet warna elegan dan legenda komposisi utama.
    - Tab pemilihan **Angkatan (Tingkat 10, 11, 12)** dan dropdown filter **Jurusan**.
    - Integrasi cetak **PDF** yang selaras dengan filter angkatan dan jurusan yang sedang aktif.

---

## 3. Hasil Audit Keamanan & Pencegahan Bug Server

Selama proses audit pra-rilis, ditemukan 4 potensi jebakan produksi (*server traps*) yang telah dinetralkan sebelum commit:

| No | Potensi Resiko / Bug | Dampak di Production | Solusi yang Diterapkan | Status |
|:--:|:---|:---|:---|:---:|
| 1 | **Admin Lockout pada Maintenance Mode** | Super Admin tidak bisa login kembali setelah logout saat maintenance aktif karena `/login` terkena 503. | Pengecualian rute `/login` di `CheckMaintenanceMode` dan seleksi role di `AuthController`. | **Terselesaikan** |
| 2 | **500 BadMethodCall pada Kosongkan Sampah** | Error saat Super Admin mengklik tombol "Kosongkan Sampah" karena metode controller bernama `emptyTrash` bukan `empty`. | Menambahkan metode alias `empty(string $type)` di `TrashController.php`. | **Terselesaikan** |
| 3 | **Null Pointer pada Banner Penyamaran** | Crash `Attempt to read property "name" on null` jika cookie sesi memiliki `impersonator_id` namun user tidak terotentikasi. | Menambahkan proteksi ganda `@if(session()->has('impersonator_id') && auth()->check())`. | **Terselesaikan** |
| 4 | **Migrasi Gagal Karena Tabel/Kolom Eksis** | `php artisan migrate` berpotensi error duplikasi jika kolom atau tabel sudah dibuat parsial di database server. | Menambahkan klausul `if (! Schema::hasTable(...))` dan `if (! Schema::hasColumn(...))` pada seluruh file migrasi baru. | **Terselesaikan** |

---

## 4. Laporan Pengetesan (Testing & Verification)

### A. Pengujian Otomatis (PHPUnit Feature Tests)
Pengujian dijalankan pada lingkungan SQLite in-memory:

1. **`tests/Feature/SuperAdminSuiteTest.php` (4/4 Passed):**
   - `test_academic_year_get_active_self_heals_when_table_is_empty` -> **PASS**
   - `test_maintenance_mode_allows_login_page_and_super_admin` -> **PASS**
   - `test_super_admin_cannot_impersonate_another_super_admin` -> **PASS**
   - `test_trash_empty_route_does_not_throw_method_not_found` -> **PASS**

2. **`tests/Feature/KepalaSekolahReportGuruTest.php` (4/4 Passed):**
   - `test_kepsek_can_access_laporan_kehadiran_guru_page` -> **PASS**
   - `test_breakdown_percentage_of_guru_reasons_calculated_accurately` -> **PASS**
   - `test_filter_guru_report_by_tingkat_and_jurusan` -> **PASS**
   - `test_export_pdf_guru_report_works` -> **PASS**

3. **`tests/Feature/KepalaSekolahReportSiswaTest.php` (3/3 Passed):**
   - `test_kepsek_can_access_laporan_kehadiran_siswa_page` -> **PASS**
   - `test_breakdown_percentage_of_reasons_calculated_accurately` -> **PASS**
   - `test_filter_by_tingkat_and_jurusan_works` -> **PASS**

4. **`tests/Feature/GuruBkKelasBinaanTest.php` (4/4 Passed):**
   - Seluruh pengujian pemetaan kelas binaan dan akses dashboard BK -> **PASS**

*Total Hasil Pengujian Otomatis:* **15 Tests, 48 Assertions, 0 Failures, 0 Errors.**

### B. Standardisasi Gaya Kode (Laravel Pint)
Semua file PHP baru dan termodifikasi telah diformat menggunakan perintah:
```bash
vendor/bin/pint --dirty --format agent
```
*Hasil:* **100% Passed (Clean)**, sesuai aturan PSR-12 dan panduan Laravel Boost.

### C. Pengujian Antarmuka Langsung (Browser Subagent Verification)
- Telah diverifikasi langsung melalui browser di `http://127.0.0.1:8008/kepala-sekolah/report/guru`.
- Grafik Pie Chart ter-render sempurna tanpa error JavaScript di console.
- Tabel pivot alasan kehadiran menampilkan data secara akurat.
- Filter tab angkatan, filter jurusan, dan modal preview foto bukti siswa berfungsi normal.

---

## 5. Panduan Menarik Perubahan di Server (Deployment Checklist)

Saat menarik (*pull*) repositori ke server produksi, jalankan langkah-langkah berikut:

```bash
# 1. Tarik pembaruan commit terbaru
git pull origin <nama-branch>

# 2. Instal dependensi dan optimalkan autoloader
composer install --no-dev --optimize-autoloader

# 3. Jalankan migrasi database aman (idempotent)
php artisan migrate --force

# 4. Bersihkan dan perbarui cache aplikasi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

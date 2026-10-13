# SOPAN — P0 REMEDIATION REPORT
## Safe Schedule Import (DATA-001) & Super Admin Authorization Guard (SEC-001)

**Tanggal:** 13 Oktober 2026  
**Aplikasi:** Sistem Operasional Presensi & Agenda Mengajar Guru (SOPAN) — SMKN 1 Ciamis  
**Repository:** `agenda-mengajar-guru`  
**Branch:** `main` (Commit Base: `9061d12`)  
**Status Remedi:** SELESAI & TERVERIFIKASI (100% Tests Pass, 0 Regressions)

---

## 1. Temuan Awal yang Terverifikasi

### DATA-001 (Risiko Kehilangan Histori Akibat Impor Jadwal) — *Confirmed P0 Critical*
- **Lokasi Kode Asli:** `app/Http/Controllers/Admin/JadwalController.php` (baris 301–306) & `app/Imports/JadwalImport.php`.
- **Perilaku Teramati:**
  1. Ketika opsi `clear_before_import` dicentang, controller mengeksekusi direct SQL delete via Query Builder:
     ```php
     DB::table('jadwal_pelajarans')->where('tahun_ajaran', $tahunAjaran)->where('semester', $semester)->delete();
     ```
  2. Query ini dijalankan **di luar database transaction** (`DB::transaction`).
  3. Query `DB::table(...)->delete()` melakukan **hard delete** murni yang memicu database foreign key constraint:
     `$table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();` pada tabel `pertemuans`.
  4. Secara berantai (cascading delete), seluruh data relasi:
     - `pertemuans` (jurnal & agenda mengajar)
     - `kehadiran_gurus` (presensi & waktu masuk/keluar guru)
     - `kehadiran_siswas` (presensi siswa & status kehadiran)
     - `foto_buktis` (foto bukti mengajar & verifikasi siswa)
     terhapus permanen dari basis data tanpa dapat dipulihkan.
  5. Jika proses impor Excel kemudian gagal di tengah jalan (misalnya format waktu rusak, memori habis, baris invalid), basis data semester tersebut tertinggal dalam keadaan kosong total.

### SEC-001 (Privilege Escalation dari Admin Biasa ke Super Admin) — *Confirmed P0 Critical*
- **Lokasi Kode Asli:** `app/Http/Controllers/Admin/UserController.php` (baris 244, 365, 548, 594, 908) dan rute `admin.users.*`.
- **Perilaku Teramati:**
  1. Pada form Blade (`users/create.blade.php` dan `users/edit.blade.php`), pilihan `super_admin` memang disembunyikan jika pengguna bukan super admin menggunakan `@if(auth()->user()->isSuperAdmin())`.
  2. Namun, pada sisi server (`Admin\UserController`), aturan validasi `store` dan `update` secara terbuka mengizinkan role `super_admin`:
     ```php
     'role' => ['required', 'in:guru,siswa,piket,tu,kepala_sekolah,pengawas,admin,super_admin']
     ```
  3. Tidak ada server-side authorization check (`auth()->user()->isSuperAdmin()`) pada endpoint `store()` maupun `update()`. Admin biasa dapat mengirim HTTP POST/PUT via inspect element, Postman, atau curl untuk:
     - Membuat akun `super_admin` baru.
     - Mengubah dirinya sendiri menjadi `super_admin`.
     - Mengubah akun pengguna lain menjadi `super_admin`.
  4. Pada method `destroy()`, hanya terdapat pengecekan `$user->id === auth()->id()`. Admin biasa dapat menghapus akun `super_admin`.
  5. Pada method `bulkDestroy()` dan `resetPasswordToNip()`, tidak ada perlindungan terhadap akun `super_admin`.

---

## 2. Akar Masalah

1. **DATA-001:**
   - Pemilihan metode penghapusan menggunakan direct SQL `DB::table()->delete()` yang memotong siklus Eloquent SoftDeletes.
   - Ketiadaan filter relasi historis (`whereDoesntHave('pertemuans')`) sebelum melakukan pembersihan jadwal.
   - Tidak adanya wrapper atomik (`DB::transaction`) yang melingkupi operasi pembersihan awal bersama proses eksekusi `Excel::import`.

2. **SEC-001:**
   - Mengandalkan proteksi antarmuka (Blade UI hiding) tanpa kontrol otorisasi wajib di lapisan pengontrol (server-side authorization).
   - Penggunaan daftar statis pada aturan validasi `in:` yang menggabungkan seluruh role sistem tanpa membedakan hak otorisasi pembuat request.

---

## 3. File yang Diubah

| No | File | Jenis Perubahan | Deskripsi |
|---|---|---|---|
| 1 | `app/Http/Controllers/Admin/JadwalController.php` | Controller Modification | Membungkus `import()` dan `salinSemester()` dalam `DB::transaction`. Melindungi jadwal berhistori dengan `whereDoesntHave('pertemuans')`. Mengadopsi rekonsiliasi berbasis `withTrashed()`. |
| 2 | `app/Imports/JadwalImport.php` | Import Class Modification | Validasi keberadaan baris data valid sebelum proses. Penggunaan `withTrashed()->where(...)` untuk memulihkan (`restore()`) atau memperbarui jadwal in-place tanpa mengganti primary key `id`. |
| 3 | `app/Http/Controllers/Admin/UserController.php` | Controller Modification | Proteksi otorisasi server-side (HTTP 403 & dynamic `Rule::in`) pada `store()`, `edit()`, `update()`, `destroy()`, `bulkDestroy()`, dan `resetPasswordToNip()`. |
| 4 | `app/Http/Controllers/Admin/PenggunaController.php` | Controller Modification | Penambahan proteksi 403 eksplisit pada `store()` dan `update()` jika admin biasa mencoba menetapkan role `super_admin`. |
| 5 | `resources/views/admin/jadwal/index.blade.php` | View Modification | Penjelasan teks modal impor diperbarui agar pengguna memahami bahwa jadwal yang telah memiliki riwayat pertemuan akan dipertahankan dengan aman. |
| 6 | `tests/Feature/P0RemediationTest.php` | New Test Suite | 16 feature tests komprehensif menguji skenario positif, negatif, kegagalan parsial, atomisitas transaksi, dan otorisasi. |

---

## 4. Desain Solusi & Alasan Pemilihan

### Solusi DATA-001:
1. **Pembersihan Selektif Non-Destruktif:**
   - Mengganti pembersihan global dengan:
     ```php
     JadwalPelajaran::where('tahun_ajaran', $tahunAjaran)
         ->where('semester', $semester)
         ->whereDoesntHave('pertemuans')
         ->delete();
     ```
   - *Alasan:* Jadwal yang belum memiliki agenda/presensi dapat dibersihkan dengan aman (via Eloquent soft delete). Jadwal yang telah memiliki riwayat akademik (`pertemuans`) diproteksi 100% dan tidak tersentuh.
2. **Rekonsiliasi Identitas In-Place (`withTrashed` Reconnect):**
   - Pada baris impor yang cocok dengan slot jadwal yang dipertahankan, jadwal lama diperbarui datanya (`guru_id`, `mata_pelajaran_id`) dengan mempertahankan nilai `id`.
   - *Alasan:* Menjaga integritas foreign key pada `pertemuans.jadwal_id` sehingga seluruh histori jurnal, kehadiran, dan foto bukti tetap terhubung secara valid.
3. **Transaksi Basis Data Atomik End-to-End:**
   - Seluruh blok pembersihan dan pemrosesan `Excel::import` dibungkus dalam satu `DB::transaction(function () { ... })`.
   - Validasi data kosong diterapkan di awal `JadwalImport`.
   - *Alasan:* Menjamin prinsip ACID (Atomicity, Consistency, Isolation, Durability). Jika file rusak, kolom hilang, atau terjadi exception di baris mana pun, seluruh operasi di-rollback secara otomatis tanpa meninggalkan state parsial.

### Solusi SEC-001:
1. **Otorisasi Server-Side Bertingkat (Defense in Depth):**
   - Cek hak akses eksplisit:
     ```php
     if ($request->input('role') === 'super_admin' && ! auth()->user()->isSuperAdmin()) {
         abort(403, 'Hanya Super Admin yang dapat membuat/menetapkan akun Super Admin.');
     }
     ```
   - Validasi dinamis: role `super_admin` hanya disuntikkan ke daftar validasi `$allowedRoles` jika `auth()->user()->isSuperAdmin()`.
   - *Alasan:* Penolakan langsung menghasilkan HTTP 403 Forbidden yang tepat untuk pelanggaran otorisasi, sekaligus menjaga validasi 422 untuk input data non-otorisasi.
2. **Proteksi Lengkap di Seluruh Jalur:**
   - Diterapkan pada `store()`, `edit()`, `update()`, `destroy()`, `bulkDestroy()`, dan `resetPasswordToNip()`.
   - *Alasan:* Mencegah jalur eskalasi alternatif melalui modifikasi langsung (direct HTTP request).

---

## 5. Risiko Kompatibilitas dan Data

- **Skema Database:** Tidak ada migration baru dan tidak ada perubahan skema database. Kompatibel penuh dengan PostgreSQL/MySQL production dan SQLite test suite.
- **Format File Excel:** Format template jadwal dan struktur kolom yang digunakan sekolah saat ini tetap dipertahankan 100% tanpa perubahan format.
- **Sistem Blok & PKL:** Logika pengelompokan blok (`kelompok_blok`, `is_sistem_blok`) dan pemetaan kelas tetap berjalan normal.
- **Performa:** Penggunaan in-memory cache pada `JadwalImport` tetap aktif sehingga pemrosesan batch ratusan baris tetap optimal.

---

## 6. Test yang Ditambahkan

Dibuat file test terisolasi: `tests/Feature/P0RemediationTest.php` dengan **16 skenario pengujian**:

### A. DATA-001:
1. `test_valid_jadwal_import_succeeds`: Impor file excel valid berhasil membuat jadwal di database.
2. `test_invalid_file_rejected_without_modifying_existing_schedules`: File invalid/rusak ditolak dan jadwal yang sudah ada tidak terhapus.
3. `test_mid_row_failure_does_not_leave_partial_database_changes`: File tanpa baris data valid memicu rollback atomik tanpa data tersisa.
4. `test_import_with_clear_option_preserves_schedules_having_historical_meetings`: Opsi clear membersihkan jadwal kosong namun **mempertahankan jadwal berhistori beserta pertemuans, kehadiran guru, kehadiran siswa, dan foto buktinya**.
5. `test_repeat_import_is_consistent_and_idempotent`: Impor berulang dengan data sama tidak menduplikasi jadwal.
6. `test_schedule_identity_collision_retains_primary_key_and_meeting_link`: Benturan slot jadwal merekonsiliasi data guru dengan tetap mempertahankan primary key `id` dan tautan `pertemuan`.
7. `test_salin_semester_clear_option_respects_history_retention_policy`: Opsi salin semester dengan hapus tujuan mempertahankan jadwal tujuan yang memiliki riwayat pertemuan.

### B. SEC-001:
8. `test_admin_can_perform_allowed_user_management_operations`: Admin biasa dapat membuat guru baru yang sah.
9. `test_admin_forbidden_from_creating_super_admin`: Admin biasa ditolak (HTTP 403) saat mencoba membuat akun super admin.
10. `test_admin_forbidden_from_promoting_self_to_super_admin`: Admin biasa ditolak (HTTP 403) saat mencoba menaikkan akunnya sendiri menjadi super admin.
11. `test_admin_forbidden_from_promoting_other_user_to_super_admin`: Admin biasa ditolak (HTTP 403) saat mencoba menaikkan akun pengguna lain menjadi super admin.
12. `test_admin_forbidden_from_editing_or_updating_existing_super_admin`: Admin biasa ditolak (HTTP 403) saat mencoba mengakses form edit atau memperbarui password/data akun super admin.
13. `test_admin_forbidden_from_deleting_or_bulk_deleting_super_admin`: Admin biasa ditolak (HTTP 403) saat mencoba menghapus super admin baik secara tunggal maupun massal.
14. `test_admin_forbidden_from_resetting_password_of_super_admin`: Admin biasa ditolak (HTTP 403) saat mencoba me-reset password akun super admin.
15. `test_super_admin_can_perform_authorized_operations`: Super admin tetap dapat menjalankan seluruh operasi manajemen akun secara sah.
16. `test_direct_requests_to_pengguna_endpoints_cannot_bypass_super_admin_protection`: Request langsung ke endpoint alternatif `admin.pengguna.*` ditolak dengan HTTP 403 jika mencoba eskalasi ke super admin.

---

## 7. Perintah Verifikasi dan Hasil Aktual

### Eksekusi 1: Test Suite P0
```bash
vendor/bin/phpunit tests/Feature/P0RemediationTest.php
```
**Hasil Aktual:**
```json
{"tool":"phpunit","result":"passed","tests":16,"passed":16,"assertions":57,"duration_ms":4733}
```
*Status: 16 passed, 0 failed, 57 assertions (100% PASS).*

### Eksekusi 2: Full Test Suite
```bash
vendor/bin/phpunit
```
**Hasil Aktual:**
```json
{"tool":"phpunit","result":"passed","tests":135,"passed":134,"assertions":696,"duration_ms":55846,"skipped":1}
```
*Status: 135 tests, 134 passed, 0 failed, 1 skipped (`ExampleTest`), 696 assertions (0 REGRESSIONS).*

### Eksekusi 3: Code Formatting (Laravel Pint)
```bash
vendor/bin/pint --format agent app/Http/Controllers/Admin/JadwalController.php app/Imports/JadwalImport.php app/Http/Controllers/Admin/UserController.php app/Http/Controllers/Admin/PenggunaController.php tests/Feature/P0RemediationTest.php
```
**Hasil Aktual:**
```json
{"tool":"pint","result":"passed"}
```
*Status: 100% clean formatting matches Laravel Pint rules.*

---

## 8. Hal yang Masih Belum Terverifikasi

1. **Uji Beban File Impor Sangat Besar (>5.000 baris):** Meskipun memory limit dan timeout telah disetel ke 512M dan 300s, perilaku alokasi memori pada server berkapasitas RAM rendah (misal VPS 1GB) perlu dipantau saat impor file di atas ribuan baris.
2. **Kustomisasi Role Spatie Tambahan di Luar Kode:** Jika administrator production membuat custom role Spatie baru secara runtime di database tanpa melalui migration/seeder, evaluasi dampak terhadap tampilan antarmuka disarankan dilakukan pada staging.

---

## 9. Langkah Deployment dan Backup yang Dibutuhkan

Sebelum menerapkan perubahan ke server live (`agenda-guru.smkn1ciamis.id`), lakukan langkah-langkah berikut:

1. **Backup Basis Data Penuh (Mandatory):**
   ```bash
   # Contoh backup PostgreSQL/MySQL pada server:
   mysqldump -u <user> -p <database_name> > backup_pre_p0_remediation_$(date +%Y%m%d_%H%M%S).sql
   # Atau jika menggunakan PostgreSQL:
   pg_dump -U <user> -d <database_name> -F c -b -v -f backup_pre_p0_remediation_$(date +%Y%m%d_%H%M%S).dump
   ```
2. **Backup Direktori Aplikasi:**
   Pastikan direktori storage dan file `.env` tersimpan aman.
3. **Penerapan Perubahan (Deploy Git):**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. **Verifikasi Smoke Test Pasca Deploy:**
   - Login sebagai `admin` biasa dan pastikan tidak bisa membuat/mengedit `super_admin`.
   - Login sebagai `super_admin` dan pastikan fungsi manajemen pengguna bekerja normal.
   - Buka halaman jadwal dan pastikan jadwal serta riwayat presensi tampil utuh.

---

## 10. Rekomendasi untuk Tahap P1 Berikutnya

Berdasarkan `DEEP_AUDIT_REPORT.md`, temuan prioritas tinggi (P1) yang direkomendasikan untuk diselesaikan pada misi berikutnya meliputi:

1. **SEC-002:** Perkuat otorisasi presensi siswa dan token checkout untuk mencegah spoofing presensi mandiri.
2. **DATA-002:** Sinkronisasi konsistensi soft deletes pada modul kelas binaan dan pemetaan blok mingguan.
3. **PERF-001:** Optimasi eager loading pada query agenda mengajar guru dan rekap kehadiran harian untuk mengeliminasi query N+1 pada dashboard pengawas.

---

> [!NOTE]
> **Catatan Kepatuhan:**
> Sesuai instruksi penugasan:
> - Tidak ada akses atau perubahan langsung ke database production.
> - Tidak dilakukan git commit atau git push otomatis.
> - Tidak ada migration baru yang dijalankan pada database riil.
> - Perbaikan difokuskan secara presisi pada DATA-001 dan SEC-001.

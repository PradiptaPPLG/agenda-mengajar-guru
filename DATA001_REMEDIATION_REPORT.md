# SOPAN — DATA-001 REMEDIATION REPORT
## Semantic Integrity & Atomic Import Verification

- **Repository**: `agenda-mengajar-guru`
- **Target Branch**: `main`
- **Execution Date**: 2026-10-13
- **Scope**: DATA-001 (Historical Academic Semantic Integrity & Atomic Excel Import)
- **Status**: REMEDIATED & VERIFIED LOCALLY

---

## 1. EXECUTIVE SUMMARY

Dalam laporan `P0_VERIFICATION_REPORT.md`, implementasi awal DATA-001 ditolak dengan status **FAIL WITH BLOCKERS** akibat dua temuan kritis:
1. **Semantic Mutation pada Histori Akademik**: Ketika slot jadwal yang sudah memiliki histori pertemuan (`pertemuans`) diimpor dengan guru baru (`guru_id`) atau mata pelajaran baru (`mata_pelajaran_id`), sistem memperbarui kolom tersebut secara langsung pada jadwal lama (*in-place mutation*). Hal ini mengubah sejarah pengajaran di masa lalu dan menyebabkan guru asli terkunci (*HTTP 403 Forbidden*) saat mengakses pertemuan yang pernah diampunya.
2. **Silent Row Skipping & Non-Atomic Import**: File Excel dengan baris tidak lengkap atau data tidak valid dilewati secara diam-diam menggunakan `Log::warning()`. Jika opsi `clear_before_import` aktif, jadwal lama dihapus, baris valid tersimpan sebagian, dan aplikasi tetap menampilkan notifikasi sukses.

Kedua blocker tersebut kini telah **diperbaiki secara menyeluruh**, diuji dengan **17 skenario regresi atomik**, diformat dengan **Laravel Pint**, dan divalidasi terhadap seluruh test suite aplikasi (**144 passed, 1 skipped, 0 failed**).

---

## 2. ROOT CAUSE ANALISIS

### A. Blocker 1: In-Place Mutation pada Slot Berhistori
- **Lokasi Kode Lama**: `app/Imports/JadwalImport.php` & `app/Http/Controllers/Admin/JadwalController.php` (`salinSemester`).
- **Akar Masalah**:
  Logika rekonsiliasi slot jadwal menggunakan premis bahwa primary key (`id`) jadwal harus dipertahankan untuk slot waktu yang sama. Akibatnya, saat jadwal baru dengan guru pengganti atau mata pelajaran baru masuk, atribut `guru_id` dan `mata_pelajaran_id` ditimpa:
  ```php
  // Kode lama yang bermasalah:
  $activeJadwal->update([
      'guru_id' => $guru->id,
      'mata_pelajaran_id' => $mapel->id,
  ]);
  ```
  Karena tabel `pertemuans` berelasi via foreign key `jadwal_id` ke `jadwal_pelajarans`, perubahan ini langsung mengubah data histori akademik pertemuan lama. Saat guru asli membuka agenda, otorisasi `abort_unless($pertemuan->jadwal->guru_id === Auth::id(), 403)` gagal dan melempar HTTP 403.

### B. Blocker 2: Silent Row Skipping & Partial Import
- **Lokasi Kode Lama**: `app/Imports/JadwalImport.php`.
- **Akar Masalah**:
  Ketika baris Excel tidak menemukan referensi guru, kelas, mapel, atau format jam tidak valid, kode mengeksekusi `continue` setelah mencatat log warning. Import berjalan satu tahap tanpa pra-validasi (*pre-flight validation*). Controller menganggap proses sukses dan menampilkan notifikasi berhasil, meskipun sebagian jadwal penting tidak terimpor. Jika `clear_before_import` diaktifkan, jadwal sebelumnya hilang tanpa cadangan.

---

## 3. DESAIN PERBAIKAN & ARSITEKTUR SOLUSI

### A. Desain Penanganan Konflik Identitas Historis (Semantic Integrity)
1. **Pemisahan Entitas Historis vs Entitas Aktif**:
   - Jadwal dengan histori (`pertemuans()->exists()`) diperlakukan sebagai **rekaman akademik permanen**.
   - Jika slot waktu yang sama diimpor dengan guru atau mata pelajaran yang berbeda, jadwal historis **diarsipkan via soft-delete** (`$activeJadwal->delete()`).
   - Jadwal lama mempertahankan nilai `guru_id` dan `mata_pelajaran_id` aslinya tanpa modifikasi apa pun.
   - Jadwal baru dibuat (atau direstorasi jika record serupa pernah diarsipkan) sebagai jadwal aktif tunggal (`deleted_at IS NULL`) untuk slot tersebut.
2. **Integritas Relasi Pertemuan (`Pertemuan::jadwal()`)**:
   - Model `App\Models\Pertemuan` diperbarui:
     ```php
     public function jadwal(): BelongsTo
     {
         return $this->belongsTo(JadwalPelajaran::class, 'jadwal_id')->withTrashed();
     }
     ```
   - Dengan penambahan `->withTrashed()`, seluruh pertemuan historis tetap terhubung utuh ke jadwal aslinya, atribut guru lama tetap terbaca, dan guru lama tetap memiliki hak akses legal atas jurnal masa lalunya.
3. **Pembaruan In-Place Hanya untuk Jadwal Tanpa Histori**:
   - Jika jadwal belum memiliki pertemuan (`pertemuans()->doesntExist()`), sistem tetap mengizinkan pembaruan in-place tanpa menciptakan duplikasi record.
4. **Pencegahan Slot Aktif Ganda**:
   - Setiap slot `(kelas_id, hari, jam_mulai, jam_selesai, tahun_ajaran, semester)` dijamin hanya memiliki **maksimal 1 record aktif** (`deleted_at IS NULL`).

### B. Desain Atomic Import & All-or-Nothing (Two-Phase Processing)
1. **Validasi Header Wajib**:
   - Sebelum membaca baris, file Excel diperiksa keberadaan header esensial: `kelas`, `guru`, `mata_pelajaran` / `mapel`, `hari`, dan `jam_mulai` / `jam_selesai` / `waktu`. Jika header wajib tidak ditemukan, proses langsung dihentikan dengan pesan kesalahan spesifik.
2. **Fase 1: Pre-Flight Validation (In-Memory)**:
   - Seluruh baris data dibaca dan divalidasi sebelum satu pun query database dijalankan:
     - Mengabaikan baris kosong murni (*empty spacer rows*).
     - Memverifikasi keberadaan kelas di database (`Kelas`).
     - Memverifikasi keberadaan guru aktif di database (`User` dengan `role: guru`).
     - Memverifikasi keberadaan mata pelajaran di database (`MataPelajaran`).
     - Memverifikasi nama hari (`Senin` s.d. `Minggu`).
     - Memverifikasi format jam (`HH:MM` atau `HH:MM:SS`) dan aturan `jam_mulai < jam_selesai`.
     - Mendeteksi bentrok slot internal di dalam file Excel yang sama.
   - Jika ada **satu saja baris cacat**, error dikumpulkan bersama nomor baris Excel dan alasannya, lalu melempar `\InvalidArgumentException`.
3. **Fase 2: Transactional Execution**:
   - Jika dan hanya jika seluruh baris lolos validasi, Fase 2 dieksekusi di dalam transaksi database (`DB::beginTransaction()` / `DB::commit()`).
   - Pembersihan `clear_before_import` hanya menghapus jadwal tanpa histori.
   - Jika terjadi kegagalan tak terduga, `DB::rollBack()` membatalkan seluruh perubahan, termasuk pembersihan awal.

---

## 4. DAFTAR FILE YANG DIUBAH

| File | Peran / Alasan Perubahan |
|---|---|
| `app/Models/Pertemuan.php` | Menambahkan `->withTrashed()` pada relasi `jadwal()` agar pertemuan historis tetap dapat mengakses jadwal yang diarsipkan tanpa menghasilkan `null`. |
| `app/Imports/JadwalImport.php` | Mengimplementasikan validasi header, arsitektur import dua fase (all-or-nothing), pembatalan silent row skipping, dan pemisahan jadwal historis vs aktif saat pergantian guru/mapel. |
| `app/Http/Controllers/Admin/JadwalController.php` | Membungkus import ke dalam transaksi database atomik dengan penanganan rollback eksplisit, serta memperbarui logika `salinSemester()` agar tidak memutasi jadwal berhistori. |
| `tests/Feature/P0RemediationTest.php` | Memperbarui dan memperluas test suite regresi mencakup seluruh 17 skenario (A.1–A.7 dan B.8–B.17) untuk membuktikan integritas semantik dan atomisitas import. |

---

## 5. HASIL PENGUJIAN REGRESI

### A. Blocker A: Histori & Identitas Akademik

| No | Skenario Uji | Status |
|---|---|:---:|
| A.1 | Jadwal berhistori tetap mempertahankan `guru_id` dan `mata_pelajaran_id` jika slot impor cocok dan identitas sama | **PASSED** |
| A.2 | Jadwal berhistori tidak berubah identitas ketika impor menggunakan guru berbeda (diarsipkan, guru lama tetap) | **PASSED** |
| A.3 | Jadwal berhistori tidak berubah identitas ketika impor menggunakan mapel berbeda (diarsipkan, mapel lama tetap) | **PASSED** |
| A.4 | Seluruh pertemuan, kehadiran guru, kehadiran siswa, dan foto bukti tetap utuh 100% | **PASSED** |
| A.5 | Guru lama tetap dapat mengakses histori pertemuannya; guru baru ditolak (HTTP 403) jika mencoba memanipulasi pertemuan lama | **PASSED** |
| A.6 | Slot aktif tidak terduplikasi; hanya ada tepat 1 jadwal aktif per slot kelas/hari/jam | **PASSED** |
| A.7 | Jadwal tanpa histori dapat diperbarui secara aman in-place tanpa meninggalkan duplikasi | **PASSED** |

### B. Blocker B: Atomic Import & All-or-Nothing

| No | Skenario Uji | Status |
|---|---|:---:|
| B.8 | File dengan baris valid dan 1 baris invalid membatalkan seluruh impor (0 baris tersimpan) | **PASSED** |
| B.9 | Baris dengan guru/kelas/mapel fiktif membatalkan seluruh impor tanpa perubahan DB | **PASSED** |
| B.10 | Format jam tidak valid atau jam selesai <= jam mulai membatalkan seluruh impor | **PASSED** |
| B.11 | File Excel tanpa header wajib ditolak dengan pesan jelas tanpa menyentuh data | **PASSED** |
| B.12 | File rusak / non-Excel ditolak oleh validasi upload tanpa mengubah jadwal yang ada | **PASSED** |
| B.13 | File hanya berisi header atau baris kosong ditolak tanpa modifikasi database | **PASSED** |
| B.14 | Opsi `clear_before_import` aktif dengan file invalid me-rollback pembersihan awal (jadwal lama utuh) | **PASSED** |
| B.15 | Operasi `salinSemester` dengan opsi bersihkan mempertahankan jadwal yang memiliki histori | **PASSED** |
| B.16 | File valid sepenuhnya berhasil diimpor dan pesan sukses ditampilkan | **PASSED** |
| B.17 | Impor berulang bersifat idempoten dan tidak menimbulkan duplikasi jadwal | **PASSED** |

### C. Ringkasan Eksekusi Test Suite

1. **`tests/Feature/P0RemediationTest.php`**:
   ```
   Tests: 26, Passed: 26, Assertions: 89, Failures: 0
   ```
   *(Mencakup 17 test DATA-001 dan 9 test SEC-001).*

2. **Full PHPUnit Test Suite (`vendor/bin/phpunit`)**:
   ```
   Tests: 145, Passed: 144, Skipped: 1, Failures: 0, Assertions: 728
   ```

3. **Formatting & Linter (`vendor/bin/pint --dirty --format agent`)**:
   ```
   {"tool":"pint","result":"passed"}
   ```

---

## 6. SISA RISIKO (OPEN RISKS)

1. **Mekanisme Soft-Delete pada Storage Database Besar**:
   - Jika pergantian jadwal sangat sering terjadi di semester yang sama, tabel `jadwal_pelajarans` akan mengakumulasi jadwal berstatus soft-deleted (`deleted_at IS NOT NULL`). Ini merupakan perilaku yang disengaja demi audit trail, namun disarankan untuk memiliki indeks komposit pada `(kelas_id, hari, deleted_at)`.
2. **Kesesuaian Teks Nama Guru di Excel vs Database**:
   - Pra-validasi melakukan exact match dan trim pada nama guru (`User::where('name', ...)->where('role', 'guru')`). Jika nama guru di Excel memiliki gelar yang berbeda dari database, baris tersebut akan ditolak (all-or-nothing). Operator sekolah harus memastikan penulisan nama guru konsisten dengan akun sistem.

---

## 7. PROSEDUR VERIFIKASI MANUAL DI STAGING (READ-ONLY INSTRUCTIONS)

Jalankan pengujian manual berikut pada server staging sebelum rilis production:

1. **Verifikasi Import Gagal (Atomic Rollback)**:
   - Buat file Excel berisi 5 baris jadwal valid dan 1 baris terakhir dengan nama kelas fiktif `99NONEXISTENT`.
   - Unggah file melalui menu Admin > Impor Jadwal dengan opsi "Hapus jadwal lama sebelum impor" dicentang.
   - **Hasil yang diharapkan**: Muncul notifikasi error berwarna merah yang menyebutkan baris ke-6 dan alasan kelas tidak ditemukan. Jadwal sebelumnya di database tetap utuh 100%.

2. **Verifikasi Integritas Histori Guru**:
   - Catat satu jadwal yang sudah pernah diisi agendanya oleh Guru A (misal: Senin jam 07:00-08:30 kelas 10PPLG1).
   - Unggah jadwal baru yang mengubah slot tersebut menjadi Guru B.
   - Login sebagai Guru A dan buka agenda pertemuan lama tersebut.
   - **Hasil yang diharapkan**: Guru A tetap dapat melihat data pertemuannya, nama Guru A tetap tercatat sebagai pengajar pertemuan tersebut, dan tidak muncul HTTP 403.
   - Login sebagai Guru B.
   - **Hasil yang diharapkan**: Guru B melihat jadwal aktif baru pada jadwal mengajar hari Senin, namun tidak dapat mengubah jurnal lama Guru A.

---

## 8. FINAL VERDICT

```
================================================================================
VERDICT: PASS
--------------------------------------------------------------------------------
Semantic Mutation: REMEDIATED (Historical identity preserved via soft-delete archive)
Silent Skipping:   REMEDIATED (Two-phase all-or-nothing atomic pre-flight validation)
P0 Test Suite:     26 / 26 PASSED
Full Test Suite:   144 / 144 PASSED (1 skipped)
Code Style:        CLEAN (Pint passed with 0 dirty warnings)
Scope Enforcement: STRICT (SEC-001 untouched, no automatic commit/push/deploy)
================================================================================
```
*Sesuai panduan keselamatan, pengerjaan dihentikan pada penyusunan laporan ini tanpa melakukan commit, push, atau deployment.*

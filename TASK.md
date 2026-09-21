# Dokumen Perencanaan & Checklist Tugas (TASK.md)
## Sistem Agenda Mengajar Guru (SMK)

Dokumen ini memetakan 16 kebutuhan, perbaikan fungsional, fitur baru, dan keputusan arsitektural sistem ke dalam fase-fase terstruktur berbasis prioritas dan kompleksitas.

---

## 💡 Jawaban & Rekomendasi Terhadap Pertanyaan Desain

### 1. Pertanyaan Poin 4: *Apakah di landing page harus ditambahkan card untuk siswa seperti yang guru?*
- **Klarifikasi Kebutuhan:** Yang dimaksud adalah **Card KPI Ringkasan Statistik Siswa**, serupa dengan 4 card metrik statistik guru yang sudah ada di landing page.
- **Desain & Implementasi yang Disepakati:**
  Menambahkan deretan 4 card metrik statistik siswa di landing page publik berdampingan/sejajar dengan card statistik guru:
  1. **Total Siswa Hadir** (🟢 Jumlah siswa hadir di KBM hari ini)
  2. **Total Siswa Terlambat** (🟠 Jumlah siswa terlambat hari ini)
  3. **Total Siswa Alpa / Tidak Hadir** (🔴 Jumlah siswa alpa/izin/sakit hari ini)
  4. **Total Siswa yang Ada** (👥 Total seluruh siswa aktif terdaftar / terjadwal)

### 2. Pertanyaan Poin 9: *Apakah di piket akan bagus jika ada broadcast ke guru-guru yang statusnya belum hadir?*
- **Rekomendasi: SANGAT BAGUS (High Value Feature) & sangat relevan dengan operasional nyata guru piket.**
  - Di sekolah, tugas utama guru piket saat bel KBM berbunyi adalah menertibkan dan mengingatkan guru yang belum masuk kelas.
  - **Implementasi Efektif (Tanpa Biaya SMS Gateway / Pihak Ketiga):**
    Sediakan tombol aksi cepat **"Panggil Guru (WhatsApp)"** di setiap card guru yang berstatus *Belum Hadir* di dashboard piket.
    Ketika diklik, langsung membuka WhatsApp Web / App (`https://wa.me/628xxx?text=...`) dengan template otomatis:
    > *"Yth. Bpk/Ibu [Nama Guru], jadwal mengajar Anda di kelas [11 RPL 1] mata pelajaran [Pemrograman Web] jam ke-2 sudah dimulai. Mohon segera memasuki ruang kelas. Terima kasih - Tim Piket [Nama Sekolah]"*

---

## 🗺️ Peta Jalan & Rencana Fase Pengembangan

```mermaid
flowchart TD
    Fase1[Fase 1: Quick Fixes & Presensi Inti] --> Fase2[Fase 2: Manajemen Jadwal & Piket Broadcast]
    Fase2 --> Fase3[Fase 3: Multi-Role & Dashboard Khusus BK, Walas, TU]
    Fase3 --> Fase4[Fase 4: Sistem Jadwal Blok SMK Produktif/Umum & Ganjil-Genap]

    style Fase1 fill:#e0f2fe,stroke:#0284c7,stroke-width:2px
    style Fase2 fill:#fef3c7,stroke:#d97706,stroke-width:2px
    style Fase3 fill:#dcfce7,stroke:#16a34a,stroke-width:2px
    style Fase4 fill:#fee2e2,stroke:#dc2626,stroke-width:2px
```

---

## 📌 FASE 1: Quick Fixes & Penyempurnaan Presensi (Prioritas Tinggi)
Fase ini berfokus pada perbaikan bug visual, penyempurnaan alur presensi harian, dan akurasi data yang langsung dirasakan pengguna.

### [x] Task 1.1: Standarisasi Dropdown Jam Pelajaran di Dashboard Awal / Piket (Poin 1)
- **Masalah:** Dropdown jam di dashboard saat ini mengelompokkan jam berdasarkan rentang `jam_mulai` - `jam_selesai` unik acak dari data jadwal, bukan urutan baku jam pelajaran sekolah (Jam ke-1, Jam ke-2, dst).
- **Rencana Pengerjaan:**
  - [x] Buat master/konfigurasi waktu slot jam pelajaran standar sekolah (misal: Jam 1: 07.00–07.45, Jam 2: 07.45–08.30, Istirahat: 09.45–10.15, dst).
  - [x] Perbarui `Piket/DashboardController` dan `KepalaSekolah/DashboardController` agar melakukan pemetaan jadwal ke nomor jam pelajaran standar.
  - [x] Pastikan slot aktif saat ini (`currentActiveSlotIndex`) otomatis terpilih saat halaman pertama kali dibuka berdasarkan `now()`.

### [x] Task 1.2: Logika Toleransi Keterlambatan Guru (5 - 7 Menit) (Poin 2)
- **Masalah:** Belum ada acuan baku batas toleransi menit sebelum guru ditandai sebagai *Terlambat*.
- **Rencana Pengerjaan:**
  - [ ] Tambahkan konfigurasi waktu toleransi (default: 7 menit) di `config/agenda.php` atau konstanta model.
  - [ ] Pada alur pelaporan siswa (`CaptureController`) & kehadiran guru:
    - Jika waktu hadir $\le$ `jam_mulai + 7 menit` $\rightarrow$ Status: `hadir` (🟢).
    - Jika waktu hadir $>$ `jam_mulai + 7 menit` $\rightarrow$ Status: `terlambat` (🟠).
  - [ ] Tampilkan keterangan waktu terlambat di dashboard piket (misal: *"Terlambat 12 menit"*).

### [ ] Task 1.3: Keterangan / Alasan Khusus Saat Siswa Izin / Sakit (Poin 3)
- **Masalah:** Saat guru mengabsen siswa "Izin" atau "Sakit", tidak ada tempat mencatat alasan (misal: "Dispen FLS2N", "Urusan Keluarga", "Rawat Inap").
- **Rencana Pengerjaan:**
  - [ ] Buat migrasi penambahan kolom `keterangan` (nullable string/text) pada tabel `kehadiran_siswas`.
  - [ ] Perbarui model `KehadiranSiswa` (`$fillable`).
  - [ ] Pada view input agenda guru (`guru/pertemuan/show.blade.php`), tambahkan kolom/modal input catatan saat opsi status `izin` atau `sakit` dipilih.
  - [ ] Tampilkan keterangan tersebut di rekapitulasi kehadiran siswa (Wali Kelas, BK, dan TU).

### [ ] Task 1.4: Perbaikan Foto Bukti Siswa Broken / Tidak Muncul di Dashboard Guru (Poin 11)
- **Masalah:** Foto yang diunggah siswa saat capture tidak tampil di halaman pertemuan guru.
- **Rencana Pengerjaan:**
  - [ ] Periksa disk penyimpanan di `CaptureController`: pastikan tersimpan di `disk('public')`.
  - [ ] Perbaiki pemanggilan URL di Blade dari `Storage::url(...)` menjadi `Storage::disk('public')->url($foto->foto_path)` atau `asset('storage/' . $foto->foto_path)`.
  - [ ] Verifikasi symlink publik server (`php artisan storage:link`) dan tambahkan pengecekan fallback jika file fisik belum terunggah/rusak.

### [ ] Task 1.5: Rekalkulasi Persentase Kehadiran Berbasis Sesi Mapel (Bukan Harian) (Poin 10)
- **Masalah:** Persentase kehadiran dihitung per hari kerja, padahal di SMK kehadiran siswa dinilai per mata pelajaran yang diambil.
- **Rencana Pengerjaan:**
  - [ ] Ubah rumus perhitungan rekap presensi siswa:
    $$\text{Tingkat Kehadiran Mapel (\%)} = \frac{\text{Total Hadir di Mapel X}}{\text{Total Pertemuan Mapel X yang Terlaksana}} \times 100\%$$
  - [ ] Terapkan kalkulasi ini pada rekap agenda guru dan laporan siswa.

---

## 📌 FASE 2: Manajemen Jadwal, Leaderboard & Fitur Piket (Prioritas Menengah)
Fase ini berfokus pada kemudahan administrasi jadwal pelajaran dan alat pemantauan guru.

### [ ] Task 2.1: Dropdown Export (PDF/Excel) & Fitur Import Jadwal dari Excel (Poin 8)
- **Kebutuhan:** Tombol export jadwal dijadikan dropdown (pilih PDF atau Excel), dan tambahkan fitur import jadwal dari file Excel (`.xlsx`).
- **Rencana Pengerjaan:**
  - [ ] Ubah UI export di daftar jadwal admin menjadi split-button / dropdown:
  - [x] Ubah UI export di daftar jadwal admin menjadi split-button / dropdown:
    - *Opsi 1: Export PDF*
    - *Opsi 2: Export Excel (.xlsx)*
  - [x] Siapkan template Excel standar untuk import jadwal (Kolom: Hari, Tingkat, Kelas, Kode Mapel, NIP/Nama Guru, Jam Mulai, Jam Selesai).
  - [x] Buat tombol dan modal "Import Jadwal Excel" di halaman `admin/jadwal`.
  - [x] Implementasikan backend import dengan validasi:
    - Cek keberadaan kelas, guru, dan mapel.
    - Jalankan deteksi bentrok (*conflict detection*) otomatis saat import berlangsung.
    - Bungkus dalam `DB::transaction` agar jika ada baris yang bentrok/error, data tidak rusak parsial.

### [x] Task 2.2: Leaderboard Top Guru Terajin di Dashboard Admin (Poin 5)
- **Kebutuhan:** Menampilkan daftar peringkat guru yang paling rajin hadir. Jika ada keterlambatan atau alpa, persentase kehadiran berkurang.
- **Rencana Pengerjaan:**
  - [x] Buat query agregasi di `Admin/DashboardController`:
    $$\text{Skor Kehadiran} = \frac{\text{Pertemuan Hadir Tepat Waktu} + (0.5 \times \text{Terlambat})}{\text{Total Jadwal Mengajar}} \times 100\%$$
  - [x] Desain widget leaderboard modern di dashboard admin dengan:
    - Avatar guru & gelar
    - Badge persentase kehadiran (misal: 98.5%)
    - Rincian ringkas (Hadir: X, Terlambat: Y, Izin/Sakit: Z)
    - Indikator medali/peringkat (Top 5 / Top 10).

### [ ] Task 2.3: Fitur Panggil / Broadcast Guru Belum Hadir di Dashboard Piket (Poin 9)
- **Kebutuhan:** Membantu guru piket menghubungi guru yang belum masuk kelas secara instan.
- **Rencana Pengerjaan:**
  - [ ] Pastikan data nomor telepon/WhatsApp guru tersedia di `guru_profiles.no_hp` / `users.telepon`.
  - [ ] Pada card monitoring guru piket berstatus "Belum Hadir", tambahkan tombol aksi hijau: **"Panggil Guru (WA)"**.
  - [ ] Generate URL `https://wa.me/{no_hp}?text={template_pesan_terformat}`.
  - [ ] Tambahkan modal konfirmasi atau daftar broadcast jika ingin memanggil seluruh guru yang terlambat sekaligus dalam satu klik.

---

## 📌 FASE 3: Multi-Role & Dashboard Khusus Pengguna (Prioritas Strategis)
Fase ini memperluas sistem otorisasi dan menyediakan antarmuka khusus sesuai peran struktural di sekolah.

### [x] Task 3.1: Manajemen Hak Akses & Role di Sisi Admin (Poin 16)
- **Kebutuhan:** Admin dapat mengatur dan menugaskan role secara fleksibel dari dashboard admin.
- **Rencana Pengerjaan:**
  - [x] Dukung daftar role lengkap di sistem: `super_admin`, `admin`, `kepala_sekolah`, `guru`, `piket`, `bk`, `tu`, `siswa`.
  - [x] Buat antarmuka di manajemen user admin untuk mengubah role, status aktif, dan penugasan khusus (penugasan kelas wali kelas, penugasan tingkat BK).
  - [x] Pasang middleware otorisasi yang ketat untuk setiap rute dashboard baru.

### [x] Task 3.2: Dashboard Guru BK (Bimbingan Konseling) (Poin 12)
- **Kebutuhan:** Guru BK dapat memantau presensi siswa sesuai angkatan/kelas binaan yang dipegangnya.
- **Rencana Pengerjaan:**
  - [x] Tambahkan relasi/tabel penugasan Guru BK ke tingkat atau daftar kelas tertentu (`bk_kelas_assignments` atau array kelas binaan).
  - [x] Buat rute dan controller `App\Http\Controllers\BK\DashboardController`.
  - [x] Tampilkan fitur di dashboard BK:
    - *Early Warning System:* Daftar siswa dengan alpa $\ge 3$ kali dalam sebulan.
    - Filter berdasarkan kelas binaan.
    - Riwayat detail ketidakhadiran per mata pelajaran beserta keterangan/alasan siswa.
    - Catatan/tindak lanjut penanganan kasus siswa oleh BK.

### [ ] Task 3.3: Dashboard Khusus Wali Kelas di Sisi Guru (Poin 13)
- **Kebutuhan:** Guru yang ditugaskan sebagai wali kelas memiliki menu dashboard tersendiri untuk mengontrol kelasnya.
- **Rencana Pengerjaan:**
  - [ ] Manfaatkan relasi `Kelas::where('wali_kelas_id', $user->id)`.
  - [ ] Tambahkan navigasi "Kelas Perwalian Saya" di sidebar/header guru jika `user` terdaftar sebagai wali kelas.
  - [ ] Fitur Dashboard Wali Kelas:
    - Rekapitulasi absensi harian dan per-mapel seluruh siswa di kelasnya.
    - Grafik tren kehadiran kelas bulanan.
    - Notifikasi jika ada siswa kelasnya yang tidak hadir berturut-turut.
    - Ekspor rekap presensi kelas (Excel & PDF) untuk laporan kenaikan kelas.

### [x] Task 3.4: Data Isolation: Dashboard Guru Khusus Siswa yang Diajar (Poin 14)
- **Kebutuhan:** Guru biasa hanya dapat melihat daftar presensi dan data siswa dari kelas dan mata pelajaran yang diampunya saja.
- **Rencana Pengerjaan:**
  - [x] Audit query rekap siswa di sisi guru (`Guru\ReportController` / `SiswaController`).
  - [x] Kunci scope data menggunakan `whereHas('jadwalPelajarans', fn($q) => $q->where('guru_id', $user->id))`.
  - [x] Cegah guru mengakses agenda atau data siswa dari kelas lain yang tidak diajarnya.

### [x] Task 3.5: Dashboard Tata Usaha (TU) untuk Rekapitulasi Presensi Siswa (Poin 15)
- **Kebutuhan:** Staf TU membutuhkan akses cepat untuk mencetak dan mengunduh rekap presensi seluruh siswa sekolah tanpa hak mengedit materi KBM.
- **Rencana Pengerjaan:**
  - [x] Buat rute dan controller `App\Http\Controllers\TU\DashboardController`.
  - [x] Fitur Dashboard TU:
    - Rekap presensi siswa tingkat sekolah (Harian, Mingguan, Bulanan, Semesteran).
    - Filter fleksibel: Berdasarkan Jurusan, Tingkat (X, XI, XII), Kelas, dan Rentang Tanggal.
    - Export massal format Dapodik / Dinas Pendidikan (Excel `.xlsx`).

### [ ] Task 3.6: Card KPI Ringkasan Statistik Presensi Siswa di Landing Page (Poin 4)
- **Kebutuhan:** Menampilkan deretan 4 Card KPI Statistik Siswa di Landing Page / Public Dashboard, selevel dan sejajar dengan 4 Card KPI Guru yang sudah ada.
- **Rencana Pengerjaan:**
  - [ ] Tambahkan kalkulasi agregat di `HomeController` / `PublicDashboardController`:
    - `siswa_hadir_hari_ini`: Total siswa dengan status presensi `hadir` pada pertemuan hari ini.
    - `siswa_terlambat_hari_ini`: Total siswa dengan status `terlambat` hari ini.
    - `siswa_alpa_hari_ini`: Total siswa dengan status `alpa` (atau `izin`/`sakit`) hari ini.
    - `total_siswa`: Total siswa aktif terdaftar di sekolah.
  - [ ] Tambahkan grid 4 Card KPI Siswa di `public-dashboard.blade.php`:
    - 🟢 **Card Siswa Hadir:** Ikon check-circle / siswa, total angka, label "Siswa Hadir".
    - 🟠 **Card Siswa Terlambat:** Ikon clock / terlambat, total angka, label "Siswa Terlambat".
    - 🔴 **Card Siswa Alpa / Tidak Hadir:** Ikon x-circle, total angka, label "Siswa Alpa / Izin".
    - 👥 **Card Total Siswa:** Ikon user-group, total angka, label "Total Seluruh Siswa".

---

## 📌 FASE 4 (TERPISAH & PALING AKHIR): Sistem Jadwal Blok SMK (Produktif vs Umum & Ganjil-Genap) (Poin 6 & 7)
> [!WARNING]
> **Tingkat Kesulitan: Sangat Tinggi (High Architectural Complexity).**  
> Sistem blok mengubah struktur dasar jadwal KBM mingguan menjadi rotasi berbasis siklus minggu (Minggu Ganjil / Minggu Genap atau Siklus Blok A / Blok B). Seluruh validasi jadwal bentrok, kalender pertemuan, dan filter monitoring harian akan terpengaruh. Dikerjakan setelah seluruh Fase 1, 2, dan 3 stabil.

### Konsep Bisnis Sistem Blok di SMK:
- **Model Reguler:** Jadwal berjalan statis setiap minggu (berlaku untuk mata pelajaran umum).
- **Model Blok Kejuruan/Produktif:**
  - **Minggu Ganjil (Blok Produktif):** Kelas kejuruan (misal: Kuliner / RPL) fokus 100% pada praktik kejuruan di bengkel/lab selama 1 minggu penuh.
  - **Minggu Genap (Blok Umum):** Kelas tersebut berganti jadwal mempelajari mata pelajaran normatif/adaptif (Matematika, Bahasa Indonesia, Agama, dll).
  - Berlaku sistem rotasi antar-kelas agar fasilitas lab/bengkel tidak bentrok.

### Rencana Arsitektur & Pengerjaan Teknis:

#### [ ] Task 4.1: Skema Database & Model Kalender Akademik
- [ ] Tambahkan tabel `akademik_minggus` atau konfigurasi penanda minggu KBM:
  - `nomor_minggu_semester` (1 s/d 20).
  - `jenis_minggu` (`ganjil` / `genap` atau `blok_a` / `blok_b`).
  - `tanggal_mulai` dan `tanggal_selesai`.
- [ ] Tambahkan kolom pada tabel `jadwal_pelajarans`:
  - `tipe_jadwal` enum: `['reguler', 'blok_ganjil', 'blok_genap']` (default: `reguler`).

#### [ ] Task 4.2: Pembaruan Engine Validasi Bentrok Jadwal (*Conflict Detection*)
- [ ] Sesuaikan `Admin\JadwalController`:
  - Bentrok guru atau kelas hanya berlaku jika `tipe_jadwal` berada di blok minggu yang sama atau jika salah satunya bertipe `reguler`.
  - Jadwal blok ganjil dan blok genap boleh menggunakan ruangan/guru yang sama di jam yang sama karena tidak berjalan di minggu yang sama.

#### [ ] Task 4.3: Perubahan Query Dashboard & Resolver Jadwal Berjalan
- [ ] Buat service class `JadwalResolverService` yang mendeteksi:
  1. Hari ini tanggal berapa?
  2. Apakah minggu ini tergolong minggu ganjil atau genap dalam kalender akademik?
  3. Ambil jadwal dengan kriteria: `whereIn('tipe_jadwal', ['reguler', $currentMingguBlok])`.
- [ ] Integrasikan `JadwalResolverService` ke:
  - Dashboard Siswa (hanya menampilkan mapel yang aktif minggu ini).
  - Dashboard Guru (hanya menampilkan kelas yang diajar minggu ini).
  - Dashboard Piket & Kepala Sekolah (monitoring real-time presisi).

#### [ ] Task 4.4: UI Form Input Jadwal & Filter Kalender Blok
- [ ] Tambahkan opsi pilihan `Tipe Jadwal (Reguler / Blok Ganjil / Blok Genap)` pada form tambah/edit jadwal admin.
- [ ] Tambahkan visual badge (misal: `[Blok Ganjil]`) pada tabel jadwal admin dan dashboard monitoring.

---

## 📊 Matriks Ketergantungan & Urutan Eksekusi

| No | Komponen / Fitur | Target Fase | Estimasi Tingkat Kesulitan | Ketergantungan (*Prerequisite*) |
|---|---|---|---|---|
| 1 | Dropdown Jam Standar | Fase 1 | Rendah | Tidak ada |
| 2 | Toleransi Keterlambatan 5-7 Menit | Fase 1 | Rendah | Tidak ada |
| 3 | Keterangan Izin/Sakit Siswa | Fase 1 | Rendah | Migrasi `kehadiran_siswas` |
| 4 | Fix Foto Bukti Broken di Guru | Fase 1 | Rendah | Storage config / symlink |
| 5 | Persentase Hadir per-Mapel | Fase 1 | Sedang | Perhitungan query |
| 6 | Export Dropdown & Import Excel | Fase 2 | Sedang | Library Excel / Maatwebsite |
| 7 | Leaderboard Top Guru di Admin | Fase 2 | Sedang | Query agregasi kehadiran |
| 8 | Broadcast WA Guru Belum Hadir | Fase 2 | Rendah - Sedang | Data No HP Guru |
| 9 | Manajemen Role Dinamis Admin | Fase 3 | Sedang | Middleware & User CRUD |
| 10 | Dashboard BK | Fase 3 | Sedang | Role BK & Penugasan Kelas |
| 11 | Dashboard Wali Kelas | Fase 3 | Sedang | Relasi `wali_kelas_id` |
| 12 | Isolasi Data Siswa per Guru | Fase 3 | Sedang | Query Scoping |
| 13 | Dashboard TU Rekap Siswa | Fase 3 | Sedang | Role TU & Report View |
| 14 | Evaluasi Landing Page Siswa | Fase 3 | Rendah | Ringkasan Statistik |
| 15 | **Sistem Blok SMK (Produktif/Umum)** | **Fase 4** | **Sangat Tinggi** | **Fase 1, 2, & 3 selesai stabil** |
| 16 | **Rotasi Jadwal Ganjil/Genap** | **Fase 4** | **Sangat Tinggi** | **Task 15 (Sistem Blok)** |

---

*Dokumen ini diperbarui secara berkala seiring berjalannya implementasi kode.*

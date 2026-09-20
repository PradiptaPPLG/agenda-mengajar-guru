# Roadmap & Checklist Pengembangan Sistem Agenda Mengajar Guru

Dokumen ini mencatat peta jalan implementasi perbaikan alur bisnis, pencegahan bug, dan penguatan integritas data.

---

## 📌 Status Fase Pengembangan

- [x] **Fase 1: Keamanan Integritas Data & Validasi Jadwal Bentrok (Prioritas Tinggi / High)**
  - [x] Buat migrasi `SoftDeletes` untuk tabel: `users`, `jadwal_pelajarans`, `kelas`, `mata_pelajarans`.
  - [x] Pasang trait `SoftDeletes` di model `User`, `JadwalPelajaran`, `Kelas`, `MataPelajaran`.
  - [x] Bungkus `PertemuanController::saveAll` dalam `DB::transaction`.
  - [x] Bungkus proses import siswa di `KelasSiswaController` & `SiswaController` dalam `DB::transaction`.
  - [x] Cegah pembuatan pertemuan masa depan (*ghost meeting*) di `PertemuanController::show` dan `CaptureController::show`.
  - [x] Implementasikan validasi bentrok jadwal (*conflict detection*) pada `Admin\JadwalController` (`store` dan `update`) untuk mendeteksi guru bertabrakan dan kelas bertabrakan.
  - [x] Buat unit/feature test untuk validasi bentrok jadwal & integritas data.

- [x] **Fase 2: Kalender Hari Libur Sekolah & Nasional (Prioritas Sedang)**
  - [x] Guru Pengganti: Dipertahankan sesuai konsep awal (input teks nama oleh siswa/guru, tanpa akun terpisah).
  - [x] Buat migrasi tabel `hari_liburs` (tanggal unique, keterangan, jenis).
  - [x] Buat model `HariLibur` dengan helper `isLibur` dan `getLibur`.
  - [x] Buat Admin Controller & UI untuk manajemen Hari Libur (`admin/hari-libur`).
  - [x] Integrasi Dashboard Guru: Tampilkan badge libur pada tanggal merah di matriks mingguan.
  - [x] Integrasi Dashboard & Capture Siswa: Tampilkan banner libur dan nonaktifkan kewajiban lapor di tanggal merah.
  - [x] Integrasi Laporan Kepala Sekolah: Hari libur tidak dihitung sebagai pelanggaran alpa guru.
  - [x] Buat feature test untuk Kalender Hari Libur.

- [x] **Fase 3: Kompresi Foto, Optimasi Penyimpanan & Rekonsiliasi (Prioritas Lanjutan)**
  - [x] Kompresi foto bukti di client & server (WebP / max 1200px via Canvas & GD ImageCompressor).
  - [x] Atribut kamera HP langsung `capture="environment"` pada input foto siswa.
  - [x] Indikator ketidaksesuaian (*discrepancy flag*) antara laporan siswa vs klaim guru di model dan laporan Kepala Sekolah.
  - [x] Fitur auto-save draft materi dan penugasan di browser (`localStorage`) beserta tombol pulihkan/abaikan.

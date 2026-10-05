# 📚 Katalog Fitur Lengkap — SOPAN (Sistem Operasional Presensi & Agenda Mengajar Guru)

Dokumen ini memuat daftar lengkap seluruh fitur, alur kerja, dan kemampuan sistem dalam aplikasi **SOPAN SMK Negeri 1 Ciamis**.

---

## 📑 Daftar Isi
1. [Manajemen Akademik & Kenaikan Kelas (Baru)](#1-manajemen-akademik--kenaikan-kelas)
2. [Status & Jadwal PKL Kelas 12 (Baru)](#2-status--jadwal-pkl-kelas-12)
3. [Sistem Pembelajaran Reguler & Sistem Blok SMK](#3-sistem-pembelajaran-reguler--sistem-blok-smk)
4. [Presensi KBM & Bukti Foto Cerdas (Dual Capture)](#4-presensi-kbm--bukti-foto-cerdas-dual-capture)
5. [Monitoring Harian & Dashboard Publik Real-Time](#5-monitoring-harian--dashboard-publik-real-time)
6. [Modul Guru & Jurnal KBM](#6-modul-guru--jurnal-kbm)
7. [Modul Peran Khusus: Wali Kelas, BK, dan Kaprog](#7-modul-peran-khusus-wali-kelas-bk-dan-kaprog)
8. [Modul Petugas Piket](#8-modul-petugas-piket)
9. [Modul Kepala Sekolah & Tata Usaha (Pelaporan & Rekap)](#9-modul-kepala-sekolah--tata-usaha)
10. [Administrasi Master Data & Hak Akses (Super Admin & Admin)](#10-administrasi-master-data--hak-akses)
11. [Progressive Web App (PWA) & Optimasi Seluler](#11-progressive-web-app-pwa--optimasi-seluler)

---

## 1. Manajemen Akademik & Kenaikan Kelas
Modul wizard otomatis untuk pergantian tahun ajaran dan promosi jenjang kelas siswa secara massal dan aman:
* **Pemetaan Otomatis Kelas 11 &rarr; 12**: Algoritma pintar mencocokkan rombel asal ke rombel tujuan (contoh: `11 DKV` &rarr; `12 DKV`, `11 AK 1` &rarr; `12 AK 1`).
* **Pemetaan Kurikulum Merdeka Kelas 10 &rarr; 11**: Otomatisasi penyesuaian perubahan singkatan program keahlian ke konsentrasi kejuruan:
  * `10 AKL` (Akuntansi dan Keuangan Lembaga) &rarr; `11 AK`
  * `10 MPLB` (Manajemen Perkantoran dan Layanan Bisnis) &rarr; `11 MP`
  * `10 PPLG` (Pengembangan Perangkat Lunak dan Gim) &rarr; `11 RPL`
* **Penanganan Siswa Tinggal Kelas (Retensi)**:
  * Admin dapat membuka daftar siswa per rombel melalui popup AJAX interaktif.
  * Siswa yang ditandai *Tinggal Kelas* **tidak akan dipindahkan** dan tetap menempati rombel asalnya untuk tahun ajaran baru.
* **Kelulusan Siswa Kelas 12**:
  * Opsi otomatis meluluskan siswa tingkat akhir.
  * Akun siswa dinonaktifkan (`is_active = false`) agar tidak dapat lagi login atau mengabsen.
  * Ikatan rombel dilepas (`kelas_id = null`, status Alumni) sehingga kelas 12 kosong untuk diisi angkatan baru.
  * **Integritas Riwayat**: Seluruh arsip presensi masa lalu di `kehadiran_siswas` tetap 100% utuh karena terikat pada `user_id`.
* **Pembaruan Tahun Ajaran & Reset Semester**:
  * Otomatis mengkalkulasi penambahan tahun ajaran (misal: `2026/2027` &rarr; `2027/2028`).
  * Mereset status semester kembali ke **Semester 1 (Ganjil)**.
* **Transaksi Basis Data Atomik**: Seluruh eksekusi berjalan di dalam `DB::transaction` berurutan (Luluskan 12 &rarr; Naikkan 11 &rarr; Naikkan 10 &rarr; Update Pengaturan).

---

## 2. Status & Jadwal PKL Kelas 12
Fitur khusus untuk mengatasi kendala presensi KBM ketika kelas tingkat akhir sedang melaksanakan Praktik Kerja Lapangan (PKL/Prakerin):
* **Bypass Jadwal KBM Otomatis**: Selama rentang tanggal PKL aktif, seluruh jadwal pelajaran harian kelas tersebut otomatis dinonaktifkan.
* **Perlindungan Rekap Kehadiran Guru**: Guru yang mengajar di kelas PKL tidak akan dianggap mangkir/alpa (*tidak hadir*), sehingga persentase kehadiran guru tetap adil dan terjaga.
* **Banner Informasi Lintas Peran**:
  * **Dashboard Siswa**: Muncul notifikasi bahwa kelas sedang PKL sehingga siswa tidak dituntut melakukan foto presensi.
  * **Dashboard Guru**: Menampilkan daftar kelas ampuannya yang sedang PKL.
  * **Dashboard Piket, Kepsek, & Publik**: Memuat rekapitulasi kelas PKL yang jadwalnya sedang di-bypass.
* **Proteksi Form Presensi**: Siswa yang mencoba mengakses URL presensi saat masa PKL aktif akan langsung dialihkan dengan peringatan keamanan.
* **Manajemen CRUD & Toggle Periode**: Admin dapat menambah periode PKL, mengubah rentang tanggal, mengaktifkan/menonaktifkan, serta memfilter berdasarkan kelas atau status (*Sedang Berlangsung*, *Akan Datang*, *Selesai*, *Nonaktif*).
* **Filter Khusus Kelas 12**: Form pendaftaran PKL otomatis dibatasi hanya untuk kelas tingkat 12.

---

## 3. Sistem Pembelajaran Reguler & Sistem Blok SMK
Arsitektur penjadwalan fleksibel yang mendukung karakteristik kurikulum SMK:
* **Jadwal Reguler**: Pembelajaran standar mingguan tetap (Senin - Jumat).
* **Sistem Blok Mingguan (Rotasi A/B)**:
  * Minggu Ganjil / Genap yang membagi rombel menjadi Kelompok A (KBM Teori) dan Kelompok B (Praktik Industri/Bengkel).
  * Kalender Blok Terpusat per minggu sepanjang semester.
* **Sistem Blok Harian (Split Harian)**:
  * Pembagian kelompok A dan B dalam satu rombel pada hari yang sama.
* **Pemetaan Blok Terpusat**:
  * Admin dapat memetakan siswa per kelas ke Kelompok A atau B secara massal dengan antarmuka seret/pilih cepat.
* **Resolver Jadwal Otomatis**: Layanan `JadwalBlokResolverService` secara dinamis menentukan jadwal KBM yang aktif berdasarkan kalender blok tanggal bersangkutan.

---

## 4. Presensi KBM & Bukti Foto Cerdas (Dual Capture)
Pencatatan bukti kehadiran kegiatan belajar mengajar berbasis visual:
* **Foto Check-In (Awal Jam Pelajaran)**: Perwakilan siswa di kelas mengambil foto guru yang sedang mengajar di depan kelas sebagai bukti kehadiran masuk.
* **Foto Check-Out (Akhir Jam Pelajaran)**: Pengambilan foto penutup saat KBM berakhir untuk memastikan guru mendampingi hingga jam usai.
* **Kompresi Gambar di Sisi Browser (Client-Side GPU)**:
  * Foto kamera ponsel (yang berukuran 5MB - 15MB) secara instan dikompresi menjadi JPEG ringan (< 300KB) sebelum dikirimkan ke server.
  * Menggunakan `createImageBitmap` yang cepat dan hemat baterai tanpa membebani server backend.
* **Toleransi Keterlambatan Otomatis**:
  * Konfigurasi toleransi berbeda antara **Jam Pertama** (misal 10 menit) dan **Jam Lanjutan** (misal 15 menit).
  * Tombol status terlambat dikunci secara otomatis sebelum batas toleransi habis.

---

## 5. Monitoring Harian & Dashboard Publik Real-Time
Layar informasi KBM transparan yang dapat diakses melalui monitor lobi sekolah atau perangkat umum:
* **Status Jam Pelajaran Berjalan**: Menampilkan status real-time setiap kelas pada jam pelajaran aktif:
  * 🟢 **Hadir / Mengajar**: Guru sudah berada di kelas dan terverifikasi foto.
  * 🟡 **Terlambat**: Guru hadir setelah batas toleransi waktu.
  * 🔵 **Digantikan**: Guru utama berhalangan dan digantikan guru piket/pengganti.
  * 🔴 **Kosong / Belum Hadir**: Belum ada presensi masuk setelah jam dimulai.
  * 🟣 **Praktik Kerja Lapangan (PKL)**: Kelas sedang masa dinas luar/industri.
* **Diagram Statistik Harian & 7 Hari Terakhir**: Grafik donat dan diagram batang persentase kehadiran guru dan siswa menggunakan Chart.js.
* **Filter Cepat**: Memfilter tampilan berdasarkan tingkat kelas (10, 11, 12), status kehadiran, atau jurusan.

---

## 6. Modul Guru & Jurnal KBM
Antarmuka khusus pengajar untuk mengelola kegiatan akademik:
* **Jadwal Mengajar Hari Ini**: Daftar kelas dan jam mengajar guru pada hari aktif.
* **Pengisian Jurnal Mengajar**:
  * Judul materi / kompetensi dasar yang diajarkan.
  * Uraian kegiatan pembelajaran dan instruksi tugas siswa.
* **Presensi Siswa per Pertemuan**:
  * Guru dapat mencentang status kehadiran siswa di kelas (*Hadir*, *Sakit*, *Izin*, *Alpa*, *Dispensasi*).
* **Statistik & Riwayat Kehadiran Pribadi**:
  * Rekapitulasi jam mengajar, total sesi, dan persentase kehadiran guru per semester.

---

## 7. Modul Peran Khusus: Wali Kelas, BK, dan Kaprog
Fasilitas tambahan untuk guru yang memegang tugas tambahan struktural:
* **Wali Kelas**:
  * Memantau rekap absensi harian dan bulanan seluruh siswa di kelas perwaliannya.
  * **Kontrol Akses Siswa**: Dapat mengaktifkan atau menonaktifkan akun siswa tertentu untuk mencegah penyalahgunaan fitur upload presensi.
* **Guru Bimbingan Konseling (BK)**:
  * Pemantauan khusus terhadap siswa dengan akumulasi alpa atau masalah kedisiplinan KBM untuk tindak lanjut konseling.
* **Ketua Program Keahlian (Kaprog)**:
  * Monitoring keterlaksanaan KBM pada seluruh rombel di jurusan/konsentrasi keahlian yang dipimpinnya.

---

## 8. Modul Petugas Piket
Pusat komando tata tertib KBM harian di meja piket:
* **Monitoring Kelas Kosong**: Menampilkan daftar kelas yang belum dihadiri guru pada jam yang sedang berjalan.
* **Teguran Digital**: Mengirimkan notifikasi peringatan kepada guru yang belum masuk kelas.
* **Pengisian Guru Pengganti / Tugas Mandiri**:
  * Mencatat guru pengganti yang mengisi jam kosong.
  * Memberikan tugas tertulis/instruksi jika guru pengampu berhalangan hadir dengan surat izin.

---

## 9. Modul Kepala Sekolah & Tata Usaha (Pelaporan & Rekap)
Fasilitas eksekutif untuk evaluasi kedisiplinan dan pelaporan dinas:
* **Tinjauan Eksekutif**: Ringkasan performa KBM seluruh sekolah dalam hitungan persentase dan grafik.
* **Leaderboard Kedisiplinan Guru**: Menampilkan guru dengan persentase kehadiran tertinggi (di luar jam PKL/dispensasi).
* **Ekspor Laporan PDF**: Unduh berkas rekapitulasi kehadiran resmi siap cetak format PDF menggunakan `DomPDF`.
* **Ekspor Laporan Excel**: Unduh data mentah rekapitulasi kehadiran per guru, per mapel, atau per kelas format `.xlsx` menggunakan `Maatwebsite Excel` / `SimpleExcel`.

---

## 10. Administrasi Master Data & Hak Akses
Kontrol menyeluruh oleh Super Admin dan Admin:
* **Manajemen Pengguna (User Management)**:
  * Kelola Guru, Siswa, Petugas Piket, TU, Kepala Sekolah, dan Admin.
  * Fitur reset password massal ke format default (NIP untuk guru, NIS untuk siswa).
  * Import & Export data pengguna melalui template Excel.
* **Manajemen Kelas & Rombel**:
  * Pengaturan tingkat, wali kelas, guru BK, dan penetapan model sistem blok.
* **Manajemen Mata Pelajaran & Jam Pelajaran**:
  * Pengaturan jam pelajaran (JP), waktu istirahat, dan kode mapel.
* **Manajemen Jadwal Pelajaran**:
  * Input manual atau import jadwal massal via Excel.
  * Fitur *Salin Jadwal Antar Semester* untuk menghemat waktu saat pergantian semester baru.
  * Deteksi otomatis jadwal bentrok (*conflict detection*) untuk guru maupun kelas.
* **Kalender Hari Libur**:
  * Penetapan libur nasional, cuti bersama, atau kegiatan khusus sekolah yang otomatis menonaktifkan agenda KBM.
* **Spatie Role & Permission**:
  * Manajemen kategori permission, role kustom, dan pembagian hak akses terperinci.

---

## 11. Progressive Web App (PWA) & Optimasi Seluler
* **Instalasi Tanpa Toko Aplikasi**: Web app dapat dipasang langsung ke layar utama (*Home Screen*) perangkat Android dan iOS melalui web browser.
* **Service Worker Caching**: Aset antarmuka (CSS, JS, ikon) di-cache secara lokal untuk mempercepat waktu muat halaman pada jaringan sekolah yang padat.
* **Desain Responsif**: Tata letak antarmuka dioptimalkan untuk berbagai ukuran layar mulai dari ponsel (360px), tablet, laptop, hingga TV display monitoring.

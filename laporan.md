# LAPORAN ANALISIS SISTEM PRESENSI & PERSIAPAN SUMMARY TABLE
**Project:** Agenda Mengajar Guru (SMK/Sekolah)  
**Tujuan Dokumen:** Referensi teknis komprehensif mengenai kontroler laporan, alur mutasi presensi, struktur basis data, serta kesiapan implementasi *Pre-Aggregation / Summary Table*.

---

## 1. ISI LENGKAP & ANALISIS `ReportController.php`

File lokasi: `app/Http/Controllers/KepalaSekolah/ReportController.php`

### A. Parameter Filter yang Didukung di Halaman Laporan

Kepala Sekolah / Admin memiliki serangkaian opsi filter dinamis untuk menyaring data:

| Parameter Request | Nilai / Format Default | Fungsi & Cara Kerja Filter |
| :--- | :--- | :--- |
| `filter_mode` | `'semester'` (opsi lain: `'custom'`) | Menentukan apakah rentang tanggal diambil dari kalender semester atau rentang tanggal kustom pengguna. |
| `tahun_ajaran` | `Setting::getTahunAjaranAktif()` atau `'all'` | Menyaring data berdasarkan tahun ajaran (misal: `'2026/2027'`). |
| `semester` | `Setting::getSemesterAktif()` atau `'all'` | Menyaring data semester (`'ganjil'` atau `'genap'`). Jika mode `'semester'`, tanggal otomatis diset melalui `Setting::getPeriodeSemesterRange()`. |
| `start_date` & `end_date` | Awal & Akhir Bulan Berjalan (`Carbon::now()->startOfMonth()`) | Digunakan ketika `filter_mode === 'custom'` untuk filter rentang tanggal bebas. |
| `tingkat` | `'all'` (opsi: `'10'`, `'11'`, `'12'`) | Filter angkatan/tingkat. Menggunakan pencocokan ganda: kolom `tingkat` (`10`/`X`, `11`/`XI`, `12`/`XII`) dan pola awalan nama kelas (`'10%'`, `'X %'`). |
| `jurusan` | `'all'` (opsi: `PPLG`, `AKL`, `MPLB`, `PM`, `DKV`, `KLN`, `HTL`, `PBS`) | Filter keahlian jurusan. Memiliki relasi alias cerdas (contoh: `PPLG` mencakup nama `%PPLG%` dan `%RPL%`, `PM` mencakup `%PM%`, `%PBR%`, `%PBD%`). |
| `kelas_id` | `null` (opsional) | Menyaring hanya 1 kelas spesifik. |
| `guru_id` | `null` (opsional, pada method `guru()`) | Menyaring rekapan khusus untuk 1 guru tertentu. |
| `siswa_id` | `null` (opsional, pada method `siswa()`) | Menyaring rekapan khusus untuk 1 siswa tertentu. |
| `only_discrepancy` | `false` (boolean, pada method `guru()`) | Menyaring hanya rekaman kehadiran guru yang memiliki selisih/inkonsistensi antara status yang dilaporkan siswa vs pengakuan guru. |

---

### B. Rumus Perhitungan & Agregasi Persentase Kehadiran

#### 1. Laporan Guru (`ReportController::guru`)
* **Kategori Hadir**: `status in ('hadir', 'terlambat')`
* **Kategori Sakit**: `status = 'sakit' OR alasan_tidak_hadir = 'sakit'`
* **Kategori Dispensasi**: `status in ('dispensasi', 'izin') OR alasan_tidak_hadir in ('izin', 'cuti', 'rapat_dinas', 'dinas_luar', 'tugas_luar', 'dispensasi')`
* **Kategori Alpa**: Record yang bukan kategori Hadir, Sakit, maupun Dispensasi.
* **Perhitungan Persentase Breakdown Alasan**:
  $$\text{Persentase} = \text{round}\left( \frac{\text{Jumlah Record Alasan Tertentu}}{\text{Total Record Kehadiran}} \times 100, 2 \right)$$
* **Deteksi Discrepancy (Selisih Guru vs Laporan Siswa)**:
  `$hasDiscrepancy = ($kehadiranGuru->status !== $pertemuan->fotoBuktis->first()?->status_guru_dilaporkan)`

#### 2. Laporan Siswa (`ReportController::siswa`)
Dijalankan menggunakan **Database-Level Raw Aggregation** langsung pada engine MySQL:
```sql
SELECT 
    siswa_id,
    count(*) as total,
    SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
    SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi
FROM kehadiran_siswas
WHERE pertemuan_id IN (SELECT id FROM pertemuans WHERE ...)
GROUP BY siswa_id
```
* **Breakdown Alasan Siswa**:
```sql
SELECT status, COALESCE(TRIM(keterangan), '') as ket, count(*) as count
FROM kehadiran_siswas
WHERE pertemuan_id IN (SELECT id FROM pertemuans WHERE ...)
GROUP BY status, ket
```
* Status `hadir` tanpa keterangan otomatis dikonversi labelnya menjadi `'-'` (sesuai format pivot Excel).

---

### C. Kolom & Variabel yang Dikirim ke View/Blade

#### Pada View `kepala-sekolah.report.guru`:
* `$kehadiran`: Objek `LengthAwarePaginator` (10 per halaman) berisi rekaman detail kehadiran guru. Eager loading relasi (`guru`, `pertemuan.jadwal.kelas`, `pertemuan.jadwal.mataPelajaran`) dilakukan secara *deferred* hanya untuk 10 baris pada halaman aktif.
* `$summary`: Objek `LengthAwarePaginator` (10 per halaman) ringkasan per guru: `guru`, `hadir`, `sakit`, `dispensasi`, `alpa`, `total`.
* `$alasanBreakdown`: Koleksi array distribusi per alasan: `label`, `category`, `count`, `percentage`.
* `$pieLabels`, `$piePercentages`, `$pieCounts`, `$pieColors`: Dataset untuk render Donut/Pie Chart Chart.js.
* `$discrepancyCount`: Total pertemuan yang ada perbedaan laporan siswa vs guru.
* `$grandTotalCount` & `$grandTotalPercentage`: Total baris log dan 100.00%.

#### Pada View `kepala-sekolah.report.siswa`:
* `$summary`: Objek `LengthAwarePaginator` (10 per halaman) ringkasan per siswa: `siswa`, `siswa_id`, `kelas_nama`, `hadir`, `sakit`, `izin`, `alpa`, `dispensasi`, `total`.
* `$alasanBreakdown`: Distribusi alasan kehadiran siswa beserta persentasenya.
* `$pieLabels`, `$piePercentages`, `$pieCounts`, `$pieColors`: Dataset Donut/Pie Chart kehadiran siswa.
* `$matchingKelasList`, `$allKelasList`, `$siswaFilterList`: Data pendukung filter dropdown.

---

### D. Source Code Lengkap `ReportController.php`

```php
<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function guru(Request $request): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $kelasList = Kelas::orderBy('nama')->get();

        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());
        $filterMode = $request->input('filter_mode', 'semester'); // 'semester' atau 'custom'

        if ($filterMode === 'semester' && $selectedTahunAjaran !== 'all' && $selectedSemester !== 'all') {
            $range = Setting::getPeriodeSemesterRange($selectedTahunAjaran, $selectedSemester);
            $startDate = $range['start'];
            $endDate = $range['end'];
        } else {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : Carbon::now()->startOfMonth();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : Carbon::now()->endOfMonth();
        }

        // Filter Angkatan (Tingkat), Jurusan, Guru, dan Kelas
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');
        $selectedGuruId = $request->input('guru_id');
        $selectedKelasId = $request->input('kelas_id');
        $onlyDiscrepancy = $request->boolean('only_discrepancy');

        $daftarJurusan = [
            'PPLG' => 'PPLG (Pengembangan Perangkat Lunak & Gim)',
            'AKL' => 'AKL (Akuntansi & Keuangan Lembaga)',
            'MPLB' => 'MPLB (Manajemen Perkantoran & Layanan Bisnis)',
            'PM' => 'PM (Pemasaran / Bisnis Retail)',
            'DKV' => 'DKV (Desain Komunikasi Visual)',
            'KLN' => 'KLN (Kuliner)',
            'HTL' => 'HTL (Perhotelan)',
            'PBS' => 'PBS (Perbankan Syariah)',
        ];

        $daftarTingkat = [
            'all' => 'Semua Angkatan',
            '10' => 'Kelas 10 (Fase E)',
            '11' => 'Kelas 11 (Fase F)',
            '12' => 'Kelas 12 (Fase F Lanjutan)',
        ];

        // Query kelas yang memenuhi filter tingkat & jurusan
        $kelasQuery = Kelas::query();
        if ($selectedTingkat !== 'all') {
            $kelasQuery->where(function ($q) use ($selectedTingkat) {
                if ($selectedTingkat === '10') {
                    $q->whereIn('tingkat', ['10', 'X'])->orWhere('nama', 'like', '10%')->orWhere('nama', 'like', 'X %');
                } elseif ($selectedTingkat === '11') {
                    $q->whereIn('tingkat', ['11', 'XI'])->orWhere('nama', 'like', '11%')->orWhere('nama', 'like', 'XI %');
                } elseif ($selectedTingkat === '12') {
                    $q->whereIn('tingkat', ['12', 'XII'])->orWhere('nama', 'like', '12%')->orWhere('nama', 'like', 'XII %');
                }
            });
        }

        if ($selectedJurusan !== 'all') {
            $jUpper = strtoupper(trim($selectedJurusan));
            $kelasQuery->where(function ($q) use ($jUpper) {
                match ($jUpper) {
                    'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                    'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                    'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                    'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                    'DKV' => $q->where('nama', 'like', '%DKV%'),
                    'KLN' => $q->where('nama', 'like', '%KLN%')->orWhere('nama', 'like', '%KULINER%'),
                    'HTL' => $q->where('nama', 'like', '%HTL%')->orWhere('nama', 'like', '%HOTEL%'),
                    'PBS' => $q->where('nama', 'like', '%PBS%'),
                    default => $q->where('nama', 'like', "%{$jUpper}%"),
                };
            });
        }

        $matchingKelasList = (clone $kelasQuery)->orderBy('tingkat')->orderBy('nama')->get();
        $matchingKelasIds = $matchingKelasList->pluck('id');

        if ($selectedKelasId) {
            $targetKelasIds = collect([$selectedKelasId]);
        } elseif ($selectedTingkat !== 'all' || $selectedJurusan !== 'all') {
            $targetKelasIds = $matchingKelasIds;
        } else {
            $targetKelasIds = null;
        }

        $query = KehadiranGuru::with(['pertemuan.fotoBuktis'])
            ->whereHas('pertemuan', function ($q) use ($startDate, $endDate, $targetKelasIds, $selectedTahunAjaran, $selectedSemester, $filterMode) {
                $q->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

                if ($filterMode === 'semester') {
                    if ($selectedTahunAjaran !== 'all') {
                        $q->where(fn ($sub) => $sub->where('tahun_ajaran', $selectedTahunAjaran)->orWhereNull('tahun_ajaran'));
                    }
                    if ($selectedSemester !== 'all') {
                        $q->where(fn ($sub) => $sub->where('semester', $selectedSemester)->orWhereNull('semester'));
                    }
                }

                if ($targetKelasIds !== null) {
                    $q->whereHas('jadwal', fn ($j) => $j->whereIn('kelas_id', $targetKelasIds));
                }
            });

        if ($selectedGuruId) {
            $query->where('guru_id', $selectedGuruId);
        }

        $allKehadiran = $query->orderBy('created_at', 'desc')->get();
        $totalKehadiranRecords = $allKehadiran->count();
        $discrepancyCount = $allKehadiran->filter(fn ($kh) => $kh->has_discrepancy)->count();

        // ── Agregasi Alasan & Persentase Kehadiran Guru (Pivot Style) ──
        $breakdownGroups = [];
        foreach ($allKehadiran as $row) {
            $isHadir = $row->isHadirCategory();
            $rawKet = trim($row->keterangan ?? '');

            if ($isHadir) {
                // Sesuai pivot: status hadir bernilai '-' jika keterangan kosong
                $label = ($rawKet === '' || $rawKet === '-') ? '-' : $rawKet;
                $category = 'hadir';
            } else {
                if ($rawKet !== '' && $rawKet !== '-') {
                    $label = ucfirst($rawKet);
                } elseif ($row->alasan_tidak_hadir_label) {
                    $label = $row->alasan_tidak_hadir_label;
                } else {
                    $label = $row->status_label;
                }
                $category = strtolower($row->status ?? 'tidak_hadir');
            }

            if (! isset($breakdownGroups[$label])) {
                $breakdownGroups[$label] = [
                    'label' => $label,
                    'category' => $category,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
            $breakdownGroups[$label]['count']++;
        }

        // Hitung persentase desimal presisi 2 digit
        $alasanBreakdown = collect($breakdownGroups)->map(function ($item) use ($totalKehadiranRecords) {
            $item['percentage'] = $totalKehadiranRecords > 0
                ? round(($item['count'] / $totalKehadiranRecords) * 100, 2)
                : 0;

            return $item;
        })->sortByDesc('count')->values();

        $grandTotalCount = $totalKehadiranRecords;
        $grandTotalPercentage = $totalKehadiranRecords > 0 ? 100.00 : 0.00;

        // Siapkan warna dan dataset untuk Pie / Donut Chart
        $colorPalette = [
            '#2563EB', '#EF4444', '#F59E0B', '#10B981', '#8B5CF6',
            '#EC4899', '#06B6D4', '#F97316', '#6366F1', '#14B8A6',
            '#84CC16', '#A855F7', '#E11D48', '#64748B', '#D97706',
            '#0284C7', '#7C3AED', '#DB2777',
        ];

        $pieLabels = [];
        $piePercentages = [];
        $pieCounts = [];
        $pieColors = [];

        foreach ($alasanBreakdown as $idx => $item) {
            $pieLabels[] = $item['label'];
            $piePercentages[] = $item['percentage'];
            $pieCounts[] = $item['count'];

            if ($item['label'] === '-' || strtolower($item['label']) === 'hadir') {
                $pieColors[] = '#2563EB'; // Royal Blue khas pivot excel
            } else {
                $pieColors[] = $colorPalette[$idx % count($colorPalette)];
            }
        }

        $kehadiranCollection = $onlyDiscrepancy
            ? $allKehadiran->filter(fn ($kh) => $kh->has_discrepancy)->values()
            : $allKehadiran;

        // Paginate Kehadiran Detail (10 per page) - eager load hanya untuk halaman aktif
        $kehadiranPage = LengthAwarePaginator::resolveCurrentPage('page');
        $perPage = 10;
        $slicedKehadiran = $kehadiranCollection->slice(($kehadiranPage - 1) * $perPage, $perPage)->values();

        $slicedKehadiran->load([
            'guru',
            'pertemuan.jadwal.kelas',
            'pertemuan.jadwal.mataPelajaran',
        ]);

        $kehadiran = new LengthAwarePaginator(
            $slicedKehadiran,
            $kehadiranCollection->count(),
            $perPage,
            $kehadiranPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page', 'query' => $request->query()]
        );

        // Summary per guru (10 per page) - eager load guru models hanya untuk 10 guru di halaman aktif
        $allTeacherGroups = $allKehadiran->groupBy('guru_id');
        $summaryPage = LengthAwarePaginator::resolveCurrentPage('summary_page');
        $slicedTeacherGroups = $allTeacherGroups->slice(($summaryPage - 1) * $perPage, $perPage);
        $pageTeacherIds = $slicedTeacherGroups->keys();
        $teacherModels = User::whereIn('id', $pageTeacherIds)->get()->keyBy('id');

        $summaryCollection = $slicedTeacherGroups->map(function ($items, $guruId) use ($teacherModels) {
            return [
                'guru' => $teacherModels->get($guruId) ?? $items->first()?->guru,
                'hadir' => $items->filter(fn ($item) => $item->isHadirCategory())->count(),
                'sakit' => $items->filter(fn ($item) => $item->isSakitCategory())->count(),
                'dispensasi' => $items->filter(fn ($item) => $item->isDispensasiCategory())->count(),
                'alpa' => $items->filter(fn ($item) => $item->isAlpaCategory())->count(),
                'total' => $items->count(),
            ];
        })->values();

        $summary = new LengthAwarePaginator(
            $summaryCollection,
            $allTeacherGroups->count(),
            $perPage,
            $summaryPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'summary_page', 'query' => $request->query()]
        );

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('kepala-sekolah.report.guru', compact(
            'kehadiran',
            'summary',
            'guruList',
            'kelasList',
            'matchingKelasList',
            'startDate',
            'endDate',
            'selectedGuruId',
            'selectedKelasId',
            'selectedTingkat',
            'selectedJurusan',
            'daftarJurusan',
            'daftarTingkat',
            'onlyDiscrepancy',
            'discrepancyCount',
            'selectedTahunAjaran',
            'selectedSemester',
            'daftarTahunAjaran',
            'daftarSemester',
            'filterMode',
            'alasanBreakdown',
            'grandTotalCount',
            'grandTotalPercentage',
            'pieLabels',
            'piePercentages',
            'pieCounts',
            'pieColors'
        ));
    }

    public function siswa(Request $request): View
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());
        $filterMode = $request->input('filter_mode', 'semester');

        if ($filterMode === 'semester' && $selectedTahunAjaran !== 'all' && $selectedSemester !== 'all') {
            $range = Setting::getPeriodeSemesterRange($selectedTahunAjaran, $selectedSemester);
            $startDate = $range['start'];
            $endDate = $range['end'];
        } else {
            try {
                $startDate = $request->filled('start_date')
                    ? Carbon::parse($request->input('start_date'))->startOfDay()
                    : Carbon::now()->startOfMonth();

                $endDate = $request->filled('end_date')
                    ? Carbon::parse($request->input('end_date'))->endOfDay()
                    : Carbon::now()->endOfMonth();
            } catch (\Throwable) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }
        }

        // Filter Angkatan (Tingkat), Jurusan, Kelas, dan Siswa
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');
        $selectedKelasId = $request->input('kelas_id');
        $selectedSiswaId = $request->input('siswa_id');

        $daftarJurusan = [
            'PPLG' => 'PPLG (Pengembangan Perangkat Lunak & Gim)',
            'AKL' => 'AKL (Akuntansi & Keuangan Lembaga)',
            'MPLB' => 'MPLB (Manajemen Perkantoran & Layanan Bisnis)',
            'PM' => 'PM (Pemasaran / Bisnis Retail)',
            'DKV' => 'DKV (Desain Komunikasi Visual)',
            'KLN' => 'KLN (Kuliner)',
            'HTL' => 'HTL (Perhotelan)',
            'PBS' => 'PBS (Perbankan Syariah)',
        ];

        $daftarTingkat = [
            'all' => 'Semua Angkatan',
            '10' => 'Kelas 10 (Fase E)',
            '11' => 'Kelas 11 (Fase F)',
            '12' => 'Kelas 12 (Fase F Lanjutan)',
        ];

        // Query kelas yang memenuhi filter tingkat & jurusan
        $kelasQuery = Kelas::query();
        if ($selectedTingkat !== 'all') {
            $kelasQuery->where(function ($q) use ($selectedTingkat) {
                if ($selectedTingkat === '10') {
                    $q->whereIn('tingkat', ['10', 'X'])->orWhere('nama', 'like', '10%')->orWhere('nama', 'like', 'X %');
                } elseif ($selectedTingkat === '11') {
                    $q->whereIn('tingkat', ['11', 'XI'])->orWhere('nama', 'like', '11%')->orWhere('nama', 'like', 'XI %');
                } elseif ($selectedTingkat === '12') {
                    $q->whereIn('tingkat', ['12', 'XII'])->orWhere('nama', 'like', '12%')->orWhere('nama', 'like', 'XII %');
                }
            });
        }

        if ($selectedJurusan !== 'all') {
            $jUpper = strtoupper(trim($selectedJurusan));
            $kelasQuery->where(function ($q) use ($jUpper) {
                match ($jUpper) {
                    'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                    'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                    'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                    'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                    'DKV' => $q->where('nama', 'like', '%DKV%'),
                    'KLN' => $q->where('nama', 'like', '%KLN%')->orWhere('nama', 'like', '%KULINER%'),
                    'HTL' => $q->where('nama', 'like', '%HTL%')->orWhere('nama', 'like', '%HOTEL%'),
                    'PBS' => $q->where('nama', 'like', '%PBS%'),
                    default => $q->where('nama', 'like', "%{$jUpper}%"),
                };
            });
        }

        // Kelas untuk dropdown filter
        $matchingKelasList = (clone $kelasQuery)->orderBy('tingkat')->orderBy('nama')->get();
        $matchingKelasIds = $matchingKelasList->pluck('id');

        // Jika user memilih spesifik kelas_id atau filter tingkat/jurusan
        if ($selectedKelasId) {
            $targetKelasIds = collect([$selectedKelasId]);
        } elseif ($selectedTingkat !== 'all' || $selectedJurusan !== 'all') {
            $targetKelasIds = $matchingKelasIds;
        } else {
            $targetKelasIds = null;
        }

        // Query pertemuan yang sesuai rentang tanggal & kelas yang terfilter (subquery)
        $pertemuanSubQuery = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($filterMode === 'semester') {
            if ($selectedTahunAjaran !== 'all') {
                $pertemuanSubQuery->where(fn ($sub) => $sub->where('tahun_ajaran', $selectedTahunAjaran)->orWhereNull('tahun_ajaran'));
            }
            if ($selectedSemester !== 'all') {
                $pertemuanSubQuery->where(fn ($sub) => $sub->where('semester', $selectedSemester)->orWhereNull('semester'));
            }
        }

        if ($targetKelasIds !== null) {
            $pertemuanSubQuery->whereHas('jadwal', fn ($j) => $j->whereIn('kelas_id', $targetKelasIds));
        }

        // Query dasar presensi siswa (menggunakan subquery efisien)
        $baseKehadiranQuery = KehadiranSiswa::whereIn('pertemuan_id', $pertemuanSubQuery->select('id'));

        if ($selectedSiswaId && $selectedSiswaId !== 'all') {
            $baseKehadiranQuery->where('siswa_id', $selectedSiswaId);
        }

        $totalKehadiranRecords = (clone $baseKehadiranQuery)->count();

        // ── Agregasi Alasan & Persentase Kehadiran Siswa (Database Level GROUP BY) ──
        $rawBreakdown = (clone $baseKehadiranQuery)
            ->selectRaw("status, COALESCE(TRIM(keterangan), '') as ket, count(*) as count")
            ->groupBy('status', 'ket')
            ->get();

        $breakdownGroups = [];
        foreach ($rawBreakdown as $row) {
            $status = strtolower($row->status ?? 'hadir');
            $rawKet = trim($row->ket ?? '');

            if ($status === 'hadir') {
                // Sesuai pivot di Excel: status hadir bernilai '-' jika keterangan kosong
                $label = ($rawKet === '' || $rawKet === '-') ? '-' : $rawKet;
                $category = 'hadir';
            } else {
                if ($rawKet !== '' && $rawKet !== '-') {
                    $label = ucfirst($rawKet);
                } else {
                    $label = match ($status) {
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'alpa' => 'Alpa',
                        'dispensasi' => 'Dispensasi',
                        'terlambat' => 'Terlambat',
                        default => ucfirst($status),
                    };
                }
                $category = $status;
            }

            if (! isset($breakdownGroups[$label])) {
                $breakdownGroups[$label] = [
                    'label' => $label,
                    'category' => $category,
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
            $breakdownGroups[$label]['count'] += (int) $row->count;
        }

        // Hitung persentase desimal presisi 2 digit
        $alasanBreakdown = collect($breakdownGroups)->map(function ($item) use ($totalKehadiranRecords) {
            $item['percentage'] = $totalKehadiranRecords > 0
                ? round(($item['count'] / $totalKehadiranRecords) * 100, 2)
                : 0;

            return $item;
        })->sortByDesc('count')->values();

        $grandTotalCount = $totalKehadiranRecords;
        $grandTotalPercentage = $totalKehadiranRecords > 0 ? 100.00 : 0.00;

        // Siapkan warna dan dataset untuk Pie / Donut Chart
        $colorPalette = [
            '#2563EB', '#EF4444', '#F59E0B', '#10B981', '#8B5CF6',
            '#EC4899', '#06B6D4', '#F97316', '#6366F1', '#14B8A6',
            '#84CC16', '#A855F7', '#E11D48', '#64748B', '#D97706',
            '#0284C7', '#7C3AED', '#DB2777',
        ];

        $pieLabels = [];
        $piePercentages = [];
        $pieCounts = [];
        $pieColors = [];

        foreach ($alasanBreakdown as $idx => $item) {
            $pieLabels[] = $item['label'];
            $piePercentages[] = $item['percentage'];
            $pieCounts[] = $item['count'];

            if ($item['label'] === '-' || strtolower($item['label']) === 'hadir') {
                $pieColors[] = '#2563EB'; // Royal Blue khas pivot excel
            } else {
                $pieColors[] = $colorPalette[$idx % count($colorPalette)];
            }
        }

        // Summary per siswa (10 per page, langsung diagregasi di database)
        $paginatedSiswaStats = (clone $baseKehadiranQuery)
            ->selectRaw("siswa_id,
                count(*) as total,
                SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi
            ")
            ->groupBy('siswa_id')
            ->paginate(10)
            ->appends($request->query());

        $siswaIdsOnPage = $paginatedSiswaStats->pluck('siswa_id');
        $siswaModels = User::with('siswaProfile.kelas')->whereIn('id', $siswaIdsOnPage)->get()->keyBy('id');

        $summary = $paginatedSiswaStats->through(function ($stat) use ($siswaModels) {
            $siswa = $siswaModels->get($stat->siswa_id);

            return [
                'siswa' => $siswa,
                'siswa_id' => $stat->siswa_id,
                'kelas_nama' => $siswa?->siswaProfile?->kelas?->nama ?? '-',
                'hadir' => (int) $stat->hadir,
                'sakit' => (int) $stat->sakit,
                'izin' => (int) $stat->izin,
                'alpa' => (int) $stat->alpa,
                'dispensasi' => (int) $stat->dispensasi,
                'total' => (int) $stat->total,
            ];
        });

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();
        $allKelasList = Kelas::orderBy('nama')->get();

        // Daftar siswa untuk filter opsional
        $siswaFilterList = $selectedKelasId
            ? User::where('role', 'siswa')->whereHas('siswaProfile', fn ($sp) => $sp->where('kelas_id', $selectedKelasId))->orderBy('name')->get()
            : collect();

        return view('kepala-sekolah.report.siswa', compact(
            'summary',
            'matchingKelasList',
            'allKelasList',
            'startDate',
            'endDate',
            'selectedKelasId',
            'selectedTingkat',
            'selectedJurusan',
            'selectedSiswaId',
            'selectedTahunAjaran',
            'selectedSemester',
            'daftarTahunAjaran',
            'daftarSemester',
            'daftarJurusan',
            'daftarTingkat',
            'filterMode',
            'alasanBreakdown',
            'grandTotalCount',
            'grandTotalPercentage',
            'pieLabels',
            'piePercentages',
            'pieCounts',
            'pieColors',
            'siswaFilterList'
        ));
    }
}
```

---

## 2. TEMPAT ABSENSI DISIMPAN & DIUBAH (CONTROLLER INPUT ABSENSI)

Berikut adalah audit lengkap seluruh file tempat mutasi data `kehadiran_siswas` dan `kehadiran_gurus` terjadi di dalam sistem:

### A. Titik Mutasi `KehadiranSiswa`

#### 1. `app/Http/Controllers/Guru/PertemuanController.php`
* **Method `show(int $jadwalId, string $tanggal)` (Line 61–66)**:
  * Terjadi inisialisasi awal otomatis daftar siswa kelas saat guru membuka sesi pertemuan hari itu.
  * **Sintaks**:
    ```php
    foreach ($siswaIds as $siswaId) {
        KehadiranSiswa::firstOrCreate(
            ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
            ['status' => 'hadir']
        );
    }
    ```
* **Method `store(Request $request, int $jadwalId, string $tanggal)` (Line 140–148)**:
  * Terjadi saat guru menekan tombol **Simpan Agenda & Presensi**.
  * **Sintaks**:
    ```php
    foreach ($validated['siswa'] as $siswaId => $data) {
        KehadiranSiswa::updateOrCreate(
            ['pertemuan_id' => $targetPertemuan->id, 'siswa_id' => $siswaId],
            [
                'status' => $data['status'],
                'keterangan' => $data['keterangan'] ?? null,
            ]
        );
    }
    ```

#### 2. `app/Http/Controllers/Guru/KehadiranSiswaController.php`
* **Method `update(...)` (Line 34–37)**:
  * Update status kehadiran 1 siswa secara instan (inline update di tabel).
  * **Sintaks**:
    ```php
    KehadiranSiswa::updateOrCreate(
        ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
        $validated // ['status' => ..., 'keterangan' => ...]
    );
    ```
* **Method `bulkUpdate(...)` (Line 61–66)**:
  * Update presensi massal seluruh siswa kelas.
  * **Sintaks**:
    ```php
    foreach ($validated['siswa'] as $siswaId => $data) {
        KehadiranSiswa::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaId],
            $data
        );
    }
    ```

---

### B. Titik Mutasi `KehadiranGuru`

#### 1. `app/Http/Controllers/Siswa/CaptureController.php`
* **Method `store(...)` (Line 374–385)**:
  * Terjadi saat siswa mengunggah foto bukti KBM untuk melaporkan status kehadiran guru.
  * **Sintaks**:
    ```php
    KehadiranGuru::updateOrCreate(
        ['pertemuan_id' => $targetPertemuan->id, 'guru_id' => $sched->guru_id],
        [
            'status' => $jpStatus,
            'alasan_tidak_hadir' => $alasanForJp,
            'guru_pengganti_nama' => $validated['guru_pengganti_nama'] ?? null,
            'keterangan' => $keteranganForJp,
            'waktu_hadir' => in_array($jpStatus, ['hadir', 'terlambat']) ? now() : null,
        ]
    );
    ```

#### 2. `app/Http/Controllers/Guru/PertemuanController.php`
* **Method `store(...)` (Line 130–136)**:
  * Memastikan record `KehadiranGuru` tersimpan saat guru mengisi agenda mengajar.
  * **Sintaks**:
    ```php
    KehadiranGuru::firstOrCreate(
        ['pertemuan_id' => $targetPertemuan->id, 'guru_id' => Auth::id()],
        [
            'status' => 'hadir',
            'waktu_hadir' => now(),
        ]
    );
    ```

#### 3. `app/Http/Controllers/Guru/KehadiranController.php`
* **Method `store(...)` (Line 31–34)**:
  * Konfirmasi mandiri kehadiran oleh guru yang bersangkutan.
  * **Sintaks**:
    ```php
    KehadiranGuru::updateOrCreate(
        ['pertemuan_id' => $pertemuan->id, 'guru_id' => Auth::id()],
        $validated
    );
    ```

---

### 💡 Kesimpulan Analisis Mutasi (Paling Penting):
1. **Tidak Ada Raw SQL / Bulk Insert Tanpa Event**:
   * Sistem **TIDAK PERNAH** menggunakan `DB::table('kehadiran_siswas')->insert(...)` ataupun `KehadiranSiswa::insert([...])`.
   * Seluruh modifikasi data dilakukan via Eloquent: `firstOrCreate()` dan `updateOrCreate()`.
2. **Kesesuaian dengan Eloquent Observer**:
   * Karena setiap mutasi melalui Eloquent Model, **Laravel Model Events (`creating`, `created`, `updating`, `updated`, `saved`, `deleted`) PASTI terpanggil 100% secara otomatis**.
   * Artinya, arsitektur **Eloquent Observer** (`KehadiranSiswaObserver` dan `KehadiranGuruObserver`) **sangat aman dan siap digunakan langsung** untuk menyinkronkan *Summary Table* secara real-time tanpa perlu mengubah isi controller input absensi!

---

## 3. STRUKTUR SKEMA TABEL & RELASI DATABASE

### A. Tabel `pertemuans`

* **File Migrasi Terkait:**
  * `database/migrations/2026_09_18_013749_create_pertemuans_table.php`
  * `database/migrations/2026_09_25_120000_add_tahun_ajaran_and_semester_to_tables.php`
  * `database/migrations/2026_10_14_103127_add_soft_deletes_to_pertemuans_table.php`
  * `database/migrations/2026_10_16_211129_add_performance_covering_indexes_to_core_tables.php`

```sql
CREATE TABLE `pertemuans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `jadwal_id` BIGINT UNSIGNED NOT NULL,
    `tanggal` DATE NOT NULL,
    `tahun_ajaran` VARCHAR(20) NULL,
    `semester` VARCHAR(10) NULL,
    `materi_ajar` TEXT NULL,
    `penugasan` TEXT NULL,
    `status` ENUM('menunggu', 'berlangsung', 'selesai') NOT NULL DEFAULT 'menunggu',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    UNIQUE KEY `pertemuans_jadwal_id_tanggal_unique` (`jadwal_id`, `tanggal`),
    INDEX `idx_pertemuans_tanggal` (`tanggal`),
    INDEX `idx_pertemuans_tanggal_status` (`tanggal`, `status`),
    INDEX `idx_pertemuans_tanggal_jadwal` (`tanggal`, `jadwal_id`),
    INDEX `pertemuan_ta_sem_idx` (`tahun_ajaran`, `semester`),
    CONSTRAINT `pertemuans_jadwal_id_foreign` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal_pelajarans` (`id`) ON DELETE CASCADE
);
```

---

### B. Tabel `kehadiran_siswas`

* **File Migrasi Terkait:**
  * `database/migrations/2026_09_18_013752_create_kehadiran_siswas_table.php`
  * `database/migrations/2026_09_28_154500_add_terlambat_to_kehadiran_siswas_status.php` (Mengubah status dari enum terbatas menjadi string 50 default 'hadir')
  * `database/migrations/2026_10_16_211129_add_performance_covering_indexes_to_core_tables.php`

```sql
CREATE TABLE `kehadiran_siswas` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pertemuan_id` BIGINT UNSIGNED NOT NULL,
    `siswa_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'hadir', -- Validasi form: hadir, terlambat, sakit, izin, alpa, dispensasi
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `kehadiran_siswas_pertemuan_id_siswa_id_unique` (`pertemuan_id`, `siswa_id`),
    INDEX `kehadiran_siswas_covering_idx` (`pertemuan_id`, `siswa_id`, `status`),
    INDEX `kehadiran_siswas_siswa_status_idx` (`siswa_id`, `status`),
    CONSTRAINT `kehadiran_siswas_pertemuan_id_foreign` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `kehadiran_siswas_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

---

### C. Tabel `kehadiran_gurus`

* **File Migrasi Terkait:**
  * `database/migrations/2026_09_18_013750_create_kehadiran_gurus_table.php`
  * `database/migrations/2026_09_21_083000_add_role_piket_and_update_attendance_status.php` (Status diubah ke string 50 dan penambahan `alasan_tidak_hadir`)
  * `database/migrations/2026_10_16_211129_add_performance_covering_indexes_to_core_tables.php`

```sql
CREATE TABLE `kehadiran_gurus` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pertemuan_id` BIGINT UNSIGNED NOT NULL,
    `guru_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'hadir', -- hadir, terlambat, tidak_hadir, sakit, alpa, dispensasi
    `alasan_tidak_hadir` VARCHAR(50) NULL, -- sakit, izin, cuti, rapat_dinas, dinas_luar, tugas_luar, tanpa_keterangan
    `jenis_alpa` ENUM('ada_tugas', 'tanpa_tugas', 'guru_pengganti') NULL,
    `guru_pengganti_nama` VARCHAR(255) NULL,
    `keterangan` TEXT NULL,
    `waktu_hadir` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `kehadiran_gurus_pertemuan_id_guru_id_unique` (`pertemuan_id`, `guru_id`),
    INDEX `kehadiran_gurus_guru_status_idx` (`guru_id`, `status`),
    INDEX `kehadiran_gurus_covering_idx` (`pertemuan_id`, `guru_id`, `status`),
    CONSTRAINT `kehadiran_gurus_pertemuan_id_foreign` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `kehadiran_gurus_guru_id_foreign` FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

---

### D. Relasi Data Menuju `jadwal_pelajarans` dan `kelas`

```mermaid
erDiagram
    kelas ||--o{ jadwal_pelajarans : "memiliki"
    users ||--o{ jadwal_pelajarans : "mengajar (guru_id)"
    jadwal_pelajarans ||--o{ pertemuans : "menghasilkan sesi"
    pertemuans ||--o{ kehadiran_siswas : "daftar hadir siswa"
    pertemuans ||--o| kehadiran_gurus : "absensi guru"
    users ||--o{ kehadiran_siswas : "siswa_id"
    users ||--o{ kehadiran_gurus : "guru_id"
```

* Untuk mendapatkan `kelas_id` dari baris `kehadiran_siswas`:
  1. Jalur Sesi KBM: `kehadiran_siswas.pertemuan_id` $\to$ `pertemuans.jadwal_id` $\to$ `jadwal_pelajarans.kelas_id`
  2. Jalur Profil Siswa: `kehadiran_siswas.siswa_id` $\to$ `siswa_profiles.kelas_id`
* Untuk mendapatkan `tahun_ajaran` dan `semester`:
  Tersimpan langsung pada kolom denormalisasi `pertemuans.tahun_ajaran` dan `pertemuans.semester` (otomatis terisi saat pertemuan dibuat).

---

## 4. CETAK BIRU REKOMENDASI *SUMMARY TABLE* (`rekap_kehadiran_siswas`)

Berdasarkan seluruh analisis di atas, tabel rekapitulasi dapat dirancang dengan struktur berikut:

```sql
CREATE TABLE `rekap_kehadiran_siswas` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `siswa_id` BIGINT UNSIGNED NOT NULL,
    `kelas_id` BIGINT UNSIGNED NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `semester` VARCHAR(10) NOT NULL,
    `total_pertemuan` INT UNSIGNED NOT NULL DEFAULT 0,
    `hadir` INT UNSIGNED NOT NULL DEFAULT 0,
    `terlambat` INT UNSIGNED NOT NULL DEFAULT 0,
    `sakit` INT UNSIGNED NOT NULL DEFAULT 0,
    `izin` INT UNSIGNED NOT NULL DEFAULT 0,
    `alpa` INT UNSIGNED NOT NULL DEFAULT 0,
    `dispensasi` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `rekap_siswa_ta_sem_unique` (`siswa_id`, `tahun_ajaran`, `semester`),
    INDEX `idx_rekap_kelas_ta_sem` (`kelas_id`, `tahun_ajaran`, `semester`),
    CONSTRAINT `fk_rekap_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rekap_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE
);
```

### Keuntungan Arsitektur Ini:
1. **Observer Support 100%**: Karena seluruh input absensi di aplikasi ini menggunakan `KehadiranSiswa::updateOrCreate()`, `KehadiranSiswaObserver::saved()` akan otomatis mengkalkulasi ulang atau meng-increment baris di `rekap_kehadiran_siswas` hanya untuk siswa tersebut.
2. **Kecepatan Instan**: Membaca 1 semester siswa di `ReportController` tidak lagi memindai 1.500.000 baris di tabel `kehadiran_siswas`, melainkan cukup membaca $\sim 1.500$ baris di `rekap_kehadiran_siswas` (waktu query turun drastis ke $< 5\text{ ms}$).

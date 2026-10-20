<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\RekapKehadiranSiswa;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomReportController extends Controller
{
    /**
     * Definition of available modules, descriptions, groups, and allowed whitelisted columns.
     *
     * @var array<string, array{
     *     group: string,
     *     group_label: string,
     *     key: string,
     *     label: string,
     *     description: string,
     *     badge: string,
     *     icon: string,
     *     columns: array<string, array{label: string, default: bool}>
     * }>
     */
    public const MODULES = [
        'rekap_kehadiran' => [
            'group' => 'siswa',
            'group_label' => 'Data Siswa',
            'key' => 'rekap_kehadiran',
            'label' => 'Rekap Kehadiran Siswa (Fast Agregasi)',
            'description' => 'Data statistik dan persentase kehadiran peserta didik per semester / kelas dari tabel agregasi cepat.',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'icon' => 'attendance',
            'columns' => [
                'nama_siswa' => ['label' => 'Nama Siswa', 'default' => true],
                'nis' => ['label' => 'NIS', 'default' => true],
                'kelas' => ['label' => 'Kelas', 'default' => true],
                'tingkat' => ['label' => 'Tingkat Kelas', 'default' => false],
                'tahun_ajaran' => ['label' => 'Tahun Ajaran', 'default' => true],
                'semester' => ['label' => 'Semester', 'default' => true],
                'total_pertemuan' => ['label' => 'Total Pertemuan (Jam/Sesi)', 'default' => true],
                'hadir' => ['label' => 'Total Hadir', 'default' => true],
                'hadir_murni' => ['label' => 'Hadir Tepat Waktu', 'default' => false],
                'terlambat' => ['label' => 'Terlambat', 'default' => true],
                'sakit' => ['label' => 'Sakit (S)', 'default' => true],
                'izin' => ['label' => 'Izin (I)', 'default' => true],
                'alpa' => ['label' => 'Alpa / Tanpa Keterangan (A)', 'default' => true],
                'dispensasi' => ['label' => 'Dispensasi (D)', 'default' => false],
                'persentase_kehadiran' => ['label' => 'Persentase Kehadiran (%)', 'default' => true],
            ],
        ],
        'master_siswa' => [
            'group' => 'siswa',
            'group_label' => 'Data Siswa',
            'key' => 'master_siswa',
            'label' => 'Data Master Siswa & Kelas',
            'description' => 'Basis data identitas seluruh peserta didik aktif/nonaktif, kelas, tingkat, dan pembagian kelompok blok KBM.',
            'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            'icon' => 'students',
            'columns' => [
                'nama' => ['label' => 'Nama Lengkap', 'default' => true],
                'nis' => ['label' => 'NIS', 'default' => true],
                'kelas' => ['label' => 'Kelas', 'default' => true],
                'tingkat' => ['label' => 'Tingkat (10/11/12)', 'default' => true],
                'kelompok_blok' => ['label' => 'Kelompok Blok (A/B)', 'default' => true],
                'email' => ['label' => 'Email Akun', 'default' => true],
                'status_akun' => ['label' => 'Status Akun', 'default' => true],
                'tanggal_terdaftar' => ['label' => 'Tanggal Registrasi', 'default' => false],
            ],
        ],
        'kehadiran_guru' => [
            'group' => 'guru',
            'group_label' => 'Data Guru',
            'key' => 'kehadiran_guru',
            'label' => 'Jurnal Presensi & KBM Guru',
            'description' => 'Catatan kehadiran guru per sesi KBM, status tepat waktu/terlambat/izin/alpa, kelas dan mata pelajaran yang diajar.',
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            'icon' => 'teacher_attendance',
            'columns' => [
                'tanggal' => ['label' => 'Tanggal KBM', 'default' => true],
                'nama_guru' => ['label' => 'Nama Guru', 'default' => true],
                'nip' => ['label' => 'NIP', 'default' => true],
                'mata_pelajaran' => ['label' => 'Mata Pelajaran', 'default' => true],
                'kelas' => ['label' => 'Kelas yang Diajar', 'default' => true],
                'tingkat' => ['label' => 'Tingkat Kelas', 'default' => false],
                'status_kehadiran' => ['label' => 'Status Kehadiran', 'default' => true],
                'alasan_keterangan' => ['label' => 'Keterangan / Alasan', 'default' => true],
                'guru_pengganti' => ['label' => 'Guru Pengganti', 'default' => false],
                'waktu_hadir' => ['label' => 'Waktu Presensi', 'default' => false],
                'materi_ajar' => ['label' => 'Materi Pembelajaran (Jurnal)', 'default' => false],
            ],
        ],
        'master_guru' => [
            'group' => 'guru',
            'group_label' => 'Data Guru',
            'key' => 'master_guru',
            'label' => 'Data Master Guru & Penugasan',
            'description' => 'Basis data identitas seluruh guru pengajar, NIP, penugasan mata pelajaran, status wali kelas, dan kaprog.',
            'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
            'icon' => 'teachers',
            'columns' => [
                'nama' => ['label' => 'Nama Lengkap Guru', 'default' => true],
                'nip' => ['label' => 'NIP Guru', 'default' => true],
                'email' => ['label' => 'Email Akun', 'default' => true],
                'mapel' => ['label' => 'Mata Pelajaran Diampu', 'default' => true],
                'wali_kelas' => ['label' => 'Penugasan Wali Kelas', 'default' => true],
                'kaprog_jurusan' => ['label' => 'Kaprog / Jurusan', 'default' => true],
                'status_akun' => ['label' => 'Status Akun (Aktif/Nonaktif)', 'default' => true],
                'tanggal_terdaftar' => ['label' => 'Tanggal Registrasi', 'default' => false],
            ],
        ],
        'activity_log' => [
            'group' => 'sistem',
            'group_label' => 'Log Sistem',
            'key' => 'activity_log',
            'label' => 'Log Aktivitas & Audit Trail Sistem',
            'description' => 'Rekam jejak mutasi data (CREATE, UPDATE, DELETE, RESTORE), login, dan audit log pengguna.',
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            'icon' => 'logs',
            'columns' => [
                'waktu' => ['label' => 'Waktu & Tanggal', 'default' => true],
                'user_name' => ['label' => 'Nama Pelaksana', 'default' => true],
                'role' => ['label' => 'Role Akun', 'default' => true],
                'action' => ['label' => 'Aksi (Action)', 'default' => true],
                'module' => ['label' => 'Modul Sistem', 'default' => true],
                'kategori' => ['label' => 'Kategori Tematik', 'default' => true],
                'description' => ['label' => 'Deskripsi Aktivitas', 'default' => true],
                'ip_address' => ['label' => 'IP Address', 'default' => true],
            ],
        ],
    ];

    /**
     * Display the dynamic report builder form.
     */
    public function index(): View
    {
        $modules = self::MODULES;

        $kelasList = Kelas::orderBy('tingkat')->orderBy('nama')->get();

        $tingkatList = Kelas::select('tingkat')->distinct()->orderBy('tingkat')->pluck('tingkat')->filter()->values()->all();
        if (empty($tingkatList)) {
            $tingkatList = ['10', '11', '12'];
        }

        $guruList = User::where('role', 'guru')->orderBy('name')->get(['id', 'name']);
        $mapelList = MataPelajaran::orderBy('nama')->get(['id', 'nama', 'kode']);

        $tahunAjaranList = Setting::getDaftarTahunAjaran();
        if (empty($tahunAjaranList)) {
            $tahunAjaranList = Kelas::select('tahun_ajaran')->distinct()->pluck('tahun_ajaran')->filter()->values()->all();
            if (empty($tahunAjaranList)) {
                $tahunAjaranList = [Setting::getTahunAjaranAktif()];
            }
        }

        $currentTahunAjaran = Setting::getTahunAjaranAktif();
        $currentSemester = Setting::getSemesterAktif();

        // High-level statistics for information pills
        $stats = [
            'rekap_kehadiran_count' => RekapKehadiranSiswa::count(),
            'students_count' => User::where('role', 'siswa')->count(),
            'gurus_count' => User::where('role', 'guru')->count(),
            'kehadiran_guru_count' => KehadiranGuru::count(),
            'activity_logs_count' => ActivityLog::count(),
        ];

        return view('super-admin.custom-report.index', compact(
            'modules',
            'kelasList',
            'tingkatList',
            'guruList',
            'mapelList',
            'tahunAjaranList',
            'currentTahunAjaran',
            'currentSemester',
            'stats'
        ));
    }

    /**
     * Generate and download custom Excel file with Raw Data and Automated Pivot/Summary Sheets.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'string', 'in:rekap_kehadiran,master_siswa,kehadiran_guru,master_guru,activity_log'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string'],
            'include_pivot' => ['nullable', 'boolean'],
            'kelas_id' => ['nullable'],
            'guru_id' => ['nullable'],
            'mapel_id' => ['nullable'],
            'status_wali' => ['nullable', 'string'],
            'status_kehadiran' => ['nullable', 'string'],
            'tahun_ajaran' => ['nullable', 'string'],
            'semester' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'kategori' => ['nullable', 'string'],
            'role' => ['nullable', 'string'],
            'tingkat' => ['nullable', 'string'],
            'status_aktif' => ['nullable', 'string'],
        ]);

        $moduleKey = $validated['module'];
        $moduleDef = self::MODULES[$moduleKey];

        // Strict Whitelist Filtering: Prevent arbitrary injection of unwhitelisted column keys
        $allowedColumnKeys = array_keys($moduleDef['columns']);
        $selectedColumns = array_values(array_intersect($validated['columns'], $allowedColumnKeys));

        if (empty($selectedColumns)) {
            throw ValidationException::withMessages([
                'columns' => 'Minimal pilih satu kolom yang valid untuk diunduh.',
            ]);
        }

        $includePivot = $request->boolean('include_pivot', true);

        // Auto-fill end_date when only start_date is supplied (single-day / perhari mode)
        if (! empty($validated['start_date']) && empty($validated['end_date'])) {
            $validated['end_date'] = $validated['start_date'];
        }
        if (! empty($validated['end_date']) && empty($validated['start_date'])) {
            $validated['start_date'] = $validated['end_date'];
        }

        // Prepare temporary file path
        $timestamp = Carbon::now()->format('Ymd_His');
        $fileName = 'custom_report_'.$moduleKey.'_'.$timestamp.'.xlsx';
        $tempBase = tempnam(sys_get_temp_dir(), 'rep_builder_');
        if ($tempBase && file_exists($tempBase)) {
            @unlink($tempBase);
        }
        $tempPath = ($tempBase ?: sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('rep_builder_')).'.xlsx';

        $writer = SimpleExcelWriter::create($tempPath);

        // Header Style: Professional bold with pastel slate background
        $headerStyle = (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setBackgroundColor(Color::rgb(226, 232, 240));

        $writer->setHeaderStyle($headerStyle);
        $writer->nameCurrentSheet('Data Detail');

        $totalRows = 0;

        // Dispatch to module-specific exporter
        match ($moduleKey) {
            'rekap_kehadiran' => $this->exportRekapKehadiran($writer, $selectedColumns, $includePivot, $validated, $totalRows),
            'master_siswa' => $this->exportMasterSiswa($writer, $selectedColumns, $includePivot, $validated, $totalRows),
            'kehadiran_guru' => $this->exportKehadiranGuru($writer, $selectedColumns, $includePivot, $validated, $totalRows),
            'master_guru' => $this->exportMasterGuru($writer, $selectedColumns, $includePivot, $validated, $totalRows),
            'activity_log' => $this->exportActivityLog($writer, $selectedColumns, $includePivot, $validated, $totalRows),
        };

        $writer->close();

        // Audit Trail log
        ActivityLogger::log(
            'EXPORT',
            'CustomReport',
            "Super Admin mengekspor laporan kustom '{$moduleDef['label']}' dengan ".count($selectedColumns)." kolom dan {$totalRows} baris data."
        );

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Process and write Rekap Kehadiran module (Data Detail + Pivot Rekap Kelas).
     *
     * Dual-path routing:
     *  - If start_date & end_date are supplied: aggregate from KehadiranSiswa (daily/range mode).
     *  - Otherwise: use fast RekapKehadiranSiswa semester aggregation table.
     *
     * @param  array<int, string>  $selectedColumns
     * @param  array<string, mixed>  $filters
     */
    protected function exportRekapKehadiran(
        SimpleExcelWriter $writer,
        array $selectedColumns,
        bool $includePivot,
        array $filters,
        int &$totalRows
    ): void {
        $startDate = ! empty($filters['start_date']) ? $filters['start_date'] : null;
        $endDate = ! empty($filters['end_date']) ? $filters['end_date'] : null;
        $useDateMode = $startDate !== null && $endDate !== null;

        $colLabels = self::MODULES['rekap_kehadiran']['columns'];
        $pivotByClass = [];

        if ($useDateMode) {
            // ── DATE MODE: aggregate per-siswa from KehadiranSiswa ──────────────────
            // Build pertemuan subquery scoped by date range and optional class/tingkat
            $pertemuanQuery = Pertemuan::whereBetween('tanggal', [$startDate, $endDate])
                ->whereNull('deleted_at');

            if (! empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
                $pertemuanQuery->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $filters['kelas_id']));
            } elseif (! empty($filters['tingkat']) && $filters['tingkat'] !== 'all') {
                $pertemuanQuery->whereHas('jadwal.kelas', fn ($k) => $k->where('tingkat', $filters['tingkat']));
            }

            $pertemuanIds = $pertemuanQuery->pluck('id');

            // Aggregate attendance per siswa grouped at DB level
            $aggregateQuery = KehadiranSiswa::whereIn('pertemuan_id', $pertemuanIds)
                ->join('users', 'kehadiran_siswas.siswa_id', '=', 'users.id')
                ->join('siswa_profiles', 'siswa_profiles.user_id', '=', 'users.id')
                ->join('kelas', 'siswa_profiles.kelas_id', '=', 'kelas.id')
                ->whereNull('users.deleted_at')
                ->selectRaw("
                    kehadiran_siswas.siswa_id,
                    users.name AS nama_siswa,
                    siswa_profiles.nis,
                    kelas.id AS kelas_id,
                    kelas.nama AS nama_kelas,
                    kelas.tingkat,
                    COUNT(*) AS total_pertemuan,
                    SUM(CASE WHEN kehadiran_siswas.status IN ('hadir','terlambat') THEN 1 ELSE 0 END) AS hadir,
                    SUM(CASE WHEN kehadiran_siswas.status = 'hadir' THEN 1 ELSE 0 END) AS hadir_murni,
                    SUM(CASE WHEN kehadiran_siswas.status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
                    SUM(CASE WHEN kehadiran_siswas.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
                    SUM(CASE WHEN kehadiran_siswas.status = 'izin' THEN 1 ELSE 0 END) AS izin,
                    SUM(CASE WHEN kehadiran_siswas.status = 'alpa' THEN 1 ELSE 0 END) AS alpa,
                    SUM(CASE WHEN kehadiran_siswas.status = 'dispensasi' THEN 1 ELSE 0 END) AS dispensasi
                ")
                ->groupBy(
                    'kehadiran_siswas.siswa_id',
                    'users.name',
                    'siswa_profiles.nis',
                    'kelas.id',
                    'kelas.nama',
                    'kelas.tingkat'
                )
                ->orderBy('kelas.tingkat')
                ->orderBy('kelas.nama')
                ->orderBy('users.name');

            $aggregateQuery->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, &$pivotByClass, &$totalRows, $startDate, $endDate) {
                foreach ($records as $item) {
                    $totalRows++;
                    $kelasName = $item->nama_kelas ?? '-';
                    $tingkat = $item->tingkat ?? '-';

                    if (! isset($pivotByClass[$kelasName])) {
                        $pivotByClass[$kelasName] = [
                            'kelas' => $kelasName,
                            'tingkat' => $tingkat,
                            'total_siswa' => 0,
                            'hadir' => 0,
                            'hadir_murni' => 0,
                            'terlambat' => 0,
                            'sakit' => 0,
                            'izin' => 0,
                            'alpa' => 0,
                            'dispensasi' => 0,
                            'total_pertemuan' => 0,
                        ];
                    }

                    $pivotByClass[$kelasName]['total_siswa']++;
                    $pivotByClass[$kelasName]['hadir'] += (int) $item->hadir;
                    $pivotByClass[$kelasName]['hadir_murni'] += (int) $item->hadir_murni;
                    $pivotByClass[$kelasName]['terlambat'] += (int) $item->terlambat;
                    $pivotByClass[$kelasName]['sakit'] += (int) $item->sakit;
                    $pivotByClass[$kelasName]['izin'] += (int) $item->izin;
                    $pivotByClass[$kelasName]['alpa'] += (int) $item->alpa;
                    $pivotByClass[$kelasName]['dispensasi'] += (int) $item->dispensasi;
                    $pivotByClass[$kelasName]['total_pertemuan'] += (int) $item->total_pertemuan;

                    $row = [];
                    $persentase = (int) $item->total_pertemuan > 0
                        ? round(((int) $item->hadir / (int) $item->total_pertemuan) * 100, 1)
                        : 0;

                    $periodeLabel = $startDate === $endDate
                        ? Carbon::parse($startDate)->translatedFormat('d F Y')
                        : Carbon::parse($startDate)->translatedFormat('d F Y').' s/d '.Carbon::parse($endDate)->translatedFormat('d F Y');

                    foreach ($selectedColumns as $col) {
                        $headerTitle = $colLabels[$col]['label'];
                        $row[$headerTitle] = match ($col) {
                            'nama_siswa' => $item->nama_siswa ?? '-',
                            'nis' => $item->nis ?? '-',
                            'kelas' => $kelasName,
                            'tingkat' => $tingkat,
                            'tahun_ajaran' => $periodeLabel,
                            'semester' => 'Per Tanggal',
                            'total_pertemuan' => (int) $item->total_pertemuan,
                            'hadir' => (int) $item->hadir,
                            'hadir_murni' => (int) $item->hadir_murni,
                            'terlambat' => (int) $item->terlambat,
                            'sakit' => (int) $item->sakit,
                            'izin' => (int) $item->izin,
                            'alpa' => (int) $item->alpa,
                            'dispensasi' => (int) $item->dispensasi,
                            'persentase_kehadiran' => $persentase.'%',
                            default => '-',
                        };
                    }

                    $writer->addRow($row);
                }
            });
        } else {
            // ── SEMESTER MODE: fast aggregation from RekapKehadiranSiswa ─────────────
            $query = RekapKehadiranSiswa::with(['siswa.siswaProfile', 'kelas'])
                ->join('kelas', 'rekap_kehadiran_siswas.kelas_id', '=', 'kelas.id')
                ->join('users', 'rekap_kehadiran_siswas.siswa_id', '=', 'users.id')
                ->whereNull('kelas.deleted_at')
                ->whereNull('users.deleted_at')
                ->select('rekap_kehadiran_siswas.*')
                ->orderBy('kelas.tingkat')
                ->orderBy('kelas.nama')
                ->orderBy('users.name');

            if (! empty($filters['tingkat']) && $filters['tingkat'] !== 'all') {
                $query->where('kelas.tingkat', $filters['tingkat']);
            }

            if (! empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
                $query->where('rekap_kehadiran_siswas.kelas_id', $filters['kelas_id']);
            }

            if (! empty($filters['tahun_ajaran']) && $filters['tahun_ajaran'] !== 'all') {
                $query->where('rekap_kehadiran_siswas.tahun_ajaran', $filters['tahun_ajaran']);
            }

            if (! empty($filters['semester']) && $filters['semester'] !== 'all') {
                $sem = in_array(strtolower($filters['semester']), ['2', 'genap'], true) ? 'genap' : 'ganjil';
                $query->where('rekap_kehadiran_siswas.semester', $sem);
            }

            $query->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, &$pivotByClass, &$totalRows) {
                foreach ($records as $item) {
                    $totalRows++;
                    $kelasName = $item->kelas?->nama ?? '-';

                    if (! isset($pivotByClass[$kelasName])) {
                        $pivotByClass[$kelasName] = [
                            'kelas' => $kelasName,
                            'tingkat' => $item->kelas?->tingkat ?? '-',
                            'total_siswa' => 0,
                            'hadir' => 0,
                            'hadir_murni' => 0,
                            'terlambat' => 0,
                            'sakit' => 0,
                            'izin' => 0,
                            'alpa' => 0,
                            'dispensasi' => 0,
                            'total_pertemuan' => 0,
                        ];
                    }

                    $pivotByClass[$kelasName]['total_siswa']++;
                    $pivotByClass[$kelasName]['hadir'] += (int) $item->hadir;
                    $pivotByClass[$kelasName]['hadir_murni'] += (int) $item->hadir_murni;
                    $pivotByClass[$kelasName]['terlambat'] += (int) $item->terlambat;
                    $pivotByClass[$kelasName]['sakit'] += (int) $item->sakit;
                    $pivotByClass[$kelasName]['izin'] += (int) $item->izin;
                    $pivotByClass[$kelasName]['alpa'] += (int) $item->alpa;
                    $pivotByClass[$kelasName]['dispensasi'] += (int) $item->dispensasi;
                    $pivotByClass[$kelasName]['total_pertemuan'] += (int) $item->total;

                    $row = [];
                    $persentase = $item->total > 0
                        ? round(($item->hadir / $item->total) * 100, 1)
                        : 0;

                    foreach ($selectedColumns as $col) {
                        $headerTitle = $colLabels[$col]['label'];
                        $row[$headerTitle] = match ($col) {
                            'nama_siswa' => $item->siswa?->name ?? '-',
                            'nis' => $item->siswa?->siswaProfile?->nis ?? '-',
                            'kelas' => $kelasName,
                            'tingkat' => $item->kelas?->tingkat ?? '-',
                            'tahun_ajaran' => $item->tahun_ajaran,
                            'semester' => ucfirst($item->semester),
                            'total_pertemuan' => (int) $item->total,
                            'hadir' => (int) $item->hadir,
                            'hadir_murni' => (int) $item->hadir_murni,
                            'terlambat' => (int) $item->terlambat,
                            'sakit' => (int) $item->sakit,
                            'izin' => (int) $item->izin,
                            'alpa' => (int) $item->alpa,
                            'dispensasi' => (int) $item->dispensasi,
                            'persentase_kehadiran' => $persentase.'%',
                            default => '-',
                        };
                    }

                    $writer->addRow($row);
                }
            });
        }

        if ($totalRows === 0) {
            $emptyRow = [];
            foreach ($selectedColumns as $col) {
                $emptyRow[$colLabels[$col]['label']] = '(Tidak ada data kehadiran yang sesuai filter)';
            }
            $writer->addRow($emptyRow);
        }

        // Write Sheet 2: Automated Pivot Table
        if ($includePivot) {
            $pivotHeaderStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(219, 234, 254)); // Soft blue

            $writer->addNewSheetAndMakeItCurrent('Tabel Rekap - Pivot');
            $writer->setHeaderStyle($pivotHeaderStyle);

            if (empty($pivotByClass)) {
                $writer->addRow([
                    'No' => '-',
                    'Kelas' => 'Tidak ada data kehadiran yang sesuai filter',
                    'Tingkat' => '-',
                    'Jumlah Siswa' => 0,
                    'Total Hadir' => 0,
                    'Sakit (S)' => 0,
                    'Izin (I)' => 0,
                    'Alpa (A)' => 0,
                    'Dispensasi (D)' => 0,
                    'Total Akumulasi Sesi' => 0,
                    'Rata-rata Kehadiran (%)' => '0%',
                ]);
            }

            $no = 1;
            $grandSiswa = 0;
            $grandHadir = 0;
            $grandSakit = 0;
            $grandIzin = 0;
            $grandAlpa = 0;
            $grandDispen = 0;
            $grandTotal = 0;

            foreach ($pivotByClass as $p) {
                $grandSiswa += $p['total_siswa'];
                $grandHadir += $p['hadir'];
                $grandSakit += $p['sakit'];
                $grandIzin += $p['izin'];
                $grandAlpa += $p['alpa'];
                $grandDispen += $p['dispensasi'];
                $grandTotal += $p['total_pertemuan'];

                $pct = $p['total_pertemuan'] > 0
                    ? round(($p['hadir'] / $p['total_pertemuan']) * 100, 1)
                    : 0;

                $writer->addRow([
                    'No' => $no++,
                    'Kelas' => $p['kelas'],
                    'Tingkat' => $p['tingkat'],
                    'Jumlah Siswa' => $p['total_siswa'],
                    'Total Hadir' => $p['hadir'],
                    'Sakit (S)' => $p['sakit'],
                    'Izin (I)' => $p['izin'],
                    'Alpa (A)' => $p['alpa'],
                    'Dispensasi (D)' => $p['dispensasi'],
                    'Total Akumulasi Sesi' => $p['total_pertemuan'],
                    'Rata-rata Kehadiran (%)' => $pct.'%',
                ]);
            }

            // Summary Grand Total Row
            $grandPct = $grandTotal > 0 ? round(($grandHadir / $grandTotal) * 100, 1) : 0;
            $grandTotalStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(254, 243, 199)); // Golden amber

            $writer->addRow([
                'No' => 'TOTAL',
                'Kelas' => 'KESELURUHAN SEKOLAH',
                'Tingkat' => '-',
                'Jumlah Siswa' => $grandSiswa,
                'Total Hadir' => $grandHadir,
                'Sakit (S)' => $grandSakit,
                'Izin (I)' => $grandIzin,
                'Alpa (A)' => $grandAlpa,
                'Dispensasi (D)' => $grandDispen,
                'Total Akumulasi Sesi' => $grandTotal,
                'Rata-rata Kehadiran (%)' => $grandPct.'%',
            ], $grandTotalStyle);
        }
    }

    /**
     * Process and write Activity Log module (Data Detail + Pivot Aktivitas Sistem).
     *
     * @param  array<int, string>  $selectedColumns
     * @param  array<string, mixed>  $filters
     */
    protected function exportActivityLog(
        SimpleExcelWriter $writer,
        array $selectedColumns,
        bool $includePivot,
        array $filters,
        int &$totalRows
    ): void {
        $query = ActivityLog::latest('created_at');

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['kategori']) && isset(ActivityLogController::CATEGORIES[$filters['kategori']])) {
            $cat = ActivityLogController::CATEGORIES[$filters['kategori']];
            $query->where(function ($q) use ($cat) {
                if (! empty($cat['modules'])) {
                    $q->whereIn('module', $cat['modules']);
                }
                if (! empty($cat['actions'])) {
                    $q->orWhereIn('action', $cat['actions']);
                }
            });
        }

        $colLabels = self::MODULES['activity_log']['columns'];

        // Pivot aggregator per Aksi & per Modul
        $pivotByAction = [];
        $pivotByModule = [];

        $query->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, &$pivotByAction, &$pivotByModule, &$totalRows) {
            foreach ($records as $log) {
                $totalRows++;
                $act = is_string($log->action) ? strtoupper($log->action) : 'UNKNOWN';
                $mod = is_string($log->module) ? $log->module : 'Umum';

                $pivotByAction[$act] = ($pivotByAction[$act] ?? 0) + 1;
                $pivotByModule[$mod] = ($pivotByModule[$mod] ?? 0) + 1;

                $row = [];
                foreach ($selectedColumns as $col) {
                    $headerTitle = $colLabels[$col]['label'];
                    $row[$headerTitle] = match ($col) {
                        'waktu' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
                        'user_name' => $log->user_name ?? 'Sistem',
                        'role' => strtoupper($log->role ?? 'system'),
                        'action' => $act,
                        'module' => $mod,
                        'kategori' => $log->category_meta['label'] ?? 'Umum',
                        'description' => $log->description,
                        'ip_address' => $log->ip_address ?? '127.0.0.1',
                        default => '-',
                    };
                }

                $writer->addRow($row);
            }
        });

        if ($totalRows === 0) {
            $emptyRow = [];
            foreach ($selectedColumns as $col) {
                $emptyRow[$colLabels[$col]['label']] = '(Tidak ada data log yang sesuai filter)';
            }
            $writer->addRow($emptyRow);
        }

        // Write Sheet 2: Automated Pivot Table for Activity Logs
        if ($includePivot) {
            $pivotHeaderStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(219, 234, 254));

            $writer->addNewSheetAndMakeItCurrent('Tabel Rekap - Pivot');
            $writer->setHeaderStyle($pivotHeaderStyle);

            $writer->addRow([
                'Dimensi Rekap' => 'Aksi Sistem (Action Breakdown)',
                'Kunci Entitas' => '',
                'Frekuensi (Kali)' => '',
                'Persentase terhadap Total' => '',
            ]);

            arsort($pivotByAction);
            foreach ($pivotByAction as $actionName => $count) {
                $pct = $totalRows > 0 ? round(($count / $totalRows) * 100, 1) : 0;
                $writer->addRow([
                    'Dimensi Rekap' => 'Aksi Sistem',
                    'Kunci Entitas' => $actionName,
                    'Frekuensi (Kali)' => $count,
                    'Persentase terhadap Total' => $pct.'%',
                ]);
            }

            // Sub-Pivot: Breakdown per Modul Sistem
            $writer->addRow([
                'Dimensi Rekap' => '--- BREAKDOWN PER MODUL SISTEM ---',
                'Kunci Entitas' => '',
                'Frekuensi (Kali)' => '',
                'Persentase terhadap Total' => '',
            ]);

            arsort($pivotByModule);
            foreach ($pivotByModule as $modName => $count) {
                $pct = $totalRows > 0 ? round(($count / $totalRows) * 100, 1) : 0;
                $writer->addRow([
                    'Dimensi Rekap' => 'Modul Sistem',
                    'Kunci Entitas' => $modName,
                    'Frekuensi (Kali)' => $count,
                    'Persentase terhadap Total' => $pct.'%',
                ]);
            }

            // Total summary
            $grandStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(254, 243, 199));

            $writer->addRow([
                'Dimensi Rekap' => 'TOTAL KESELURUHAN LOG',
                'Kunci Entitas' => 'SELURUH AKSI',
                'Frekuensi (Kali)' => $totalRows,
                'Persentase terhadap Total' => '100%',
            ], $grandStyle);
        }
    }

    /**
     * Process and write Master Siswa module (Data Detail + Pivot Kelas/Tingkat).
     *
     * @param  array<int, string>  $selectedColumns
     * @param  array<string, mixed>  $filters
     */
    protected function exportMasterSiswa(
        SimpleExcelWriter $writer,
        array $selectedColumns,
        bool $includePivot,
        array $filters,
        int &$totalRows
    ): void {
        $query = User::where('role', 'siswa')
            ->with(['siswaProfile.kelas'])
            ->orderBy('name');

        if (! empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
            $query->whereHas('siswaProfile', function ($q) use ($filters) {
                $q->where('kelas_id', $filters['kelas_id']);
            });
        }

        if (! empty($filters['tingkat']) && $filters['tingkat'] !== 'all') {
            $query->whereHas('siswaProfile.kelas', function ($q) use ($filters) {
                $q->where('tingkat', $filters['tingkat']);
            });
        }

        if (isset($filters['status_aktif']) && $filters['status_aktif'] !== 'all') {
            $query->where('is_active', (bool) $filters['status_aktif']);
        }

        $colLabels = self::MODULES['master_siswa']['columns'];

        $pivotByClass = [];

        $query->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, &$pivotByClass, &$totalRows) {
            foreach ($records as $user) {
                $totalRows++;
                $profile = $user->siswaProfile;
                $kelasName = $profile?->kelas?->nama ?? 'Belum Ditentukan';
                $tingkat = $profile?->kelas?->tingkat ? (string) $profile->kelas->tingkat : '-';
                $rawBlok = strtolower($profile?->kelompok_blok ?? '');
                $blok = str_contains($rawBlok, 'b') ? 'B' : 'A';
                $isActive = $user->is_active;

                if (! isset($pivotByClass[$kelasName])) {
                    $pivotByClass[$kelasName] = [
                        'kelas' => $kelasName,
                        'tingkat' => $tingkat,
                        'total' => 0,
                        'blok_a' => 0,
                        'blok_b' => 0,
                        'aktif' => 0,
                        'nonaktif' => 0,
                    ];
                }

                $pivotByClass[$kelasName]['total']++;
                if ($blok === 'B') {
                    $pivotByClass[$kelasName]['blok_b']++;
                } else {
                    $pivotByClass[$kelasName]['blok_a']++;
                }

                if ($isActive) {
                    $pivotByClass[$kelasName]['aktif']++;
                } else {
                    $pivotByClass[$kelasName]['nonaktif']++;
                }

                $row = [];
                foreach ($selectedColumns as $col) {
                    $headerTitle = $colLabels[$col]['label'];
                    $row[$headerTitle] = match ($col) {
                        'nama' => $user->name,
                        'nis' => $profile?->nis ?? '-',
                        'kelas' => $kelasName,
                        'tingkat' => $tingkat,
                        'kelompok_blok' => 'Blok '.$blok,
                        'email' => $user->email,
                        'status_akun' => $user->is_active ? 'Aktif' : 'Nonaktif',
                        'tanggal_terdaftar' => $user->created_at ? $user->created_at->format('Y-m-d') : '-',
                        default => '-',
                    };
                }

                $writer->addRow($row);
            }
        });

        if ($totalRows === 0) {
            $emptyRow = [];
            foreach ($selectedColumns as $col) {
                $emptyRow[$colLabels[$col]['label']] = '(Tidak ada data siswa yang sesuai filter)';
            }
            $writer->addRow($emptyRow);
        }

        // Write Sheet 2: Automated Pivot Table for Students Master
        if ($includePivot) {
            $pivotHeaderStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(219, 234, 254));

            $writer->addNewSheetAndMakeItCurrent('Tabel Rekap - Pivot');
            $writer->setHeaderStyle($pivotHeaderStyle);

            if (empty($pivotByClass)) {
                $writer->addRow([
                    'No' => '-',
                    'Kelas' => 'Tidak ada data siswa yang sesuai filter',
                    'Tingkat' => '-',
                    'Total Siswa' => 0,
                    'Kelompok Blok A' => 0,
                    'Kelompok Blok B' => 0,
                    'Akun Aktif' => 0,
                    'Akun Nonaktif' => 0,
                ]);
            }

            $no = 1;
            $grandTotal = 0;
            $grandBlokA = 0;
            $grandBlokB = 0;
            $grandAktif = 0;
            $grandNonaktif = 0;

            foreach ($pivotByClass as $p) {
                $grandTotal += $p['total'];
                $grandBlokA += $p['blok_a'];
                $grandBlokB += $p['blok_b'];
                $grandAktif += $p['aktif'];
                $grandNonaktif += $p['nonaktif'];

                $writer->addRow([
                    'No' => $no++,
                    'Kelas' => $p['kelas'],
                    'Tingkat' => $p['tingkat'],
                    'Total Siswa' => $p['total'],
                    'Kelompok Blok A' => $p['blok_a'],
                    'Kelompok Blok B' => $p['blok_b'],
                    'Akun Aktif' => $p['aktif'],
                    'Akun Nonaktif' => $p['nonaktif'],
                ]);
            }

            // Grand Total
            $grandStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(254, 243, 199));

            $writer->addRow([
                'No' => 'TOTAL',
                'Kelas' => 'KESELURUHAN SISWA',
                'Tingkat' => '-',
                'Total Siswa' => $grandTotal,
                'Kelompok Blok A' => $grandBlokA,
                'Kelompok Blok B' => $grandBlokB,
                'Akun Aktif' => $grandAktif,
                'Akun Nonaktif' => $grandNonaktif,
            ], $grandStyle);
        }
    }

    /**
     * Process and write Kehadiran Guru module (Data Detail + Pivot Sesi Mengajar).
     *
     * @param  array<int, string>  $selectedColumns
     * @param  array<string, mixed>  $filters
     */
    protected function exportKehadiranGuru(
        SimpleExcelWriter $writer,
        array $selectedColumns,
        bool $includePivot,
        array $filters,
        int &$totalRows
    ): void {
        $query = KehadiranGuru::with([
            'guru.guruProfile',
            'pertemuan.jadwal.kelas',
            'pertemuan.jadwal.mataPelajaran',
        ])
            ->join('users', 'kehadiran_gurus.guru_id', '=', 'users.id')
            ->join('pertemuans', 'kehadiran_gurus.pertemuan_id', '=', 'pertemuans.id')
            ->whereNull('users.deleted_at')
            ->whereNull('pertemuans.deleted_at')
            ->select('kehadiran_gurus.*')
            ->orderBy('pertemuans.tanggal', 'desc')
            ->orderBy('users.name');

        if (! empty($filters['start_date'])) {
            $query->whereDate('pertemuans.tanggal', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('pertemuans.tanggal', '<=', $filters['end_date']);
        }

        if (! empty($filters['guru_id']) && $filters['guru_id'] !== 'all') {
            $query->where('kehadiran_gurus.guru_id', $filters['guru_id']);
        }

        if (! empty($filters['status_kehadiran']) && $filters['status_kehadiran'] !== 'all') {
            $query->where('kehadiran_gurus.status', $filters['status_kehadiran']);
        }

        if (! empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
            $query->whereHas('pertemuan.jadwal', function ($q) use ($filters) {
                $q->where('kelas_id', $filters['kelas_id']);
            });
        }

        if (! empty($filters['tingkat']) && $filters['tingkat'] !== 'all') {
            $query->whereHas('pertemuan.jadwal.kelas', function ($q) use ($filters) {
                $q->where('tingkat', $filters['tingkat']);
            });
        }

        $colLabels = self::MODULES['kehadiran_guru']['columns'];
        $pivotByGuru = [];

        $query->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, &$pivotByGuru, &$totalRows) {
            foreach ($records as $item) {
                $totalRows++;
                $guruName = $item->guru?->name ?? 'Guru Tidak Ditemukan';
                $nip = $item->guru?->guruProfile?->nip ?? '-';
                $status = strtolower($item->status ?? 'hadir');

                if (! isset($pivotByGuru[$guruName])) {
                    $pivotByGuru[$guruName] = [
                        'nama' => $guruName,
                        'nip' => $nip,
                        'total_sesi' => 0,
                        'hadir' => 0,
                        'terlambat' => 0,
                        'sakit' => 0,
                        'izin' => 0,
                        'alpa' => 0,
                        'dispensasi' => 0,
                    ];
                }

                $pivotByGuru[$guruName]['total_sesi']++;
                if (isset($pivotByGuru[$guruName][$status])) {
                    $pivotByGuru[$guruName][$status]++;
                } elseif ($status === 'tidak_hadir') {
                    $pivotByGuru[$guruName]['alpa']++;
                }

                $row = [];
                $tgl = $item->pertemuan?->tanggal ? Carbon::parse($item->pertemuan->tanggal)->format('Y-m-d') : '-';
                $mapel = $item->pertemuan?->jadwal?->mataPelajaran?->nama ?? '-';
                $kelas = $item->pertemuan?->jadwal?->kelas?->nama ?? '-';
                $tingkat = $item->pertemuan?->jadwal?->kelas?->tingkat ? (string) $item->pertemuan->jadwal->kelas->tingkat : '-';

                foreach ($selectedColumns as $col) {
                    $headerTitle = $colLabels[$col]['label'];
                    $row[$headerTitle] = match ($col) {
                        'tanggal' => $tgl,
                        'nama_guru' => $guruName,
                        'nip' => $nip,
                        'mata_pelajaran' => $mapel,
                        'kelas' => $kelas,
                        'tingkat' => $tingkat,
                        'status_kehadiran' => $item->status_label ?? ucfirst($status),
                        'alasan_keterangan' => $item->alasan_tidak_hadir ? ucfirst($item->alasan_tidak_hadir).($item->keterangan ? ' - '.$item->keterangan : '') : ($item->keterangan ?: '-'),
                        'guru_pengganti' => $item->guru_pengganti_nama ?? '-',
                        'waktu_hadir' => $item->waktu_hadir ? $item->waktu_hadir->format('H:i') : '-',
                        'materi_ajar' => $item->pertemuan?->materi_ajar ?? '-',
                        default => '-',
                    };
                }

                $writer->addRow($row);
            }
        });

        if ($totalRows === 0) {
            $emptyRow = [];
            foreach ($selectedColumns as $col) {
                $emptyRow[$colLabels[$col]['label']] = '(Tidak ada data presensi guru yang sesuai filter)';
            }
            $writer->addRow($emptyRow);
        }

        // Sheet 2: Automated Pivot Table for Guru Attendance
        if ($includePivot) {
            $pivotHeaderStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(254, 243, 199)); // Soft amber

            $writer->addNewSheetAndMakeItCurrent('Tabel Rekap - Pivot');
            $writer->setHeaderStyle($pivotHeaderStyle);

            if (empty($pivotByGuru)) {
                $writer->addRow([
                    'No' => '-',
                    'Nama Guru' => 'Tidak ada data presensi guru yang sesuai filter',
                    'NIP' => '-',
                    'Total Sesi Mengajar' => 0,
                    'Hadir' => 0,
                    'Terlambat' => 0,
                    'Sakit' => 0,
                    'Izin' => 0,
                    'Alpa' => 0,
                    'Dispensasi' => 0,
                    'Tingkat Kehadiran (%)' => '0%',
                ]);
            }

            $no = 1;
            $grandSesi = 0;
            $grandHadir = 0;
            $grandTerlambat = 0;
            $grandSakit = 0;
            $grandIzin = 0;
            $grandAlpa = 0;
            $grandDispen = 0;

            foreach ($pivotByGuru as $p) {
                $grandSesi += $p['total_sesi'];
                $grandHadir += $p['hadir'];
                $grandTerlambat += $p['terlambat'];
                $grandSakit += $p['sakit'];
                $grandIzin += $p['izin'];
                $grandAlpa += $p['alpa'];
                $grandDispen += $p['dispensasi'];

                $totalMasuk = $p['hadir'] + $p['terlambat'];
                $pct = $p['total_sesi'] > 0 ? round(($totalMasuk / $p['total_sesi']) * 100, 1) : 0;

                $writer->addRow([
                    'No' => $no++,
                    'Nama Guru' => $p['nama'],
                    'NIP' => $p['nip'],
                    'Total Sesi Mengajar' => $p['total_sesi'],
                    'Hadir' => $p['hadir'],
                    'Terlambat' => $p['terlambat'],
                    'Sakit' => $p['sakit'],
                    'Izin' => $p['izin'],
                    'Alpa' => $p['alpa'],
                    'Dispensasi' => $p['dispensasi'],
                    'Tingkat Kehadiran (%)' => $pct.'%',
                ]);
            }

            $grandMasuk = $grandHadir + $grandTerlambat;
            $grandPct = $grandSesi > 0 ? round(($grandMasuk / $grandSesi) * 100, 1) : 0;
            $grandStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(219, 234, 254));

            $writer->addRow([
                'No' => 'TOTAL',
                'Nama Guru' => 'KESELURUHAN DEWAN GURU',
                'NIP' => '-',
                'Total Sesi Mengajar' => $grandSesi,
                'Hadir' => $grandHadir,
                'Terlambat' => $grandTerlambat,
                'Sakit' => $grandSakit,
                'Izin' => $grandIzin,
                'Alpa' => $grandAlpa,
                'Dispensasi' => $grandDispen,
                'Tingkat Kehadiran (%)' => $grandPct.'%',
            ], $grandStyle);
        }
    }

    /**
     * Process and write Master Guru module (Data Detail + Pivot Mapel & Penugasan).
     *
     * @param  array<int, string>  $selectedColumns
     * @param  array<string, mixed>  $filters
     */
    protected function exportMasterGuru(
        SimpleExcelWriter $writer,
        array $selectedColumns,
        bool $includePivot,
        array $filters,
        int &$totalRows
    ): void {
        $waliMap = Kelas::whereNotNull('wali_kelas_id')
            ->pluck('nama', 'wali_kelas_id')
            ->all();

        $query = User::where('role', 'guru')
            ->with(['guruProfile', 'mapels'])
            ->orderBy('name');

        if (! empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $query->whereHas('mapels', function ($q) use ($filters) {
                $q->where('mata_pelajarans.id', $filters['mapel_id']);
            });
        }

        if (! empty($filters['status_wali']) && $filters['status_wali'] !== 'all') {
            $waliIds = array_keys($waliMap);
            if ($filters['status_wali'] === 'ya') {
                $query->whereIn('id', $waliIds);
            } elseif ($filters['status_wali'] === 'tidak') {
                $query->whereNotIn('id', $waliIds);
            }
        }

        if (isset($filters['status_aktif']) && $filters['status_aktif'] !== 'all') {
            $query->where('is_active', (bool) $filters['status_aktif']);
        }

        $colLabels = self::MODULES['master_guru']['columns'];

        $pivotByMapel = [];
        $totalWali = 0;
        $totalKaprog = 0;
        $totalAktif = 0;
        $totalNonaktif = 0;

        $query->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, $waliMap, &$pivotByMapel, &$totalWali, &$totalKaprog, &$totalAktif, &$totalNonaktif, &$totalRows) {
            foreach ($records as $user) {
                $totalRows++;
                $profile = $user->guruProfile;
                $nip = $profile?->nip ?? '-';
                $kaprog = $profile?->kaprog_jurusan ?: '-';
                $waliKelas = $waliMap[$user->id] ?? '-';
                $isWali = isset($waliMap[$user->id]);
                $isActive = $user->is_active;

                if ($isWali) {
                    $totalWali++;
                }
                if ($kaprog !== '-') {
                    $totalKaprog++;
                }
                if ($isActive) {
                    $totalAktif++;
                } else {
                    $totalNonaktif++;
                }

                $mapelNames = $user->mapels->pluck('nama')->all();
                $mapelStr = ! empty($mapelNames) ? implode(', ', $mapelNames) : '-';

                foreach ($mapelNames as $mName) {
                    $pivotByMapel[$mName] = ($pivotByMapel[$mName] ?? 0) + 1;
                }
                if (empty($mapelNames)) {
                    $pivotByMapel['Belum Ditugaskan'] = ($pivotByMapel['Belum Ditugaskan'] ?? 0) + 1;
                }

                $row = [];
                foreach ($selectedColumns as $col) {
                    $headerTitle = $colLabels[$col]['label'];
                    $row[$headerTitle] = match ($col) {
                        'nama' => $user->name,
                        'nip' => $nip,
                        'email' => $user->email,
                        'mapel' => $mapelStr,
                        'wali_kelas' => $waliKelas !== '-' ? 'Wali Kelas '.$waliKelas : 'Bukan Wali Kelas',
                        'kaprog_jurusan' => $kaprog,
                        'status_akun' => $user->is_active ? 'Aktif' : 'Nonaktif',
                        'tanggal_terdaftar' => $user->created_at ? $user->created_at->format('Y-m-d') : '-',
                        default => '-',
                    };
                }

                $writer->addRow($row);
            }
        });

        if ($totalRows === 0) {
            $emptyRow = [];
            foreach ($selectedColumns as $col) {
                $emptyRow[$colLabels[$col]['label']] = '(Tidak ada data guru yang sesuai filter)';
            }
            $writer->addRow($emptyRow);
        }

        // Sheet 2: Automated Pivot Table for Teachers
        if ($includePivot) {
            $pivotHeaderStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(219, 234, 254));

            $writer->addNewSheetAndMakeItCurrent('Tabel Rekap - Pivot');
            $writer->setHeaderStyle($pivotHeaderStyle);

            $writer->addRow([
                'Dimensi Distribusi Guru' => 'DISTRIBUSI GURU PER MATA PELAJARAN',
                'Kategori / Penugasan' => '',
                'Jumlah Guru' => '',
                'Persentase terhadap Total' => '',
            ]);

            arsort($pivotByMapel);
            foreach ($pivotByMapel as $mName => $count) {
                $pct = $totalRows > 0 ? round(($count / $totalRows) * 100, 1) : 0;
                $writer->addRow([
                    'Dimensi Distribusi Guru' => 'Mata Pelajaran',
                    'Kategori / Penugasan' => $mName,
                    'Jumlah Guru' => $count,
                    'Persentase terhadap Total' => $pct.'%',
                ]);
            }

            $writer->addRow([
                'Dimensi Distribusi Guru' => '--- REKAP PENUGASAN KHUSUS & STATUS AKUN ---',
                'Kategori / Penugasan' => '',
                'Jumlah Guru' => '',
                'Persentase terhadap Total' => '',
            ]);

            $pctWali = $totalRows > 0 ? round(($totalWali / $totalRows) * 100, 1) : 0;
            $writer->addRow([
                'Dimensi Distribusi Guru' => 'Penugasan Khusus',
                'Kategori / Penugasan' => 'Guru Wali Kelas',
                'Jumlah Guru' => $totalWali,
                'Persentase terhadap Total' => $pctWali.'%',
            ]);

            $pctKaprog = $totalRows > 0 ? round(($totalKaprog / $totalRows) * 100, 1) : 0;
            $writer->addRow([
                'Dimensi Distribusi Guru' => 'Penugasan Khusus',
                'Kategori / Penugasan' => 'Kepala Program Keahlian (Kaprog)',
                'Jumlah Guru' => $totalKaprog,
                'Persentase terhadap Total' => $pctKaprog.'%',
            ]);

            $pctAktif = $totalRows > 0 ? round(($totalAktif / $totalRows) * 100, 1) : 0;
            $writer->addRow([
                'Dimensi Distribusi Guru' => 'Status Akun',
                'Kategori / Penugasan' => 'Akun Aktif',
                'Jumlah Guru' => $totalAktif,
                'Persentase terhadap Total' => $pctAktif.'%',
            ]);

            $pctNonaktif = $totalRows > 0 ? round(($totalNonaktif / $totalRows) * 100, 1) : 0;
            $writer->addRow([
                'Dimensi Distribusi Guru' => 'Status Akun',
                'Kategori / Penugasan' => 'Akun Nonaktif',
                'Jumlah Guru' => $totalNonaktif,
                'Persentase terhadap Total' => $pctNonaktif.'%',
            ]);

            $grandStyle = (new Style)
                ->setFontBold()
                ->setFontSize(11)
                ->setBackgroundColor(Color::rgb(254, 243, 199));

            $writer->addRow([
                'Dimensi Distribusi Guru' => 'TOTAL KESELURUHAN DEWAN GURU',
                'Kategori / Penugasan' => 'SELURUH GURU',
                'Jumlah Guru' => $totalRows,
                'Persentase terhadap Total' => '100%',
            ], $grandStyle);
        }
    }
}

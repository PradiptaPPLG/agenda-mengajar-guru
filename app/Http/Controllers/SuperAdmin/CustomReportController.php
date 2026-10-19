<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Kelas;
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
     * Definition of available modules, descriptions, and allowed whitelisted columns.
     *
     * @var array<string, array{
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
        'activity_log' => [
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
        'master_siswa' => [
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
            'activity_logs_count' => ActivityLog::count(),
            'students_count' => User::where('role', 'siswa')->count(),
        ];

        return view('super-admin.custom-report.index', compact(
            'modules',
            'kelasList',
            'tingkatList',
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
            'module' => ['required', 'string', 'in:rekap_kehadiran,activity_log,master_siswa'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string'],
            'include_pivot' => ['nullable', 'boolean'],
            'kelas_id' => ['nullable'],
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
            'activity_log' => $this->exportActivityLog($writer, $selectedColumns, $includePivot, $validated, $totalRows),
            'master_siswa' => $this->exportMasterSiswa($writer, $selectedColumns, $includePivot, $validated, $totalRows),
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

        // Pivot aggregator per kelas
        $pivotByClass = [];

        $colLabels = self::MODULES['rekap_kehadiran']['columns'];

        $query->chunk(500, function ($records) use ($writer, $selectedColumns, $colLabels, &$pivotByClass, &$totalRows) {
            foreach ($records as $item) {
                $totalRows++;
                $kelasName = $item->kelas?->nama ?? '-';

                // Aggregate for Sheet 2 Pivot
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

                // Format row values for Sheet 1 Raw Data
                $row = [];
                $persentase = $item->total > 0 ? round(($item->hadir / $item->total) * 100, 1) : 0;

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

            // Summary Grand Total Row with golden amber highlight
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
}

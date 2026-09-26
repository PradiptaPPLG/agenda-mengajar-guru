<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JadwalImport;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JadwalController extends Controller
{
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());

        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->when($selectedTahunAjaran && $selectedTahunAjaran !== 'all', fn ($q) => $q->where('tahun_ajaran', $selectedTahunAjaran))
            ->when($selectedSemester && $selectedSemester !== 'all', fn ($q) => $q->where('semester', $selectedSemester))
            ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $hariNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

        $tempPath = tempnam(sys_get_temp_dir(), 'jadwal_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        foreach ($jadwals as $j) {
            $writer->addRow([
                'Tahun Ajaran' => $j->tahun_ajaran ?? Setting::getTahunAjaranAktif(),
                'Semester' => ucfirst($j->semester ?? Setting::getSemesterAktif()),
                'Hari' => $hariNames[$j->hari] ?? 'Hari '.$j->hari,
                'Jam Mulai' => substr($j->jam_mulai, 0, 5),
                'Jam Selesai' => substr($j->jam_selesai, 0, 5),
                'Kelas' => $j->kelas->nama ?? '-',
                'Mata Pelajaran' => $j->mataPelajaran->nama ?? '-',
                'Kode Mapel' => $j->mataPelajaran->kode ?? '-',
                'Guru Pengampu' => $j->guru->name ?? '-',
                'Kelompok Blok' => $j->kelompok_blok ?? 'reguler',
            ]);
        }

        $writer->close();

        return response()->download($tempPath, 'jadwal-pelajaran-'.now()->format('Y-m-d').'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'jadwal_template_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        // Header dan contoh data yang sesuai dengan JadwalImport
        $writer->addRow([
            'kelas' => 'X RPL 1 (Sesuai nama kelas di sistem)',
            'guru' => 'Nama Guru (Persis sesuai di sistem)',
            'mata_pelajaran' => 'Nama Mapel',
            'hari' => '1 (1=Senin, 2=Selasa, ... 6=Sabtu)',
            'jam_mulai' => '07:00',
            'jam_selesai' => '09:00',
        ]);

        $writer->close();

        return response()->download($tempPath, 'template-import-jadwal.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request): Response
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());

        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->when($selectedTahunAjaran && $selectedTahunAjaran !== 'all', fn ($q) => $q->where('tahun_ajaran', $selectedTahunAjaran))
            ->when($selectedSemester && $selectedSemester !== 'all', fn ($q) => $q->where('semester', $selectedSemester))
            ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $hariNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $schoolName = Setting::get('school_name', 'SMK Negeri 1 Ciamis');
        $schoolYear = $selectedTahunAjaran.' ('.Setting::getSemesterLabel($selectedSemester).')';

        $pdf = Pdf::loadView('admin.jadwal.pdf', compact('jadwals', 'hariNames', 'schoolName', 'schoolYear'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('jadwal-pelajaran-'.now()->format('Y-m-d').'.pdf');
    }

    public function index(Request $request): View
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());

        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->when($selectedTahunAjaran && $selectedTahunAjaran !== 'all', fn ($q) => $q->where('tahun_ajaran', $selectedTahunAjaran))
            ->when($selectedSemester && $selectedSemester !== 'all', fn ($q) => $q->where('semester', $selectedSemester))
            ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(30)
            ->withQueryString();

        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('admin.jadwal.index', compact(
            'jadwals',
            'kelasList',
            'guruList',
            'selectedTahunAjaran',
            'selectedSemester',
            'daftarTahunAjaran',
            'daftarSemester'
        ));
    }

    public function create(): View
    {
        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();
        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();
        $activeTahunAjaran = Setting::getTahunAjaranAktif();
        $activeSemester = Setting::getSemesterAktif();

        return view('admin.jadwal.create', compact(
            'kelasList',
            'guruList',
            'mataPelajarans',
            'daftarTahunAjaran',
            'daftarSemester',
            'activeTahunAjaran',
            'activeSemester'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'guru_id' => ['required', 'exists:users,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'hari' => ['required', 'integer', 'between:1,6'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kelompok_blok' => ['nullable', 'in:reguler,kelompok_a,kelompok_b'],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'in:ganjil,genap'],
        ]);

        $validated['tahun_ajaran'] = ! empty($validated['tahun_ajaran']) ? $validated['tahun_ajaran'] : Setting::getTahunAjaranAktif();
        $validated['semester'] = ! empty($validated['semester']) ? $validated['semester'] : Setting::getSemesterAktif();

        // Jika tidak diisi, ambil default dari mata pelajaran
        if (empty($validated['kelompok_blok'])) {
            $validated['kelompok_blok'] = MataPelajaran::find($validated['mata_pelajaran_id'])?->kelompok_blok ?? 'reguler';
        }

        $this->validateNoConflict($validated);

        DB::transaction(function () use ($validated) {
            JadwalPelajaran::create($validated);
        });

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(JadwalPelajaran $jadwal): View
    {
        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();
        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('admin.jadwal.edit', compact(
            'jadwal',
            'kelasList',
            'guruList',
            'mataPelajarans',
            'daftarTahunAjaran',
            'daftarSemester'
        ));
    }

    public function update(Request $request, JadwalPelajaran $jadwal): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'guru_id' => ['required', 'exists:users,id'],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'hari' => ['required', 'integer', 'between:1,6'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kelompok_blok' => ['nullable', 'in:reguler,kelompok_a,kelompok_b'],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'in:ganjil,genap'],
        ]);

        $validated['tahun_ajaran'] = ! empty($validated['tahun_ajaran']) ? $validated['tahun_ajaran'] : ($jadwal->tahun_ajaran ?: Setting::getTahunAjaranAktif());
        $validated['semester'] = ! empty($validated['semester']) ? $validated['semester'] : ($jadwal->semester ?: Setting::getSemesterAktif());

        // Jika tidak diisi, ambil default dari mata pelajaran
        if (empty($validated['kelompok_blok'])) {
            $validated['kelompok_blok'] = MataPelajaran::find($validated['mata_pelajaran_id'])?->kelompok_blok ?? 'reguler';
        }

        $this->validateNoConflict($validated, $jadwal->id);

        DB::transaction(function () use ($jadwal, $validated) {
            $jadwal->update($validated);
        });

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(JadwalPelajaran $jadwal): RedirectResponse
    {
        $jadwal->delete();

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', 'in:ganjil,genap'],
        ]);

        $tahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $semester = $request->input('semester', Setting::getSemesterAktif());

        set_time_limit(300);
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        try {
            // Hapus jadwal untuk semester & tahun ajaran target jika diminta
            if ($request->boolean('clear_before_import')) {
                DB::table('jadwal_pelajarans')
                    ->where('tahun_ajaran', $tahunAjaran)
                    ->where('semester', $semester)
                    ->delete();
            }

            Excel::import(new JadwalImport($tahunAjaran, $semester), $request->file('file'));

            $semLabel = Setting::getSemesterLabel($semester);
            $message = $request->boolean('clear_before_import')
                ? "Jadwal lama {$semLabel} {$tahunAjaran} berhasil dihapus dan jadwal baru berhasil diimport."
                : "Jadwal untuk {$semLabel} {$tahunAjaran} berhasil diimport.";

            return redirect()->route('admin.jadwal.index', [
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
            ])->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }

    /**
     * Salin seluruh jadwal dari satu semester/tahun ajaran ke semester/tahun ajaran lain.
     */
    public function salinSemester(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sumber_tahun_ajaran' => ['required', 'string'],
            'sumber_semester' => ['required', 'in:ganjil,genap'],
            'tujuan_tahun_ajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'tujuan_semester' => ['required', 'in:ganjil,genap'],
            'hapus_tujuan_dulu' => ['nullable', 'boolean'],
        ]);

        if (
            $validated['sumber_tahun_ajaran'] === $validated['tujuan_tahun_ajaran'] &&
            $validated['sumber_semester'] === $validated['tujuan_semester']
        ) {
            return back()->with('error', 'Tahun ajaran dan semester tujuan tidak boleh sama persis dengan sumber.');
        }

        $sumberJadwals = JadwalPelajaran::where('tahun_ajaran', $validated['sumber_tahun_ajaran'])
            ->where('semester', $validated['sumber_semester'])
            ->get();

        if ($sumberJadwals->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan jadwal pada Semester '.ucfirst($validated['sumber_semester'])." {$validated['sumber_tahun_ajaran']}.");
        }

        DB::transaction(function () use ($validated, $sumberJadwals) {
            if (! empty($validated['hapus_tujuan_dulu'])) {
                JadwalPelajaran::where('tahun_ajaran', $validated['tujuan_tahun_ajaran'])
                    ->where('semester', $validated['tujuan_semester'])
                    ->delete();
            }

            foreach ($sumberJadwals as $sj) {
                JadwalPelajaran::updateOrCreate(
                    [
                        'kelas_id' => $sj->kelas_id,
                        'hari' => $sj->hari,
                        'jam_mulai' => $sj->jam_mulai,
                        'jam_selesai' => $sj->jam_selesai,
                        'kelompok_blok' => $sj->kelompok_blok,
                        'tahun_ajaran' => $validated['tujuan_tahun_ajaran'],
                        'semester' => $validated['tujuan_semester'],
                    ],
                    [
                        'guru_id' => $sj->guru_id,
                        'mata_pelajaran_id' => $sj->mata_pelajaran_id,
                    ]
                );
            }
        });

        $tujuanLabel = Setting::getSemesterLabel($validated['tujuan_semester']).' '.$validated['tujuan_tahun_ajaran'];

        return redirect()->route('admin.jadwal.index', [
            'tahun_ajaran' => $validated['tujuan_tahun_ajaran'],
            'semester' => $validated['tujuan_semester'],
        ])->with('success', "Berhasil menyalin {$sumberJadwals->count()} jadwal ke {$tujuanLabel}.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all')) {
            $query = JadwalPelajaran::query()
                ->when($request->input('tahun_ajaran') && $request->input('tahun_ajaran') !== 'all', fn ($q) => $q->where('tahun_ajaran', $request->input('tahun_ajaran')))
                ->when($request->input('semester') && $request->input('semester') !== 'all', fn ($q) => $q->where('semester', $request->input('semester')))
                ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
                ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id));

            $count = $query->count();
            DB::transaction(function () use ($query) {
                $query->delete();
            });

            return redirect()->route('admin.jadwal.index')
                ->with('success', $count.' jadwal berhasil dihapus.');
        }

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:jadwal_pelajarans,id'],
        ]);

        DB::transaction(function () use ($request) {
            JadwalPelajaran::whereIn('id', $request->ids)->delete();
        });

        return redirect()->route('admin.jadwal.index')
            ->with('success', count($request->ids).' jadwal berhasil dihapus.');
    }

    /**
     * Validate that neither the teacher nor the class has an overlapping schedule
     * within the SAME tahun ajaran and semester.
     *
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    protected function validateNoConflict(array $validated, ?int $excludeJadwalId = null): void
    {
        $kelompokBaru = $validated['kelompok_blok'] ?? 'reguler';
        $kelas = Kelas::find($validated['kelas_id']);
        $isModelSplitHarian = $kelas?->model_rotasi === 'split_harian';

        $tahunAjaran = $validated['tahun_ajaran'] ?? Setting::getTahunAjaranAktif();
        $semester = $validated['semester'] ?? Setting::getSemesterAktif();

        // ─── 1. Cek Bentrok GURU (dalam semester & tahun ajaran yang sama) ─────────
        $guruConflict = JadwalPelajaran::with('kelas')
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->where('hari', $validated['hari'])
            ->where('guru_id', $validated['guru_id'])
            ->when($excludeJadwalId, fn ($q) => $q->where('id', '!=', $excludeJadwalId))
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai'])
            ->first();

        if ($guruConflict) {
            $namaKelas = $guruConflict->kelas?->nama ?? 'lain';
            $jam = substr($guruConflict->jam_mulai, 0, 5).' - '.substr($guruConflict->jam_selesai, 0, 5);

            throw ValidationException::withMessages([
                'guru_id' => "Guru ini sudah memiliki jadwal mengajar di kelas {$namaKelas} pada jam {$jam} ({$tahunAjaran} - Semester {$semester}).",
            ]);
        }

        // ─── 2. Cek Bentrok KELAS (dalam semester & tahun ajaran yang sama) ────────
        if ($isModelSplitHarian) {
            return;
        }

        $kelasConflictQuery = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->where('hari', $validated['hari'])
            ->where('kelas_id', $validated['kelas_id'])
            ->when($excludeJadwalId, fn ($q) => $q->where('id', '!=', $excludeJadwalId))
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai']);

        if ($kelompokBaru === 'kelompok_a') {
            $kelasConflictQuery->whereIn('kelompok_blok', ['reguler', 'kelompok_a']);
        } elseif ($kelompokBaru === 'kelompok_b') {
            $kelasConflictQuery->whereIn('kelompok_blok', ['reguler', 'kelompok_b']);
        }

        $kelasConflict = $kelasConflictQuery->first();

        if ($kelasConflict) {
            $namaMapel = $kelasConflict->mataPelajaran?->nama ?? 'lain';
            $namaGuru = $kelasConflict->guru?->name ?? 'lain';
            $jam = substr($kelasConflict->jam_mulai, 0, 5).' - '.substr($kelasConflict->jam_selesai, 0, 5);
            $kelompokLabel = match ($kelasConflict->kelompok_blok) {
                'kelompok_a' => ' [Kelompok A]',
                'kelompok_b' => ' [Kelompok B]',
                default => '',
            };

            throw ValidationException::withMessages([
                'kelas_id' => "Kelas ini sudah memiliki jadwal{$kelompokLabel} {$namaMapel} (Guru: {$namaGuru}) pada jam {$jam} ({$tahunAjaran} - Semester {$semester}).",
            ]);
        }
    }
}

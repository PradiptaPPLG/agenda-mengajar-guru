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
        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
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
                'Hari' => $hariNames[$j->hari] ?? 'Hari '.$j->hari,
                'Jam Mulai' => substr($j->jam_mulai, 0, 5),
                'Jam Selesai' => substr($j->jam_selesai, 0, 5),
                'Kelas' => $j->kelas->nama ?? '-',
                'Mata Pelajaran' => $j->mataPelajaran->nama ?? '-',
                'Kode Mapel' => $j->mataPelajaran->kode ?? '-',
                'Guru Pengampu' => $j->guru->name ?? '-',
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
        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $hariNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $schoolName = Setting::get('school_name', 'SMK Negeri 1 Ciamis');
        $schoolYear = Setting::get('school_year', '2025/2026');

        $pdf = Pdf::loadView('admin.jadwal.pdf', compact('jadwals', 'hariNames', 'schoolName', 'schoolYear'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('jadwal-pelajaran-'.now()->format('Y-m-d').'.pdf');
    }

    public function index(Request $request): View
    {
        $jadwals = JadwalPelajaran::with(['kelas', 'guru', 'mataPelajaran'])
            ->when($request->input('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->input('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(30)
            ->withQueryString();

        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();

        return view('admin.jadwal.index', compact('jadwals', 'kelasList', 'guruList'));
    }

    public function create(): View
    {
        $kelasList = Kelas::orderBy('nama')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mataPelajarans = MataPelajaran::orderBy('nama')->get();

        return view('admin.jadwal.create', compact('kelasList', 'guruList', 'mataPelajarans'));
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
        ]);

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

        return view('admin.jadwal.edit', compact('jadwal', 'kelasList', 'guruList', 'mataPelajarans'));
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
        ]);

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
        ]);

        try {
            Excel::import(new JadwalImport, $request->file('file'));

            return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: '.$e->getMessage());
        }
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all')) {
            $query = JadwalPelajaran::query()
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
     * Validate that neither the teacher nor the class has an overlapping schedule.
     *
     * Aturan Bentrok Sistem Blok:
     * - Jadwal kelompok_a vs kelompok_b pada KELAS yang sama → TIDAK bentrok
     *   (karena tidak pernah aktif di minggu yang sama).
     * - Jadwal split_harian: kelompok_a vs kelompok_b boleh jam sama karena
     *   menggunakan ruangan berbeda (validasi kelas dilewati).
     * - Bentrok GURU tetap berlaku lintas semua kelompok (guru tidak bisa ada di 2 tempat).
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

        // ─── 1. Cek Bentrok GURU (berlaku untuk semua kelompok) ──────────────────
        $guruConflict = JadwalPelajaran::with('kelas')
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
                'guru_id' => "Guru ini sudah memiliki jadwal mengajar di kelas {$namaKelas} pada jam {$jam}.",
            ]);
        }

        // ─── 2. Cek Bentrok KELAS ────────────────────────────────────────────────
        // Lewati pengecekan bentrok kelas jika model split_harian:
        // kelompok A dan B boleh overlap jam karena di ruangan berbeda.
        if ($isModelSplitHarian) {
            return;
        }

        $kelasConflictQuery = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('hari', $validated['hari'])
            ->where('kelas_id', $validated['kelas_id'])
            ->when($excludeJadwalId, fn ($q) => $q->where('id', '!=', $excludeJadwalId))
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai']);

        // Jika jadwal baru adalah Kelompok A atau B, hanya bentrok dengan kelompok yang sama
        // atau dengan jadwal reguler (yang selalu aktif). Tidak bentrok dengan kelompok lainnya.
        if ($kelompokBaru === 'kelompok_a') {
            // Bentrok dengan: reguler dan kelompok_a (tidak dengan kelompok_b)
            $kelasConflictQuery->whereIn('kelompok_blok', ['reguler', 'kelompok_a']);
        } elseif ($kelompokBaru === 'kelompok_b') {
            // Bentrok dengan: reguler dan kelompok_b (tidak dengan kelompok_a)
            $kelasConflictQuery->whereIn('kelompok_blok', ['reguler', 'kelompok_b']);
        }
        // Jika reguler: bentrok dengan semua (kelompok_a, kelompok_b, reguler)

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
                'kelas_id' => "Kelas ini sudah memiliki jadwal{$kelompokLabel} {$namaMapel} (Guru: {$namaGuru}) pada jam {$jam}.",
            ]);
        }
    }
}

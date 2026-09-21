<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        ]);

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
        ]);

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

    /**
     * Validate that neither the teacher nor the class has an overlapping schedule on the given day.
     *
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    protected function validateNoConflict(array $validated, ?int $excludeJadwalId = null): void
    {
        // 1. Check Guru Conflict
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

        // 2. Check Kelas Conflict
        $kelasConflict = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('hari', $validated['hari'])
            ->where('kelas_id', $validated['kelas_id'])
            ->when($excludeJadwalId, fn ($q) => $q->where('id', '!=', $excludeJadwalId))
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai'])
            ->first();

        if ($kelasConflict) {
            $namaMapel = $kelasConflict->mataPelajaran?->nama ?? 'lain';
            $namaGuru = $kelasConflict->guru?->name ?? 'lain';
            $jam = substr($kelasConflict->jam_mulai, 0, 5).' - '.substr($kelasConflict->jam_selesai, 0, 5);

            throw ValidationException::withMessages([
                'kelas_id' => "Kelas ini sudah memiliki jadwal pelajaran {$namaMapel} (Guru: {$namaGuru}) pada jam {$jam}.",
            ]);
        }
    }
}

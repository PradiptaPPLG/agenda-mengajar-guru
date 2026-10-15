<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlokMinggu;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $query = Kelas::with(['waliKelas', 'guruBk'])->withCount('siswaProfiles');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('nama', 'like', "%{$search}%");
        }

        if ($request->filled('tingkat')) {
            $tingkat = $request->tingkat;
            $query->where(function ($q) use ($tingkat) {
                $q->where('tingkat', $tingkat);
                if ($tingkat === '10') {
                    $q->orWhere('tingkat', 'X');
                } elseif ($tingkat === '11') {
                    $q->orWhere('tingkat', 'XI');
                } elseif ($tingkat === '12') {
                    $q->orWhere('tingkat', 'XII');
                }
            });
        }

        if ($request->filled('jurusan')) {
            $jurusan = strtoupper(trim($request->jurusan));
            $query->where(function ($q) use ($jurusan) {
                match ($jurusan) {
                    'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                    'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                    'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                    'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                    'DKV' => $q->where('nama', 'like', '%DKV%'),
                    'KLN' => $q->where('nama', 'like', '%KLN%'),
                    'HTL' => $q->where('nama', 'like', '%HTL%'),
                    'PBS' => $q->where('nama', 'like', '%PBS%'),
                    default => $q->where('nama', 'like', "%{$jurusan}%"),
                };
            });
        }

        $kelas = $query->orderBy('nama')->paginate(15)->withQueryString();
        $hasKalender = KalenderBlokMinggu::exists();

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

        return view('admin.kelas.index', compact('kelas', 'hasKalender', 'daftarJurusan'));
    }

    public function show(Request $request, Kelas $kelas): View
    {
        $selectedTahunAjaran = $request->input('tahun_ajaran', Setting::getTahunAjaranAktif());
        $selectedSemester = $request->input('semester', Setting::getSemesterAktif());

        $kelas->load([
            'waliKelas.guruProfile',
            'guruBk.guruProfile',
            'siswaProfiles.user',
        ]);

        $jadwalPelajarans = $kelas->jadwalPelajarans()
            ->when($selectedTahunAjaran && $selectedTahunAjaran !== 'all', fn ($q) => $q->where('tahun_ajaran', $selectedTahunAjaran))
            ->when($selectedSemester && $selectedSemester !== 'all', fn ($q) => $q->where('semester', $selectedSemester))
            ->with(['guru.guruProfile', 'mataPelajaran'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $jadwalGrouped = $jadwalPelajarans->groupBy('hari');

        $totalSiswa = $kelas->siswaProfiles->count();
        $siswaUserIds = $kelas->siswaProfiles->pluck('user_id')->filter();

        $kehadiran = KehadiranSiswa::whereIn('siswa_id', $siswaUserIds)->get();

        $rekapSiswa = [];
        foreach ($siswaUserIds as $uid) {
            $studentKh = $kehadiran->where('siswa_id', $uid);
            $total = $studentKh->count();
            $hadir = $studentKh->where('status', 'hadir')->count();
            $rekapSiswa[$uid] = [
                'hadir' => $hadir,
                'sakit' => $studentKh->where('status', 'sakit')->count(),
                'izin' => $studentKh->where('status', 'izin')->count(),
                'alpa' => $studentKh->where('status', 'alpa')->count(),
                'dispensasi' => $studentKh->where('status', 'dispensasi')->count(),
                'total' => $total,
                'persentase' => $total > 0 ? round(($hadir / $total) * 100, 1) : 100,
            ];
        }

        $totalAll = $kehadiran->count();
        $hadirAll = $kehadiran->where('status', 'hadir')->count();
        $persentaseKelas = $totalAll > 0 ? round(($hadirAll / $totalAll) * 100, 1) : 100;

        $daftarTahunAjaran = Setting::getDaftarTahunAjaran();
        $daftarSemester = Setting::getDaftarSemester();

        return view('admin.kelas.show', compact(
            'kelas',
            'jadwalGrouped',
            'totalSiswa',
            'rekapSiswa',
            'persentaseKelas',
            'selectedTahunAjaran',
            'selectedSemester',
            'daftarTahunAjaran',
            'daftarSemester'
        ));
    }

    public function create(): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();

        return view('admin.kelas.create', compact('guruList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $guruRule = Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'guru')->whereNull('deleted_at'));

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'string', 'max:20'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_kelas_id' => ['nullable', $guruRule],
            'bk_id' => ['nullable', $guruRule],
            'is_sistem_blok' => ['nullable', 'boolean'],
            'model_rotasi' => ['nullable', 'in:rotasi_minggu,split_harian'],
            'blok_awal' => ['nullable', 'in:kelompok_a,kelompok_b'],
        ]);

        $validated['is_sistem_blok'] = $request->boolean('is_sistem_blok');
        if (! $validated['is_sistem_blok']) {
            $validated['model_rotasi'] = null;
            $validated['blok_awal'] = null;
        }

        Kelas::create($validated);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kelas): View
    {
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $semuaSiswa = SiswaProfile::with(['user', 'kelas'])->get();

        return view('admin.kelas.edit', compact('kelas', 'guruList', 'semuaSiswa'));
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $guruRule = Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'guru')->whereNull('deleted_at'));

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'string', 'max:20'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'wali_kelas_id' => ['nullable', $guruRule],
            'bk_id' => ['nullable', $guruRule],
            'is_sistem_blok' => ['nullable', 'boolean'],
            'model_rotasi' => ['nullable', 'in:rotasi_minggu,split_harian'],
            'blok_awal' => ['nullable', 'in:kelompok_a,kelompok_b'],
        ]);

        $validated['is_sistem_blok'] = $request->boolean('is_sistem_blok');
        if (! $validated['is_sistem_blok']) {
            $validated['model_rotasi'] = null;
            $validated['blok_awal'] = null;
        }

        $kelas->update($validated);

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $kelas->delete();

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all')) {
            $query = Kelas::query();

            if ($request->filled('search')) {
                $query->where('nama', 'like', "%{$request->search}%");
            }

            if ($request->filled('tingkat')) {
                $tingkat = $request->tingkat;
                $query->where(function ($q) use ($tingkat) {
                    $q->where('tingkat', $tingkat);
                    if ($tingkat === '10') {
                        $q->orWhere('tingkat', 'X');
                    } elseif ($tingkat === '11') {
                        $q->orWhere('tingkat', 'XI');
                    } elseif ($tingkat === '12') {
                        $q->orWhere('tingkat', 'XII');
                    }
                });
            }

            if ($request->filled('jurusan')) {
                $jurusan = strtoupper(trim($request->jurusan));
                $query->where(function ($q) use ($jurusan) {
                    match ($jurusan) {
                        'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                        'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                        'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                        'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                        'DKV' => $q->where('nama', 'like', '%DKV%'),
                        'KLN' => $q->where('nama', 'like', '%KLN%'),
                        'HTL' => $q->where('nama', 'like', '%HTL%'),
                        'PBS' => $q->where('nama', 'like', '%PBS%'),
                        default => $q->where('nama', 'like', "%{$jurusan}%"),
                    };
                });
            }

            $count = $query->count();
            $query->delete();

            return redirect()->route('admin.kelas.index')->with('success', "Seluruh {$count} kelas berhasil dihapus.");
        }

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:kelas,id'],
        ]);

        Kelas::whereIn('id', $request->input('ids'))->delete();

        return redirect()->route('admin.kelas.index')->with('success', count($request->input('ids')).' kelas berhasil dihapus.');
    }

    /**
     * Toggle aktif/non-aktif sistem blok untuk kelas tertentu.
     */
    public function toggleBlok(Request $request, Kelas $kelas): RedirectResponse
    {
        $newState = ! $kelas->is_sistem_blok;
        $data = ['is_sistem_blok' => $newState];
        if ($newState) {
            $data['model_rotasi'] = $request->input('model_rotasi', $kelas->model_rotasi ?: 'rotasi_minggu');
            $data['blok_awal'] = $request->input('blok_awal', $kelas->blok_awal ?: 'kelompok_a');
        } else {
            // Reset agar tidak ada data kotor saat sistem blok dimatikan
            $data['model_rotasi'] = null;
            $data['blok_awal'] = null;
        }

        $kelas->update($data);

        $label = ($data['blok_awal'] ?? $kelas->blok_awal) === 'kelompok_b' ? 'Kelompok B (Kejuruan)' : 'Kelompok A (Umum)';
        $status = $newState ? "diaktifkan mulai {$label}" : 'dinonaktifkan';

        return back()->with('success', "Sistem blok untuk kelas {$kelas->nama} berhasil {$status}.");
    }

    /**
     * Update konfigurasi sistem blok (model rotasi & blok awal) untuk kelas.
     */
    public function updateBlok(Request $request, Kelas $kelas): RedirectResponse
    {
        $validated = $request->validate([
            'model_rotasi' => ['nullable', 'in:rotasi_minggu,split_harian'],
            'blok_awal' => ['required', 'in:kelompok_a,kelompok_b'],
        ]);

        $data = [
            'blok_awal' => $validated['blok_awal'],
        ];

        if (! empty($validated['model_rotasi'])) {
            $data['model_rotasi'] = $validated['model_rotasi'];
        }

        $kelas->update($data);

        $label = $validated['blok_awal'] === 'kelompok_b' ? 'Kelompok B (Kejuruan)' : 'Kelompok A (Umum)';

        return back()->with('success', "Blok awal kelas {$kelas->nama} berhasil diubah ke {$label}.");
    }
}

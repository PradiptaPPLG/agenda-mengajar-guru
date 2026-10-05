<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KenaikanKelasController extends Controller
{
    public function index(Request $request): View
    {
        $currentSchoolYear = Setting::getTahunAjaranAktif();
        $currentSemester = Setting::getSemesterAktif();

        // Hitung tahun ajaran baru berikutnya (misal 2026/2027 -> 2027/2028)
        $nextSchoolYear = $this->calculateNextSchoolYear($currentSchoolYear);

        // Ambil kelas per tingkat
        $kelas10 = Kelas::where('tingkat', '10')
            ->orWhere('tingkat', 'X')
            ->orWhere('nama', 'like', '10%')
            ->withCount(['siswaProfiles' => function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('role', 'siswa'));
            }])
            ->orderBy('nama')
            ->get();

        $kelas11 = Kelas::where('tingkat', '11')
            ->orWhere('tingkat', 'XI')
            ->orWhere('nama', 'like', '11%')
            ->withCount(['siswaProfiles' => function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('role', 'siswa'));
            }])
            ->orderBy('nama')
            ->get();

        $kelas12 = Kelas::where('tingkat', '12')
            ->orWhere('tingkat', 'XII')
            ->orWhere('nama', 'like', '12%')
            ->withCount(['siswaProfiles' => function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('role', 'siswa'));
            }])
            ->orderBy('nama')
            ->get();

        // Rekomendasi Pemetaan Otomatis
        $mapping11to12 = [];
        foreach ($kelas11 as $k11) {
            $suggested = $this->suggestTargetClass($k11, $kelas12, '12');
            $mapping11to12[$k11->id] = $suggested?->id;
        }

        $mapping10to11 = [];
        foreach ($kelas10 as $k10) {
            $suggested = $this->suggestTargetClass($k10, $kelas11, '11');
            $mapping10to11[$k10->id] = $suggested?->id;
        }

        $totalSiswa10 = $kelas10->sum('siswa_profiles_count');
        $totalSiswa11 = $kelas11->sum('siswa_profiles_count');
        $totalSiswa12 = $kelas12->sum('siswa_profiles_count');
        $totalSiswaAll = $totalSiswa10 + $totalSiswa11 + $totalSiswa12;

        return view('admin.kenaikan-kelas.index', compact(
            'currentSchoolYear',
            'currentSemester',
            'nextSchoolYear',
            'kelas10',
            'kelas11',
            'kelas12',
            'mapping11to12',
            'mapping10to11',
            'totalSiswa10',
            'totalSiswa11',
            'totalSiswa12',
            'totalSiswaAll'
        ));
    }

    /**
     * Ambil data siswa dalam satu kelas (untuk modal pilih siswa yang tinggal kelas).
     */
    public function getSiswaKelas(Kelas $kelas): JsonResponse
    {
        $siswaProfiles = $kelas->siswaProfiles()
            ->whereHas('user')
            ->with('user:id,name,email,is_active,foto')
            ->get()
            ->map(function (SiswaProfile $profile) {
                return [
                    'profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'name' => $profile->user->name ?? 'Tanpa Nama',
                    'nis' => $profile->nis ?? '-',
                    'is_active' => (bool) ($profile->user->is_active ?? true),
                ];
            })
            ->sortBy('name')
            ->values();

        return response()->json([
            'kelas_id' => $kelas->id,
            'kelas_nama' => $kelas->nama,
            'tingkat' => $kelas->tingkat,
            'total' => $siswaProfiles->count(),
            'siswa' => $siswaProfiles,
        ]);
    }

    /**
     * Proses eksekusi kenaikan kelas massal dan tutup tahun ajaran.
     */
    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'mapping_11_to_12' => ['nullable', 'array'],
            'mapping_11_to_12.*' => ['nullable', 'exists:kelas,id'],
            'mapping_10_to_11' => ['nullable', 'array'],
            'mapping_10_to_11.*' => ['nullable', 'exists:kelas,id'],
            'tinggal_kelas_profiles' => ['nullable', 'array'],
            'tinggal_kelas_profiles.*' => ['integer'],
            'luluskan_kelas_12' => ['nullable', 'boolean'],
            'update_school_year' => ['nullable', 'boolean'],
            'target_school_year' => ['nullable', 'string', 'max:20'],
            'reset_semester' => ['nullable', 'boolean'],
        ]);

        $tinggalKelasProfileIds = collect($request->input('tinggal_kelas_profiles', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        $luluskanKelas12 = $request->boolean('luluskan_kelas_12', true);
        $updateSchoolYear = $request->boolean('update_school_year', true);
        $targetSchoolYear = $request->input('target_school_year');
        $resetSemester = $request->boolean('reset_semester', true);

        $mapping11to12 = $request->input('mapping_11_to_12', []);
        $mapping10to11 = $request->input('mapping_10_to_11', []);

        $stats = [
            'lulus_12' => 0,
            'promoted_11' => 0,
            'promoted_10' => 0,
            'tinggal_kelas' => count($tinggalKelasProfileIds),
        ];

        DB::transaction(function () use (
            $mapping11to12,
            $mapping10to11,
            $tinggalKelasProfileIds,
            $luluskanKelas12,
            $updateSchoolYear,
            $targetSchoolYear,
            $resetSemester,
            &$stats
        ) {
            // 1. Eksekusi Siswa Kelas 12 (Kelulusan / Nonaktifkan Akun)
            if ($luluskanKelas12) {
                $kelas12Ids = Kelas::where('tingkat', '12')
                    ->orWhere('tingkat', 'XII')
                    ->orWhere('nama', 'like', '12%')
                    ->pluck('id');

                $graduatingProfiles = SiswaProfile::whereIn('kelas_id', $kelas12Ids)
                    ->whereNotIn('id', $tinggalKelasProfileIds)
                    ->get();

                $graduatingUserIds = $graduatingProfiles->pluck('user_id')->filter()->unique();

                if ($graduatingUserIds->isNotEmpty()) {
                    User::whereIn('id', $graduatingUserIds)->update(['is_active' => false]);

                    // Lepaskan rombel kelas aktif untuk siswa yang telah lulus (status Alumni)
                    SiswaProfile::whereIn('id', $graduatingProfiles->pluck('id'))->update([
                        'kelas_id' => null,
                        'kelompok_blok' => null,
                    ]);

                    $stats['lulus_12'] = $graduatingUserIds->count();
                }
            }

            // 2. Kenaikan Siswa Kelas 11 -> Kelas 12
            // PENTING: Dijalankan sebelum Kelas 10 -> 11 agar tidak terjadi tumpang tindih
            foreach ($mapping11to12 as $sourceKelasId => $targetKelasId) {
                if (empty($targetKelasId)) {
                    continue;
                }

                $query = SiswaProfile::where('kelas_id', $sourceKelasId);
                if (! empty($tinggalKelasProfileIds)) {
                    $query->whereNotIn('id', $tinggalKelasProfileIds);
                }

                $updated = $query->update([
                    'kelas_id' => $targetKelasId,
                    'kelompok_blok' => null, // Reset kelompok blok kelas baru
                ]);

                $stats['promoted_11'] += $updated;
            }

            // 3. Kenaikan Siswa Kelas 10 -> Kelas 11
            foreach ($mapping10to11 as $sourceKelasId => $targetKelasId) {
                if (empty($targetKelasId)) {
                    continue;
                }

                $query = SiswaProfile::where('kelas_id', $sourceKelasId);
                if (! empty($tinggalKelasProfileIds)) {
                    $query->whereNotIn('id', $tinggalKelasProfileIds);
                }

                $updated = $query->update([
                    'kelas_id' => $targetKelasId,
                    'kelompok_blok' => null,
                ]);

                $stats['promoted_10'] += $updated;
            }

            // 4. Update Tahun Ajaran & Reset Semester ke Ganjil jika dipilih
            if ($updateSchoolYear && ! empty($targetSchoolYear)) {
                Setting::set('school_year', $targetSchoolYear);

                // Update kolom tahun_ajaran di semua tabel kelas jika diperlukan
                Kelas::query()->update(['tahun_ajaran' => $targetSchoolYear]);
            }

            if ($resetSemester) {
                Setting::set('semester', '1');
            }
        });

        $message = "Proses Kenaikan Kelas Berhasil! {$stats['lulus_12']} siswa kelas 12 diluluskan, {$stats['promoted_11']} siswa kelas 11 naik ke kelas 12, {$stats['promoted_10']} siswa kelas 10 naik ke kelas 11.";
        if ($stats['tinggal_kelas'] > 0) {
            $message .= " ({$stats['tinggal_kelas']} siswa tetap tinggal kelas).";
        }

        return redirect()->route('admin.kelas.index')->with('success', $message);
    }

    /**
     * Hitung tahun ajaran berikutnya dari format YYYY/YYYY (misal 2026/2027 -> 2027/2028).
     */
    private function calculateNextSchoolYear(string $current): string
    {
        if (preg_match('/^(\d{4})\/(\d{4})$/', $current, $matches)) {
            $start = (int) $matches[1] + 1;
            $end = (int) $matches[2] + 1;

            return "{$start}/{$end}";
        }

        $y = (int) date('Y');

        return $y.'/'.($y + 1);
    }

    /**
     * Algoritma pintar untuk mencocokkan target kelas tujuan berdasarkan nama dan konsentrasi kejuruan.
     */
    private function suggestTargetClass(Kelas $sourceClass, Collection $targetClasses, string $targetTingkat): ?Kelas
    {
        $sourceName = strtoupper(trim($sourceClass->nama));

        // 1. Ganti prefiks tingkat (10 -> 11, atau 11 -> 12)
        $expectedExact = preg_replace('/^(10|11|X|XI)[\s\-_]*/i', $targetTingkat, $sourceName);
        $directMatch = $targetClasses->first(fn ($c) => strtoupper(trim($c->nama)) === $expectedExact);
        if ($directMatch) {
            return $directMatch;
        }

        // 2. Pemetaan spesifik singkatan kejuruan SMK Negeri 1 Ciamis (Kurikulum Merdeka 10 ke 11)
        if ($targetTingkat === '11') {
            // AKL -> AK (misal: 10AKL1 -> 11AK1)
            $akl = preg_replace('/^11AKL/i', '11AK', $expectedExact);
            $match = $targetClasses->first(fn ($c) => strtoupper(trim($c->nama)) === $akl);
            if ($match) {
                return $match;
            }

            // MPLB -> MP (misal: 10MPLB1 -> 11MP1)
            $mplb = preg_replace('/^11MPLB/i', '11MP', $expectedExact);
            $match = $targetClasses->first(fn ($c) => strtoupper(trim($c->nama)) === $mplb);
            if ($match) {
                return $match;
            }

            // PPLG -> RPL (misal: 10PPLG -> 11RPL)
            $pplg = preg_replace('/^11PPLG/i', '11RPL', $expectedExact);
            $match = $targetClasses->first(fn ($c) => strtoupper(trim($c->nama)) === $pplg);
            if ($match) {
                return $match;
            }
        }

        // 3. Fallback similarity matching
        $bestMatch = null;
        $highestSimilarity = 0;
        foreach ($targetClasses as $target) {
            similar_text($sourceName, strtoupper($target->nama), $sim);
            if ($sim > $highestSimilarity && $sim > 65) {
                $highestSimilarity = $sim;
                $bestMatch = $target;
            }
        }

        return $bestMatch;
    }
}

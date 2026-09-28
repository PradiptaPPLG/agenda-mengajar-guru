<?php

namespace Database\Seeders;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class CleanAndSyncMataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('mapel_dengan_kategori.xlsx');
        if (! file_exists($filePath)) {
            $this->command?->error("File {$filePath} tidak ditemukan!");

            return;
        }

        $excelRows = SimpleExcelReader::create($filePath)->getRows()->toArray();
        $this->command?->info('Membaca '.count($excelRows).' baris dari mapel_dengan_kategori.xlsx...');

        $kelompokSeeder = new KelompokBlokMataPelajaranSeeder;

        // 1. Build canonical definitions
        $canonicalDefs = [];
        foreach ($excelRows as $i => $r) {
            $rawNama = trim(preg_replace('/\s+/', ' ', $r['Mata pelajaran'] ?? ''));
            $rawKode = trim(preg_replace('/\s+/', ' ', $r['Kode'] ?? ''));

            // Normalize Kode: "KKRPL 12" -> "KKRPL-12", "MP 11" -> "MP-11", "PKK PBD" -> "PKK-PBD"
            $cleanKode = preg_replace('/\s+/', '-', $rawKode);

            // Normalize Nama: "MP-DKV12" -> "MP-DKV 12"
            $cleanNama = $rawNama === 'MP-DKV12' ? 'MP-DKV 12' : $rawNama;
            $jenis = strtolower(trim($r['Kategori'] ?? '')) === 'produktif' ? 'produktif' : 'umum';
            $kelompokBlok = $kelompokSeeder->classify($cleanNama, $cleanKode);

            $canonicalDefs[] = [
                'nama' => $cleanNama,
                'kode' => $cleanKode,
                'jenis' => $jenis,
                'kelompok_blok' => $kelompokBlok,
            ];
        }

        DB::transaction(function () use ($canonicalDefs) {
            // 2. Insert or update the 67 canonical records
            $canonicalModels = [];
            $canonicalIds = [];

            foreach ($canonicalDefs as $def) {
                // Find existing by normalized exact name
                $condensedTarget = preg_replace('/[^A-Z0-9]/', '', strtoupper($def['nama']));
                $existing = MataPelajaran::withTrashed()->get()->first(function ($m) use ($condensedTarget) {
                    return preg_replace('/[^A-Z0-9]/', '', strtoupper($m->nama)) === $condensedTarget;
                });

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update([
                        'nama' => $def['nama'],
                        'kode' => $def['kode'],
                        'jenis' => $def['jenis'],
                        'kelompok_blok' => $def['kelompok_blok'],
                    ]);
                    $canonicalModels[] = $existing;
                    $canonicalIds[] = $existing->id;
                } else {
                    $newM = MataPelajaran::create([
                        'nama' => $def['nama'],
                        'kode' => $def['kode'],
                        'jenis' => $def['jenis'],
                        'kelompok_blok' => $def['kelompok_blok'],
                    ]);
                    $canonicalModels[] = $newM;
                    $canonicalIds[] = $newM->id;
                }
            }

            // 3. Remap all JadwalPelajaran to canonical records
            $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran'])->get();
            $remappedJadwals = 0;

            foreach ($jadwals as $jadwal) {
                $currentMapel = $jadwal->mataPelajaran;
                if (! $currentMapel) {
                    continue;
                }

                $target = $this->resolveToCanonical(
                    $currentMapel->nama,
                    $currentMapel->kode,
                    $jadwal->kelas?->nama ?? '',
                    $canonicalModels
                );

                if ($target) {
                    if ($jadwal->mata_pelajaran_id !== $target->id || $jadwal->kelompok_blok !== $target->kelompok_blok) {
                        $jadwal->update([
                            'mata_pelajaran_id' => $target->id,
                            'kelompok_blok' => $target->kelompok_blok,
                        ]);
                        $remappedJadwals++;
                    }
                }
            }

            // 4. Remap guru_mata_pelajaran
            $guruMapels = DB::table('guru_mata_pelajaran')->get();
            $remappedGuru = 0;

            foreach ($guruMapels as $gm) {
                $m = MataPelajaran::withTrashed()->find($gm->mata_pelajaran_id);
                if (! $m) {
                    DB::table('guru_mata_pelajaran')->where('id', $gm->id)->delete();

                    continue;
                }

                $target = $this->resolveToCanonical($m->nama, $m->kode, '', $canonicalModels);
                if ($target) {
                    if ($gm->mata_pelajaran_id !== $target->id) {
                        $alreadyExists = DB::table('guru_mata_pelajaran')
                            ->where('user_id', $gm->user_id)
                            ->where('mata_pelajaran_id', $target->id)
                            ->exists();

                        if (! $alreadyExists) {
                            DB::table('guru_mata_pelajaran')->where('id', $gm->id)->update([
                                'mata_pelajaran_id' => $target->id,
                            ]);
                            $remappedGuru++;
                        } else {
                            DB::table('guru_mata_pelajaran')->where('id', $gm->id)->delete();
                        }
                    }
                }
            }

            // 5. Remap kelas_mata_pelajaran
            $kelasMapels = DB::table('kelas_mata_pelajaran')->get();
            foreach ($kelasMapels as $km) {
                $m = MataPelajaran::withTrashed()->find($km->mata_pelajaran_id);
                if (! $m) {
                    DB::table('kelas_mata_pelajaran')->where('id', $km->id)->delete();

                    continue;
                }

                $target = $this->resolveToCanonical($m->nama, $m->kode, '', $canonicalModels);
                if ($target) {
                    if ($km->mata_pelajaran_id !== $target->id) {
                        $alreadyExists = DB::table('kelas_mata_pelajaran')
                            ->where('kelas_id', $km->kelas_id)
                            ->where('mata_pelajaran_id', $target->id)
                            ->exists();

                        if (! $alreadyExists) {
                            DB::table('kelas_mata_pelajaran')->where('id', $km->id)->update([
                                'mata_pelajaran_id' => $target->id,
                            ]);
                        } else {
                            DB::table('kelas_mata_pelajaran')->where('id', $km->id)->delete();
                        }
                    }
                }
            }

            // 6. Delete all non-canonical mapel records
            $nonCanonical = MataPelajaran::withTrashed()->whereNotIn('id', $canonicalIds)->get();
            $deletedCount = $nonCanonical->count();
            foreach ($nonCanonical as $oldM) {
                $oldM->forceDelete();
            }

            $this->command?->info('✅ Berhasil menyelaraskan mata pelajaran:');
            $this->command?->info('   - Total mapel canonical resmi: '.count($canonicalIds));
            $this->command?->info("   - Jadwal yang diselaraskan: {$remappedJadwals}");
            $this->command?->info("   - Relasi guru yang diselaraskan: {$remappedGuru}");
            $this->command?->info("   - Mapel duplikat/kacau yang dibersihkan: {$deletedCount}");
        });
    }

    /**
     * Resolve any messy/room-tagged mapel string + optional kelas to a canonical MataPelajaran model.
     */
    private function resolveToCanonical(string $rawNama, ?string $rawKode, ?string $kelasNama, array $canonicalModels): ?MataPelajaran
    {
        $s = strtoupper($rawNama);
        $roomTags = ['LAB TIK', 'LAB RPL', 'LAB DKV', 'SIMDIG', 'L.MPLB-1', 'L.MPLB-2', 'KOMPAK-1', 'KOMPAK-2', 'L-PM-1', 'L-PM-2'];
        foreach ($roomTags as $rt) {
            $s = str_replace($rt, '', $s);
        }

        $cond = preg_replace('/[^A-Z0-9]/', '', $s);

        // 1. Direct name match (highest precedence)
        foreach ($canonicalModels as $c) {
            $cNameCond = preg_replace('/[^A-Z0-9]/', '', strtoupper($c->nama));
            if ($cond === $cNameCond) {
                // If it's MP-RPL / etc., but kelas says otherwise, check kelas context
                if (str_starts_with($cond, 'MP') && $kelasNama) {
                    $grade = str_contains($cond, '12') ? '12' : '11';
                    $byKelas = $this->resolveMpByKelas($grade, $kelasNama, $canonicalModels);
                    if ($byKelas) {
                        return $byKelas;
                    }
                }

                return $c;
            }
        }

        // 2. Direct code match (ONLY if NOT multi-major codes like MP11, MP12)
        foreach ($canonicalModels as $c) {
            $cKodeCond = preg_replace('/[^A-Z0-9]/', '', strtoupper($c->kode));
            if ($cond === $cKodeCond && ! in_array($cond, ['MP11', 'MP12'])) {
                return $c;
            }
        }

        // 3. Aliases
        if (str_contains($cond, 'MATEMATIKA') || in_array($cond, ['MAT', 'MATEMATIK'])) {
            return $this->findModelByName('MATEMATIKA', $canonicalModels);
        }
        if (str_contains($cond, 'INDONESIA') || in_array($cond, ['INDO', 'BIND', 'BAHINDONESIA'])) {
            return $this->findModelByName('BAH. INDONESIA', $canonicalModels);
        }
        if (str_contains($cond, 'INGGRIS') || in_array($cond, ['INGG', 'BING', 'BAHINGGRIS'])) {
            return $this->findModelByName('BAH.INGGRIS', $canonicalModels);
        }
        if (str_contains($cond, 'SUNDA') || in_array($cond, ['SUNDA', 'BAHSUNDA'])) {
            return $this->findModelByName('BAH.SUNDA', $canonicalModels);
        }
        if (str_contains($cond, 'SEJARAH') || in_array($cond, ['SEJ', 'SEJINDO'])) {
            return $this->findModelByName('SEJARAH INDONESIA', $canonicalModels);
        }
        if (str_contains($cond, 'SENIBUDAYA') || in_array($cond, ['SBD', 'SENI'])) {
            return $this->findModelByName('SENI BUDAYA', $canonicalModels);
        }
        if (str_contains($cond, 'PENJAS') || in_array($cond, ['PJOK'])) {
            return $this->findModelByName('PENJAS', $canonicalModels);
        }
        if (str_contains($cond, 'PPKN') || in_array($cond, ['PPK'])) {
            return $this->findModelByName('PPKn', $canonicalModels);
        }
        if (str_contains($cond, 'PAI') || str_contains($cond, 'AGAMA')) {
            return $this->findModelByName('PAI', $canonicalModels);
        }
        if (str_contains($cond, 'IPAS')) {
            return $this->findModelByName('PROYEK IPAS', $canonicalModels);
        }
        if (str_contains($cond, 'INFORMATIKA') || in_array($cond, ['INFOR', 'INF', 'INFORMATIKAKKA'])) {
            return $this->findModelByName('INFORMATIKA_KKA', $canonicalModels);
        }
        if (str_contains($cond, 'KODING') || in_array($cond, ['KKA'])) {
            return $this->findModelByName('KODING DAN KECERDASAN ARTIFISIAL', $canonicalModels);
        }
        if (in_array($cond, ['BP', 'BK', 'BPBK'])) {
            return $this->findModelByName('BP/BK', $canonicalModels);
        }
        if (in_array($cond, ['APEL'])) {
            return $this->findModelByName('Apel', $canonicalModels);
        }
        if (in_array($cond, ['PEMBIASAAN'])) {
            return $this->findModelByName('Pembiasaan', $canonicalModels);
        }

        // DPK
        if (str_contains($cond, 'DPK')) {
            if (str_contains($cond, 'PPLG') || str_contains($cond, 'RPL')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN PPLG', $canonicalModels);
            }
            if (str_contains($cond, 'DKV')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN DKV', $canonicalModels);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PEMASARAN')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN PEMASARAN', $canonicalModels);
            }
            if (str_contains($cond, 'MPLB') || str_contains($cond, 'MP')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN MPLB', $canonicalModels);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN AKL', $canonicalModels);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN HTL', $canonicalModels);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return $this->findModelByName('DASAR PROGRAM KEAHLIAN KLN', $canonicalModels);
            }
        }

        // PKK
        if (str_contains($cond, 'PKK') || str_contains($cond, 'PRODUKKREATIF')) {
            if (str_contains($cond, 'RPL') || str_contains($cond, 'PPLG')) {
                return $this->findModelByName('PRODUK KREATIF RPL', $canonicalModels);
            }
            if (str_contains($cond, 'DKV')) {
                return $this->findModelByName('PRODUK KREATIF DKV', $canonicalModels);
            }
            if (str_contains($cond, 'PBD')) {
                return $this->findModelByName('PRODUK KREATIF PBD', $canonicalModels);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PEMASARAN')) {
                return $this->findModelByName('PRODUK KREATIF PM', $canonicalModels);
            }
            if (str_contains($cond, 'MPLB') || str_contains($cond, 'MP')) {
                return $this->findModelByName('PRODUK KREATIF MPLB', $canonicalModels);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return $this->findModelByName('PRODUK KREATIF AKL', $canonicalModels);
            }
            if (str_contains($cond, 'PBS')) {
                return $this->findModelByName('PRODUK KREATIF PBS', $canonicalModels);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return $this->findModelByName('PRODUK KREATIF HTL', $canonicalModels);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return $this->findModelByName('PRODUK KREATIF KLN', $canonicalModels);
            }
        }

        // KK
        if (str_contains($cond, 'KK')) {
            $grade = str_contains($cond, '12') ? '12' : '11';
            if (str_contains($cond, 'RPL') || str_contains($cond, 'PPLG')) {
                return $this->findModelByName("KK-RPL $grade", $canonicalModels);
            }
            if (str_contains($cond, 'DKV')) {
                return $this->findModelByName($grade === '12' ? 'KK DKV 12' : 'KK-DKV 11', $canonicalModels);
            }
            if (str_contains($cond, 'PBD')) {
                return $this->findModelByName("KK PBD $grade", $canonicalModels) ?: $this->findModelByName("KK PBD-$grade", $canonicalModels);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PEMASARAN')) {
                return $this->findModelByName("KK-PM $grade", $canonicalModels);
            }
            if (str_contains($cond, 'MPLB') || str_contains($cond, 'MP')) {
                return $this->findModelByName("KK-MPLB $grade", $canonicalModels);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return $this->findModelByName("KK-AKL $grade", $canonicalModels);
            }
            if (str_contains($cond, 'PBS')) {
                return $this->findModelByName($grade === '12' ? 'KK PBS-12' : 'KK PBS 11', $canonicalModels);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return $this->findModelByName("KK-HTL $grade", $canonicalModels);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return $this->findModelByName("KK-KLN $grade", $canonicalModels);
            }
        }

        // MP
        if (str_contains($cond, 'MP')) {
            $grade = str_contains($cond, '12') ? '12' : '11';

            // Check if major is specified in mapel name itself
            if (str_contains($cond, 'RPL') || str_contains($cond, 'PPLG')) {
                return $this->findModelByName("MP-RPL $grade", $canonicalModels);
            }
            if (str_contains($cond, 'DKV')) {
                return $this->findModelByName($grade === '12' ? 'MP-DKV 12' : 'MP-DKV 11', $canonicalModels);
            }
            if (str_contains($cond, 'PBD')) {
                return $this->findModelByName("MP PBD $grade", $canonicalModels);
            }
            if (str_contains($cond, 'PBS')) {
                return $this->findModelByName($grade === '12' ? 'MP PBS-12' : 'MP PBS-11', $canonicalModels);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PBR') || str_contains($cond, 'PEMASARAN')) {
                return $this->findModelByName("MP-PM $grade", $canonicalModels);
            }
            if (str_contains($cond, 'MPLB')) {
                return $this->findModelByName("MP-MPLB $grade", $canonicalModels);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return $this->findModelByName("MP-AKL $grade", $canonicalModels);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return $this->findModelByName("MP-HTL $grade", $canonicalModels);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return $this->findModelByName("MP-KLN $grade", $canonicalModels);
            }

            // If mapel is generic MP-11 or MP-12, resolve by kelas
            if ($kelasNama) {
                $byKelas = $this->resolveMpByKelas($grade, $kelasNama, $canonicalModels);
                if ($byKelas) {
                    return $byKelas;
                }
            }
        }

        return null;
    }

    private function resolveMpByKelas(string $grade, string $kelasNama, array $canonicalModels): ?MataPelajaran
    {
        $kUpper = strtoupper($kelasNama);
        if (str_contains($kUpper, 'RPL') || str_contains($kUpper, 'PPLG')) {
            return $this->findModelByName("MP-RPL $grade", $canonicalModels);
        }
        if (str_contains($kUpper, 'DKV')) {
            return $this->findModelByName($grade === '12' ? 'MP-DKV 12' : 'MP-DKV 11', $canonicalModels);
        }
        if (str_contains($kUpper, 'PBD')) {
            return $this->findModelByName("MP PBD $grade", $canonicalModels);
        }
        if (str_contains($kUpper, 'PBS')) {
            return $this->findModelByName($grade === '12' ? 'MP PBS-12' : 'MP PBS-11', $canonicalModels);
        }
        if (str_contains($kUpper, 'PM') || str_contains($kUpper, 'PBR')) {
            return $this->findModelByName("MP-PM $grade", $canonicalModels);
        }
        if (str_contains($kUpper, 'MPLB') || str_contains($kUpper, 'MP')) {
            return $this->findModelByName("MP-MPLB $grade", $canonicalModels);
        }
        if (str_contains($kUpper, 'AKL') || str_contains($kUpper, 'AK')) {
            return $this->findModelByName("MP-AKL $grade", $canonicalModels);
        }
        if (str_contains($kUpper, 'HTL')) {
            return $this->findModelByName("MP-HTL $grade", $canonicalModels);
        }
        if (str_contains($kUpper, 'KLN')) {
            return $this->findModelByName("MP-KLN $grade", $canonicalModels);
        }

        return null;
    }

    private function findModelByName(string $nama, array $canonicalModels): ?MataPelajaran
    {
        $target = preg_replace('/[^A-Z0-9]/', '', strtoupper($nama));
        foreach ($canonicalModels as $c) {
            if (preg_replace('/[^A-Z0-9]/', '', strtoupper($c->nama)) === $target) {
                return $c;
            }
        }

        return null;
    }
}

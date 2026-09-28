<?php

namespace App\Models;

use Database\Factories\MataPelajaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MataPelajaran extends Model
{
    /** @use HasFactory<MataPelajaranFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['nama', 'kode', 'jenis', 'kelompok_blok'];

    public function jadwalPelajarans(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_mata_pelajaran')->withTimestamps();
    }

    /**
     * Cari mata pelajaran yang cocok dari nama string yang mungkin memiliki tag ruangan/spasi acak.
     */
    public static function findMatchingMapel(string $rawNama, ?string $kelasNama = null): ?self
    {
        $s = strtoupper($rawNama);
        $roomTags = ['LAB TIK', 'LAB RPL', 'LAB DKV', 'SIMDIG', 'L.MPLB-1', 'L.MPLB-2', 'KOMPAK-1', 'KOMPAK-2', 'L-PM-1', 'L-PM-2'];
        foreach ($roomTags as $rt) {
            $s = str_replace($rt, '', $s);
        }

        $cond = preg_replace('/[^A-Z0-9]/', '', $s);
        if (! $cond) {
            return null;
        }

        $all = self::all();

        // 1. Direct name match
        foreach ($all as $c) {
            $cNameCond = preg_replace('/[^A-Z0-9]/', '', strtoupper($c->nama));
            if ($cond === $cNameCond) {
                if (str_starts_with($cond, 'MP') && $kelasNama) {
                    $grade = str_contains($cond, '12') ? '12' : '11';
                    $byKelas = self::resolveMpByKelas($grade, $kelasNama, $all);
                    if ($byKelas) {
                        return $byKelas;
                    }
                }

                return $c;
            }
        }

        // 2. Direct code match (only if not generic MP-11/MP-12)
        foreach ($all as $c) {
            $cKodeCond = preg_replace('/[^A-Z0-9]/', '', strtoupper($c->kode));
            if ($cond === $cKodeCond && ! in_array($cond, ['MP11', 'MP12'])) {
                return $c;
            }
        }

        // 3. Aliases
        if (str_contains($cond, 'MATEMATIKA') || in_array($cond, ['MAT', 'MATEMATIK'])) {
            return self::findFirstByName('MATEMATIKA', $all);
        }
        if (str_contains($cond, 'INDONESIA') || in_array($cond, ['INDO', 'BIND', 'BAHINDONESIA'])) {
            return self::findFirstByName('BAH. INDONESIA', $all);
        }
        if (str_contains($cond, 'INGGRIS') || in_array($cond, ['INGG', 'BING', 'BAHINGGRIS'])) {
            return self::findFirstByName('BAH.INGGRIS', $all);
        }
        if (str_contains($cond, 'SUNDA') || in_array($cond, ['SUNDA', 'BAHSUNDA'])) {
            return self::findFirstByName('BAH.SUNDA', $all);
        }
        if (str_contains($cond, 'SEJARAH') || in_array($cond, ['SEJ', 'SEJINDO'])) {
            return self::findFirstByName('SEJARAH INDONESIA', $all);
        }
        if (str_contains($cond, 'SENIBUDAYA') || in_array($cond, ['SBD', 'SENI'])) {
            return self::findFirstByName('SENI BUDAYA', $all);
        }
        if (str_contains($cond, 'PENJAS') || in_array($cond, ['PJOK'])) {
            return self::findFirstByName('PENJAS', $all);
        }
        if (str_contains($cond, 'PPKN') || in_array($cond, ['PPK'])) {
            return self::findFirstByName('PPKn', $all);
        }
        if (str_contains($cond, 'PAI') || str_contains($cond, 'AGAMA')) {
            return self::findFirstByName('PAI', $all);
        }
        if (str_contains($cond, 'IPAS')) {
            return self::findFirstByName('PROYEK IPAS', $all);
        }
        if (str_contains($cond, 'INFORMATIKA') || in_array($cond, ['INFOR', 'INF', 'INFORMATIKAKKA'])) {
            return self::findFirstByName('INFORMATIKA_KKA', $all);
        }
        if (str_contains($cond, 'KODING') || in_array($cond, ['KKA'])) {
            return self::findFirstByName('KODING DAN KECERDASAN ARTIFISIAL', $all);
        }
        if (in_array($cond, ['BP', 'BK', 'BPBK'])) {
            return self::findFirstByName('BP/BK', $all);
        }
        if (in_array($cond, ['APEL'])) {
            return self::findFirstByName('Apel', $all);
        }
        if (in_array($cond, ['PEMBIASAAN'])) {
            return self::findFirstByName('Pembiasaan', $all);
        }

        // DPK
        if (str_contains($cond, 'DPK')) {
            if (str_contains($cond, 'PPLG') || str_contains($cond, 'RPL')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN PPLG', $all);
            }
            if (str_contains($cond, 'DKV')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN DKV', $all);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PEMASARAN')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN PEMASARAN', $all);
            }
            if (str_contains($cond, 'MPLB') || str_contains($cond, 'MP')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN MPLB', $all);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN AKL', $all);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN HTL', $all);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return self::findFirstByName('DASAR PROGRAM KEAHLIAN KLN', $all);
            }
        }

        // PKK
        if (str_contains($cond, 'PKK') || str_contains($cond, 'PRODUKKREATIF')) {
            if (str_contains($cond, 'RPL') || str_contains($cond, 'PPLG')) {
                return self::findFirstByName('PRODUK KREATIF RPL', $all);
            }
            if (str_contains($cond, 'DKV')) {
                return self::findFirstByName('PRODUK KREATIF DKV', $all);
            }
            if (str_contains($cond, 'PBD')) {
                return self::findFirstByName('PRODUK KREATIF PBD', $all);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PEMASARAN')) {
                return self::findFirstByName('PRODUK KREATIF PM', $all);
            }
            if (str_contains($cond, 'MPLB') || str_contains($cond, 'MP')) {
                return self::findFirstByName('PRODUK KREATIF MPLB', $all);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return self::findFirstByName('PRODUK KREATIF AKL', $all);
            }
            if (str_contains($cond, 'PBS')) {
                return self::findFirstByName('PRODUK KREATIF PBS', $all);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return self::findFirstByName('PRODUK KREATIF HTL', $all);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return self::findFirstByName('PRODUK KREATIF KLN', $all);
            }
        }

        // KK
        if (str_contains($cond, 'KK')) {
            $grade = str_contains($cond, '12') ? '12' : '11';
            if (str_contains($cond, 'RPL') || str_contains($cond, 'PPLG')) {
                return self::findFirstByName("KK-RPL $grade", $all);
            }
            if (str_contains($cond, 'DKV')) {
                return self::findFirstByName($grade === '12' ? 'KK DKV 12' : 'KK-DKV 11', $all);
            }
            if (str_contains($cond, 'PBD')) {
                return self::findFirstByName("KK PBD $grade", $all) ?: self::findFirstByName("KK PBD-$grade", $all);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PEMASARAN')) {
                return self::findFirstByName("KK-PM $grade", $all);
            }
            if (str_contains($cond, 'MPLB') || str_contains($cond, 'MP')) {
                return self::findFirstByName("KK-MPLB $grade", $all);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return self::findFirstByName("KK-AKL $grade", $all);
            }
            if (str_contains($cond, 'PBS')) {
                return self::findFirstByName($grade === '12' ? 'KK PBS-12' : 'KK PBS 11', $all);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return self::findFirstByName("KK-HTL $grade", $all);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return self::findFirstByName("KK-KLN $grade", $all);
            }
        }

        // MP
        if (str_contains($cond, 'MP')) {
            $grade = str_contains($cond, '12') ? '12' : '11';

            if (str_contains($cond, 'RPL') || str_contains($cond, 'PPLG')) {
                return self::findFirstByName("MP-RPL $grade", $all);
            }
            if (str_contains($cond, 'DKV')) {
                return self::findFirstByName($grade === '12' ? 'MP-DKV 12' : 'MP-DKV 11', $all);
            }
            if (str_contains($cond, 'PBD')) {
                return self::findFirstByName("MP PBD $grade", $all);
            }
            if (str_contains($cond, 'PBS')) {
                return self::findFirstByName($grade === '12' ? 'MP PBS-12' : 'MP PBS-11', $all);
            }
            if (str_contains($cond, 'PM') || str_contains($cond, 'PBR') || str_contains($cond, 'PEMASARAN')) {
                return self::findFirstByName("MP-PM $grade", $all);
            }
            if (str_contains($cond, 'MPLB')) {
                return self::findFirstByName("MP-MPLB $grade", $all);
            }
            if (str_contains($cond, 'AKL') || str_contains($cond, 'AK')) {
                return self::findFirstByName("MP-AKL $grade", $all);
            }
            if (str_contains($cond, 'HTL') || str_contains($cond, 'HOTEL')) {
                return self::findFirstByName("MP-HTL $grade", $all);
            }
            if (str_contains($cond, 'KLN') || str_contains($cond, 'KULINER')) {
                return self::findFirstByName("MP-KLN $grade", $all);
            }

            if ($kelasNama) {
                $byKelas = self::resolveMpByKelas($grade, $kelasNama, $all);
                if ($byKelas) {
                    return $byKelas;
                }
            }
        }

        return null;
    }

    private static function resolveMpByKelas(string $grade, string $kelasNama, $all): ?self
    {
        $kUpper = strtoupper($kelasNama);
        if (str_contains($kUpper, 'RPL') || str_contains($kUpper, 'PPLG')) {
            return self::findFirstByName("MP-RPL $grade", $all);
        }
        if (str_contains($kUpper, 'DKV')) {
            return self::findFirstByName($grade === '12' ? 'MP-DKV 12' : 'MP-DKV 11', $all);
        }
        if (str_contains($kUpper, 'PBD')) {
            return self::findFirstByName("MP PBD $grade", $all);
        }
        if (str_contains($kUpper, 'PBS')) {
            return self::findFirstByName($grade === '12' ? 'MP PBS-12' : 'MP PBS-11', $all);
        }
        if (str_contains($kUpper, 'PM') || str_contains($kUpper, 'PBR')) {
            return self::findFirstByName("MP-PM $grade", $all);
        }
        if (str_contains($kUpper, 'MPLB') || str_contains($kUpper, 'MP')) {
            return self::findFirstByName("MP-MPLB $grade", $all);
        }
        if (str_contains($kUpper, 'AKL') || str_contains($kUpper, 'AK')) {
            return self::findFirstByName("MP-AKL $grade", $all);
        }
        if (str_contains($kUpper, 'HTL')) {
            return self::findFirstByName("MP-HTL $grade", $all);
        }
        if (str_contains($kUpper, 'KLN')) {
            return self::findFirstByName("MP-KLN $grade", $all);
        }

        return null;
    }

    private static function findFirstByName(string $nama, $all): ?self
    {
        $target = preg_replace('/[^A-Z0-9]/', '', strtoupper($nama));
        foreach ($all as $c) {
            if (preg_replace('/[^A-Z0-9]/', '', strtoupper($c->nama)) === $target) {
                return $c;
            }
        }

        return null;
    }
}

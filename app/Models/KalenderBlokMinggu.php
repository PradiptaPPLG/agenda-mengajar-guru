<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class KalenderBlokMinggu extends Model
{
    protected $fillable = [
        'nomor_minggu',
        'label',
        'tanggal_mulai',
        'tanggal_selesai',
        'semester',
        'tahun_ajaran',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'nomor_minggu' => 'integer',
    ];

    /**
     * Scope: minggu yang sedang aktif berdasarkan tanggal hari ini.
     */
    public function scopeAktif(Builder $query, ?Carbon $tanggal = null): Builder
    {
        $tanggal ??= Carbon::today();

        return $query->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->orderBy('nomor_minggu');
    }

    /**
     * Scope: filter berdasarkan tahun ajaran dan semester.
     */
    public function scopePerSemester(Builder $query, string $tahunAjaran, string $semester): Builder
    {
        return $query->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester);
    }

    /**
     * Generate kalender blok untuk satu semester secara otomatis.
     *
     * @param  Carbon  $tanggalMulai  Senin pertama semester
     * @param  int  $jumlahMinggu  Total minggu dalam semester
     * @param  string  $tahunAjaran  Contoh: "2025/2026"
     * @param  string  $semester  "ganjil" atau "genap"
     */
    public static function generateSemester(
        Carbon $tanggalMulai,
        int $jumlahMinggu,
        string $tahunAjaran,
        string $semester
    ): Collection {
        $records = collect();

        for ($i = 0; $i < $jumlahMinggu; $i++) {
            $senin = $tanggalMulai->copy()->addWeeks($i);
            $sabtu = $senin->copy()->addDays(5);
            $nomorMinggu = $i + 1;
            $semesterLabel = ucfirst($semester);

            $record = self::updateOrCreate(
                [
                    'nomor_minggu' => $nomorMinggu,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $semester,
                ],
                [
                    'label' => "Minggu ke-{$nomorMinggu} Semester {$semesterLabel} {$tahunAjaran}",
                    'tanggal_mulai' => $senin->toDateString(),
                    'tanggal_selesai' => $sabtu->toDateString(),
                ]
            );

            $records->push($record);
        }

        return $records;
    }

    /**
     * Tentukan kelompok blok aktif untuk sebuah kelas pada minggu ini.
     * Mengembalikan 'kelompok_a' atau 'kelompok_b'.
     */
    public function kelompokAktifUntukKelas(Kelas $kelas): string
    {
        if (! $kelas->is_sistem_blok || ! $kelas->blok_awal) {
            return 'reguler';
        }

        if ($kelas->blok_awal === 'split') {
            return 'split';
        }

        // 0-indexed offset, genap = blok_awal, ganjil = kelompok lainnya
        $isGanjilOffset = ($this->nomor_minggu - 1) % 2 !== 0;

        if (! $isGanjilOffset) {
            return $kelas->blok_awal;
        }

        return $kelas->blok_awal === 'kelompok_a' ? 'kelompok_b' : 'kelompok_a';
    }
}

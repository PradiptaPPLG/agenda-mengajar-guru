<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapKehadiranSiswa extends Model
{
    use HasFactory;

    protected $table = 'rekap_kehadiran_siswas';

    protected $fillable = [
        'siswa_id',
        'kelas_id',
        'tahun_ajaran',
        'semester',
        'total',
        'hadir',
        'hadir_murni',
        'terlambat',
        'sakit',
        'izin',
        'alpa',
        'dispensasi',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'total' => 'integer',
        'hadir' => 'integer',
        'hadir_murni' => 'integer',
        'terlambat' => 'integer',
        'sakit' => 'integer',
        'izin' => 'integer',
        'alpa' => 'integer',
        'dispensasi' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * Synchronize and recalculate single student statistics for a specific class, academic year, and semester.
     */
    public static function syncForSiswa(int $siswaId, int $kelasId, string $tahunAjaran, string $semester): void
    {
        $sem = in_array($semester, ['2', 'genap'], true) ? 'genap' : 'ganjil';
        $range = Setting::getPeriodeSemesterRange($tahunAjaran, $sem);
        $startDate = $range['start']->toDateString();
        $endDate = $range['end']->toDateString();

        $stats = KehadiranSiswa::where('siswa_id', $siswaId)
            ->whereHas('pertemuan', function ($q) use ($startDate, $endDate, $tahunAjaran, $semester, $sem, $kelasId) {
                $q->whereBetween('tanggal', [$startDate, $endDate])
                    ->where(fn ($sub) => $sub->where('tahun_ajaran', $tahunAjaran)->orWhereNull('tahun_ajaran'))
                    ->where(fn ($sub) => $sub->where('semester', $semester)->orWhere('semester', $sem)->orWhereNull('semester'))
                    ->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $kelasId));
            })
            ->selectRaw("
                count(*) as total,
                SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir_murni,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                SUM(CASE WHEN status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi
            ")
            ->first();

        $total = (int) ($stats?->total ?? 0);

        if ($total > 0) {
            static::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'kelas_id' => $kelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $sem,
                ],
                [
                    'total' => $total,
                    'hadir' => (int) $stats->hadir,
                    'hadir_murni' => (int) $stats->hadir_murni,
                    'terlambat' => (int) $stats->terlambat,
                    'sakit' => (int) $stats->sakit,
                    'izin' => (int) $stats->izin,
                    'alpa' => (int) $stats->alpa,
                    'dispensasi' => (int) $stats->dispensasi,
                ]
            );

            if ($semester !== $sem) {
                static::where([
                    'siswa_id' => $siswaId,
                    'kelas_id' => $kelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $semester,
                ])->delete();
            }
        } else {
            static::where([
                'siswa_id' => $siswaId,
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran,
            ])->whereIn('semester', [$semester, $sem])->delete();
        }
    }
}

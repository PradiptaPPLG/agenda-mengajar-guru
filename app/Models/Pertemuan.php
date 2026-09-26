<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PertemuanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pertemuan extends Model
{
    /** @use HasFactory<PertemuanFactory> */
    use HasFactory;

    protected $fillable = [
        'jadwal_id',
        'tanggal',
        'materi_ajar',
        'penugasan',
        'status',
        'tahun_ajaran',
        'semester',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $pertemuan) {
            if (empty($pertemuan->tahun_ajaran) || empty($pertemuan->semester)) {
                $jadwal = $pertemuan->jadwal;
                if ($jadwal && ! empty($jadwal->tahun_ajaran)) {
                    $pertemuan->tahun_ajaran = $pertemuan->tahun_ajaran ?: $jadwal->tahun_ajaran;
                    $pertemuan->semester = $pertemuan->semester ?: $jadwal->semester;
                } else {
                    $tgl = $pertemuan->tanggal ? Carbon::parse($pertemuan->tanggal) : Carbon::today();
                    $month = (int) $tgl->month;
                    $year = (int) $tgl->year;
                    if ($month >= 7) {
                        $pertemuan->semester = $pertemuan->semester ?: 'ganjil';
                        $pertemuan->tahun_ajaran = $pertemuan->tahun_ajaran ?: ($year.'/'.($year + 1));
                    } else {
                        $pertemuan->semester = $pertemuan->semester ?: 'genap';
                        $pertemuan->tahun_ajaran = $pertemuan->tahun_ajaran ?: (($year - 1).'/'.$year);
                    }
                }
            }
        });
    }

    /**
     * Scope untuk pertemuan pada tahun ajaran & semester tertentu.
     */
    public function scopePerSemester(Builder $query, ?string $tahunAjaran = null, ?string $semester = null): Builder
    {
        $ta = $tahunAjaran ?: Setting::getTahunAjaranAktif();
        $sem = $semester ?: Setting::getSemesterAktif();

        return $query->where('tahun_ajaran', $ta)->where('semester', $sem);
    }

    /** @var array<string, string> */
    protected $casts = [
        'tanggal' => 'date',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_id');
    }

    public function kehadiranGuru(): HasOne
    {
        return $this->hasOne(KehadiranGuru::class);
    }

    public function kehadiranSiswas(): HasMany
    {
        return $this->hasMany(KehadiranSiswa::class);
    }

    public function fotoBuktis(): HasMany
    {
        return $this->hasMany(FotoBukti::class);
    }
}

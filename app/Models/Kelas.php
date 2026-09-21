<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['nama', 'tingkat', 'tahun_ajaran', 'wali_kelas_id', 'bk_id', 'is_sistem_blok', 'model_rotasi', 'blok_awal'];

    protected $casts = [
        'is_sistem_blok' => 'boolean',
    ];

    /**
     * Tentukan kelompok blok aktif untuk kelas ini pada tanggal tertentu.
     * Mengembalikan 'kelompok_a', 'kelompok_b', atau null jika tidak sistem blok.
     */
    public function kelompokAktifHariIni(?Carbon $tanggal = null): ?string
    {
        if (! $this->is_sistem_blok) {
            return null;
        }

        $minggu = KalenderBlokMinggu::aktif($tanggal)->first();

        if (! $minggu) {
            return null;
        }

        return $minggu->kelompokAktifUntukKelas($this);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function guruBk(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bk_id');
    }

    public function siswaProfiles(): HasMany
    {
        return $this->hasMany(SiswaProfile::class);
    }

    public function mataPelajarans(): BelongsToMany
    {
        return $this->belongsToMany(MataPelajaran::class, 'kelas_mata_pelajaran')->withTimestamps();
    }

    public function jadwalPelajarans(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }
}

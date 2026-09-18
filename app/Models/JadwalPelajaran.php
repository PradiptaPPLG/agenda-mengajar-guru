<?php

namespace App\Models;

use Database\Factories\JadwalPelajaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalPelajaran extends Model
{
    /** @use HasFactory<JadwalPelajaranFactory> */
    use HasFactory;

    protected $fillable = [
        'kelas_id',
        'guru_id',
        'mata_pelajaran_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'hari' => 'integer',
    ];

    public static array $namaHari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    public function getNamaHariAttribute(): string
    {
        return self::$namaHari[$this->hari] ?? '-';
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function pertemuans(): HasMany
    {
        return $this->hasMany(Pertemuan::class, 'jadwal_id');
    }
}

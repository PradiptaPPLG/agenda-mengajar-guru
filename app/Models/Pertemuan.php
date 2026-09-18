<?php

namespace App\Models;

use Database\Factories\PertemuanFactory;
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
    ];

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

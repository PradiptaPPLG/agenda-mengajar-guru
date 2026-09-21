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
}

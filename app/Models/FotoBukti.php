<?php

namespace App\Models;

use Database\Factories\FotoBuktiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoBukti extends Model
{
    /** @use HasFactory<FotoBuktiFactory> */
    use HasFactory;

    protected $fillable = [
        'pertemuan_id',
        'siswa_id',
        'foto_path',
        'status_guru_dilaporkan',
        'alasan_tidak_hadir',
        'jenis_alpa_dilaporkan',
        'guru_pengganti_nama',
    ];

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}

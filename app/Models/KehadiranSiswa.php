<?php

namespace App\Models;

use Database\Factories\KehadiranSiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KehadiranSiswa extends Model
{
    /** @use HasFactory<KehadiranSiswaFactory> */
    use HasFactory;

    protected $fillable = [
        'pertemuan_id',
        'siswa_id',
        'status',
        'keterangan',
    ];

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'hadir' => 'Hadir',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'alpa' => 'Alpa',
            'dispensasi' => 'Dispensasi',
            default => ucfirst($this->status),
        };
    }
}

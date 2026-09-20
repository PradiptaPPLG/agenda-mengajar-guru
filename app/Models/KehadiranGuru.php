<?php

namespace App\Models;

use Database\Factories\KehadiranGuruFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KehadiranGuru extends Model
{
    /** @use HasFactory<KehadiranGuruFactory> */
    use HasFactory;

    protected $fillable = [
        'pertemuan_id',
        'guru_id',
        'status',
        'jenis_alpa',
        'guru_pengganti_nama',
        'keterangan',
        'waktu_hadir',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'waktu_hadir' => 'datetime',
    ];

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'hadir' => 'Hadir',
            'sakit' => 'Sakit',
            'alpa' => 'Alpa',
            default => ucfirst($this->status),
        };
    }

    public function getJenisAlpaLabelAttribute(): string
    {
        return match ($this->jenis_alpa) {
            'ada_tugas' => 'Ada Tugas',
            'tanpa_tugas' => 'Tanpa Tugas',
            'guru_pengganti' => 'Guru Pengganti',
            default => '-',
        };
    }

    public function getSiswaReportAttribute(): ?FotoBukti
    {
        return $this->pertemuan?->fotoBuktis?->first();
    }

    public function getHasDiscrepancyAttribute(): bool
    {
        $siswaReport = $this->siswa_report;
        if (! $siswaReport || ! $siswaReport->status_guru_dilaporkan) {
            return false;
        }

        return $this->status !== $siswaReport->status_guru_dilaporkan;
    }
}

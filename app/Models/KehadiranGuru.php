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
        'alasan_tidak_hadir',
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
            'terlambat' => 'Terlambat',
            'tidak_hadir' => 'Tidak Hadir',
            'sakit' => 'Sakit',
            'alpa' => 'Alpa',
            'dispensasi' => 'Dispensasi',
            default => ucfirst($this->status),
        };
    }

    public function getAlasanTidakHadirLabelAttribute(): ?string
    {
        return match ($this->alasan_tidak_hadir) {
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'rapat_dinas' => 'Rapat Dinas',
            'dinas_luar' => 'Dinas Luar',
            'tugas_luar' => 'Tugas Luar',
            'tanpa_keterangan' => 'Tanpa Keterangan',
            default => $this->alasan_tidak_hadir ? ucfirst($this->alasan_tidak_hadir) : null,
        };
    }

    public function isHadir(): bool
    {
        return $this->status === 'hadir';
    }

    public function isTerlambat(): bool
    {
        return $this->status === 'terlambat';
    }

    public function isTidakHadir(): bool
    {
        return in_array($this->status, ['tidak_hadir', 'sakit', 'alpa', 'dispensasi']);
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

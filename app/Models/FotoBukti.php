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
        'foto_checkout_path',
        'checkout_at',
        'status_guru_dilaporkan',
        'alasan_tidak_hadir',
        'jenis_alpa_dilaporkan',
        'guru_pengganti_nama',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'checkout_at' => 'datetime',
    ];

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path)) {
            return null;
        }

        return str_starts_with($this->foto_path, 'images/')
            ? asset($this->foto_path)
            : asset('storage/'.$this->foto_path);
    }

    public function getFotoCheckoutUrlAttribute(): ?string
    {
        if (empty($this->foto_checkout_path)) {
            return null;
        }

        return str_starts_with($this->foto_checkout_path, 'images/')
            ? asset($this->foto_checkout_path)
            : asset('storage/'.$this->foto_checkout_path);
    }
}

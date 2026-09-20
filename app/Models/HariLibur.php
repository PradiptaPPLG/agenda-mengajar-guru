<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'keterangan',
        'jenis',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

    public function setTanggalAttribute(mixed $value): void
    {
        $this->attributes['tanggal'] = $value instanceof Carbon
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Check if a specific date is a registered holiday.
     */
    public static function isLibur(Carbon|string $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return static::whereDate('tanggal', $dateStr)->exists();
    }

    /**
     * Get the holiday record for a specific date, if any.
     */
    public static function getLibur(Carbon|string $date): ?self
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return static::whereDate('tanggal', $dateStr)->first();
    }

    /**
     * Get a human-readable badge label for the holiday type.
     */
    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'nasional' => 'Libur Nasional',
            'cuti_bersama' => 'Cuti Bersama',
            'khusus' => 'Agenda Khusus',
            default => 'Libur',
        };
    }
}

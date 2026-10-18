<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    protected static function booted(): void
    {
        static::saved(function () {
            static::flushCache();
        });

        static::deleted(function () {
            static::flushCache();
        });
    }

    /**
     * Clear holiday caches.
     */
    public static function flushCache(): void
    {
        $current = (int) Cache::get('hari_libur_cache_v', 1);
        Cache::forever('hari_libur_cache_v', $current + 1);
    }

    protected static function getCacheVersion(): int
    {
        return (int) Cache::get('hari_libur_cache_v', 1);
    }

    /**
     * Check if a specific date is a registered holiday with caching.
     */
    public static function isLibur(Carbon|string $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $v = static::getCacheVersion();

        return (bool) Cache::remember(
            "hari_libur_is_{$v}_{$dateStr}",
            86400,
            fn () => static::whereDate('tanggal', $dateStr)->exists()
        );
    }

    /**
     * Get the holiday record for a specific date with caching.
     */
    public static function getLibur(Carbon|string $date): ?self
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $v = static::getCacheVersion();

        return Cache::remember(
            "hari_libur_get_{$v}_{$dateStr}",
            86400,
            fn () => static::whereDate('tanggal', $dateStr)->first()
        );
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

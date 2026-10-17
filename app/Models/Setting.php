<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            static::clearCache($setting->key);
        });

        static::deleted(function (Setting $setting) {
            static::clearCache($setting->key);
        });
    }

    /**
     * Clear all cached settings or a specific key.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget("setting:{$key}");
        }
        Cache::forget('setting:tahun_ajaran_aktif');
        Cache::forget('setting:semester_aktif');
        Cache::forget('setting:daftar_tahun_ajaran');
    }

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting:{$key}", function () use ($key) {
            return static::where('key', $key)->value('value') ?? '__NOT_SET__';
        });

        if ($value === '__NOT_SET__') {
            return $default;
        }

        return $value ?? $default;
    }

    /**
     * Set a setting value and invalidate cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        if ($key === 'tahun_ajaran_aktif') {
            static::updateOrCreate(['key' => 'school_year'], ['value' => $value]);
        } elseif ($key === 'school_year') {
            static::updateOrCreate(['key' => 'tahun_ajaran_aktif'], ['value' => $value]);
        } elseif ($key === 'semester_aktif') {
            static::updateOrCreate(['key' => 'semester'], ['value' => $value]);
        } elseif ($key === 'semester') {
            static::updateOrCreate(['key' => 'semester_aktif'], ['value' => $value]);
        }

        static::clearCache($key);
    }

    /**
     * Dapatkan tahun ajaran aktif saat ini (contoh: "2026/2027").
     */
    public static function getTahunAjaranAktif(): string
    {
        return Cache::rememberForever('setting:tahun_ajaran_aktif', function () {
            $now = Carbon::now();
            $year = $now->year;
            $default = $now->month >= 7 ? "{$year}/".($year + 1) : ($year - 1)."/{$year}";

            $val = static::get('tahun_ajaran_aktif') ?? static::get('school_year');

            return (string) ($val ?: $default);
        });
    }

    /**
     * Dapatkan semester aktif saat ini: 'ganjil' atau 'genap'.
     */
    public static function getSemesterAktif(): string
    {
        return Cache::rememberForever('setting:semester_aktif', function () {
            $sem = (string) (static::get('semester_aktif') ?? static::get('semester', '1'));

            return in_array($sem, ['2', 'genap'], true) ? 'genap' : 'ganjil';
        });
    }

    /**
     * Dapatkan nomor semester aktif (1 atau 2).
     */
    public static function getSemesterNumber(): int
    {
        return static::getSemesterAktif() === 'genap' ? 2 : 1;
    }

    /**
     * Dapatkan label representatif semester (misal: "Semester Ganjil (1)").
     */
    public static function getSemesterLabel(?string $semester = null): string
    {
        $sem = $semester ?? static::getSemesterAktif();

        return in_array($sem, ['genap', '2'], true) ? 'Semester Genap (2)' : 'Semester Ganjil (1)';
    }

    /**
     * Dapatkan daftar pilihan semester.
     *
     * @return array<string, string>
     */
    public static function getDaftarSemester(): array
    {
        return [
            'ganjil' => 'Semester Ganjil (1)',
            'genap' => 'Semester Genap (2)',
        ];
    }

    /**
     * Dapatkan daftar tahun ajaran yang pernah ada dalam sistem.
     *
     * @return array<int, string>
     */
    public static function getDaftarTahunAjaran(): array
    {
        return Cache::remember('setting:daftar_tahun_ajaran', 3600, function () {
            $fromJadwal = JadwalPelajaran::distinct()->pluck('tahun_ajaran')->filter()->all();
            $fromKelas = Kelas::distinct()->pluck('tahun_ajaran')->filter()->all();
            $current = static::getTahunAjaranAktif();

            $all = array_unique(array_merge([$current], $fromJadwal, $fromKelas));
            rsort($all);

            return array_values($all);
        });
    }

    /**
     * Dapatkan rentang tanggal default untuk tahun ajaran & semester tertentu.
     * Ganjil: 1 Juli s/d 31 Desember
     * Genap: 1 Januari s/d 30 Juni
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public static function getPeriodeSemesterRange(?string $tahunAjaran = null, ?string $semester = null): array
    {
        $ta = $tahunAjaran ?: static::getTahunAjaranAktif();
        $sem = $semester ?: static::getSemesterAktif();

        $parts = explode('/', $ta);
        $tahunAwal = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : (int) date('Y');
        $tahunAkhir = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : $tahunAwal + 1;

        if (in_array($sem, ['genap', '2'], true)) {
            return [
                'start' => Carbon::createFromDate($tahunAkhir, 1, 1)->startOfDay(),
                'end' => Carbon::createFromDate($tahunAkhir, 6, 30)->endOfDay(),
            ];
        }

        return [
            'start' => Carbon::createFromDate($tahunAwal, 7, 1)->startOfDay(),
            'end' => Carbon::createFromDate($tahunAwal, 12, 31)->endOfDay(),
        ];
    }

    /**
     * Dapatkan status default akun siswa ('aktif' atau 'nonaktif').
     */
    public static function getDefaultSiswaStatus(): string
    {
        return (string) static::get('default_siswa_status', 'aktif');
    }

    /**
     * Apakah akun siswa default-nya aktif secara boolean.
     */
    public static function isDefaultSiswaActive(): bool
    {
        return static::getDefaultSiswaStatus() === 'aktif';
    }
}

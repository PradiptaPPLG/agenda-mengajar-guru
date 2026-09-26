<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Dapatkan tahun ajaran aktif saat ini (contoh: "2026/2027").
     */
    public static function getTahunAjaranAktif(): string
    {
        return (string) static::get('school_year', '2026/2027');
    }

    /**
     * Dapatkan semester aktif saat ini: 'ganjil' atau 'genap'.
     */
    public static function getSemesterAktif(): string
    {
        $sem = (string) static::get('semester', '1');

        return in_array($sem, ['2', 'genap'], true) ? 'genap' : 'ganjil';
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
        $fromJadwal = JadwalPelajaran::distinct()->pluck('tahun_ajaran')->filter()->all();
        $fromKelas = Kelas::distinct()->pluck('tahun_ajaran')->filter()->all();
        $current = static::getTahunAjaranAktif();

        $all = array_unique(array_merge([$current], $fromJadwal, $fromKelas));
        rsort($all);

        return array_values($all);
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
}

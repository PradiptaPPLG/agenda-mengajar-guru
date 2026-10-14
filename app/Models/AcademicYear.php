<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_ajaran',
        'semester',
        'is_active',
        'is_locked',
        'keterangan',
    ];

    protected $casts = [
        'semester' => 'integer',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function getSemesterLabelAttribute(): string
    {
        return $this->semester === 1 ? 'Ganjil' : 'Genap';
    }

    public function getFullPeriodLabelAttribute(): string
    {
        return "{$this->tahun_ajaran} - Semester {$this->semester_label}";
    }

    public static function getActive(): ?self
    {
        $active = self::where('is_active', true)->first();

        if (! $active && self::count() === 0) {
            $schoolYear = Setting::get('school_year', '2024/2025');
            $semester = (int) Setting::get('semester', 1);

            return self::create([
                'tahun_ajaran' => $schoolYear,
                'semester' => $semester,
                'is_active' => true,
                'is_locked' => false,
                'keterangan' => 'Periode Akademik Default Sistem',
            ]);
        }

        return $active;
    }
}

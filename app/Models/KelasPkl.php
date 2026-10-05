<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class KelasPkl extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kelas_pkls';

    protected $fillable = [
        'kelas_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'is_aktif',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'is_aktif' => 'boolean',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Scope periode yang aktif (tidak dinonaktifkan manual).
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    /**
     * Scope periode PKL yang sedang berlangsung pada tanggal tertentu (default: hari ini).
     */
    public function scopeSedangBerlangsung(Builder $query, Carbon|string|null $date = null): Builder
    {
        $dateStr = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString());

        return $query->where('is_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $dateStr)
            ->whereDate('tanggal_selesai', '>=', $dateStr);
    }

    /**
     * Scope periode PKL yang akan datang.
     */
    public function scopeAkanDatang(Builder $query, Carbon|string|null $date = null): Builder
    {
        $dateStr = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString());

        return $query->where('is_aktif', true)
            ->whereDate('tanggal_mulai', '>', $dateStr);
    }

    /**
     * Scope periode PKL yang telah selesai.
     */
    public function scopeSelesai(Builder $query, Carbon|string|null $date = null): Builder
    {
        $dateStr = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString());

        return $query->where('is_aktif', true)
            ->whereDate('tanggal_selesai', '<', $dateStr);
    }

    /**
     * Dapatkan daftar ID kelas yang sedang berstatus PKL aktif pada tanggal tertentu.
     *
     * @return Collection<int, int>
     */
    public static function getActivePklKelasIds(Carbon|string|null $date = null): Collection
    {
        $dateStr = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString());

        return static::query()
            ->where('is_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $dateStr)
            ->whereDate('tanggal_selesai', '>=', $dateStr)
            ->pluck('kelas_id')
            ->unique()
            ->values();
    }

    /**
     * Cek apakah suatu kelas sedang PKL pada tanggal tertentu.
     */
    public static function isKelasPkl(int $kelasId, Carbon|string|null $date = null): bool
    {
        $dateStr = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString());

        return static::query()
            ->where('kelas_id', $kelasId)
            ->where('is_aktif', true)
            ->whereDate('tanggal_mulai', '<=', $dateStr)
            ->whereDate('tanggal_selesai', '>=', $dateStr)
            ->exists();
    }

    /**
     * Label dan styling badge status periode.
     *
     * @return array{label: string, class: string}
     */
    public function getStatusBadgeAttribute(): array
    {
        if (! $this->is_aktif) {
            return [
                'label' => 'Nonaktif',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            ];
        }

        $today = Carbon::today()->toDateString();
        $mulai = $this->tanggal_mulai?->toDateString();
        $selesai = $this->tanggal_selesai?->toDateString();

        if ($mulai && $today < $mulai) {
            return [
                'label' => 'Akan Datang',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
        }

        if ($selesai && $today > $selesai) {
            return [
                'label' => 'Selesai',
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            ];
        }

        return [
            'label' => 'Sedang Berlangsung',
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ];
    }
}

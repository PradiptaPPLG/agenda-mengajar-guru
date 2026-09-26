<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\JadwalPelajaranFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class JadwalPelajaran extends Model
{
    /** @use HasFactory<JadwalPelajaranFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kelas_id',
        'guru_id',
        'mata_pelajaran_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'kelompok_blok',
        'tahun_ajaran',
        'semester',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'hari' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $jadwal) {
            if (empty($jadwal->tahun_ajaran)) {
                $jadwal->tahun_ajaran = $jadwal->kelas?->tahun_ajaran ?: Setting::getTahunAjaranAktif();
            }
            if (empty($jadwal->semester)) {
                $jadwal->semester = Setting::getSemesterAktif();
            }
        });
    }

    /**
     * Scope untuk jadwal pada tahun ajaran & semester tertentu.
     */
    public function scopePerSemester(Builder $query, ?string $tahunAjaran = null, ?string $semester = null): Builder
    {
        $ta = $tahunAjaran ?: Setting::getTahunAjaranAktif();
        $sem = $semester ?: Setting::getSemesterAktif();

        return $query->where('tahun_ajaran', $ta)->where('semester', $sem);
    }

    public static array $namaHari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    public function getNamaHariAttribute(): string
    {
        return self::$namaHari[$this->hari] ?? '-';
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function pertemuans(): HasMany
    {
        return $this->hasMany(Pertemuan::class, 'jadwal_id');
    }

    /**
     * Dapatkan semua jadwal yang berada dalam satu sesi bersambung/berurutan
     * dengan jadwal ini pada hari yang sama (misal 3 JP berturut-turut di kelas yang sama).
     *
     * @return Collection<int, JadwalPelajaran>
     */
    public function getConsecutiveSchedules(): Collection
    {
        $query = self::where('guru_id', $this->guru_id)
            ->where('kelas_id', $this->kelas_id)
            ->where('hari', $this->hari);

        if (! empty($this->tahun_ajaran)) {
            $query->where('tahun_ajaran', $this->tahun_ajaran);
        }

        if (! empty($this->semester)) {
            $query->where('semester', $this->semester);
        }

        // Kelompok blok check
        if (empty($this->kelompok_blok) || $this->kelompok_blok === 'reguler') {
            $query->where(fn ($q) => $q->whereNull('kelompok_blok')->orWhere('kelompok_blok', 'reguler'));
        } else {
            $query->where('kelompok_blok', $this->kelompok_blok);
        }

        $siblings = $query->with(['kelas', 'mataPelajaran', 'guru'])
            ->orderBy('jam_mulai')
            ->get();

        $thisMapelNorm = str_replace(' ', '', strtoupper($this->mataPelajaran?->nama ?? ''));

        // Filter jadwal yang mapelnya sama
        $sameMapels = $siblings->filter(function ($item) use ($thisMapelNorm) {
            $itemMapelNorm = str_replace(' ', '', strtoupper($item->mataPelajaran?->nama ?? ''));

            return $item->mata_pelajaran_id === $this->mata_pelajaran_id || ($thisMapelNorm !== '' && $thisMapelNorm === $itemMapelNorm);
        })->values();

        if ($sameMapels->isEmpty()) {
            return collect([$this]);
        }

        // Kelompokkan ke dalam blok-blok bersambung
        $blocks = [];
        $currentBlock = collect();

        foreach ($sameMapels as $item) {
            if ($currentBlock->isEmpty()) {
                $currentBlock->push($item);
            } else {
                $prev = $currentBlock->last();
                $prevEnd = Carbon::parse($prev->jam_selesai);
                $currStart = Carbon::parse($item->jam_mulai);
                $diffMinutes = $currStart->diffInMinutes($prevEnd, false);

                // Jika bersambung atau jeda istirahat <= 75 menit (toleransi jeda/istirahat KBM)
                if ($diffMinutes >= -15 && $diffMinutes <= 75) {
                    $currentBlock->push($item);
                } else {
                    $blocks[] = $currentBlock;
                    $currentBlock = collect([$item]);
                }
            }
        }
        if ($currentBlock->isNotEmpty()) {
            $blocks[] = $currentBlock;
        }

        // Cari blok yang mengandung $this->id
        foreach ($blocks as $block) {
            if ($block->contains('id', $this->id)) {
                return $block;
            }
        }

        return collect([$this]);
    }

    /**
     * Mengelompokkan koleksi jadwal pelajaran yang berurutan dalam hari yang sama
     * menjadi item sesi gabungan untuk tampilan antarmuka.
     */
    public static function groupContinuousSchedules(Collection $jadwals): Collection
    {
        if ($jadwals->isEmpty()) {
            return collect();
        }

        $sorted = $jadwals->sortBy('jam_mulai')->values();
        $grouped = collect();
        $currentGroup = collect();

        foreach ($sorted as $item) {
            if ($currentGroup->isEmpty()) {
                $currentGroup->push($item);
            } else {
                $prev = $currentGroup->last();
                $prevNorm = str_replace(' ', '', strtoupper($prev->mataPelajaran?->nama ?? ''));
                $itemNorm = str_replace(' ', '', strtoupper($item->mataPelajaran?->nama ?? ''));

                $sameClass = $item->kelas_id === $prev->kelas_id;
                $sameGuru = $item->guru_id === $prev->guru_id;
                $sameBlok = $item->kelompok_blok === $prev->kelompok_blok;
                $sameMapel = ($item->mata_pelajaran_id === $prev->mata_pelajaran_id) || ($prevNorm !== '' && $prevNorm === $itemNorm);

                $prevEnd = Carbon::parse($prev->jam_selesai);
                $currStart = Carbon::parse($item->jam_mulai);
                $diffMinutes = $currStart->diffInMinutes($prevEnd, false);

                if ($sameClass && $sameGuru && $sameBlok && $sameMapel && $diffMinutes <= 60 && $currStart >= $prevEnd) {
                    $currentGroup->push($item);
                } else {
                    $grouped->push(static::formatGroupedSession($currentGroup));
                    $currentGroup = collect([$item]);
                }
            }
        }

        if ($currentGroup->isNotEmpty()) {
            $grouped->push(static::formatGroupedSession($currentGroup));
        }

        return $grouped;
    }

    protected static function formatGroupedSession(Collection $group): self
    {
        $first = $group->first();
        $last = $group->last();
        $count = $group->count();

        $session = clone $first;
        $session->sub_jadwals = $group;
        $session->sub_jadwal_ids = $group->pluck('id')->all();
        $session->total_jp = $count;
        $session->is_multi_jam = ($count > 1);
        $session->jam_mulai_formatted = substr($first->jam_mulai, 0, 5);
        $session->jam_selesai_formatted = substr($last->jam_selesai, 0, 5);

        return $session;
    }
}

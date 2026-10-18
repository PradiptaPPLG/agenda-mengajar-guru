<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumumans';

    protected $fillable = [
        'judul',
        'isi',
        'tipe',
        'target_roles',
        'target_tingkat',
        'file_lampiran',
        'nama_file',
        'is_pinned',
        'is_active',
        'tanggal_mulai',
        'tanggal_selesai',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'target_roles' => 'array',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PengumumanRead::class, 'pengumuman_id');
    }

    /**
     * Check if a specific user has marked this announcement as read.
     */
    public function isReadBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Scope for currently active and scheduled announcements.
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('tanggal_mulai', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', $now);
            });
    }

    /**
     * Scope announcements matching a specific user's role and education level.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $role = $user->role;
        $userTingkat = $user->siswaProfile?->kelas?->tingkat ?? null;
        $isBk = method_exists($user, 'isGuruBk') && $user->isGuruBk();

        return $query->where(function ($q) use ($role, $isBk) {
            $q->whereJsonContains('target_roles', 'all')
                ->orWhereJsonContains('target_roles', $role)
                ->orWhereNull('target_roles');

            if ($isBk) {
                $q->orWhereJsonContains('target_roles', 'bk');
            }
        })->when($role === 'siswa', function ($q) use ($userTingkat) {
            $q->where(function ($sub) use ($userTingkat) {
                $sub->whereNull('target_tingkat')
                    ->orWhere('target_tingkat', 'all');

                if ($userTingkat) {
                    $sub->orWhere('target_tingkat', (string) $userTingkat);
                }
            });
        });
    }

    /**
     * Scope announcements that haven't been marked read by the given user.
     */
    public function scopeUnreadBy(Builder $query, User $user): Builder
    {
        return $query->whereDoesntHave('reads', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    /**
     * Get badge color classes for urgency type.
     */
    public function getTipeBadgeClassAttribute(): string
    {
        return match ($this->tipe) {
            'urgent' => 'bg-rose-50 text-rose-700 border-rose-200',
            'penting' => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-blue-50 text-blue-700 border-blue-200',
        };
    }
}

<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }

    public function isKepalaSekolah(): bool
    {
        return $this->role === 'kepala_sekolah';
    }

    public function isPiket(): bool
    {
        return $this->role === 'piket';
    }

    public function guruProfile(): HasOne
    {
        return $this->hasOne(GuruProfile::class);
    }

    public function mapels(): BelongsToMany
    {
        return $this->belongsToMany(MataPelajaran::class, 'guru_mata_pelajaran', 'user_id', 'mata_pelajaran_id')->withTimestamps();
    }

    public function siswaProfile(): HasOne
    {
        return $this->hasOne(SiswaProfile::class);
    }

    public function jadwalPelajarans(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'guru_id');
    }

    public function kehadiranGurus(): HasMany
    {
        return $this->hasMany(KehadiranGuru::class, 'guru_id');
    }

    public function kehadiranSiswas(): HasMany
    {
        return $this->hasMany(KehadiranSiswa::class, 'siswa_id');
    }

    public function fotoBuktis(): HasMany
    {
        return $this->hasMany(FotoBukti::class, 'siswa_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'kepala_sekolah' => 'Kepala Sekolah',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
            'piket' => 'Guru Piket',
            default => ucfirst($this->role),
        };
    }

    public static function findMatchingGuru(string $name, ?string $nip = null, ?string $email = null): ?self
    {
        $cleanName = trim(preg_replace('/\s+/', ' ', $name));

        if ($cleanName !== '') {
            // 1. Exact case-insensitive match
            $user = self::withTrashed()->where('role', 'guru')->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($cleanName)])->first();
            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                return $user;
            }

            // 2. Alphanumeric normalized match (ignoring dots, commas, spaces, dashes)
            $targetNorm = preg_replace('/[^a-z0-9]/', '', strtolower($cleanName));
            if (strlen($targetNorm) >= 3) {
                $allGurus = self::withTrashed()->where('role', 'guru')->get();
                foreach ($allGurus as $cand) {
                    if (preg_replace('/[^a-z0-9]/', '', strtolower($cand->name)) === $targetNorm) {
                        if ($cand->trashed()) {
                            $cand->restore();
                        }

                        return $cand;
                    }
                }

                // 3. Match without honorifics and academic titles
                $titlePatterns = [
                    '/\b(drs|dra|dr|prof|ir|h|hj|kh)\b\.?/i',
                    '/\b(s\.?pd|m\.?pd|s\.?ag|m\.?ag|s\.?t|m\.?t|s\.?kom|m\.?kom|s\.?e|m\.?m|s\.?si|m\.?si|s\.?sos|s\.?par|s\.?pd\.?i|m\.?pd\.?i|a\.?md\.?par)\b\.?/i',
                ];
                $targetStripped = trim(preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace($titlePatterns, '', $cleanName))));
                if (strlen($targetStripped) >= 4) {
                    $matchedCandidates = [];
                    foreach ($allGurus as $cand) {
                        $candStripped = trim(preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace($titlePatterns, '', $cand->name))));
                        if ($candStripped === $targetStripped) {
                            $matchedCandidates[] = $cand;
                        }
                    }
                    if (count($matchedCandidates) === 1) {
                        $cand = $matchedCandidates[0];
                        if ($cand->trashed()) {
                            $cand->restore();
                        }

                        return $cand;
                    }
                }
            }
        }

        // 4. Match by Email if provided
        if ($email && trim($email) !== '') {
            $user = self::withTrashed()->where('role', 'guru')->where('email', trim($email))->first();
            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                return $user;
            }
        }

        // 5. Match by NIP if provided, but ONLY if stripped name matches or cleanName is blank
        if ($nip && $nip !== '-' && trim($nip) !== '') {
            $profile = GuruProfile::where('nip', trim($nip))->first();
            if ($profile && $profile->user) {
                $cand = $profile->user;
                if ($cleanName === '') {
                    if ($cand->trashed()) {
                        $cand->restore();
                    }

                    return $cand;
                }

                $titlePatterns = [
                    '/\b(drs|dra|dr|prof|ir|h|hj|kh)\b\.?/i',
                    '/\b(s\.?pd|m\.?pd|s\.?ag|m\.?ag|s\.?t|m\.?t|s\.?kom|m\.?kom|s\.?e|m\.?m|s\.?si|m\.?si|s\.?sos|s\.?par|s\.?pd\.?i|m\.?pd\.?i|a\.?md\.?par)\b\.?/i',
                ];
                $candStripped = trim(preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace($titlePatterns, '', $cand->name))));
                $targetStripped = trim(preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace($titlePatterns, '', $cleanName))));

                if ($candStripped !== '' && $candStripped === $targetStripped) {
                    if ($cand->trashed()) {
                        $cand->restore();
                    }

                    return $cand;
                }
            }
        }

        return null;
    }
}

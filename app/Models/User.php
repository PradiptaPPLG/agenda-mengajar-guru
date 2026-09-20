<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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

    public function guruProfile(): HasOne
    {
        return $this->hasOne(GuruProfile::class);
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
            default => ucfirst($this->role),
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'role',
        'action',
        'module',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryAttribute(): string
    {
        $module = is_string($this->module) ? $this->module : '';
        $action = is_string($this->action) ? strtoupper($this->action) : '';

        if (in_array($action, ['LOGIN', 'IMPERSONATE']) || in_array($module, ['Auth', 'Pengguna', 'Role', 'Permission', 'User'])) {
            return 'keamanan';
        }

        if (in_array($module, ['Jadwal', 'Agenda Mengajar', 'Pertemuan', 'Hari Libur', 'Kalender Blok', 'Pemetaan Blok', 'Mata Pelajaran', 'Guru'])) {
            return 'akademik';
        }

        if (in_array($module, ['Siswa', 'Kelas', 'Kenaikan Kelas', 'Pengumuman', 'Teguran', 'Presensi'])) {
            return 'kesiswaan';
        }

        return 'sistem';
    }

    /**
     * @return array{label: string, badge: string, dot: string}
     */
    public function getCategoryMetaAttribute(): array
    {
        return match ($this->category) {
            'keamanan' => [
                'label' => 'Keamanan & Akun',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                'dot' => 'bg-blue-500',
            ],
            'akademik' => [
                'label' => 'Akademik & KBM',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500',
            ],
            'kesiswaan' => [
                'label' => 'Kesiswaan & Presensi',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot' => 'bg-amber-500',
            ],
            default => [
                'label' => 'Pengaturan & Sistem',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
                'dot' => 'bg-purple-500',
            ],
        };
    }
}

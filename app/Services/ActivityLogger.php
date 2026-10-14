<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Catat log aktivitas ke database.
     *
     * @param  string  $action  CREATE | UPDATE | DELETE | LOGIN | IMPERSONATE | RESTORE
     * @param  string  $module  Modul sistem yang diubah
     * @param  string  $description  Keterangan aktivitas yang ramah dibaca
     * @param  array|null  $oldValues  Data sebelum diubah
     * @param  array|null  $newValues  Data sesudah diubah
     * @param  int|null  $userId  User ID override (opsional)
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): ?ActivityLog {
        try {
            $user = Auth::user();
            $effectiveUserId = $userId ?? $user?->id;
            $userName = $user ? $user->name : 'Sistem';
            $userRole = $user ? $user->role : 'system';

            return ActivityLog::create([
                'user_id' => $effectiveUserId,
                'user_name' => $userName,
                'role' => $userRole,
                'action' => strtoupper($action),
                'module' => $module,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai kegagalan logging menghentikan alur aplikasi utama
            report($e);

            return null;
        }
    }
}

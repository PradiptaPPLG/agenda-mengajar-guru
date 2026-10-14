<?php

namespace App\Observers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class AuditTrailObserver
{
    private function resolveModuleName(Model $model): string
    {
        return match (get_class($model)) {
            JadwalPelajaran::class => 'Jadwal',
            Kelas::class => 'Kelas',
            Pertemuan::class => 'Agenda Mengajar',
            User::class => match ($model->role ?? null) {
                'guru' => 'Guru',
                'siswa' => 'Siswa',
                default => 'Pengguna',
            },
            default => class_basename($model),
        };
    }

    private function resolveDescription(Model $model, string $action): string
    {
        $name = $model->name ?? $model->nama ?? ('ID #'.$model->id);
        $module = $this->resolveModuleName($model);

        return match ($action) {
            'CREATE' => "Menambahkan data {$module} baru: {$name}",
            'UPDATE' => "Memperbarui data {$module}: {$name}",
            'DELETE' => "Menghapus data {$module}: {$name}",
            'RESTORE' => "Memulihkan data {$module}: {$name}",
            default => "Melakukan aksi {$action} pada {$module}: {$name}",
        };
    }

    public function created(Model $model): void
    {
        $module = $this->resolveModuleName($model);
        $desc = $this->resolveDescription($model, 'CREATE');
        $new = $model->attributesToArray();
        unset($new['password'], $new['remember_token']);

        ActivityLogger::log('CREATE', $module, $desc, null, $new);
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        unset($dirty['updated_at'], $dirty['password'], $dirty['remember_token']);

        if (empty($dirty)) {
            return;
        }

        $old = [];
        $new = [];
        foreach ($dirty as $key => $val) {
            $old[$key] = $model->getOriginal($key);
            $new[$key] = $val;
        }

        $module = $this->resolveModuleName($model);
        $desc = $this->resolveDescription($model, 'UPDATE');

        ActivityLogger::log('UPDATE', $module, $desc, $old, $new);
    }

    public function deleted(Model $model): void
    {
        $module = $this->resolveModuleName($model);
        $desc = $this->resolveDescription($model, 'DELETE');
        $old = $model->attributesToArray();
        unset($old['password'], $old['remember_token']);

        ActivityLogger::log('DELETE', $module, $desc, $old, null);
    }

    public function restored(Model $model): void
    {
        $module = $this->resolveModuleName($model);
        $desc = $this->resolveDescription($model, 'RESTORE');

        ActivityLogger::log('RESTORE', $module, $desc);
    }
}

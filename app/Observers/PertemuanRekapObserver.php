<?php

namespace App\Observers;

use App\Models\Pertemuan;
use App\Models\RekapKehadiranSiswa;
use App\Models\Setting;

class PertemuanRekapObserver
{
    /**
     * Cache student IDs during force deleting before database cascade removes child rows.
     *
     * @var array<int, array<int>>
     */
    protected array $forceDeletingSiswaIds = [];

    /**
     * Handle the Pertemuan "deleted" event (soft-delete).
     */
    public function deleted(Pertemuan $pertemuan): void
    {
        $this->syncStudents($pertemuan);
    }

    /**
     * Handle the Pertemuan "restored" event.
     */
    public function restored(Pertemuan $pertemuan): void
    {
        $this->syncStudents($pertemuan);
    }

    /**
     * Handle the Pertemuan "forceDeleting" event.
     */
    public function forceDeleting(Pertemuan $pertemuan): void
    {
        $this->forceDeletingSiswaIds[$pertemuan->id] = $pertemuan->kehadiranSiswas()->pluck('siswa_id')->unique()->all();
    }

    /**
     * Handle the Pertemuan "forceDeleted" event.
     */
    public function forceDeleted(Pertemuan $pertemuan): void
    {
        $siswaIds = $this->forceDeletingSiswaIds[$pertemuan->id] ?? $pertemuan->kehadiranSiswas()->pluck('siswa_id')->unique()->all();
        unset($this->forceDeletingSiswaIds[$pertemuan->id]);
        $this->syncStudentsWithIds($pertemuan, $siswaIds);
    }

    /**
     * Synchronize all students attached to this meeting.
     */
    protected function syncStudents(Pertemuan $pertemuan): void
    {
        $siswaIds = $pertemuan->kehadiranSiswas()->pluck('siswa_id')->unique()->all();
        $this->syncStudentsWithIds($pertemuan, $siswaIds);
    }

    /**
     * Recalculate summary stats for the specified list of student IDs.
     *
     * @param  array<int>  $siswaIds
     */
    protected function syncStudentsWithIds(Pertemuan $pertemuan, array $siswaIds): void
    {
        if (empty($siswaIds)) {
            return;
        }

        $jadwal = $pertemuan->jadwal()->withTrashed()->first();
        $kelasId = $jadwal?->kelas_id;
        if (! $kelasId) {
            return;
        }

        $tahunAjaran = $pertemuan->tahun_ajaran ?: ($jadwal?->tahun_ajaran ?: Setting::getTahunAjaranAktif());
        $semester = $pertemuan->semester ?: ($jadwal?->semester ?: Setting::getSemesterAktif());

        foreach ($siswaIds as $siswaId) {
            RekapKehadiranSiswa::syncForSiswa(
                (int) $siswaId,
                (int) $kelasId,
                (string) $tahunAjaran,
                (string) $semester
            );
        }
    }
}

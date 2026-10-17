<?php

namespace App\Observers;

use App\Models\KehadiranSiswa;
use App\Models\RekapKehadiranSiswa;
use App\Models\Setting;

class KehadiranSiswaObserver
{
    /**
     * Handle the KehadiranSiswa "saved" event.
     */
    public function saved(KehadiranSiswa $kehadiranSiswa): void
    {
        if ($kehadiranSiswa->wasRecentlyCreated || $kehadiranSiswa->wasChanged('status')) {
            $this->sync($kehadiranSiswa);
        }
    }

    /**
     * Handle the KehadiranSiswa "deleted" event.
     */
    public function deleted(KehadiranSiswa $kehadiranSiswa): void
    {
        $this->sync($kehadiranSiswa);
    }

    /**
     * Trigger scoped sync for this student.
     */
    protected function sync(KehadiranSiswa $kehadiranSiswa): void
    {
        $pertemuan = $kehadiranSiswa->pertemuan()->withTrashed()->first();
        if (! $pertemuan) {
            return;
        }

        $jadwal = $pertemuan->jadwal()->withTrashed()->first();
        $kelasId = $jadwal?->kelas_id;
        if (! $kelasId) {
            return;
        }

        $tahunAjaran = $pertemuan->tahun_ajaran ?: ($jadwal?->tahun_ajaran ?: Setting::getTahunAjaranAktif());
        $semester = $pertemuan->semester ?: ($jadwal?->semester ?: Setting::getSemesterAktif());

        RekapKehadiranSiswa::syncForSiswa(
            (int) $kehadiranSiswa->siswa_id,
            (int) $kelasId,
            (string) $tahunAjaran,
            (string) $semester
        );
    }
}

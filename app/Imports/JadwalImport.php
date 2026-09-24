<?php

namespace App\Imports;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JadwalImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Find relations by name
            $kelas = Kelas::where('nama', trim($row['kelas']))->first();
            $guru = User::where('role', 'guru')->where('name', trim($row['guru']))->first();
            $mapel = MataPelajaran::where('nama', trim($row['mata_pelajaran']))->first();

            if ($kelas && $guru && $mapel) {
                JadwalPelajaran::updateOrCreate(
                    [
                        'kelas_id' => $kelas->id,
                        'hari' => (int) $row['hari'],
                        'jam_mulai' => trim($row['jam_mulai']),
                        'jam_selesai' => trim($row['jam_selesai']),
                    ],
                    [
                        'guru_id' => $guru->id,
                        'mata_pelajaran_id' => $mapel->id,
                    ]
                );
            }
        }
    }
}

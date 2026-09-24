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
            $kelasName = trim($row['kelas'] ?? '');
            $guruName = trim($row['guru'] ?? '');
            $mapelName = trim($row['mata_pelajaran'] ?? $row['mapel'] ?? '');
            
            // Parsing Hari
            $hariVal = $row['hari'] ?? '';
            if (is_numeric($hariVal)) {
                $hari = (int) $hariVal;
            } else {
                $hariMap = [
                    'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7
                ];
                $hari = $hariMap[strtolower(trim($hariVal))] ?? 1;
            }

            // Parsing Waktu
            $waktu = trim($row['waktu'] ?? '');
            if ($waktu && strpos($waktu, '-') !== false) {
                [$jam_mulai, $jam_selesai] = array_map('trim', explode('-', $waktu));
            } else {
                $jam_mulai = trim($row['jam_mulai'] ?? '');
                $jam_selesai = trim($row['jam_selesai'] ?? '');
            }

            // Find relations by name
            $kelas = Kelas::where('nama', $kelasName)->first();
            $guru = User::where('role', 'guru')->where('name', $guruName)->first();
            $mapel = MataPelajaran::where('nama', $mapelName)->first();

            if ($kelas && $guru && $mapel && $jam_mulai && $jam_selesai) {
                JadwalPelajaran::updateOrCreate(
                    [
                        'kelas_id' => $kelas->id,
                        'hari' => $hari,
                        'jam_mulai' => $jam_mulai,
                        'jam_selesai' => $jam_selesai,
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

<?php

namespace App\Imports;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JadwalImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $kelasName = trim($row->get('kelas', ''));
            $guruName = trim($row->get('guru', ''));
            $mapelName = trim($row->get('mata_pelajaran', $row->get('mapel', '')));

            // Parsing Hari
            $hariVal = $row->get('hari', '');
            if (is_numeric($hariVal)) {
                $hari = (int) $hariVal;
            } else {
                $hariMap = [
                    'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
                ];
                $hari = $hariMap[strtolower(trim($hariVal))] ?? 1;
            }

            // Parsing Waktu
            $waktu = trim($row->get('waktu', ''));
            if ($waktu && strpos($waktu, '-') !== false) {
                [$jam_mulai, $jam_selesai] = array_map('trim', explode('-', $waktu));
            } else {
                $jam_mulai = trim($row->get('jam_mulai', ''));
                $jam_selesai = trim($row->get('jam_selesai', ''));
            }

            // Normalize waktu from 07.30 to 07:30
            $jam_mulai = str_replace('.', ':', $jam_mulai);
            $jam_selesai = str_replace('.', ':', $jam_selesai);

            // Find or Create relations by name
            $kelas = Kelas::where('nama', $kelasName)->first();
            if (! $kelas && $kelasName) {
                $tingkat = '10';
                if (preg_match('/^12|xii/i', $kelasName)) {
                    $tingkat = '12';
                } elseif (preg_match('/^11|xi/i', $kelasName)) {
                    $tingkat = '11';
                }
                $kelas = Kelas::create(['nama' => $kelasName, 'tingkat' => $tingkat, 'tahun_ajaran' => date('Y').'/'.(date('Y') + 1)]);
            }

            $guru = User::withTrashed()->where('role', 'guru')->where('name', $guruName)->first();
            if ($guru && $guru->trashed()) {
                $guru->restore();
            }
            if (! $guru && $guruName) {
                $guruEmail = Str::slug($guruName).rand(100, 999).'@guru.com';
                while (User::withTrashed()->where('email', $guruEmail)->exists()) {
                    $guruEmail = Str::slug($guruName).rand(1000, 9999).'@guru.com';
                }
                $guru = User::create([
                    'name' => $guruName,
                    'email' => $guruEmail,
                    'password' => Hash::make('password'),
                    'role' => 'guru',
                    'is_active' => true,
                ]);
            }

            $mapel = MataPelajaran::where('nama', $mapelName)->first();
            if (! $mapel && $mapelName) {
                $prefix = substr(strtoupper(preg_replace('/[^a-zA-Z]/', '', $mapelName)), 0, 3);
                $mapel = MataPelajaran::create([
                    'nama' => $mapelName,
                    'kode' => $prefix.'-'.strtoupper(Str::random(4)),
                    'jenis' => 'umum',
                    'kelompok_blok' => 'reguler',
                ]);
            }

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
            } else {
                \Log::warning('JadwalImport skipped row', [
                    'row' => $row->toArray(),
                    'kelas_found' => (bool) $kelas,
                    'guru_found' => (bool) $guru,
                    'mapel_found' => (bool) $mapel,
                    'jam_mulai_parsed' => $jam_mulai,
                    'jam_selesai_parsed' => $jam_selesai,
                ]);
            }
        }
    }
}

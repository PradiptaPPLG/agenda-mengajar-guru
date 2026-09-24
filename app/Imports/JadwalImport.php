<?php

namespace App\Imports;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JadwalImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        set_time_limit(300);

        // Preload in-memory caches to avoid N+1 queries for each row
        $kelasCache = Kelas::all()->keyBy(fn ($k) => strtolower(trim($k->nama)));
        $guruCache = User::withTrashed()->where('role', 'guru')->get()->keyBy(fn ($u) => strtolower(trim($u->name)));
        $mapelCache = MataPelajaran::withTrashed()->get()->keyBy(fn ($m) => strtolower(trim($m->nama)));

        // Pre-compute password hash once instead of hashing inside the loop
        $defaultPassword = Hash::make('password');

        $hariMap = [
            'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
        ];

        DB::transaction(function () use ($rows, &$kelasCache, &$guruCache, &$mapelCache, $defaultPassword, $hariMap) {
            foreach ($rows as $row) {
                $kelasName = trim($row->get('kelas', ''));
                $guruName = trim($row->get('guru', ''));
                $mapelName = trim($row->get('mata_pelajaran', $row->get('mapel', '')));

                // Parsing Hari
                $hariVal = $row->get('hari', '');
                if (is_numeric($hariVal)) {
                    $hari = (int) $hariVal;
                } else {
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

                // Find or Create Kelas
                $kelasKey = strtolower($kelasName);
                $kelas = $kelasCache->get($kelasKey);
                if (! $kelas && $kelasName) {
                    $tingkat = '10';
                    if (preg_match('/^12|xii/i', $kelasName)) {
                        $tingkat = '12';
                    } elseif (preg_match('/^11|xi/i', $kelasName)) {
                        $tingkat = '11';
                    }
                    $kelas = Kelas::create([
                        'nama' => $kelasName,
                        'tingkat' => $tingkat,
                        'tahun_ajaran' => date('Y').'/'.(date('Y') + 1),
                    ]);
                    $kelasCache->put($kelasKey, $kelas);
                }

                // Find or Create Guru
                $guruKey = strtolower($guruName);
                $guru = $guruCache->get($guruKey);
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
                        'password' => $defaultPassword,
                        'role' => 'guru',
                        'is_active' => true,
                    ]);
                    $guruCache->put($guruKey, $guru);
                }

                // Find or Create Mapel
                $mapelKey = strtolower($mapelName);
                $mapel = $mapelCache->get($mapelKey);
                if ($mapel && $mapel->trashed()) {
                    $mapel->restore();
                }
                if (! $mapel && $mapelName) {
                    $prefix = substr(strtoupper(preg_replace('/[^a-zA-Z]/', '', $mapelName)), 0, 3);
                    $kode = $prefix.'-'.strtoupper(Str::random(4));
                    while (MataPelajaran::withTrashed()->where('kode', $kode)->exists()) {
                        $kode = $prefix.'-'.strtoupper(Str::random(5));
                    }
                    $mapel = MataPelajaran::create([
                        'nama' => $mapelName,
                        'kode' => $kode,
                        'jenis' => 'umum',
                        'kelompok_blok' => 'reguler',
                    ]);
                    $mapelCache->put($mapelKey, $mapel);
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
        });
    }
}

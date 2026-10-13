<?php

namespace App\Imports;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JadwalImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        public ?string $tahunAjaran = null,
        public ?string $semester = null
    ) {
        $this->tahunAjaran = $this->tahunAjaran ?: Setting::getTahunAjaranAktif();
        $this->semester = $this->semester ?: Setting::getSemesterAktif();
    }

    public function collection(Collection $rows): void
    {
        set_time_limit(300);

        if ($rows->isEmpty()) {
            throw new \Exception('File Excel tidak memiliki baris data jadwal.');
        }

        // ─── 1. Validasi Keberadaan Header Wajib ───────────────────────────────
        $firstRow = $rows->first();
        $keys = $firstRow ? array_map('strtolower', array_map('trim', array_keys($firstRow->toArray()))) : [];

        $hasKelas = in_array('kelas', $keys, true);
        $hasGuru = in_array('guru', $keys, true);
        $hasMapel = in_array('mata_pelajaran', $keys, true) || in_array('mapel', $keys, true);
        $hasHari = in_array('hari', $keys, true);
        $hasWaktu = in_array('waktu', $keys, true) || (in_array('jam_mulai', $keys, true) && in_array('jam_selesai', $keys, true));

        if (! ($hasKelas && $hasGuru && $hasMapel && $hasHari && $hasWaktu)) {
            $missing = [];
            if (! $hasKelas) {
                $missing[] = 'kelas';
            }
            if (! $hasGuru) {
                $missing[] = 'guru';
            }
            if (! $hasMapel) {
                $missing[] = 'mata_pelajaran (atau mapel)';
            }
            if (! $hasHari) {
                $missing[] = 'hari';
            }
            if (! $hasWaktu) {
                $missing[] = 'jam_mulai & jam_selesai (atau waktu)';
            }

            throw new \Exception('Header file Excel tidak lengkap. Kolom wajib yang belum ada: '.implode(', ', $missing).'.');
        }

        // Preload in-memory caches to avoid N+1 queries for each row
        $kelasCache = Kelas::withTrashed()->get()->keyBy(fn ($k) => Kelas::cleanKey($k->nama));
        $allGurus = User::withTrashed()->where('role', 'guru')->get();
        $guruCache = collect();
        foreach ($allGurus as $g) {
            $guruCache->put(strtolower(trim($g->name)), $g);
        }
        $mapelCache = MataPelajaran::withTrashed()->get()->keyBy(fn ($m) => strtolower(trim($m->nama)));

        $hariMap = [
            'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
        ];

        // ─── 2. PHASE 1: Validasi Seluruh Baris (All-or-Nothing Pre-Validation) ───
        $errors = [];
        $validRows = [];
        $seenSlots = [];

        foreach ($rows as $index => $row) {
            $excelRowNum = $index + 2; // Baris 1 adalah heading

            $rawKelas = trim((string) $row->get('kelas', ''));
            $rawGuru = trim((string) $row->get('guru', ''));
            $rawMapel = trim((string) $row->get('mata_pelajaran', $row->get('mapel', '')));
            $rawHari = trim((string) $row->get('hari', ''));
            $rawMulai = trim((string) $row->get('jam_mulai', ''));
            $rawSelesai = trim((string) $row->get('jam_selesai', ''));
            $rawWaktu = trim((string) $row->get('waktu', ''));

            // Abaikan baris yang benar-benar kosong di spreadsheet
            if ($rawKelas === '' && $rawGuru === '' && $rawMapel === '' && $rawHari === '' && $rawMulai === '' && $rawSelesai === '' && $rawWaktu === '') {
                continue;
            }

            // Validasi Kelas
            $kelas = null;
            if ($rawKelas === '') {
                $errors[] = "Baris {$excelRowNum}: Kolom 'kelas' wajib diisi.";
            } else {
                $kelasKey = Kelas::cleanKey($rawKelas);
                $kelas = $kelasCache->get($kelasKey);
                if (! $kelas) {
                    $errors[] = "Baris {$excelRowNum}: Kelas '{$rawKelas}' tidak ditemukan dalam sistem.";
                } elseif ($kelas->trashed()) {
                    $kelas->restore();
                }
            }

            // Validasi Guru
            $guru = null;
            if ($rawGuru === '') {
                $errors[] = "Baris {$excelRowNum}: Kolom 'guru' wajib diisi.";
            } else {
                $guruKey = strtolower($rawGuru);
                $guru = $guruCache->get($guruKey);
                if (! $guru) {
                    $guru = User::findMatchingGuru($rawGuru);
                    if ($guru) {
                        $guruCache->put($guruKey, $guru);
                    }
                }
                if (! $guru) {
                    $errors[] = "Baris {$excelRowNum}: Guru '{$rawGuru}' tidak ditemukan dalam sistem.";
                } elseif ($guru->trashed()) {
                    $guru->restore();
                }
            }

            // Validasi Mapel
            $mapel = null;
            if ($rawMapel === '') {
                $errors[] = "Baris {$excelRowNum}: Kolom 'mata_pelajaran' wajib diisi.";
            } else {
                $mapelKey = strtolower($rawMapel);
                $mapel = $mapelCache->get($mapelKey);
                if (! $mapel) {
                    $mapel = MataPelajaran::findMatchingMapel($rawMapel, $kelas?->nama);
                    if ($mapel) {
                        $mapelCache->put($mapelKey, $mapel);
                    }
                }
                if (! $mapel) {
                    $errors[] = "Baris {$excelRowNum}: Mata pelajaran '{$rawMapel}' tidak ditemukan dalam sistem.";
                } elseif ($mapel->trashed()) {
                    $mapel->restore();
                }
            }

            // Validasi Hari
            $hari = null;
            if ($rawHari === '') {
                $errors[] = "Baris {$excelRowNum}: Kolom 'hari' wajib diisi.";
            } else {
                if (is_numeric($rawHari)) {
                    $hNum = (int) $rawHari;
                    if ($hNum >= 1 && $hNum <= 7) {
                        $hari = $hNum;
                    }
                } else {
                    $hari = $hariMap[strtolower($rawHari)] ?? null;
                }

                if (! $hari) {
                    $errors[] = "Baris {$excelRowNum}: Nilai hari '{$rawHari}' tidak valid (gunakan 1-7 atau nama hari Senin-Minggu).";
                }
            }

            // Validasi Jam Mulai & Jam Selesai
            $jamMulai = '';
            $jamSelesai = '';
            if ($rawWaktu !== '' && strpos($rawWaktu, '-') !== false) {
                [$rawMulai, $rawSelesai] = array_map('trim', explode('-', $rawWaktu));
            }

            if ($rawMulai === '' || $rawSelesai === '') {
                $errors[] = "Baris {$excelRowNum}: Jam mulai dan jam selesai wajib diisi.";
            } else {
                $jamMulai = str_replace('.', ':', $rawMulai);
                $jamSelesai = str_replace('.', ':', $rawSelesai);

                // Pad 7:00 -> 07:00:00
                if (preg_match('/^\d{1,2}:\d{2}$/', $jamMulai)) {
                    $jamMulai .= ':00';
                }
                if (preg_match('/^\d{1,2}:\d{2}$/', $jamSelesai)) {
                    $jamSelesai .= ':00';
                }
                if (preg_match('/^\d:\d{2}:\d{2}$/', $jamMulai)) {
                    $jamMulai = '0'.$jamMulai;
                }
                if (preg_match('/^\d:\d{2}:\d{2}$/', $jamSelesai)) {
                    $jamSelesai = '0'.$jamSelesai;
                }

                $validTimeFormat = preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $jamMulai) &&
                                   preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $jamSelesai);

                if (! $validTimeFormat) {
                    $errors[] = "Baris {$excelRowNum}: Format jam tidak valid ('{$rawMulai}' - '{$rawSelesai}'). Gunakan format HH:MM.";
                } elseif ($jamSelesai <= $jamMulai) {
                    $errors[] = "Baris {$excelRowNum}: Jam selesai ('{$rawSelesai}') harus lebih besar dari jam mulai ('{$rawMulai}').";
                }
            }

            // Jika baris ini valid sejauh ini, periksa collision dalam file yang sama
            if ($kelas && $guru && $mapel && $hari && $jamMulai && $jamSelesai && empty($errors)) {
                $targetKelompokBlok = ($kelas->is_sistem_blok) ? ($mapel->kelompok_blok ?? 'reguler') : 'reguler';
                $slotKey = "{$kelas->id}_{$hari}_{$jamMulai}_{$jamSelesai}_{$targetKelompokBlok}";

                if (isset($seenSlots[$slotKey])) {
                    $errors[] = "Baris {$excelRowNum}: Duplikasi slot jadwal dengan baris {$seenSlots[$slotKey]} di dalam file Excel yang sama.";
                } else {
                    $seenSlots[$slotKey] = $excelRowNum;
                    $validRows[] = [
                        'excel_row' => $excelRowNum,
                        'kelas_id' => $kelas->id,
                        'guru_id' => $guru->id,
                        'mata_pelajaran_id' => $mapel->id,
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'kelompok_blok' => $targetKelompokBlok,
                    ];
                }
            }
        }

        // Jika tidak ada satu pun baris data yang ditemukan
        if (empty($validRows) && empty($errors)) {
            throw new \Exception('File Excel tidak memiliki baris data jadwal yang dapat diproses.');
        }

        // JIKA ADA KESALAHAN PADA BARIS MANA PUN: BATALKAN TOTAL (ALL-OR-NOTHING)
        if (! empty($errors)) {
            $count = count($errors);
            $sample = array_slice($errors, 0, 5);
            $msg = "Ditemukan {$count} kesalahan dalam file Excel:\n• ".implode("\n• ", $sample);
            if ($count > 5) {
                $msg .= "\n...dan ".($count - 5).' kesalahan lainnya.';
            }

            throw new \Exception($msg);
        }

        // ─── 3. PHASE 2: Eksekusi Database dengan Proteksi Integritas Histori ────
        foreach ($validRows as $item) {
            $slot = [
                'kelas_id' => $item['kelas_id'],
                'hari' => $item['hari'],
                'jam_mulai' => $item['jam_mulai'],
                'jam_selesai' => $item['jam_selesai'],
                'kelompok_blok' => $item['kelompok_blok'],
                'tahun_ajaran' => $this->tahunAjaran,
                'semester' => $this->semester,
            ];

            $activeJadwal = JadwalPelajaran::where($slot)->first();

            if ($activeJadwal) {
                // Apakah jadwal aktif ini memiliki histori pertemuan?
                if ($activeJadwal->pertemuans()->exists()) {
                    // JIKA GURU DAN MAPEL SAMA: pertahankan identitas sepenuhnya
                    if ($activeJadwal->guru_id === $item['guru_id'] && $activeJadwal->mata_pelajaran_id === $item['mata_pelajaran_id']) {
                        continue;
                    }

                    // JIKA GURU ATAU MAPEL BERBEDA:
                    // INVARIANT: JANGAN MENGUBAH IDENTITAS GURU & MAPEL DARI JADWAL BERHISTORI!
                    // Arsipkan jadwal lama (soft delete) agar pertemuan masa lalu tetap merujuk data lama yang valid.
                    $activeJadwal->delete();

                    // Buat / aktifkan jadwal baru untuk guru & mapel baru pada slot ini
                    JadwalPelajaran::create(array_merge($slot, [
                        'guru_id' => $item['guru_id'],
                        'mata_pelajaran_id' => $item['mata_pelajaran_id'],
                    ]));
                } else {
                    // Tidak ada histori pertemuan: aman diperbarui in-place
                    $activeJadwal->update([
                        'guru_id' => $item['guru_id'],
                        'mata_pelajaran_id' => $item['mata_pelajaran_id'],
                    ]);
                }
            } else {
                // Cek apakah ada jadwal di tong sampah (soft-deleted) yang cocok dengan guru & mapel ini
                $matchingTrashed = JadwalPelajaran::onlyTrashed()->where($slot)
                    ->where('guru_id', $item['guru_id'])
                    ->where('mata_pelajaran_id', $item['mata_pelajaran_id'])
                    ->first();

                if ($matchingTrashed) {
                    $matchingTrashed->restore();
                } else {
                    JadwalPelajaran::create(array_merge($slot, [
                        'guru_id' => $item['guru_id'],
                        'mata_pelajaran_id' => $item['mata_pelajaran_id'],
                    ]));
                }
            }
        }
    }
}

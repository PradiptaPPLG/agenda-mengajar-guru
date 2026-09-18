<?php

namespace Database\Seeders;

use App\Models\FotoBukti;
use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⏳ Mempersiapkan data guru, jadwal, dan riwayat kehadiran...');

        // ── 1. Pastikan Guru Lengkap ──────────────────────────────────────────
        $gurusData = [
            [
                'name' => 'Nastiti, S.Pd.',
                'nip' => '198909242014012001',
                'mapels' => ['INF', 'RPL'],
            ],
            [
                'name' => 'Ahmad Fauzi, M.Pd.',
                'nip' => '198203152008011002',
                'mapels' => ['MTK'],
            ],
            [
                'name' => 'Dewi Lestari, S.Pd.',
                'nip' => '198507202010012003',
                'mapels' => ['BIND'],
            ],
            [
                'name' => 'Budi Santoso, S.Kom.',
                'nip' => '199011122019031004',
                'mapels' => ['PWB', 'BD'],
            ],
            [
                'name' => 'Siti Nurhaliza, S.Pd.',
                'nip' => '199304182020122005',
                'mapels' => ['BING'],
            ],
        ];

        $teachers = [];
        foreach ($gurusData as $gData) {
            $user = User::firstOrCreate(
                ['name' => $gData['name'], 'role' => 'guru'],
                ['password' => Hash::make('password'), 'is_active' => true]
            );
            $user->update(['is_active' => true]);

            GuruProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['nip' => $gData['nip']]
            );

            $teachers[$gData['name']] = $user;
        }

        // ── 2. Ambil Kelas & Mata Pelajaran ───────────────────────────────────
        $kelas11PPLG = Kelas::where('nama', '11PPLG')->first();
        $kelas12RPL = Kelas::where('nama', '12RPL')->first();
        $kelas11DKV = Kelas::where('nama', '11DKV')->first();
        $kelas11AKL1 = Kelas::where('nama', '11AKL1')->first();

        if (! $kelas12RPL) {
            $kelas12RPL = Kelas::create([
                'nama' => '12RPL',
                'tingkat' => 'XII',
                'tahun_ajaran' => '2025/2026',
            ]);
        }

        $mapels = MataPelajaran::all()->keyBy('kode');

        // ── 3. Buat Jadwal Pelajaran (Senin s/d Jumat) ───────────────────────
        // Hari: 1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat
        $jadwalDefinitions = [
            // Nastiti (INF, RPL)
            ['guru' => 'Nastiti, S.Pd.', 'kelas' => $kelas11PPLG, 'mapel' => 'INF', 'hari' => 1, 'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 'Nastiti, S.Pd.', 'kelas' => $kelas11PPLG, 'mapel' => 'RPL', 'hari' => 3, 'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 'Nastiti, S.Pd.', 'kelas' => $kelas12RPL,  'mapel' => 'INF', 'hari' => 2, 'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 'Nastiti, S.Pd.', 'kelas' => $kelas12RPL,  'mapel' => 'RPL', 'hari' => 4, 'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 'Nastiti, S.Pd.', 'kelas' => $kelas11DKV,  'mapel' => 'INF', 'hari' => 5, 'mulai' => '07:00', 'selesai' => '08:30'],

            // Ahmad Fauzi (MTK)
            ['guru' => 'Ahmad Fauzi, M.Pd.', 'kelas' => $kelas11PPLG, 'mapel' => 'MTK', 'hari' => 1, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Ahmad Fauzi, M.Pd.', 'kelas' => $kelas12RPL,  'mapel' => 'MTK', 'hari' => 3, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Ahmad Fauzi, M.Pd.', 'kelas' => $kelas11DKV,  'mapel' => 'MTK', 'hari' => 4, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Ahmad Fauzi, M.Pd.', 'kelas' => $kelas11AKL1, 'mapel' => 'MTK', 'hari' => 5, 'mulai' => '08:45', 'selesai' => '10:15'],

            // Dewi Lestari (BIND)
            ['guru' => 'Dewi Lestari, S.Pd.', 'kelas' => $kelas11PPLG, 'mapel' => 'BIND', 'hari' => 2, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Dewi Lestari, S.Pd.', 'kelas' => $kelas12RPL,  'mapel' => 'BIND', 'hari' => 5, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Dewi Lestari, S.Pd.', 'kelas' => $kelas11DKV,  'mapel' => 'BIND', 'hari' => 1, 'mulai' => '10:30', 'selesai' => '12:00'],
            ['guru' => 'Dewi Lestari, S.Pd.', 'kelas' => $kelas11AKL1, 'mapel' => 'BIND', 'hari' => 3, 'mulai' => '10:30', 'selesai' => '12:00'],

            // Budi Santoso (PWB, BD)
            ['guru' => 'Budi Santoso, S.Kom.', 'kelas' => $kelas11PPLG, 'mapel' => 'PWB', 'hari' => 4, 'mulai' => '10:30', 'selesai' => '12:00'],
            ['guru' => 'Budi Santoso, S.Kom.', 'kelas' => $kelas11PPLG, 'mapel' => 'BD',  'hari' => 5, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Budi Santoso, S.Kom.', 'kelas' => $kelas12RPL,  'mapel' => 'PWB', 'hari' => 1, 'mulai' => '10:30', 'selesai' => '12:00'],
            ['guru' => 'Budi Santoso, S.Kom.', 'kelas' => $kelas12RPL,  'mapel' => 'BD',  'hari' => 5, 'mulai' => '10:30', 'selesai' => '12:00'],

            // Siti Nurhaliza (BING)
            ['guru' => 'Siti Nurhaliza, S.Pd.', 'kelas' => $kelas11PPLG, 'mapel' => 'BING', 'hari' => 3, 'mulai' => '10:30', 'selesai' => '12:00'],
            ['guru' => 'Siti Nurhaliza, S.Pd.', 'kelas' => $kelas12RPL,  'mapel' => 'BING', 'hari' => 2, 'mulai' => '08:45', 'selesai' => '10:15'],
            ['guru' => 'Siti Nurhaliza, S.Pd.', 'kelas' => $kelas11DKV,  'mapel' => 'BING', 'hari' => 2, 'mulai' => '10:30', 'selesai' => '12:00'],
        ];

        $jadwals = [];
        foreach ($jadwalDefinitions as $jd) {
            if (! $jd['kelas'] || ! isset($mapels[$jd['mapel']])) {
                continue;
            }

            $guru = $teachers[$jd['guru']];
            $mapelModel = $mapels[$jd['mapel']];

            $jadwal = JadwalPelajaran::firstOrCreate(
                [
                    'kelas_id' => $jd['kelas']->id,
                    'guru_id' => $guru->id,
                    'hari' => $jd['hari'],
                    'jam_mulai' => $jd['mulai'],
                ],
                [
                    'mata_pelajaran_id' => $mapelModel->id,
                    'jam_selesai' => $jd['selesai'],
                ]
            );

            $jadwals[] = $jadwal;
        }

        // ── 4. Silabus Materi dan Tugas Realistis ──────────────────────────────
        $syllabus = [
            'INF' => [
                ['materi' => 'Arsitektur Komputer & Jaringan Komputer Dasar', 'tugas' => 'Analisis komponen perangkat keras pada PC lab'],
                ['materi' => 'Algoritma & Pemrograman: Logika Percabangan & Perulangan', 'tugas' => 'Menyusun pseudocode perhitungan diskon transaksi'],
                ['materi' => 'Struktur Data Array & List dalam Pemrograman', 'tugas' => 'Latihan implementasi array 2 dimensi'],
                ['materi' => 'Keamanan Informasi & Kriptografi Simetris Dasar', 'tugas' => 'Studi kasus pengamanan otentikasi login'],
            ],
            'RPL' => [
                ['materi' => 'Pengenalan Software Engineering & Metodologi Agile Scrum', 'tugas' => 'Menyusun User Story dan Product Backlog'],
                ['materi' => 'Analisis Kebutuhan Perangkat Lunak & Diagram Use Case', 'tugas' => 'Membuat diagram Use Case sistem kasir toko'],
                ['materi' => 'Desain Basis Data Relasional & Normalisasi 3NF', 'tugas' => 'Normalisasi tabel transaksi penjualan ke bentuk 3NF'],
                ['materi' => 'Arsitektur Aplikasi Modern: Model-View-Controller (MVC)', 'tugas' => 'Implementasi routing dan basic controller'],
            ],
            'MTK' => [
                ['materi' => 'Persamaan dan Pertidaksamaan Nilai Mutlak', 'tugas' => 'Mengerjakan latihan soal mandiri no. 1-10 hal 32'],
                ['materi' => 'Matriks: Operasi Penjumlahan, Pengurangan, dan Skalar', 'tugas' => 'Latihan operasi aljabar matriks di buku catatan'],
                ['materi' => 'Determinan dan Invers Matriks Ordo 2x2', 'tugas' => 'Menyelesaikan sistem persamaan linier menggunakan invers matriks'],
                ['materi' => 'Program Linier dan Pemodelan Fungsi Kendala', 'tugas' => 'Membuat model matematika untuk optimasi laba produksi'],
            ],
            'BIND' => [
                ['materi' => 'Struktur dan Aspek Kebahasaan Teks Prosedur', 'tugas' => 'Menulis teks prosedur protokol pengoperasian server'],
                ['materi' => 'Menganalisis Teks Eksplanasi Fenomena Sosial dan Teknologi', 'tugas' => 'Analisis struktur sebab-akibat fenomena kecerdasan buatan'],
                ['materi' => 'Menyusun Teks Ceramah Informatif dan Persuasif', 'tugas' => 'Menulis naskah ceramah bertema etika berinternet'],
                ['materi' => 'Kaidah Penyusunan Surat Lamaran Pekerjaan dan CV Modern', 'tugas' => 'Membuat draft surat lamaran kerja sesuai formasi impian'],
            ],
            'BING' => [
                ['materi' => 'Expressing Intentions, Future Plans, and Aspirations', 'tugas' => 'Write a 100-word paragraph about your 5-year career goals'],
                ['materi' => 'Formal Invitation Letters & Formal Acceptance/Declining', 'tugas' => 'Draft a formal invitation letter for an IT seminar'],
                ['materi' => 'Analytical Exposition: Constructing Arguments & Thesis Statement', 'tugas' => 'Create an analytical exposition outline on cybersecurity'],
                ['materi' => 'Technical Reading & Passive Voice in Software Documentation', 'tugas' => 'Rewrite technical manual instructions in passive voice'],
            ],
            'PWB' => [
                ['materi' => 'Konsep Client-Server, HTTP Requests, dan RESTful Principles', 'tugas' => 'Praktikum HTTP method GET dan POST menggunakan browser console'],
                ['materi' => 'Templating Engine: Layout Modular, Components, dan Slots', 'tugas' => 'Membuat master layout dashboard responsif'],
                ['materi' => 'Penanganan Form, Validasi Server-Side, dan Token CSRF', 'tugas' => 'Membangun form pendaftaran dengan pesan error validasi'],
                ['materi' => 'Integrasi Database: Query Builder dan Eloquent Active Record', 'tugas' => 'Membuat fitur CRUD data barang sederhana'],
            ],
            'BD' => [
                ['materi' => 'Relasi Antar Entitas: One-to-Many dan Foreign Key Constraints', 'tugas' => 'Merancang skema tabel relasional perpustakaan sekolah'],
                ['materi' => 'Data Manipulation Language: Advanced SELECT, WHERE, dan ORDER BY', 'tugas' => 'Menulis 5 query filter data siswa berprestasi'],
                ['materi' => 'Penggabungan Multi-Tabel dengan INNER JOIN dan LEFT JOIN', 'tugas' => 'Praktikum query JOIN tabel siswa, kelas, dan jurusan'],
                ['materi' => 'Aggregate Functions, GROUP BY, dan HAVING Clause', 'tugas' => 'Menghitung rata-rata nilai siswa per kelas dengan GROUP BY'],
            ],
        ];

        // ── 5. Cache Siswa per Kelas untuk Kinerja Cepat ──────────────────────
        $kelasSiswaMap = [];
        foreach ([$kelas11PPLG, $kelas12RPL, $kelas11DKV, $kelas11AKL1] as $k) {
            if ($k) {
                $kelasSiswaMap[$k->id] = SiswaProfile::where('kelas_id', $k->id)
                    ->pluck('user_id')
                    ->toArray();
            }
        }

        // Siswa Reviewer: Pradipta
        $pradipta = User::where('name', 'Pradipta Endra Maulana')->first();

        // ── 6. Bersihkan Data Kehadiran Lama Agar Idempoten ───────────────────
        // Karena cascading delete aktif, menghapus pertemuans akan otomatis membersihkan
        // kehadiran_gurus, kehadiran_siswas, dan foto_buktis.
        Pertemuan::query()->delete();

        // ── 7. Rentang Waktu (01 September s/d 18 September 2026) ─────────────
        $startDate = Carbon::create(2026, 9, 1);
        $today = Carbon::create(2026, 9, 18);
        $period = CarbonPeriod::create($startDate, $today);

        $totalPertemuanCount = 0;
        $allJadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru'])->get();

        // Counter untuk variasi silabus dan kehadiran per guru / siswa
        $jadwalMeetingCounter = [];
        $guruMeetingCounter = [];
        $pradiptaMeetingCounter = 0;

        foreach ($period as $currentDate) {
            $dayOfWeek = $currentDate->dayOfWeekIso; // 1 (Senin) s/d 7 (Minggu)
            if ($dayOfWeek > 5) {
                continue; // Libur akhir pekan
            }

            $dayJadwals = $allJadwals->where('hari', $dayOfWeek);

            foreach ($dayJadwals as $jadwal) {
                $jadwalId = $jadwal->id;
                $jadwalMeetingCounter[$jadwalId] = ($jadwalMeetingCounter[$jadwalId] ?? 0) + 1;
                $meetingIndex = $jadwalMeetingCounter[$jadwalId];

                $guruName = $jadwal->guru->name;
                $guruMeetingCounter[$guruName] = ($guruMeetingCounter[$guruName] ?? 0) + 1;
                $gMeetingIdx = $guruMeetingCounter[$guruName];

                // Pilih materi ajar & penugasan
                $kode = $jadwal->mataPelajaran->kode;
                $syllabusList = $syllabus[$kode] ?? [
                    ['materi' => 'Pembahasan Materi Pertemuan ke-'.$meetingIndex, 'tugas' => 'Mengerjakan tugas lembar kerja siswa'],
                ];
                $syllabusItem = $syllabusList[($meetingIndex - 1) % count($syllabusList)];

                $isToday = $currentDate->isSameDay($today);
                $pertemuanStatus = $isToday ? 'berlangsung' : 'selesai';

                $createdAt = $currentDate->copy()->setTimeFromTimeString($jadwal->jam_mulai);
                $updatedAt = $currentDate->copy()->setTimeFromTimeString($jadwal->jam_selesai);

                // Buat Pertemuan
                $pertemuan = new Pertemuan;
                $pertemuan->jadwal_id = $jadwal->id;
                $pertemuan->tanggal = $currentDate->format('Y-m-d');
                $pertemuan->materi_ajar = $syllabusItem['materi'];
                $pertemuan->penugasan = $syllabusItem['tugas'];
                $pertemuan->status = $pertemuanStatus;
                $pertemuan->created_at = $createdAt;
                $pertemuan->updated_at = $updatedAt;
                $pertemuan->save();

                $totalPertemuanCount++;

                // ── Tentukan Status Kehadiran Guru ──────────────────────────
                // Desain persentase kehadiran guru mencakup semua badge (Hijau, Kuning, Merah):
                // - Ahmad Fauzi: 100% Hadir (Hijau >= 80%)
                // - Budi Santoso: ~91% Hadir (Hijau >= 80%)
                // - Nastiti: ~86% Hadir (Hijau >= 80%)
                // - Dewi Lestari: ~73% Hadir (Kuning 60–79%)
                // - Siti Nurhaliza: ~56% Hadir (Merah < 60%)
                $guruStatus = 'hadir';
                $jenisAlpa = null;
                $keteranganGuru = null;
                $guruPengganti = null;
                $waktuHadir = $createdAt->copy()->subMinutes(rand(5, 15));

                if ($guruName === 'Nastiti, S.Pd.') {
                    if ($gMeetingIdx === 3) {
                        $guruStatus = 'sakit';
                        $keteranganGuru = 'Surat dokter terlampir (Flu & demam)';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 8) {
                        $guruStatus = 'dispensasi';
                        $keteranganGuru = 'Menghadiri Rapat Koordinasi Kurikulum MGMP';
                        $waktuHadir = null;
                    }
                } elseif ($guruName === 'Budi Santoso, S.Kom.') {
                    if ($gMeetingIdx === 5) {
                        $guruStatus = 'dispensasi';
                        $keteranganGuru = 'Pelatihan Asesor Uji Kompetensi Keahlian';
                        $waktuHadir = null;
                    }
                } elseif ($guruName === 'Dewi Lestari, S.Pd.') {
                    if ($gMeetingIdx === 4) {
                        $guruStatus = 'alpa';
                        $jenisAlpa = 'ada_tugas';
                        $keteranganGuru = 'Izin keperluan mendadak, tugas diberikan via ketua kelas';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 7) {
                        $guruStatus = 'sakit';
                        $keteranganGuru = 'Sakit demam, rawat jalan';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 10) {
                        $guruStatus = 'alpa';
                        $jenisAlpa = 'tanpa_tugas';
                        $keteranganGuru = 'Berhalangan hadir tanpa keterangan';
                        $waktuHadir = null;
                    }
                } elseif ($guruName === 'Siti Nurhaliza, S.Pd.') {
                    if ($gMeetingIdx === 2) {
                        $guruStatus = 'sakit';
                        $keteranganGuru = 'Surat izin sakit terlampir';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 4) {
                        $guruStatus = 'dispensasi';
                        $keteranganGuru = 'Mendampingi lomba debat Bahasa Inggris';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 6) {
                        $guruStatus = 'sakit';
                        $keteranganGuru = 'Pemeriksaan kesehatan';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 8) {
                        $guruStatus = 'alpa';
                        $jenisAlpa = 'guru_pengganti';
                        $guruPengganti = 'Ahmad Fauzi, M.Pd.';
                        $keteranganGuru = 'Digantikan oleh guru piket/pengganti';
                        $waktuHadir = null;
                    }
                }

                KehadiranGuru::updateOrCreate(
                    [
                        'pertemuan_id' => $pertemuan->id,
                        'guru_id' => $jadwal->guru_id,
                    ],
                    [
                        'status' => $guruStatus,
                        'jenis_alpa' => $jenisAlpa,
                        'guru_pengganti_nama' => $guruPengganti,
                        'keterangan' => $keteranganGuru,
                        'waktu_hadir' => $waktuHadir,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]
                );

                // ── Kehadiran Siswa ──────────────────────────────────────────
                $studentIds = $kelasSiswaMap[$jadwal->kelas_id] ?? [];
                $kehadiranSiswaBatch = [];

                foreach ($studentIds as $idx => $sId) {
                    if ($pradipta && $sId === $pradipta->id) {
                        $pradiptaMeetingCounter++;
                        if ($pradiptaMeetingCounter === 4) {
                            $siswaStatus = 'izin';
                            $ket = 'Izin ada keperluan keluarga';
                        } elseif ($pradiptaMeetingCounter === 12) {
                            $siswaStatus = 'sakit';
                            $ket = 'Surat dokter terlampir';
                        } else {
                            $siswaStatus = 'hadir';
                            $ket = null;
                        }
                    } else {
                        // Variasi per siswa berdasarkan indeks siswa & meeting
                        $seedValue = ($sId * 17 + $meetingIndex * 23) % 100;
                        if ($idx % 12 === 0 && $seedValue < 25) {
                            $siswaStatus = 'sakit';
                            $ket = 'Surat dokter terlampir';
                        } elseif ($idx % 10 === 0 && $seedValue < 20) {
                            $siswaStatus = 'izin';
                            $ket = 'Izin keperluan keluarga';
                        } elseif ($idx % 15 === 0 && $seedValue < 15) {
                            $siswaStatus = 'dispensasi';
                            $ket = 'Dispensasi kepengurusan OSIS';
                        } elseif ($idx % 20 === 0 && $seedValue < 10) {
                            $siswaStatus = 'alpa';
                            $ket = 'Tanpa keterangan';
                        } else {
                            $siswaStatus = 'hadir';
                            $ket = null;
                        }
                    }

                    $kehadiranSiswaBatch[] = [
                        'pertemuan_id' => $pertemuan->id,
                        'siswa_id' => $sId,
                        'status' => $siswaStatus,
                        'keterangan' => $ket,
                        'created_at' => $createdAt->toDateTimeString(),
                        'updated_at' => $updatedAt->toDateTimeString(),
                    ];
                }

                // Batch upsert per pertemuan
                if (! empty($kehadiranSiswaBatch)) {
                    foreach (array_chunk($kehadiranSiswaBatch, 100) as $chunk) {
                        KehadiranSiswa::upsert(
                            $chunk,
                            ['pertemuan_id', 'siswa_id'],
                            ['status', 'keterangan', 'created_at', 'updated_at']
                        );
                    }
                }

                // ── Foto Bukti Siswa Reviewer (12RPL) / Kelas Terkait ────────
                if ($pradipta && in_array($pradipta->id, $studentIds) && $guruStatus === 'hadir') {
                    FotoBukti::updateOrCreate(
                        [
                            'pertemuan_id' => $pertemuan->id,
                            'siswa_id' => $pradipta->id,
                        ],
                        [
                            'foto_path' => 'images/logo_new.png',
                            'status_guru_dilaporkan' => 'hadir',
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt,
                        ]
                    );
                }
            }
        }

        // ── 7. Cetak Rangkuman Statistik ──────────────────────────────────────
        $this->command->info('');
        $this->command->info("🎉 Berhasil membuat {$totalPertemuanCount} pertemuan dan ribuan data kehadiran!");
        $this->command->info('');
        $this->command->info('📊 PERSENTASE KEHADIRAN GURU (Periode 01–18 September 2026):');

        $guruStats = KehadiranGuru::with('guru')
            ->selectRaw('guru_id, status, count(*) as count')
            ->groupBy('guru_id', 'status')
            ->get()
            ->groupBy('guru_id');

        foreach ($guruStats as $guruId => $records) {
            $namaGuru = $records->first()->guru->name;
            $hadir = $records->where('status', 'hadir')->sum('count');
            $total = $records->sum('count');
            $pct = $total > 0 ? round(($hadir / $total) * 100) : 0;
            $sakit = $records->where('status', 'sakit')->sum('count');
            $dispensasi = $records->where('status', 'dispensasi')->sum('count');
            $alpa = $records->where('status', 'alpa')->sum('count');

            $this->command->info(sprintf(
                '   %-22s: %3d%% Hadir (Hadir: %2d, Sakit: %2d, Disp: %2d, Alpa: %2d | Total: %2d)',
                $namaGuru,
                $pct,
                $hadir,
                $sakit,
                $dispensasi,
                $alpa,
                $total
            ));
        }

        $this->command->info('');
        $this->command->info('📊 RINGKASAN PERSENTASE KEHADIRAN SISWA:');
        $totalSiswaHadir = KehadiranSiswa::where('status', 'hadir')->count();
        $totalSiswaRecords = KehadiranSiswa::count();
        $overallSiswaPct = $totalSiswaRecords > 0 ? round(($totalSiswaHadir / $totalSiswaRecords) * 100) : 0;

        $this->command->info("   Total Record Presensi Siswa: {$totalSiswaRecords} data");
        $this->command->info("   Rata-rata Presensi Hadir   : {$overallSiswaPct}% Hadir");

        if ($pradipta) {
            $pradHadir = KehadiranSiswa::where('siswa_id', $pradipta->id)->where('status', 'hadir')->count();
            $pradTotal = KehadiranSiswa::where('siswa_id', $pradipta->id)->count();
            $pradPct = $pradTotal > 0 ? round(($pradHadir / $pradTotal) * 100) : 0;
            $this->command->info("   Siswa Reviewer (Pradipta)  : {$pradPct}% Hadir ({$pradHadir}/{$pradTotal} Pertemuan)");
        }
        $this->command->info('');
    }
}

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⏳ Mempersiapkan data 56 guru, pemetaan jadwal seluruh kelas, dan riwayat presensi...');

        // ── 1. Data 56 Guru Lengkap dan Representatif ────────────────────────
        $gurusData = [
            ['name' => 'Nastiti, S.Pd.',                 'nip' => '198909242014012001', 'mapels' => ['INF', 'RPL']],
            ['name' => 'Ahmad Fauzi, M.Pd.',             'nip' => '198203152008011002', 'mapels' => ['MTK']],
            ['name' => 'Dewi Lestari, S.Pd.',            'nip' => '198507202010012003', 'mapels' => ['BIND']],
            ['name' => 'Budi Santoso, S.Kom.',           'nip' => '199011122019031004', 'mapels' => ['PWB', 'BD']],
            ['name' => 'Siti Nurhaliza, S.Pd.',          'nip' => '199304182020122005', 'mapels' => ['BING']],
            ['name' => 'Hendra Setiawan, S.Pd.',         'nip' => '198405102009021006', 'mapels' => ['PPK']],
            ['name' => 'Ratna Sari, M.Pd.',              'nip' => '198608142010012007', 'mapels' => ['SEJ']],
            ['name' => 'Bambang Wijaya, S.E., M.Ak.',    'nip' => '197901052005011008', 'mapels' => ['AKL']],
            ['name' => 'Sri Wahyuni, S.E.',              'nip' => '198302172008012009', 'mapels' => ['AKL']],
            ['name' => 'Dedi Suryadi, S.E.',             'nip' => '198506222010011010', 'mapels' => ['AKL']],
            ['name' => 'Rina Marlina, S.Pd., M.M.',      'nip' => '198104122006042011', 'mapels' => ['PM']],
            ['name' => 'Agus Pratama, S.Sos.',           'nip' => '198709302011011012', 'mapels' => ['PM']],
            ['name' => 'Yuni Astuti, S.Pd.',             'nip' => '198411082009032013', 'mapels' => ['MPLB']],
            ['name' => 'Eko Prasetyo, S.AP.',            'nip' => '198803252014021014', 'mapels' => ['MPLB']],
            ['name' => 'Fitri Handayani, S.Par.',        'nip' => '199107192018012015', 'mapels' => ['HTL']],
            ['name' => 'Rudi Hermawan, S.ST.Par.',       'nip' => '198612032010011016', 'mapels' => ['HTL']],
            ['name' => 'Chef Junaedi, S.Pd.',            'nip' => '198205162007011017', 'mapels' => ['KLN']],
            ['name' => 'Nur Azizah, S.Pd.',              'nip' => '199201042019032018', 'mapels' => ['KLN']],
            ['name' => 'Ilham Hidayat, S.Sn.',           'nip' => '198904152015031019', 'mapels' => ['DKV']],
            ['name' => 'Maya Anggraeni, M.Ds.',          'nip' => '199008282016012020', 'mapels' => ['DKV']],
            ['name' => 'Rizki Ramadhan, S.Kom.',         'nip' => '199306112019021021', 'mapels' => ['RPL']],
            ['name' => 'Fajar Nugraha, S.Kom.',          'nip' => '199110022018011022', 'mapels' => ['PWB']],
            ['name' => 'Dian Permatasari, M.Kom.',       'nip' => '198712202011012023', 'mapels' => ['BD']],
            ['name' => 'Arif Rahman, S.Pd.',             'nip' => '198308192008011024', 'mapels' => ['MTK']],
            ['name' => 'Endah Susanti, M.Pd.',           'nip' => '198504032010012025', 'mapels' => ['MTK']],
            ['name' => 'Wahyu Hidayat, S.Pd.',           'nip' => '198611142009021026', 'mapels' => ['BIND']],
            ['name' => 'Nurul Hasanah, S.Pd.',           'nip' => '199205212018012027', 'mapels' => ['BIND']],
            ['name' => 'Tri Wibowo, S.Pd.',              'nip' => '198402182008011028', 'mapels' => ['BING']],
            ['name' => 'Lilis Suryani, M.Pd.',           'nip' => '198709122011012029', 'mapels' => ['BING']],
            ['name' => 'Supriyanto, S.Pd.',              'nip' => '197806252003121030', 'mapels' => ['PPK']],
            ['name' => 'Ani Rohaeni, S.Pd.',             'nip' => '198007142006042031', 'mapels' => ['SEJ']],
            ['name' => 'Dani Ramdani, S.Kom.',           'nip' => '199203152019031032', 'mapels' => ['INF']],
            ['name' => 'Wawan Setiawan, S.Pd.',          'nip' => '198510102010011033', 'mapels' => ['INF']],
            ['name' => 'Hesti Purwanti, S.E.',           'nip' => '198901232015022034', 'mapels' => ['AKL']],
            ['name' => 'Yayan Mulyana, S.E.',            'nip' => '198207182007011035', 'mapels' => ['AKL']],
            ['name' => 'Teti Rohayati, S.Pd.',           'nip' => '198804192014022036', 'mapels' => ['PM']],
            ['name' => 'Cecep Saepudin, S.M.',           'nip' => '199002112017011037', 'mapels' => ['PM']],
            ['name' => 'Imas Masitoh, S.Pd.',            'nip' => '198603092009032038', 'mapels' => ['MPLB']],
            ['name' => 'Asep Saepuloh, S.Sos.',          'nip' => '198312052008011039', 'mapels' => ['MPLB']],
            ['name' => 'Nita Kurniawati, S.Par.',        'nip' => '199308142019032040', 'mapels' => ['HTL']],
            ['name' => 'Gilang Romadhon, S.Tr.Par.',     'nip' => '199401202020121041', 'mapels' => ['HTL']],
            ['name' => 'Rini Indriani, S.Pd.',           'nip' => '198705032011012042', 'mapels' => ['KLN']],
            ['name' => 'Ade Kosasih, S.Pd.',             'nip' => '198109152006041043', 'mapels' => ['KLN']],
            ['name' => 'Bayu Pratama, S.Sn.',            'nip' => '199104082018011044', 'mapels' => ['DKV']],
            ['name' => 'Gita Savitri, M.Ds.',            'nip' => '199211172019032045', 'mapels' => ['DKV']],
            ['name' => 'Taufik Ismail, S.Kom.',          'nip' => '198906242015031046', 'mapels' => ['RPL']],
            ['name' => 'Lukman Hakim, S.Kom.',           'nip' => '199302102019021047', 'mapels' => ['PWB']],
            ['name' => 'Mira Kusuma, S.Kom.',            'nip' => '199409152020122048', 'mapels' => ['BD']],
            ['name' => 'Joko Widodo, M.Pd.',             'nip' => '197708202002121049', 'mapels' => ['MTK']],
            ['name' => 'Sri Mulyani, S.Pd.',             'nip' => '198103112005012050', 'mapels' => ['MTK']],
            ['name' => 'Heri Kurniawan, S.Pd.',          'nip' => '198505192010011051', 'mapels' => ['BIND']],
            ['name' => 'Eni Nuraeni, S.Pd.',             'nip' => '198812042014022052', 'mapels' => ['BING']],
            ['name' => 'Dadang Suhendar, S.Pd.',         'nip' => '197910122003121053', 'mapels' => ['PPK']],
            ['name' => 'Ai Nurhayati, S.Pd.',            'nip' => '198407062009022054', 'mapels' => ['SEJ']],
            ['name' => 'Sandi Gunawan, S.Kom.',          'nip' => '199505122022031055', 'mapels' => ['INF']],
            ['name' => 'Rahmat Hidayat, S.Kom.',         'nip' => '199108252017011056', 'mapels' => ['RPL']],
        ];

        $teachers = [];
        $passwordHash = Hash::make('password');

        foreach ($gurusData as $idx => $gData) {
            $baseName = trim(explode(',', $gData['name'])[0]);
            $cleanEmail = strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z\s]/', '', $baseName))).'@sekolah.sch.id';

            $user = User::firstOrCreate(
                ['name' => $gData['name'], 'role' => 'guru'],
                [
                    'email' => $cleanEmail,
                    'password' => $passwordHash,
                    'is_active' => true,
                ]
            );
            $user->update(['is_active' => true]);

            GuruProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['nip' => $gData['nip']]
            );

            $teachers[$gData['name']] = $user;
        }

        // ── 2. Ambil Kelas & Mata Pelajaran ───────────────────────────────────
        $allClasses = Kelas::orderBy('id')->get()->keyBy('nama');
        $mapels = MataPelajaran::all()->keyBy('kode');

        // Pastikan kelas 12RPL ada
        if (! isset($allClasses['12RPL'])) {
            $kelas12RPL = Kelas::create([
                'nama' => '12RPL',
                'tingkat' => 'XII',
                'tahun_ajaran' => '2025/2026',
            ]);
            $allClasses['12RPL'] = $kelas12RPL;
        }

        $classListNames = [
            '11AKL1', '11AKL2', '11AKL3', '11AKL4', '11AKL5',
            '11PM1', '11PM2', '11PM3', '11PM4',
            '11MPLB1', '11MPLB2', '11MPLB3',
            '11HTL1', '11HTL2',
            '11KLN1', '11KLN2',
            '11DKV',
            '11PPLG',
            '12RPL',
        ];

        // ── 3. Kurikulum dan Pemetaan Mapel per Kelas ─────────────────────────
        $allMapelsList = ['MTK', 'BIND', 'BING', 'PPK', 'SEJ', 'INF', 'RPL', 'BD', 'PWB', 'DKV', 'AKL', 'PM', 'MPLB', 'HTL', 'KLN'];

        $curriculum = [
            '11AKL1' => ['AKL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11AKL2' => ['AKL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11AKL3' => ['AKL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11AKL4' => ['AKL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11AKL5' => ['AKL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11PM1' => ['PM', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11PM2' => ['PM', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11PM3' => ['PM', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11PM4' => ['PM', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11MPLB1' => ['MPLB', 'MPLB', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11MPLB2' => ['MPLB', 'MPLB', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11MPLB3' => ['MPLB', 'MPLB', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11HTL1' => ['HTL', 'HTL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11HTL2' => ['HTL', 'HTL', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11KLN1' => ['KLN', 'KLN', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11KLN2' => ['KLN', 'KLN', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11DKV' => ['DKV', 'DKV', 'DKV', 'DKV', 'INF', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '11PPLG' => ['INF', 'RPL', 'RPL', 'RPL', 'PWB', 'PWB', 'BD', 'BD', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
            '12RPL' => ['INF', 'RPL', 'RPL', 'RPL', 'PWB', 'PWB', 'BD', 'BD', 'MTK', 'BIND', 'BING', 'PPK', 'SEJ'],
        ];

        // Sinkronkan relasi kelas_mata_pelajaran pivot table
        foreach ($classListNames as $className) {
            $kelas = $allClasses[$className] ?? null;
            if ($kelas && isset($curriculum[$className])) {
                $uniqueSubjectCodes = array_unique($curriculum[$className]);
                $subjectIds = [];
                foreach ($uniqueSubjectCodes as $code) {
                    if (isset($mapels[$code])) {
                        $subjectIds[] = $mapels[$code]->id;
                    }
                }
                $kelas->mataPelajarans()->syncWithoutDetaching($subjectIds);
            }
        }

        // Kelompokkan guru berdasarkan mata pelajaran
        $teachersByMapel = [];
        foreach ($gurusData as $t) {
            foreach ($t['mapels'] as $m) {
                $teachersByMapel[$m][] = $t['name'];
            }
        }

        // Distribusikan sesi mengajar dengan sistem rotasi round-robin
        $classSessions = [];
        $mapelPointers = array_fill_keys($allMapelsList, 0);
        $nastitiRplCount = 0;
        $nastitiInfCount = 0;

        foreach ($classListNames as $className) {
            if (! isset($curriculum[$className])) {
                continue;
            }
            $subjects = $curriculum[$className];
            foreach ($subjects as $mapel) {
                if ($mapel === 'INF' && in_array($className, ['11PPLG', '12RPL', '11DKV']) && $nastitiInfCount < 3) {
                    $teacher = 'Nastiti, S.Pd.';
                    $nastitiInfCount++;
                } elseif ($mapel === 'RPL' && in_array($className, ['11PPLG', '12RPL']) && $nastitiRplCount < 2) {
                    $teacher = 'Nastiti, S.Pd.';
                    $nastitiRplCount++;
                } else {
                    $candidates = $teachersByMapel[$mapel] ?? [];
                    $ptr = $mapelPointers[$mapel] ?? 0;
                    $teacher = ! empty($candidates) ? $candidates[$ptr % count($candidates)] : 'Nastiti, S.Pd.';
                    $mapelPointers[$mapel]++;
                }

                $classSessions[] = [
                    'kelas' => $className,
                    'mapel' => $mapel,
                    'guru' => $teacher,
                ];
            }
        }

        // ── 4. Buat Jadwal Pelajaran Bebas Bentrok (Conflict-Free) ─────────────
        Pertemuan::query()->delete();
        JadwalPelajaran::query()->delete();

        $timeSlots = [
            1 => ['07:00', '08:30'],
            2 => ['08:45', '10:15'],
            3 => ['10:30', '12:00'],
            4 => ['12:30', '14:00'],
        ];

        $jadwals = [];
        $teacherBusy = [];
        $classBusy = [];

        $bookSlot = function ($className, $teacher, $mapel, $hari, $slot) use (&$jadwals, &$teacherBusy, &$classBusy, $timeSlots, $allClasses, $teachers, $mapels) {
            $kelas = $allClasses[$className] ?? null;
            $guruUser = $teachers[$teacher] ?? null;
            $mapelModel = $mapels[$mapel] ?? null;

            if ($kelas && $guruUser && $mapelModel) {
                $jadwal = JadwalPelajaran::firstOrCreate(
                    [
                        'kelas_id' => $kelas->id,
                        'guru_id' => $guruUser->id,
                        'hari' => $hari,
                        'jam_mulai' => $timeSlots[$slot][0],
                    ],
                    [
                        'mata_pelajaran_id' => $mapelModel->id,
                        'jam_selesai' => $timeSlots[$slot][1],
                    ]
                );
                $jadwals[] = $jadwal;
            }

            $teacherBusy[$teacher][$hari][$slot] = true;
            $classBusy[$className][$hari][$slot] = true;
        };

        // Jadwal pasti Guru Reviewer (Nastiti)
        $bookSlot('11PPLG', 'Nastiti, S.Pd.', 'INF', 1, 1);
        $bookSlot('11PPLG', 'Nastiti, S.Pd.', 'RPL', 3, 1);
        $bookSlot('12RPL', 'Nastiti, S.Pd.', 'INF', 2, 1);
        $bookSlot('12RPL', 'Nastiti, S.Pd.', 'RPL', 4, 1);
        $bookSlot('11DKV', 'Nastiti, S.Pd.', 'INF', 5, 1);

        // Jadwalkan sesi lainnya ke slot kosong
        foreach ($classSessions as $session) {
            $className = $session['kelas'];
            $mapel = $session['mapel'];
            $teacher = $session['guru'];

            if ($teacher === 'Nastiti, S.Pd.' && in_array($className, ['11PPLG', '12RPL', '11DKV']) && in_array($mapel, ['INF', 'RPL'])) {
                $alreadyBooked = false;
                foreach ($jadwals as $j) {
                    if ($j->kelas_id === ($allClasses[$className]->id ?? 0) && $j->guru_id === ($teachers[$teacher]->id ?? 0) && $j->mata_pelajaran_id === ($mapels[$mapel]->id ?? 0)) {
                        $alreadyBooked = true;
                        break;
                    }
                }
                if ($alreadyBooked) {
                    continue;
                }
            }

            $placed = false;
            for ($h = 1; $h <= 5; $h++) {
                for ($sl = 1; $sl <= 4; $sl++) {
                    if (empty($classBusy[$className][$h][$sl]) && empty($teacherBusy[$teacher][$h][$sl])) {
                        $bookSlot($className, $teacher, $mapel, $h, $sl);
                        $placed = true;
                        break 2;
                    }
                }
            }

            if (! $placed) {
                $candidates = $teachersByMapel[$mapel] ?? [];
                foreach ($candidates as $altTeacher) {
                    for ($h = 1; $h <= 5; $h++) {
                        for ($sl = 1; $sl <= 4; $sl++) {
                            if (empty($classBusy[$className][$h][$sl]) && empty($teacherBusy[$altTeacher][$h][$sl])) {
                                $bookSlot($className, $altTeacher, $mapel, $h, $sl);
                                $placed = true;
                                break 3;
                            }
                        }
                    }
                }
            }
        }

        // ── 5. Silabus Materi dan Tugas Realistis untuk 15 Mapel ───────────────
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
            'PPK' => [
                ['materi' => 'Pancasila sebagai Meja Statis dan Leitstar Dinamis Bangsa', 'tugas' => 'Refleksi penerapan nilai keadilan sosial di lingkungan sekolah'],
                ['materi' => 'Harmonisasi Hak dan Kewajiban Asasi Manusia dalam Konstitusi', 'tugas' => 'Analisis kasus penegakan HAM di era keterbukaan informasi'],
                ['materi' => 'Sistem Hukum dan Peradilan di Indonesia', 'tugas' => 'Membuat bagan tingkatan lembaga peradilan nasional'],
                ['materi' => 'Wawasan Nusantara dalam Konteks Negara Kesatuan Republik Indonesia', 'tugas' => 'Resume peran generasi muda dalam ketahanan ideologi'],
            ],
            'SEJ' => [
                ['materi' => 'Perjuangan Mempertahankan Kemerdekaan Indonesia Pasca Proklamasi', 'tugas' => 'Peta konsep pertempuran 10 November Surabaya'],
                ['materi' => 'Diplomasi dan Perjuangan Bersenjata Era Revolusi Fisik', 'tugas' => 'Analisis isi perjanjian Linggarjati dan Renville'],
                ['materi' => 'Perkembangan Ekonomi dan Dinamika Politik Masa Demokrasi Liberal', 'tugas' => 'Makalah kabinet pada masa demokrasi parlementer'],
                ['materi' => 'Transformasi Sosial Budaya dan Sejarah Kebangkitan Nasional', 'tugas' => 'Resume peranan organisasi pemuda Budi Utomo'],
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
            'AKL' => [
                ['materi' => 'Siklus Akuntansi Perusahaan Dagang & Jurnal Khusus', 'tugas' => 'Pencatatan bukti transaksi ke jurnal penjualan dan pembelian'],
                ['materi' => 'Penyusunan Kertas Kerja (Neraca Lajur) 10 Kolom', 'tugas' => 'Menyelesaikan neraca lajur berdasarkan neraca saldo disesuaikan'],
                ['materi' => 'Laporan Keuangan: Laba Rugi, Perubahan Modal, dan Neraca', 'tugas' => 'Menyusun laporan laba rugi single step dan multiple step'],
                ['materi' => 'Pencatatan Transaksi Perpajakan PPh Pasal 21 dan PPN', 'tugas' => 'Simulasi perhitungan pajak penghasilan karyawan'],
            ],
            'PM' => [
                ['materi' => 'Prinsip Strategi Pemasaran Digital & Social Media Marketing', 'tugas' => 'Menyusun konten kalender promosi produk UMKM lokal'],
                ['materi' => 'Pengelolaan Penataan Produk (Visual Merchandising) Modern', 'tugas' => 'Desain denah penataan gondola produk ritel modern'],
                ['materi' => 'Customer Relationship Management (CRM) dan Loyalitas Pelanggan', 'tugas' => 'Studi kasus penanganan keluhan pelanggan di e-commerce'],
                ['materi' => 'Penyusunan Rencana Bisnis dan Analisis Kelayakan Usaha', 'tugas' => 'Membuat proposal business plan mini kewirausahaan'],
            ],
            'MPLB' => [
                ['materi' => 'Manajemen Kearsipan Digital dan Sistem Klasifikasi Dokumen', 'tugas' => 'Praktik pengindeksan surat dinas masuk dan keluar'],
                ['materi' => 'Prosedur Komunikasi Telepon Bisnis dan Korespondensi Resmi', 'tugas' => 'Membuat draft surat undangan rapat kerja pimpinan'],
                ['materi' => 'Otomatisasi Tata Kelola Sarana dan Prasarana Kantor', 'tugas' => 'Menyusun daftar inventaris peralatan kantor modern'],
                ['materi' => 'Etika Profesi Humas dan Keprotokolan Acara Resmi', 'tugas' => 'Simulasi susunan acara seremonial pelepasan siswa'],
            ],
            'HTL' => [
                ['materi' => 'Operasional Kantor Depan (Front Office Procedure & Reservation)', 'tugas' => 'Simulasi check-in dan check-out tamu hotel'],
                ['materi' => 'Tata Graha (Housekeeping): Pembersihan Kamar Tamu dan Linen', 'tugas' => 'Praktik standar making bed dan sanitasi bathroom'],
                ['materi' => 'Standar Pelayanan Prima Tamu Hotel Bintang (Guest Service)', 'tugas' => 'Handling complaint skenario kamar belum siap huni'],
                ['materi' => 'Manajemen Makanan dan Minuman (Food & Beverage Service Procedure)', 'tugas' => 'Praktik table set-up standar fine dining restaurant'],
            ],
            'KLN' => [
                ['materi' => 'Sanitasi, Higiene Makanan, dan Keselamatan Kerja Dapur', 'tugas' => 'Penerapan prinsip HACCP pada penyimpanan bahan basah'],
                ['materi' => 'Pengolahan Makanan Kontinental dan Teknik Dasar Memasak', 'tugas' => 'Praktikum pembuatan mother sauce (Béchamel & Velouté)'],
                ['materi' => 'Pengolahan Pastry & Bakery: Roti Manis dan Aneka Kue', 'tugas' => 'Kalkulasi resep dan praktik pembuatan adonan donat kentang'],
                ['materi' => 'Tata Hidang dan Penataan Piring (Plating Technique) Modern', 'tugas' => 'Dokumentasi foto kreasi plating hidangan utama nusantara'],
            ],
            'DKV' => [
                ['materi' => 'Prinsip Desain Grafis, Teori Warna, dan Tata Letak (Layout)', 'tugas' => 'Membuat moodboard desain visual poster festival seni'],
                ['materi' => 'Pembuatan Ilustrasi Vektor dan Maskot Karakter', 'tugas' => 'Desain karakter maskot sekolah berbasis vektor'],
                ['materi' => 'Tipografi Lanjutan dan Desain Identitas Visual (Branding)', 'tugas' => 'Merancang logo dan brand guideline usaha rintisan'],
                ['materi' => 'Dasar Fotografi Produk dan Pengeditan Gambar Digital', 'tugas' => 'Hunting foto produk dan teknik touch up Photoshop'],
            ],
        ];

        // ── 6. Cache Siswa per Kelas untuk Presensi ───────────────────────────
        $kelasSiswaMap = [];
        foreach ($allClasses as $className => $k) {
            $kelasSiswaMap[$k->id] = SiswaProfile::where('kelas_id', $k->id)
                ->pluck('user_id')
                ->toArray();
        }

        // Siswa Reviewer: Pradipta
        $pradipta = User::where('name', 'Pradipta Endra Maulana')->first();

        // ── 7. Rentang Waktu Simulasi Pertemuan (01–18 September 2026) ────────
        $startDate = Carbon::create(2026, 9, 1);
        $today = Carbon::create(2026, 9, 18);
        $period = CarbonPeriod::create($startDate, $today);

        $totalPertemuanCount = 0;
        $allJadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru'])->get();

        $jadwalMeetingCounter = [];
        $guruMeetingCounter = [];
        $pradiptaMeetingCounter = 0;

        // Buka transaksi database untuk proses batch yang super cepat
        DB::beginTransaction();

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

                $kode = $jadwal->mataPelajaran->kode;
                $syllabusList = $syllabus[$kode] ?? [
                    ['materi' => 'Pembahasan Materi Pertemuan ke-'.$meetingIndex, 'tugas' => 'Mengerjakan tugas lembar kerja siswa'],
                ];
                $syllabusItem = $syllabusList[($meetingIndex - 1) % count($syllabusList)];

                $isToday = $currentDate->isSameDay($today);
                $pertemuanStatus = $isToday ? 'berlangsung' : 'selesai';

                $createdAt = $currentDate->copy()->setTimeFromTimeString($jadwal->jam_mulai);
                $updatedAt = $currentDate->copy()->setTimeFromTimeString($jadwal->jam_selesai);

                $pertemuan = Pertemuan::create([
                    'jadwal_id' => $jadwal->id,
                    'tanggal' => $currentDate->format('Y-m-d'),
                    'materi_ajar' => $syllabusItem['materi'],
                    'penugasan' => $syllabusItem['tugas'],
                    'status' => $pertemuanStatus,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);

                $totalPertemuanCount++;

                // ── Tentukan Status Kehadiran Guru Secara Realistis ──────────
                $guruStatus = 'hadir';
                $alasanTidakHadir = null;
                $keteranganGuru = null;
                $guruPengganti = null;
                $waktuHadir = $createdAt->copy()->subMinutes(rand(5, 15));

                if ($guruName === 'Nastiti, S.Pd.') {
                    if ($gMeetingIdx === 3) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'sakit';
                        $keteranganGuru = 'Surat dokter terlampir (Flu & demam)';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 8) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'rapat_dinas';
                        $keteranganGuru = 'Menghadiri Rapat Koordinasi Kurikulum MGMP';
                        $waktuHadir = null;
                    }
                } elseif ($guruName === 'Ahmad Fauzi, M.Pd.') {
                    // 100% Hadir
                    $guruStatus = 'hadir';
                } elseif ($guruName === 'Budi Santoso, S.Kom.') {
                    if ($gMeetingIdx === 5) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'tugas_luar';
                        $keteranganGuru = 'Pelatihan Asesor Uji Kompetensi Keahlian';
                        $waktuHadir = null;
                    }
                } elseif ($guruName === 'Dewi Lestari, S.Pd.') {
                    if ($gMeetingIdx === 4) {
                        $guruStatus = 'terlambat';
                        $waktuHadir = $createdAt->copy()->addMinutes(15);
                        $keteranganGuru = 'Terjebak macet perbaikan jalan';
                    } elseif ($gMeetingIdx === 7) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'sakit';
                        $keteranganGuru = 'Sakit demam, rawat jalan';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 10) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'dinas_luar';
                        $keteranganGuru = 'Workshop Pengimbasan SMK PK di Dinas Pendidikan';
                        $waktuHadir = null;
                    }
                } elseif ($guruName === 'Siti Nurhaliza, S.Pd.') {
                    if ($gMeetingIdx === 2) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'sakit';
                        $keteranganGuru = 'Surat izin sakit terlampir';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 4) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'tugas_luar';
                        $keteranganGuru = 'Mendampingi lomba debat Bahasa Inggris';
                        $waktuHadir = null;
                    } elseif ($gMeetingIdx === 6) {
                        $guruStatus = 'terlambat';
                        $waktuHadir = $createdAt->copy()->addMinutes(20);
                    } elseif ($gMeetingIdx === 8) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'tanpa_keterangan';
                        $guruPengganti = 'Ahmad Fauzi, M.Pd.';
                        $keteranganGuru = 'Digantikan oleh guru piket';
                        $waktuHadir = null;
                    }
                } else {
                    // Distribusi kehadiran realistis untuk guru-guru lainnya
                    $seed = ($jadwal->guru_id * 17 + $meetingIndex * 23) % 100;
                    if ($seed < 82) {
                        $guruStatus = 'hadir';
                        $waktuHadir = $createdAt->copy()->subMinutes(rand(5, 12));
                    } elseif ($seed < 90) {
                        $guruStatus = 'terlambat';
                        $waktuHadir = $createdAt->copy()->addMinutes(rand(8, 18));
                        $keteranganGuru = 'Terlambat masuk kelas karena urusan piket / koordinasi';
                    } elseif ($seed < 94) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'dinas_luar';
                        $keteranganGuru = 'Surat tugas dinas luar nomor ST/2026/09/'.rand(100, 999);
                        $waktuHadir = null;
                    } elseif ($seed < 97) {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'sakit';
                        $keteranganGuru = 'Izin sakit rawat jalan';
                        $waktuHadir = null;
                    } else {
                        $guruStatus = 'tidak_hadir';
                        $alasanTidakHadir = 'izin';
                        $keteranganGuru = 'Izin keperluan keluarga mendesak';
                        $waktuHadir = null;
                    }
                }

                KehadiranGuru::create([
                    'pertemuan_id' => $pertemuan->id,
                    'guru_id' => $jadwal->guru_id,
                    'status' => $guruStatus,
                    'alasan_tidak_hadir' => $alasanTidakHadir,
                    'guru_pengganti_nama' => $guruPengganti,
                    'keterangan' => $keteranganGuru,
                    'waktu_hadir' => $waktuHadir,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);

                // ── Presensi Siswa untuk Kelas Terpilih (PPLG, RPL, DKV, AKL1) ─
                // Fokuskan pengisian presensi siswa pada sampel kelas agar eksekusi seed tetap super cepat (< 5 detik)
                $targetClassesForStudentAttendance = ['11PPLG', '12RPL', '11DKV', '11AKL1'];
                if (in_array($jadwal->kelas->nama, $targetClassesForStudentAttendance)) {
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

                    if (! empty($kehadiranSiswaBatch)) {
                        foreach (array_chunk($kehadiranSiswaBatch, 200) as $chunk) {
                            KehadiranSiswa::insert($chunk);
                        }
                    }

                    // Foto bukti siswa reviewer (Pradipta di 12RPL)
                    if ($pradipta && in_array($pradipta->id, $studentIds) && $guruStatus === 'hadir') {
                        FotoBukti::create([
                            'pertemuan_id' => $pertemuan->id,
                            'siswa_id' => $pradipta->id,
                            'foto_path' => 'images/logo_new.png',
                            'status_guru_dilaporkan' => 'hadir',
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt,
                        ]);
                    }
                }
            }
        }

        DB::commit();

        // ── 9. Cetak Rangkuman Statistik ──────────────────────────────────────
        $totalGuruCount = count($gurusData);
        $totalJadwalCount = JadwalPelajaran::count();
        $this->command->info('');
        $this->command->info("🎉 Berhasil membuat {$totalGuruCount} guru, {$totalJadwalCount} jadwal di 19 kelas, dan {$totalPertemuanCount} riwayat pertemuan!");
        $this->command->info('');
        $this->command->info('📊 PERSENTASE KEHADIRAN GURU (Sampel Periode 01–18 September 2026):');

        $sampleNames = [
            'Nastiti, S.Pd.', 'Ahmad Fauzi, M.Pd.', 'Dewi Lestari, S.Pd.', 'Budi Santoso, S.Kom.',
            'Siti Nurhaliza, S.Pd.', 'Bambang Wijaya, S.E., M.Ak.', 'Rina Marlina, S.Pd., M.M.',
            'Fitri Handayani, S.Par.', 'Chef Junaedi, S.Pd.', 'Ilham Hidayat, S.Sn.',
        ];

        foreach ($sampleNames as $name) {
            $user = $teachers[$name] ?? null;
            if (! $user) {
                continue;
            }

            $records = KehadiranGuru::where('guru_id', $user->id)->get();
            $total = $records->count();
            $hadir = $records->where('status', 'hadir')->count();
            $terlambat = $records->where('status', 'terlambat')->count();
            $tidakHadir = $records->where('status', 'tidak_hadir')->count();
            $pct = $total > 0 ? round(($hadir / $total) * 100) : 0;

            $this->command->info(sprintf(
                '   %-26s: %3d%% Hadir (Hadir: %2d, Terlambat: %2d, Tidak Hadir: %2d | Total: %2d)',
                $name,
                $pct,
                $hadir,
                $terlambat,
                $tidakHadir,
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

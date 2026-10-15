<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuruDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->guru = User::create([
            'name' => 'Endah Rahayu Test, S.Pd.',
            'email' => 'endah.test@guru.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => '11 PPLG 1',
            'tingkat' => 11,
            'jurusan' => 'PPLG',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Pemrograman Web',
            'kode' => 'PW11',
            'jenis' => 'produktif',
        ]);

        $this->jadwal = JadwalPelajaran::create([
            'guru_id' => $this->guru->id,
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:15:00',
            'jam_selesai' => '08:45:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }

    public function test_guru_dashboard_loads_and_displays_rincian_buttons_and_modal(): void
    {
        $response = $this->actingAs($this->guru)->get(route('guru.dashboard'));

        $response->assertOk();
        $response->assertSee('Statistik Kehadiran Saya');
        $response->assertSee('Rincian');
        $response->assertSee('modal-rincian-kehadiran');
    }

    public function test_guru_dashboard_separates_izin_from_alpa_properly(): void
    {
        // Create 2 Hadir sessions
        for ($i = 0; $i < 2; $i++) {
            $p = Pertemuan::create([
                'jadwal_id' => $this->jadwal->id,
                'tanggal' => Carbon::now()->subDays($i + 1),
                'materi_ajar' => "Materi $i",
                'status' => 'selesai',
            ]);
            KehadiranGuru::create([
                'pertemuan_id' => $p->id,
                'guru_id' => $this->guru->id,
                'status' => 'hadir',
                'waktu_hadir' => now(),
            ]);
        }

        // Create 1 Izin session (stored as tidak_hadir with alasan_tidak_hadir = izin)
        $pIzin = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::now()->subDays(3),
            'materi_ajar' => 'Materi Izin',
            'status' => 'selesai',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pIzin->id,
            'guru_id' => $this->guru->id,
            'status' => 'tidak_hadir',
            'alasan_tidak_hadir' => 'izin',
            'keterangan' => 'Rapat dinas di luar sekolah',
        ]);

        $response = $this->actingAs($this->guru)->get(route('guru.dashboard'));

        $response->assertOk();

        /** @var array $stats */
        $stats = $response->viewData('stats');
        /** @var array $detailedAttendance */
        $detailedAttendance = $response->viewData('detailedAttendance');

        // Total should be 3 (2 Hadir, 1 Izin, 0 Alpa)
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['hadir']);
        $this->assertEquals(1, $stats['izin']);
        $this->assertEquals(0, $stats['alpa']);
        $this->assertEquals(0, $stats['sakit']);

        // Check detailed attendance array
        $this->assertCount(1, $detailedAttendance['izin']);
        $this->assertCount(0, $detailedAttendance['alpa']);
        $this->assertEquals('11 PPLG 1', $detailedAttendance['izin'][0]['kelas']);
        $this->assertEquals('Rapat dinas di luar sekolah', $detailedAttendance['izin'][0]['keterangan']);
    }

    public function test_guru_dashboard_records_real_alpa_accurately(): void
    {
        // Create 1 real Alpa session
        $pAlpa = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::now()->subDays(4),
            'materi_ajar' => 'Materi Alpa',
            'status' => 'selesai',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pAlpa->id,
            'guru_id' => $this->guru->id,
            'status' => 'alpa',
            'keterangan' => 'Tanpa kabar',
        ]);

        $response = $this->actingAs($this->guru)->get(route('guru.dashboard'));

        $response->assertOk();

        /** @var array $stats */
        $stats = $response->viewData('stats');
        /** @var array $detailedAttendance */
        $detailedAttendance = $response->viewData('detailedAttendance');

        $this->assertEquals(1, $stats['alpa']);
        $this->assertCount(1, $detailedAttendance['alpa']);
        $this->assertEquals('Tanpa kabar', $detailedAttendance['alpa'][0]['keterangan']);
    }
}

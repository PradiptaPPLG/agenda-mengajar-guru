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

class KepalaSekolahReportGuruTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $guru1;

    private User $guru2;

    private Kelas $kelas10;

    private Kelas $kelas11;

    private MataPelajaran $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->kepsek = User::create([
            'name' => 'Kepala Sekolah Test',
            'email' => 'kepsek@test.com',
            'password' => Hash::make('password'),
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->guru1 = User::create([
            'name' => 'Guru Satu',
            'email' => 'guru1@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->guru2 = User::create([
            'name' => 'Guru Dua',
            'email' => 'guru2@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Pemrograman Web',
            'kode' => 'PW01',
            'jenis' => 'produktif',
        ]);

        $this->kelas10 = Kelas::create([
            'nama' => '10PPLG',
            'tingkat' => '10',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->kelas11 = Kelas::create([
            'nama' => '11AKL1',
            'tingkat' => '11',
            'tahun_ajaran' => '2026/2027',
        ]);
    }

    public function test_kepsek_can_access_laporan_kehadiran_guru_page(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.guru'));

        $response->assertStatus(200);
        $response->assertSee('Distribusi Persentase Kehadiran & Alasan Ketidakhadiran Guru', false);
        $response->assertSee('Semua Angkatan');
        $response->assertSee('Kelas 10 (Fase E)');
        $response->assertSee('alasanKehadiranGuruPieChart');
    }

    public function test_breakdown_percentage_of_guru_reasons_calculated_accurately(): void
    {
        $jadwal1 = JadwalPelajaran::create([
            'kelas_id' => $this->kelas10->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $pertemuan1 = Pertemuan::create([
            'jadwal_id' => $jadwal1->id,
            'tanggal' => Carbon::now()->subDays(10)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $pertemuan2 = Pertemuan::create([
            'jadwal_id' => $jadwal1->id,
            'tanggal' => Carbon::now()->subDays(9)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $pertemuan3 = Pertemuan::create([
            'jadwal_id' => $jadwal1->id,
            'tanggal' => Carbon::now()->subDays(8)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $pertemuan4 = Pertemuan::create([
            'jadwal_id' => $jadwal1->id,
            'tanggal' => Carbon::now()->subDays(7)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // 2 Hadir, 1 Dinas Luar ke Bandung, 1 Sakit Flu -> Total 4 log
        // Hadir: 50%, Dinas Luar: 25%, Sakit Flu: 25%
        KehadiranGuru::create([
            'pertemuan_id' => $pertemuan1->id,
            'guru_id' => $this->guru1->id,
            'status' => 'hadir',
            'keterangan' => null,
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pertemuan2->id,
            'guru_id' => $this->guru1->id,
            'status' => 'hadir',
            'keterangan' => null,
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pertemuan3->id,
            'guru_id' => $this->guru1->id,
            'status' => 'dispensasi',
            'alasan_tidak_hadir' => 'dinas_luar',
            'keterangan' => 'Dinas Luar ke Bandung',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pertemuan4->id,
            'guru_id' => $this->guru1->id,
            'status' => 'sakit',
            'alasan_tidak_hadir' => 'sakit',
            'keterangan' => 'Sakit Flu',
        ]);

        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.guru'));

        $response->assertStatus(200);
        $response->assertSee('50.00%');
        $response->assertSee('25.00%');
        $response->assertSee('Dinas Luar ke Bandung');
        $response->assertSee('Sakit Flu');
    }

    public function test_filter_guru_report_by_tingkat_and_jurusan(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.guru', [
            'tingkat' => '10',
            'jurusan' => 'PPLG',
        ]));

        $response->assertStatus(200);
        $response->assertSee('10PPLG');
    }

    public function test_export_pdf_guru_report_works(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.guru', [
            'tingkat' => '10',
            'jurusan' => 'PPLG',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}

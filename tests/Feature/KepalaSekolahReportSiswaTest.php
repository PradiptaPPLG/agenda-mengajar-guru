<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KepalaSekolahReportSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $guru;

    private Kelas $kelas10;

    private Kelas $kelas12;

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

        $this->guru = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@test.com',
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

        $this->kelas12 = Kelas::create([
            'nama' => '12AKL1',
            'tingkat' => '12',
            'tahun_ajaran' => '2026/2027',
        ]);
    }

    public function test_kepsek_can_access_laporan_kehadiran_siswa_page(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa'));

        $response->assertStatus(200);
        $response->assertSee('Distribusi Persentase Kehadiran');
        $response->assertSee('Semua Angkatan');
        $response->assertSee('Kelas 10 (Fase E)');
    }

    public function test_breakdown_percentage_of_reasons_calculated_accurately(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas10->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $pertemuan = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => Carbon::now()->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa1 = User::create(['name' => 'Siswa Hadir', 'email' => 's1@test.com', 'password' => Hash::make('password'), 'role' => 'siswa']);
        $siswa2 = User::create(['name' => 'Siswa Demam', 'email' => 's2@test.com', 'password' => Hash::make('password'), 'role' => 'siswa']);
        $siswa3 = User::create(['name' => 'Siswa Asam Lambung', 'email' => 's3@test.com', 'password' => Hash::make('password'), 'role' => 'siswa']);
        $siswa4 = User::create(['name' => 'Siswa Hadir 2', 'email' => 's4@test.com', 'password' => Hash::make('password'), 'role' => 'siswa']);

        SiswaProfile::create(['user_id' => $siswa1->id, 'kelas_id' => $this->kelas10->id, 'nis' => '1001']);
        SiswaProfile::create(['user_id' => $siswa2->id, 'kelas_id' => $this->kelas10->id, 'nis' => '1002']);
        SiswaProfile::create(['user_id' => $siswa3->id, 'kelas_id' => $this->kelas10->id, 'nis' => '1003']);
        SiswaProfile::create(['user_id' => $siswa4->id, 'kelas_id' => $this->kelas10->id, 'nis' => '1004']);

        // 2 Hadir, 1 Demam, 1 Asam Lambung -> Total 4 log
        // Hadir: 50%, Demam: 25%, Asam Lambung: 25%
        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswa1->id, 'status' => 'hadir', 'keterangan' => null]);
        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswa4->id, 'status' => 'hadir', 'keterangan' => null]);
        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswa2->id, 'status' => 'sakit', 'keterangan' => 'Demam']);
        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswa3->id, 'status' => 'sakit', 'keterangan' => 'Asam Lambung']);

        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa'));

        $response->assertStatus(200);
        $response->assertSee('50.00%');
        $response->assertSee('25.00%');
        $response->assertSee('Demam');
        $response->assertSee('Asam Lambung');
    }

    public function test_filter_by_tingkat_and_jurusan_works(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa', [
            'tingkat' => '10',
            'jurusan' => 'PPLG',
        ]));

        $response->assertStatus(200);
        $response->assertSee('10PPLG');
    }

    public function test_kepsek_can_download_laporan_kehadiran_siswa_pdf(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.siswa', [
            'tingkat' => '10',
            'jurusan' => 'PPLG',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_invalid_date_inputs_handled_gracefully(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa', [
            'filter_mode' => 'custom',
            'start_date' => 'invalid-date',
            'end_date' => 'not-a-real-date',
        ]));

        $response->assertStatus(200);
    }
}

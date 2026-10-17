<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
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

class ExportPerformanceAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $admin;

    private User $guru;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->kepsek = User::create([
            'name' => 'Kepala Sekolah Test',
            'email' => 'kepsek.export@test.com',
            'password' => Hash::make('password'),
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.export@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Wali Kelas Test',
            'email' => 'guru.export@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => '10PPLG1',
            'tingkat' => '10',
            'tahun_ajaran' => '2026/2027',
            'wali_kelas_id' => $this->guru->id,
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Pemrograman Berorientasi Objek',
            'kode' => 'PBO01',
            'jenis' => 'produktif',
        ]);

        $this->jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }

    /**
     * Test 1: Ekspor PDF Laporan Guru oleh Kepala Sekolah.
     */
    public function test_kepsek_export_pdf_guru_returns_valid_download(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::now()->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        KehadiranGuru::create([
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $this->guru->id,
            'status' => 'hadir',
            'keterangan' => null,
        ]);

        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.guru', [
            'filter_mode' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'tingkat' => '10',
            'jurusan' => 'PPLG',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
    }

    /**
     * Test 2: Ekspor PDF Laporan Siswa (Mode Semester Fast-Path & Mode Custom Fallback).
     */
    public function test_kepsek_export_pdf_siswa_both_modes_work_and_return_valid_pdf(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 8, 15)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Export Test',
            'email' => 'siswa.export@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '8801',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);

        // Mode Semester (Fast-Path via rekap_kehadiran_siswas)
        $responseSem = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.siswa', [
            'filter_mode' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'kelas_id' => $this->kelas->id,
        ]));

        $responseSem->assertStatus(200);
        $this->assertEquals('application/pdf', $responseSem->headers->get('content-type'));

        // Mode Custom (Fallback via BaseKehadiranQuery)
        $responseCust = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.siswa', [
            'filter_mode' => 'custom',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'kelas_id' => $this->kelas->id,
        ]));

        $responseCust->assertStatus(200);
        $this->assertEquals('application/pdf', $responseCust->headers->get('content-type'));
    }

    /**
     * Test 3: Ekspor PDF & Excel Rekapitulasi Wali Kelas.
     */
    public function test_wali_kelas_export_pdf_and_excel(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::now()->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Binaan Wali',
            'email' => 'siswa.wali@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '8802',
            'nisn' => '008802',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);

        // 1. Export PDF
        $responsePdf = $this->actingAs($this->guru)->get(route('guru.wali-kelas.export-pdf', [
            'kelas_id' => $this->kelas->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $responsePdf->assertStatus(200);
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));

        // 2. Export Excel (.xlsx)
        $responseExcel = $this->actingAs($this->guru)->get(route('guru.wali-kelas.export-excel', [
            'kelas_id' => $this->kelas->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $responseExcel->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', (string) $responseExcel->headers->get('content-disposition'));
    }

    /**
     * Test 4: Ekspor PDF & Excel Rekap Presensi Mata Pelajaran Guru.
     */
    public function test_guru_rekap_export_pdf_and_excel(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::now()->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Mapel Guru',
            'email' => 'siswa.mapel@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '8803',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'terlambat',
        ]);

        // 1. Export PDF Rekap Guru
        $responsePdf = $this->actingAs($this->guru)->get(route('guru.rekap.export-pdf', [
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'periode_type' => 'bulanan',
            'bulan' => Carbon::now()->format('Y-m'),
        ]));
        $responsePdf->assertStatus(200);
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));

        // 2. Export Excel Rekap Guru
        $responseExcel = $this->actingAs($this->guru)->get(route('guru.rekap.export-excel', [
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'periode_type' => 'bulanan',
            'bulan' => Carbon::now()->format('Y-m'),
        ]));
        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $responseExcel->headers->get('content-disposition'));
    }

    /**
     * Test 5: Ekspor Excel & PDF Data Jadwal Pelajaran (Admin).
     */
    public function test_admin_export_jadwal_excel_and_pdf(): void
    {
        // 1. Excel
        $responseExcel = $this->actingAs($this->admin)->get(route('admin.jadwal.export.excel', [
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $responseExcel->headers->get('content-disposition'));

        // 2. PDF
        $responsePdf = $this->actingAs($this->admin)->get(route('admin.jadwal.export.pdf', [
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $responsePdf->assertStatus(200);
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));
    }

    /**
     * Test 6: Ekspor Excel & PDF Data Guru (Admin).
     */
    public function test_admin_export_users_excel_and_pdf(): void
    {
        // 1. Excel
        $responseExcel = $this->actingAs($this->admin)->get(route('admin.users.export.excel'));
        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $responseExcel->headers->get('content-disposition'));

        // 2. PDF
        $responsePdf = $this->actingAs($this->admin)->get(route('admin.users.export.pdf'));
        $responsePdf->assertStatus(200);
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));
    }
}

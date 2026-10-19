<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\RekapKehadiranSiswa;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $guru;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator Sekolah',
            'email' => 'admin@test.com',
            'username' => 'adminsekolah',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Pengajar',
            'email' => 'guru@test.com',
            'username' => 'gurupengajar',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => 'XII RPL 1',
            'tingkat' => '12',
            'tahun_ajaran' => '2026/2027',
        ]);
    }

    /**
     * Test 1: Super Admin dapat mengakses halaman form generator laporan kustom.
     */
    public function test_super_admin_can_access_custom_report_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.custom-report.index'));

        $response->assertOk();
        $response->assertSee('Custom Dynamic Report');
        $response->assertSee('Rekap Kehadiran Siswa');
        $response->assertSee('Log Aktivitas');
        $response->assertSee('Data Master Siswa');
    }

    /**
     * Test 2: Role selain Super Admin (Admin dan Guru) ditolak akses (403 Forbidden).
     */
    public function test_non_super_admin_cannot_access_custom_report_page(): void
    {
        // Admin biasa
        $resAdmin = $this->actingAs($this->admin)->get(route('super-admin.custom-report.index'));
        $resAdmin->assertForbidden();

        // Guru
        $resGuru = $this->actingAs($this->guru)->get(route('super-admin.custom-report.index'));
        $resGuru->assertForbidden();
    }

    /**
     * Test 3: Ekspor Rekap Kehadiran menghasilkan file .xlsx multi-sheet dengan status 200.
     */
    public function test_export_rekap_kehadiran_generates_valid_excel_download(): void
    {
        $siswa = User::create([
            'name' => 'Ahmad Fulan',
            'email' => 'ahmad@siswa.test',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'nis' => '12345',
            'kelas_id' => $this->kelas->id,
            'kelompok_blok' => 'kelompok_a',
        ]);

        RekapKehadiranSiswa::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'total' => 20,
            'hadir' => 18,
            'hadir_murni' => 17,
            'terlambat' => 1,
            'sakit' => 1,
            'izin' => 1,
            'alpa' => 0,
            'dispensasi' => 0,
        ]);

        $payload = [
            'module' => 'rekap_kehadiran',
            'columns' => ['nama_siswa', 'nis', 'kelas', 'hadir', 'sakit', 'izin', 'alpa', 'persentase_kehadiran'],
            'include_pivot' => 1,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'kelas_id' => $this->kelas->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.custom-report.export'), $payload);

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Test 4: Ekspor Log Aktivitas menghasilkan file .xlsx dengan status 200.
     */
    public function test_export_activity_log_generates_valid_excel_download(): void
    {
        ActivityLog::create([
            'user_name' => 'Super Admin',
            'role' => 'super_admin',
            'action' => 'LOGIN',
            'module' => 'Auth',
            'description' => 'Super Admin masuk ke sistem',
            'ip_address' => '127.0.0.1',
        ]);

        $payload = [
            'module' => 'activity_log',
            'columns' => ['waktu', 'user_name', 'role', 'action', 'module', 'description', 'ip_address'],
            'include_pivot' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.custom-report.export'), $payload);

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('custom_report_activity_log', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Test 5: Ekspor Master Siswa menghasilkan file .xlsx dengan status 200.
     */
    public function test_export_master_siswa_generates_valid_excel_download(): void
    {
        $siswa = User::create([
            'name' => 'Budi Siswa',
            'email' => 'budi@siswa.test',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'nis' => '998877',
            'kelas_id' => $this->kelas->id,
            'kelompok_blok' => 'kelompok_b',
        ]);

        $payload = [
            'module' => 'master_siswa',
            'columns' => ['nama', 'nis', 'kelas', 'tingkat', 'kelompok_blok', 'email', 'status_akun'],
            'include_pivot' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.custom-report.export'), $payload);

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('custom_report_master_siswa', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Test 6: Validasi ketat menolak request tanpa kolom atau kolom yang tidak terdaftar (whitelist guard).
     */
    public function test_export_rejects_empty_or_invalid_columns(): void
    {
        // 1. Tanpa kolom
        $resNoCols = $this->actingAs($this->superAdmin)->post(route('super-admin.custom-report.export'), [
            'module' => 'rekap_kehadiran',
            'columns' => [],
        ]);
        $resNoCols->assertSessionHasErrors('columns');

        // 2. Kolom manipulatif / non-whitelist
        $resInvalidCols = $this->actingAs($this->superAdmin)->post(route('super-admin.custom-report.export'), [
            'module' => 'rekap_kehadiran',
            'columns' => ['hack_column_injection', 'select * from users'],
        ]);
        $resInvalidCols->assertSessionHasErrors('columns');
    }
}

<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\RekapKehadiranSiswa;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $admin;

    private User $guru;

    private Kelas $kelasPplg;

    private Kelas $kelasAkl;

    private MataPelajaran $mapelWeb;

    private JadwalPelajaran $jadwalPplg;

    private JadwalPelajaran $jadwalAkl;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->kepsek = User::create([
            'name' => 'Dr. H. Kepala Sekolah',
            'email' => 'kepsek.audit@test.com',
            'password' => Hash::make('password'),
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sistem Audit',
            'email' => 'admin.audit@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Budi Santoso, S.Kom.',
            'email' => 'guru.audit@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        GuruProfile::create([
            'user_id' => $this->guru->id,
            'nip' => '198501012010011005',
        ]);

        $this->kelasPplg = Kelas::create([
            'nama' => '10 PPLG 1',
            'tingkat' => '10',
            'tahun_ajaran' => '2026/2027',
            'wali_kelas_id' => $this->guru->id,
        ]);

        $this->kelasAkl = Kelas::create([
            'nama' => '11 AKL 2',
            'tingkat' => '11',
            'tahun_ajaran' => '2026/2027',
            'wali_kelas_id' => $this->guru->id,
        ]);

        $this->mapelWeb = MataPelajaran::create([
            'nama' => 'Pemrograman Web & Perangkat Bergerak',
            'kode' => 'PWPB01',
            'jenis' => 'produktif',
        ]);

        $this->jadwalPplg = JadwalPelajaran::create([
            'kelas_id' => $this->kelasPplg->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapelWeb->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '09:00:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $this->jadwalAkl = JadwalPelajaran::create([
            'kelas_id' => $this->kelasAkl->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapelWeb->id,
            'hari' => 2,
            'jam_mulai' => '09:30:00',
            'jam_selesai' => '11:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }

    /**
     * Skenario 1: Soft-Delete Pertemuan & Restore.
     * Buat pertemuan beserta presensi siswa, verifikasi data masuk ke rekap_kehadiran_siswas.
     * Lakukan delete() (soft delete) pada pertemuan tersebut, pastikan angka rekap siswa langsung
     * berkurang/menyesuaikan dengan akurat, dan pulih saat restore().
     */
    public function test_scenario_1_pertemuan_soft_delete_and_restore_recalculates_rekap_accurately(): void
    {
        $siswaA = User::create(['name' => 'Siswa A', 'email' => 'siswaA@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);
        $siswaB = User::create(['name' => 'Siswa B', 'email' => 'siswaB@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);

        SiswaProfile::create(['user_id' => $siswaA->id, 'kelas_id' => $this->kelasPplg->id, 'nis' => '1101']);
        SiswaProfile::create(['user_id' => $siswaB->id, 'kelas_id' => $this->kelasPplg->id, 'nis' => '1102']);

        // Pertemuan 1
        $p1 = Pertemuan::create([
            'jadwal_id' => $this->jadwalPplg->id,
            'tanggal' => Carbon::create(2026, 8, 3)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        KehadiranSiswa::create(['pertemuan_id' => $p1->id, 'siswa_id' => $siswaA->id, 'status' => 'hadir']);
        KehadiranSiswa::create(['pertemuan_id' => $p1->id, 'siswa_id' => $siswaB->id, 'status' => 'izin', 'keterangan' => 'Acara']);

        // Pertemuan 2
        $p2 = Pertemuan::create([
            'jadwal_id' => $this->jadwalPplg->id,
            'tanggal' => Carbon::create(2026, 8, 10)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        KehadiranSiswa::create(['pertemuan_id' => $p2->id, 'siswa_id' => $siswaA->id, 'status' => 'hadir']);
        KehadiranSiswa::create(['pertemuan_id' => $p2->id, 'siswa_id' => $siswaB->id, 'status' => 'hadir']);

        // 1. Verifikasi rekap awal (2 pertemuan)
        $rekapA = RekapKehadiranSiswa::where('siswa_id', $siswaA->id)->first();
        $rekapB = RekapKehadiranSiswa::where('siswa_id', $siswaB->id)->first();

        $this->assertNotNull($rekapA);
        $this->assertEquals(2, $rekapA->total);
        $this->assertEquals(2, $rekapA->hadir);
        $this->assertEquals(0, $rekapA->izin);

        $this->assertNotNull($rekapB);
        $this->assertEquals(2, $rekapB->total);
        $this->assertEquals(1, $rekapB->hadir);
        $this->assertEquals(1, $rekapB->izin);

        // 2. Soft delete Pertemuan 2
        $p2->delete();

        $rekapA->refresh();
        $rekapB->refresh();

        $this->assertEquals(1, $rekapA->total, 'Total Siswa A harus berkurang menjadi 1');
        $this->assertEquals(1, $rekapA->hadir, 'Hadir Siswa A harus berkurang menjadi 1');

        $this->assertEquals(1, $rekapB->total, 'Total Siswa B harus berkurang menjadi 1');
        $this->assertEquals(0, $rekapB->hadir, 'Hadir Siswa B harus berkurang menjadi 0');
        $this->assertEquals(1, $rekapB->izin, 'Izin Siswa B tetap 1');

        // 3. Restore Pertemuan 2
        $p2->restore();

        $rekapA->refresh();
        $rekapB->refresh();

        $this->assertEquals(2, $rekapA->total, 'Total Siswa A harus kembali menjadi 2');
        $this->assertEquals(2, $rekapA->hadir, 'Hadir Siswa A harus kembali menjadi 2');

        $this->assertEquals(2, $rekapB->total, 'Total Siswa B harus kembali menjadi 2');
        $this->assertEquals(1, $rekapB->hadir, 'Hadir Siswa B harus kembali menjadi 1');
        $this->assertEquals(1, $rekapB->izin, 'Izin Siswa B tetap 1');

        // 4. Soft delete seluruh pertemuan (P1 & P2) -> rekap harus otomatis terhapus
        $p1->delete();
        $p2->delete();

        $this->assertNull(RekapKehadiranSiswa::where('siswa_id', $siswaA->id)->first());
        $this->assertNull(RekapKehadiranSiswa::where('siswa_id', $siswaB->id)->first());
    }

    /**
     * Skenario 2: Perubahan Status Siswa.
     * Ubah status kehadiran seorang siswa dari 'hadir' menjadi 'sakit'.
     * Pastikan nilai 'hadir' di tabel rekap berkurang 1 dan nilai 'sakit' bertambah 1.
     */
    public function test_scenario_2_student_status_mutation_decrements_and_increments_rekap_properly(): void
    {
        $siswa = User::create(['name' => 'Siswa Mutasi', 'email' => 'mutasi@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);
        SiswaProfile::create(['user_id' => $siswa->id, 'kelas_id' => $this->kelasPplg->id, 'nis' => '1103']);

        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwalPplg->id,
            'tanggal' => Carbon::create(2026, 8, 17)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // 1. Catat hadir
        $kehadiran = KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);

        $rekap = RekapKehadiranSiswa::where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($rekap);
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(1, $rekap->hadir);
        $this->assertEquals(1, $rekap->hadir_murni);
        $this->assertEquals(0, $rekap->sakit);

        // 2. Ubah hadir -> sakit
        $kehadiran->update(['status' => 'sakit', 'keterangan' => 'Gejala Tifus']);

        $rekap->refresh();
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(0, $rekap->hadir, 'Hadir harus berkurang 1 menjadi 0');
        $this->assertEquals(0, $rekap->hadir_murni);
        $this->assertEquals(1, $rekap->sakit, 'Sakit harus bertambah 1');

        // 3. Ubah sakit -> terlambat (kategori hadir)
        $kehadiran->update(['status' => 'terlambat', 'keterangan' => 'Ban bocor']);

        $rekap->refresh();
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(1, $rekap->hadir, 'Kategori hadir harus bertambah 1');
        $this->assertEquals(0, $rekap->hadir_murni, 'Hadir murni tetap 0');
        $this->assertEquals(1, $rekap->terlambat, 'Terlambat harus bertambah 1');
        $this->assertEquals(0, $rekap->sakit, 'Sakit harus kembali 0');

        // 4. Ubah hanya keterangan (status tidak berubah) -> pastikan tidak error dan rekap tetap konsisten
        $kehadiran->update(['keterangan' => 'Ban bocor di jalan raya']);

        $rekap->refresh();
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(1, $rekap->hadir);
        $this->assertEquals(1, $rekap->terlambat);
    }

    /**
     * Skenario 3: Ekspor Data Kosong.
     * Pemanggilan endpoint Export pada rentang tanggal atau semester yang belum memiliki data sama sekali.
     * Sistem harus tetap mengembalikan file/response dengan aman tanpa Error 500 atau White Screen of Death.
     */
    public function test_scenario_3_empty_data_exports_return_200_without_500_or_wsod(): void
    {
        // 1. Ekspor PDF Guru Kepala Sekolah pada semester kosong
        $respKepsekPdfGuru = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.guru', [
            'tahun_ajaran' => '2030/2031',
            'semester' => 'genap',
            'filter_mode' => 'semester',
        ]));
        $respKepsekPdfGuru->assertStatus(200);
        $this->assertEquals('application/pdf', $respKepsekPdfGuru->headers->get('content-type'));

        // 2. Ekspor PDF Siswa Kepala Sekolah pada semester kosong
        $respKepsekPdfSiswa = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.siswa', [
            'tahun_ajaran' => '2030/2031',
            'semester' => 'genap',
            'filter_mode' => 'semester',
        ]));
        $respKepsekPdfSiswa->assertStatus(200);
        $this->assertEquals('application/pdf', $respKepsekPdfSiswa->headers->get('content-type'));

        // 3. Ekspor PDF & Excel Wali Kelas pada periode kosong
        $respWaliPdf = $this->actingAs($this->guru)->get(route('guru.wali-kelas.export-pdf', [
            'kelas_id' => $this->kelasPplg->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2030/2031',
            'semester' => 'genap',
        ]));
        $respWaliPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $respWaliPdf->headers->get('content-type'));

        $respWaliExcel = $this->actingAs($this->guru)->get(route('guru.wali-kelas.export-excel', [
            'kelas_id' => $this->kelasPplg->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2030/2031',
            'semester' => 'genap',
        ]));
        $respWaliExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $respWaliExcel->headers->get('content-disposition'));

        // 4. Ekspor PDF & Excel Rekap Guru pada periode kosong
        $respRekapPdf = $this->actingAs($this->guru)->get(route('guru.rekap.export-pdf', [
            'kelas_id' => $this->kelasPplg->id,
            'mata_pelajaran_id' => $this->mapelWeb->id,
            'periode_type' => 'bulanan',
            'bulan' => '2030-01',
        ]));
        $respRekapPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $respRekapPdf->headers->get('content-type'));

        $respRekapExcel = $this->actingAs($this->guru)->get(route('guru.rekap.export-excel', [
            'kelas_id' => $this->kelasPplg->id,
            'mata_pelajaran_id' => $this->mapelWeb->id,
            'periode_type' => 'bulanan',
            'bulan' => '2030-01',
        ]));
        $respRekapExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $respRekapExcel->headers->get('content-disposition'));

        // 5. Ekspor Admin Users & Jadwal dengan pencarian tanpa hasil
        $respAdminUsersPdf = $this->actingAs($this->admin)->get(route('admin.users.export.pdf', [
            'search' => 'GuruYangPastiTidakAdaDalamSistem999',
        ]));
        $respAdminUsersPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $respAdminUsersPdf->headers->get('content-type'));

        $respAdminUsersExcel = $this->actingAs($this->admin)->get(route('admin.users.export.excel', [
            'search' => 'GuruYangPastiTidakAdaDalamSistem999',
        ]));
        $respAdminUsersExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $respAdminUsersExcel->headers->get('content-disposition'));

        $respAdminJadwalPdf = $this->actingAs($this->admin)->get(route('admin.jadwal.export.pdf', [
            'tahun_ajaran' => '2099/2100',
        ]));
        $respAdminJadwalPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $respAdminJadwalPdf->headers->get('content-type'));

        $respAdminJadwalExcel = $this->actingAs($this->admin)->get(route('admin.jadwal.export.excel', [
            'tahun_ajaran' => '2099/2100',
        ]));
        $respAdminJadwalExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $respAdminJadwalExcel->headers->get('content-disposition'));
    }

    /**
     * Skenario 4: Filter Campuran.
     * Uji kombinasi filter tingkat, jurusan, dan kelas spesifik pada laporan dan ekspor
     * untuk memastikan query tidak mengalami SQL syntax error atau column ambiguity.
     */
    public function test_scenario_4_mixed_filter_combinations_execute_without_sql_syntax_errors(): void
    {
        $combinations = [
            // Filter mode semester
            ['tingkat' => '10', 'jurusan' => 'PPLG', 'kelas_id' => '', 'filter_mode' => 'semester', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil'],
            ['tingkat' => '11', 'jurusan' => 'AKL', 'kelas_id' => '', 'filter_mode' => 'semester', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil'],
            ['tingkat' => '12', 'jurusan' => 'all', 'kelas_id' => '', 'filter_mode' => 'semester', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil'],
            ['tingkat' => 'all', 'jurusan' => 'DKV', 'kelas_id' => '', 'filter_mode' => 'semester', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil'],
            ['tingkat' => 'all', 'jurusan' => 'all', 'kelas_id' => $this->kelasPplg->id, 'filter_mode' => 'semester', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil'],

            // Filter mode custom (rentang tanggal)
            ['tingkat' => '10', 'jurusan' => 'PPLG', 'kelas_id' => '', 'filter_mode' => 'custom', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31'],
            ['tingkat' => '11', 'jurusan' => 'all', 'kelas_id' => '', 'filter_mode' => 'custom', 'start_date' => '2026-08-01', 'end_date' => '2026-09-30'],
            ['tingkat' => 'all', 'jurusan' => 'all', 'kelas_id' => $this->kelasAkl->id, 'filter_mode' => 'custom', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31'],
        ];

        foreach ($combinations as $index => $params) {
            // 1. ReportController::siswa (Web View)
            $responseWeb = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa', $params));
            $responseWeb->assertStatus(200);
            $this->assertNotNull($responseWeb->viewData('summary'), "Kombinasi filter {$index} gagal menghasilkan summary di web view");

            // 2. PdfController::siswa (PDF View)
            $responsePdf = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.pdf.siswa', $params));
            $responsePdf->assertStatus(200);
            $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'), "Kombinasi filter {$index} gagal menghasilkan PDF");
        }

        // Test kombinasi filter di Report Guru (Discrepancy & Guru/Kelas/Mapel specific)
        $respGuruFilter1 = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.guru', [
            'only_discrepancy' => 1,
            'kelas_id' => $this->kelasPplg->id,
            'filter_mode' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $respGuruFilter1->assertStatus(200);

        $respGuruFilter2 = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.guru', [
            'guru_id' => $this->guru->id,
            'mapel_id' => $this->mapelWeb->id,
            'filter_mode' => 'custom',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]));
        $respGuruFilter2->assertStatus(200);
    }
}

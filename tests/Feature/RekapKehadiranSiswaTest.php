<?php

namespace Tests\Feature;

use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RekapKehadiranSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

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

        $this->kelas = Kelas::create([
            'nama' => '10PPLG',
            'tingkat' => '10',
            'tahun_ajaran' => '2026/2027',
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
     * Test 1: Real-time update saat presensi siswa disimpan atau diubah.
     */
    public function test_kehadiran_siswa_mutation_updates_rekap_kehadiran_siswas_automatically(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 8, 10)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Rekap Test',
            'email' => 'siswa.rekap@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '9901',
        ]);

        // 1. Simpan presensi pertama: hadir
        $kehadiran = KehadiranSiswa::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswa->id],
            ['status' => 'hadir', 'keterangan' => null]
        );

        $rekap = RekapKehadiranSiswa::where([
            'siswa_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ])->first();

        $this->assertNotNull($rekap);
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(1, $rekap->hadir);
        $this->assertEquals(1, $rekap->hadir_murni);
        $this->assertEquals(0, $rekap->terlambat);

        // 2. Ubah status presensi menjadi terlambat
        $kehadiran->update(['status' => 'terlambat']);

        $rekap->refresh();
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(1, $rekap->hadir); // hadir category mencakup hadir & terlambat
        $this->assertEquals(0, $rekap->hadir_murni);
        $this->assertEquals(1, $rekap->terlambat);

        // 3. Tambah pertemuan kedua dengan status sakit
        $pertemuan2 = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 8, 17)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan2->id,
            'siswa_id' => $siswa->id,
            'status' => 'sakit',
            'keterangan' => 'Demam Berdarah',
        ]);

        $rekap->refresh();
        $this->assertEquals(2, $rekap->total);
        $this->assertEquals(1, $rekap->hadir);
        $this->assertEquals(1, $rekap->sakit);

        // 4. Hapus satu presensi
        $kehadiran->delete();

        $rekap->refresh();
        $this->assertEquals(1, $rekap->total);
        $this->assertEquals(0, $rekap->hadir);
        $this->assertEquals(1, $rekap->sakit);
    }

    /**
     * Test 2: Soft delete dan restore pada Pertemuan memperbarui rekap_kehadiran_siswas.
     */
    public function test_soft_delete_and_restore_pertemuan_synchronizes_rekap_kehadiran_siswas(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 9, 1)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa SoftDelete Test',
            'email' => 'siswa.sd@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '9902',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'alpa',
            'keterangan' => null,
        ]);

        $rekap = RekapKehadiranSiswa::where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($rekap);
        $this->assertEquals(1, $rekap->alpa);
        $this->assertEquals(1, $rekap->total);

        // Soft delete pertemuan -> PertemuanRekapObserver terpanggil
        $pertemuan->delete();

        $rekapAfterDelete = RekapKehadiranSiswa::where('siswa_id', $siswa->id)->first();
        $this->assertNull($rekapAfterDelete, 'Rekap row harus terhapus/total 0 saat pertemuan di-soft-delete');

        // Restore pertemuan -> angka harus kembali akurat
        $pertemuan->restore();

        $rekapAfterRestore = RekapKehadiranSiswa::where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($rekapAfterRestore);
        $this->assertEquals(1, $rekapAfterRestore->alpa);
        $this->assertEquals(1, $rekapAfterRestore->total);
    }

    /**
     * Test 3: Fast-Path vs Fallback di ReportController::siswa() menghasilkan data yang 100% identik.
     */
    public function test_report_controller_siswa_fast_path_and_fallback_yield_identical_results(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 9, 15)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswaHadir = User::create(['name' => 'Siswa Hadir', 'email' => 'sh@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);
        $siswaIzin = User::create(['name' => 'Siswa Izin', 'email' => 'si@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);
        $siswaDispen = User::create(['name' => 'Siswa Dispen', 'email' => 'sd@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);

        SiswaProfile::create(['user_id' => $siswaHadir->id, 'kelas_id' => $this->kelas->id, 'nis' => '101']);
        SiswaProfile::create(['user_id' => $siswaIzin->id, 'kelas_id' => $this->kelas->id, 'nis' => '102']);
        SiswaProfile::create(['user_id' => $siswaDispen->id, 'kelas_id' => $this->kelas->id, 'nis' => '103']);

        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaHadir->id, 'status' => 'hadir']);
        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaIzin->id, 'status' => 'izin', 'keterangan' => 'Acara Keluarga']);
        KehadiranSiswa::create(['pertemuan_id' => $pertemuan->id, 'siswa_id' => $siswaDispen->id, 'status' => 'dispensasi', 'keterangan' => 'Lomba O2SN']);

        // 1. Request Mode Semester (Fast-Path via RekapKehadiranSiswa)
        $responseSemester = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa', [
            'filter_mode' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));

        $responseSemester->assertStatus(200);
        $summarySemester = $responseSemester->viewData('summary');

        // 2. Request Mode Custom (Fallback via BaseKehadiranQuery)
        $responseCustom = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.siswa', [
            'filter_mode' => 'custom',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]));

        $responseCustom->assertStatus(200);
        $summaryCustom = $responseCustom->viewData('summary');

        $this->assertEquals($summaryCustom->count(), $summarySemester->count());

        $semesterArray = collect($summarySemester->items())->keyBy('siswa_id');
        $customArray = collect($summaryCustom->items())->keyBy('siswa_id');

        foreach ($customArray as $siswaId => $customItem) {
            $this->assertTrue($semesterArray->has($siswaId));
            $semesterItem = $semesterArray->get($siswaId);

            $this->assertEquals($customItem['hadir'], $semesterItem['hadir']);
            $this->assertEquals($customItem['sakit'], $semesterItem['sakit']);
            $this->assertEquals($customItem['izin'], $semesterItem['izin']);
            $this->assertEquals($customItem['alpa'], $semesterItem['alpa']);
            $this->assertEquals($customItem['dispensasi'], $semesterItem['dispensasi']);
            $this->assertEquals($customItem['total'], $semesterItem['total']);
        }
    }

    /**
     * Test 4: Artisan command rekap:sync-siswa melakukan backfill data secara lengkap.
     */
    public function test_artisan_sync_rekap_siswa_command_backfills_all_records(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 10, 5)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Backfill Test',
            'email' => 'siswa.backfill@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '9903',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);

        // Kosongkan tabel rekap untuk mensimulasikan data belum di-backfill
        RekapKehadiranSiswa::truncate();
        $this->assertEquals(0, RekapKehadiranSiswa::count());

        // Jalankan command
        $exitCode = Artisan::call('rekap:sync-siswa');
        $this->assertEquals(0, $exitCode);

        // Rekap harus kembali terisi
        $this->assertGreaterThan(0, RekapKehadiranSiswa::count());
        $rekap = RekapKehadiranSiswa::where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($rekap);
        $this->assertEquals(1, $rekap->hadir);
        $this->assertEquals(1, $rekap->total);
    }

    /**
     * Test 5: ReportController::guru() deteksi discrepancy bekerja dengan query optimasi memori.
     */
    public function test_report_controller_guru_discrepancy_and_memory_optimization(): void
    {
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::create(2026, 10, 10)->toDateString(),
            'status' => 'selesai',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Guru menandai diri hadir
        $kehadiranGuru = KehadiranGuru::create([
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $this->guru->id,
            'status' => 'hadir',
            'keterangan' => null,
        ]);

        // Siswa melaporkan bahwa guru sebenarnya tidak_hadir
        $siswa = User::create(['name' => 'Siswa Pelapor', 'email' => 'pelapor@test.com', 'password' => Hash::make('pw'), 'role' => 'siswa']);
        FotoBukti::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status_guru_dilaporkan' => 'tidak_hadir',
            'foto_path' => 'bukti.jpg',
        ]);

        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.report.guru', [
            'only_discrepancy' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Selisih Data Siswa vs Guru');
        $this->assertEquals(1, $response->viewData('discrepancyCount'));
    }
}

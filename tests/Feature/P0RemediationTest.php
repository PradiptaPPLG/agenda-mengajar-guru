<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

class P0RemediationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $guru;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Regular Administrator',
            'email' => 'admin@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Pengajar',
            'email' => 'guru@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        GuruProfile::create([
            'user_id' => $this->guru->id,
            'nip' => '198001012005011001',
        ]);

        $this->kelas = Kelas::create([
            'nama' => '10PPLG1',
            'tingkat' => '10',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Dasar Program Keahlian',
            'kode' => 'DPK',
            'jenis' => 'produktif',
        ]);
    }

    private function createExcelFile(array $rows, ?array $header = null): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_jadwal_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);
        if ($header !== null) {
            $writer->addHeader($header);
        }
        foreach ($rows as $row) {
            $writer->addRow($row);
        }
        $writer->close();

        return new UploadedFile($tempPath, 'jadwal.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    // ==========================================
    // DATA-001 REGRESSION SUITE: BLOCKER A (Historical Identity & Semantic Integrity)
    // ==========================================

    public function test_a1_historical_schedule_preserves_identity_when_importing_same_teacher_and_subject(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
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
            'tanggal' => '2026-08-03',
            'status' => 'selesai',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Identity and active status preserved
        $fresh = $jadwal->fresh();
        $this->assertNull($fresh->deleted_at);
        $this->assertEquals($this->guru->id, $fresh->guru_id);
        $this->assertEquals($this->mapel->id, $fresh->mata_pelajaran_id);
        $this->assertEquals($jadwal->id, $pertemuan->fresh()->jadwal_id);
    }

    public function test_a2_historical_schedule_preserves_identity_when_importing_different_teacher(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
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
            'tanggal' => '2026-08-03',
            'status' => 'selesai',
        ]);

        $newGuru = User::create([
            'name' => 'Guru Baru Pengganti',
            'email' => 'guru.baru@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Baru Pengganti',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'clear_before_import' => 1,
        ]);

        // Historical schedule is archived (soft deleted), but preserves its identity!
        $archivedJadwal = JadwalPelajaran::withTrashed()->find($jadwal->id);
        $this->assertNotNull($archivedJadwal->deleted_at, 'Historical schedule must be archived');
        $this->assertEquals($this->guru->id, $archivedJadwal->guru_id, 'Historical teacher identity must not mutate');
        $this->assertEquals($this->mapel->id, $archivedJadwal->mata_pelajaran_id);

        // Pertemuan still points to the historical schedule and resolves teacher
        $this->assertEquals($this->guru->id, $pertemuan->fresh()->jadwal->guru_id);

        // New active schedule exists for the replacement teacher
        $newActiveJadwal = JadwalPelajaran::where('kelas_id', $this->kelas->id)
            ->where('hari', 1)
            ->where('jam_mulai', '07:00:00')
            ->whereNull('deleted_at')
            ->first();

        $this->assertNotNull($newActiveJadwal);
        $this->assertNotEquals($jadwal->id, $newActiveJadwal->id);
        $this->assertEquals($newGuru->id, $newActiveJadwal->guru_id);
    }

    public function test_a3_historical_schedule_preserves_identity_when_importing_different_subject(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
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
            'tanggal' => '2026-08-03',
            'status' => 'selesai',
        ]);

        $newMapel = MataPelajaran::create([
            'nama' => 'Pemrograman Web',
            'kode' => 'PW',
            'jenis' => 'produktif',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Pemrograman Web',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Historical schedule is archived and subject is preserved
        $archivedJadwal = JadwalPelajaran::withTrashed()->find($jadwal->id);
        $this->assertNotNull($archivedJadwal->deleted_at);
        $this->assertEquals($this->mapel->id, $archivedJadwal->mata_pelajaran_id, 'Subject of historical schedule must not mutate');

        // New active schedule has the new mapel
        $newActiveJadwal = JadwalPelajaran::where('kelas_id', $this->kelas->id)
            ->where('hari', 1)
            ->where('jam_mulai', '07:00:00')
            ->whereNull('deleted_at')
            ->first();
        $this->assertEquals($newMapel->id, $newActiveJadwal->mata_pelajaran_id);
    }

    public function test_a4_historical_pertemuan_guru_and_siswa_attendance_and_photos_remain_intact(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
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
            'tanggal' => '2026-08-03',
            'status' => 'selesai',
        ]);

        $kehadiranGuru = KehadiranGuru::create([
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $this->guru->id,
            'status' => 'hadir',
            'waktu_masuk' => '07:05:00',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Sejarah',
            'email' => 'siswa.sejarah@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);
        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '99887766',
        ]);

        $kehadiranSiswa = KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);

        $fotoBukti = FotoBukti::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'foto_path' => 'uploads/foto/test.jpg',
            'status_guru_dilaporkan' => 'hadir',
        ]);

        User::create([
            'name' => 'Guru Pengganti Baru',
            'email' => 'guru.pengganti@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengganti Baru',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Pertemuan and all child records are 100% intact
        $this->assertDatabaseHas('pertemuans', ['id' => $pertemuan->id]);
        $this->assertDatabaseHas('kehadiran_gurus', ['id' => $kehadiranGuru->id, 'guru_id' => $this->guru->id]);
        $this->assertDatabaseHas('kehadiran_siswas', ['id' => $kehadiranSiswa->id, 'siswa_id' => $siswa->id]);
        $this->assertDatabaseHas('foto_buktis', ['id' => $fotoBukti->id]);

        $freshPertemuan = $pertemuan->fresh();
        $this->assertNotNull($freshPertemuan->jadwal);
        $this->assertEquals($this->guru->id, $freshPertemuan->jadwal->guru_id);
    }

    public function test_a5_original_teacher_retains_authorized_access_to_historical_meetings(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
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
            'tanggal' => now()->toDateString(),
            'status' => 'selesai',
        ]);

        $newGuru = User::create([
            'name' => 'Guru Pengganti Baru',
            'email' => 'guru.pengganti2@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengganti Baru',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // 1. Original teacher accessing save-all is NOT 403 (they still own the historical schedule)
        $respOriginal = $this->actingAs($this->guru)->patch(route('guru.pertemuan.save-all', $pertemuan), [
            'materi_ajar' => 'Materi yang diupdate oleh guru asli',
        ]);
        $this->assertNotEquals(403, $respOriginal->status());

        // 2. Replacement teacher accessing old pertemuan gets 403 Forbidden!
        $respNew = $this->actingAs($newGuru)->patch(route('guru.pertemuan.save-all', $pertemuan), [
            'materi_ajar' => 'Percobaan sabotase oleh guru baru',
        ]);
        $respNew->assertStatus(403);
    }

    public function test_a6_active_schedules_do_not_duplicate_slots_and_only_one_active_per_slot(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => '2026-08-03',
            'status' => 'selesai',
        ]);

        User::create([
            'name' => 'Guru Pengganti Ketiga',
            'email' => 'guru.pengganti3@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengganti Ketiga',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Exactly 1 active schedule exists for this slot
        $activeCount = JadwalPelajaran::where('kelas_id', $this->kelas->id)
            ->where('hari', 1)
            ->where('jam_mulai', '07:00:00')
            ->where('tahun_ajaran', '2026/2027')
            ->where('semester', 'ganjil')
            ->whereNull('deleted_at')
            ->count();

        $this->assertEquals(1, $activeCount, 'There must be exactly one active schedule per slot');
    }

    public function test_a7_schedule_without_history_is_updated_in_place(): void
    {
        $jadwalWithoutHistory = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 2,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $newGuru = User::create([
            'name' => 'Guru Baru Update',
            'email' => 'guru.baru.update@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Baru Update',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Selasa',
                'jam_mulai' => '10:00',
                'jam_selesai' => '11:30',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Primary key is preserved and updated in place
        $fresh = $jadwalWithoutHistory->fresh();
        $this->assertNull($fresh->deleted_at);
        $this->assertEquals($newGuru->id, $fresh->guru_id);
    }

    // ==========================================
    // DATA-001 REGRESSION SUITE: BLOCKER B (Atomic Import & All-or-Nothing)
    // ==========================================

    public function test_b8_multi_row_file_with_one_invalid_row_leaves_zero_database_changes(): void
    {
        $initialCount = JadwalPelajaran::count();

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
            [
                'kelas' => 'KELAS_TIDAK_ADA', // Invalid row!
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '09:00',
                'jam_selesai' => '10:30',
            ],
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Selasa',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // ZERO database changes: row 1 and row 3 were NOT saved!
        $this->assertEquals($initialCount, JadwalPelajaran::count(), 'Partial saves must be completely rolled back');
    }

    public function test_b9_missing_foreign_reference_fails_entire_import(): void
    {
        $initialCount = JadwalPelajaran::count();

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Fiktif', // Unknown teacher!
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals($initialCount, JadwalPelajaran::count());
    }

    public function test_b10_invalid_time_format_fails_entire_import(): void
    {
        $initialCount = JadwalPelajaran::count();

        // 1. Invalid time string
        $file1 = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '25:99',
                'jam_selesai' => '08:30',
            ],
        ]);

        $response1 = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file1,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('error');

        // 2. Start time >= End time
        $file2 = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '10:00',
                'jam_selesai' => '08:00',
            ],
        ]);

        $response2 = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file2,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('error');

        $this->assertEquals($initialCount, JadwalPelajaran::count());
    }

    public function test_b11_missing_mandatory_headers_fails_entire_import(): void
    {
        $initialCount = JadwalPelajaran::count();

        // Excel file missing 'guru' and 'mapel' columns
        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals($initialCount, JadwalPelajaran::count());
    }

    public function test_b12_corrupted_file_fails_without_modifying_schedules(): void
    {
        $existingJadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 2,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'invalid_').'.txt';
        file_put_contents($tempPath, 'CORRUPTED NON EXCEL CONTENT');
        $file = new UploadedFile($tempPath, 'corrupted.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'clear_before_import' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['file']);

        $this->assertDatabaseHas('jadwal_pelajarans', [
            'id' => $existingJadwal->id,
            'deleted_at' => null,
        ]);
    }

    public function test_b13_header_only_or_empty_rows_rejected_without_modifying_schedules(): void
    {
        $existingJadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 3,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '',
                'guru' => '',
                'mapel' => '',
                'hari' => '',
                'jam_mulai' => '',
                'jam_selesai' => '',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'clear_before_import' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('jadwal_pelajarans', [
            'id' => $existingJadwal->id,
            'deleted_at' => null,
        ]);
    }

    public function test_b14_clear_before_import_rolls_back_when_validation_fails(): void
    {
        $existingJadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 3,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
            [
                'kelas' => 'KELAS_INVALID',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '09:00',
                'jam_selesai' => '10:30',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'clear_before_import' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Existing schedule was NOT deleted because the transaction rolled back
        $this->assertDatabaseHas('jadwal_pelajarans', [
            'id' => $existingJadwal->id,
            'deleted_at' => null,
        ]);
    }

    public function test_b15_salin_semester_clear_option_respects_history_retention_policy(): void
    {
        JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 2,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $targetJadwalWithHistory = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 3,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:30:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'genap',
        ]);

        $pertemuanTarget = Pertemuan::create([
            'jadwal_id' => $targetJadwalWithHistory->id,
            'tanggal' => '2027-01-10',
            'status' => 'selesai',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.salin-semester'), [
            'sumber_tahun_ajaran' => '2026/2027',
            'sumber_semester' => 'ganjil',
            'tujuan_tahun_ajaran' => '2026/2027',
            'tujuan_semester' => 'genap',
            'hapus_tujuan_dulu' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('jadwal_pelajarans', [
            'id' => $targetJadwalWithHistory->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('pertemuans', [
            'id' => $pertemuanTarget->id,
            'jadwal_id' => $targetJadwalWithHistory->id,
        ]);
    }

    public function test_b16_fully_valid_file_succeeds_with_success_session_message(): void
    {
        $file = $this->createExcelFile([
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Selasa',
                'jam_mulai' => '08:30',
                'jam_selesai' => '10:00',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(2, JadwalPelajaran::where('tahun_ajaran', '2026/2027')->whereNull('deleted_at')->count());
    }

    public function test_b17_repeated_import_is_consistent_and_idempotent(): void
    {
        $rows = [
            [
                'kelas' => '10PPLG1',
                'guru' => 'Guru Pengajar',
                'mapel' => 'Dasar Program Keahlian',
                'hari' => 'Senin',
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ],
        ];

        // 1st Import
        $file1 = $this->createExcelFile($rows);
        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file1,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $countAfterFirst = JadwalPelajaran::where('tahun_ajaran', '2026/2027')->count();

        // 2nd Import
        $file2 = $this->createExcelFile($rows);
        $this->actingAs($this->admin)->post(route('admin.jadwal.import'), [
            'file' => $file2,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $countAfterSecond = JadwalPelajaran::where('tahun_ajaran', '2026/2027')->count();

        // Idempotent: no duplication
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    // ==========================================
    // SEC-001 TESTS (Strict Super Admin Authorization)
    // ==========================================

    public function test_admin_can_perform_allowed_user_management_operations(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Guru Baru Sah',
            'email' => 'guru.sah@sekolah.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'guru',
            'nip' => '199001012015011002',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'guru.sah@sekolah.sch.id',
            'role' => 'guru',
        ]);
    }

    public function test_admin_forbidden_from_creating_super_admin(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Fake Super Admin',
            'email' => 'hacked.super@sekolah.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super_admin',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', [
            'email' => 'hacked.super@sekolah.sch.id',
        ]);
    }

    public function test_admin_forbidden_from_promoting_self_to_super_admin(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'super_admin',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('admin', $this->admin->fresh()->role);
    }

    public function test_admin_forbidden_from_promoting_other_user_to_super_admin(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->guru), [
            'name' => $this->guru->name,
            'email' => $this->guru->email,
            'role' => 'super_admin',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('guru', $this->guru->fresh()->role);
    }

    public function test_admin_forbidden_from_editing_or_updating_existing_super_admin(): void
    {
        // 1. Cannot access edit page
        $editResponse = $this->actingAs($this->admin)->get(route('admin.users.edit', $this->superAdmin));
        $editResponse->assertStatus(403);

        // 2. Cannot send update request to change password or data
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.users.update', $this->superAdmin), [
            'name' => 'Tampered Super Admin',
            'email' => 'tampered@sekolah.sch.id',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role' => 'admin',
        ]);
        $updateResponse->assertStatus(403);

        $freshSuper = $this->superAdmin->fresh();
        $this->assertEquals('Super Administrator', $freshSuper->name);
        $this->assertEquals('super_admin', $freshSuper->role);
        $this->assertTrue(Hash::check('password123', $freshSuper->password));
    }

    public function test_admin_forbidden_from_deleting_or_bulk_deleting_super_admin(): void
    {
        // 1. Single delete
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->superAdmin));
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'deleted_at' => null]);

        // 2. Bulk delete
        $bulkResponse = $this->actingAs($this->admin)->post(route('admin.users.bulk-destroy'), [
            'ids' => [$this->superAdmin->id, $this->guru->id],
        ]);
        $bulkResponse->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'deleted_at' => null]);
    }

    public function test_admin_forbidden_from_resetting_password_of_super_admin(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.reset-password-nip', $this->superAdmin));
        $response->assertStatus(403);
        $this->assertTrue(Hash::check('password123', $this->superAdmin->fresh()->password));
    }

    public function test_super_admin_can_perform_authorized_operations(): void
    {
        // Super admin creates another super admin
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name' => 'Second Super Admin',
            'email' => 'second.super@sekolah.sch.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super_admin',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'second.super@sekolah.sch.id',
            'role' => 'super_admin',
        ]);

        // Super admin can edit super admin
        $created = User::where('email', 'second.super@sekolah.sch.id')->first();
        $editResponse = $this->actingAs($this->superAdmin)->get(route('admin.users.edit', $created));
        $editResponse->assertOk();

        // Super admin can delete
        $delResponse = $this->actingAs($this->superAdmin)->delete(route('admin.users.destroy', $created));
        $delResponse->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $created->id]);
    }

    public function test_direct_requests_to_pengguna_endpoints_cannot_bypass_super_admin_protection(): void
    {
        // 1. Admin trying to create super_admin via pengguna endpoint
        $storeResponse = $this->actingAs($this->admin)->post(route('admin.pengguna.store'), [
            'name' => 'Bypassed Super Admin',
            'email' => 'bypassed@sekolah.sch.id',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);
        $storeResponse->assertStatus(403);

        // 2. Admin trying to update user to super_admin via pengguna endpoint
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.pengguna.update', $this->guru), [
            'name' => $this->guru->name,
            'role' => 'super_admin',
        ]);
        $updateResponse->assertStatus(403);

        // 3. Admin trying to delete super_admin via pengguna endpoint
        $delResponse = $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $this->superAdmin));
        $delResponse->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PiketAndAttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $piket;

    private User $guru;

    private User $siswa;

    private User $kepsek;

    private User $admin;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::today()->setTime(7, 30));
        Storage::fake('public');

        $this->piket = User::create([
            'name' => 'Petugas Piket Test',
            'email' => 'piket.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'piket',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Penguji',
            'email' => 'guru.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        GuruProfile::create(['user_id' => $this->guru->id, 'nip' => '198001012005011001']);

        $this->kelas = Kelas::create([
            'nama' => '10-PPLG-1',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->siswa = User::create([
            'name' => 'Siswa Penguji',
            'email' => 'siswa.test@siswa.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswa->id,
            'nis' => '1234567890',
            'kelas_id' => $this->kelas->id,
        ]);

        $this->kepsek = User::create([
            'name' => 'Kepala Sekolah Test',
            'email' => 'kepsek.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Pemrograman Web',
            'kode' => 'PWEB',
            'jenis' => 'normatif',
        ]);

        $this->jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => Carbon::today()->dayOfWeekIso, // Jadwal hari ini
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);
    }

    public function test_piket_user_can_login_and_access_piket_dashboard(): void
    {
        $response = $this->post(route('login.post'), [
            'identifier' => 'piket.test@sekolah.sch.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('piket.dashboard'));
        $this->assertAuthenticatedAs($this->piket);

        $dashboardResponse = $this->actingAs($this->piket)->get(route('piket.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Monitoring Kehadiran Guru (Piket)');
        $dashboardResponse->assertSee('10-PPLG-1');
        $dashboardResponse->assertSee('Guru Penguji');
    }

    public function test_siswa_can_report_teacher_status_and_sync_to_kehadiran_guru(): void
    {
        $todayStr = Carbon::today()->toDateString();
        $fakeImage = UploadedFile::fake()->image('bukti.jpg', 600, 600);

        $response = $this->actingAs($this->siswa)->post(
            route('siswa.capture.store', ['jadwal' => $this->jadwal->id, 'tanggal' => $todayStr]),
            [
                'foto' => $fakeImage,
                'status_guru_dilaporkan' => 'terlambat',
            ]
        );

        $response->assertRedirect(route('siswa.dashboard'));
        $response->assertSessionHas('success');

        // Pastikan tersimpan di foto_buktis
        $this->assertDatabaseHas('foto_buktis', [
            'siswa_id' => $this->siswa->id,
            'status_guru_dilaporkan' => 'terlambat',
        ]);

        // Pastikan tersinkron otomatis ke kehadiran_gurus
        $this->assertDatabaseHas('kehadiran_gurus', [
            'guru_id' => $this->guru->id,
            'status' => 'terlambat',
        ]);
    }

    public function test_siswa_can_report_teacher_tidak_hadir_with_sub_category(): void
    {
        $todayStr = Carbon::today()->toDateString();
        $fakeImage = UploadedFile::fake()->image('bukti2.jpg', 600, 600);

        $response = $this->actingAs($this->siswa)->post(
            route('siswa.capture.store', ['jadwal' => $this->jadwal->id, 'tanggal' => $todayStr]),
            [
                'foto' => $fakeImage,
                'status_guru_dilaporkan' => 'tidak_hadir',
                'alasan_tidak_hadir' => 'dinas_luar',
                'guru_pengganti_nama' => 'Pak Joko, S.T.',
            ]
        );

        $response->assertRedirect(route('siswa.dashboard'));

        $this->assertDatabaseHas('kehadiran_gurus', [
            'guru_id' => $this->guru->id,
            'status' => 'tidak_hadir',
            'alasan_tidak_hadir' => 'dinas_luar',
            'guru_pengganti_nama' => 'Pak Joko, S.T.',
        ]);
    }

    public function test_guru_can_save_materi_and_presensi_siswa_without_overwriting_student_report(): void
    {
        $todayStr = Carbon::today()->toDateString();

        // 1. Siswa melapor guru dinas luar terlebih dahulu
        $fakeImage = UploadedFile::fake()->image('bukti3.jpg', 600, 600);
        $this->actingAs($this->siswa)->post(
            route('siswa.capture.store', ['jadwal' => $this->jadwal->id, 'tanggal' => $todayStr]),
            [
                'foto' => $fakeImage,
                'status_guru_dilaporkan' => 'tidak_hadir',
                'alasan_tidak_hadir' => 'dinas_luar',
            ]
        );

        $pertemuan = Pertemuan::where('jadwal_id', $this->jadwal->id)->first();

        // 2. Guru membuka pertemuan dan menyimpan materi ajar & kehadiran siswa
        $saveResponse = $this->actingAs($this->guru)->patch(
            route('guru.pertemuan.save-all', $pertemuan->id),
            [
                'materi_ajar' => 'Belajar Routing Laravel dan Controller',
                'penugasan' => 'Buat CRUD sederhana',
                'siswa' => [
                    $this->siswa->id => ['status' => 'hadir'],
                ],
            ]
        );

        $saveResponse->assertRedirect();
        $saveResponse->assertSessionHas('success');

        // Materi tersimpan
        $pertemuan->refresh();
        $this->assertEquals('Belajar Routing Laravel dan Controller', $pertemuan->materi_ajar);

        // Kehadiran siswa tersimpan
        $this->assertDatabaseHas('kehadiran_siswas', [
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'status' => 'hadir',
        ]);

        // Status kehadiran guru tetap dinas_luar (tidak tertimpa)
        $khGuru = KehadiranGuru::where('pertemuan_id', $pertemuan->id)->first();
        $this->assertEquals('tidak_hadir', $khGuru->status);
        $this->assertEquals('dinas_luar', $khGuru->alasan_tidak_hadir);
    }

    public function test_admin_can_export_jadwal_to_excel_and_pdf(): void
    {
        // Test export Excel
        $excelResponse = $this->actingAs($this->admin)->get(route('admin.jadwal.export.excel'));
        $excelResponse->assertStatus(200);
        $excelResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Test export PDF
        $pdfResponse = $this->actingAs($this->admin)->get(route('admin.jadwal.export.pdf'));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_kelas_index_displays_pagination(): void
    {
        // Buat kelas tambahan agar pagination aktif
        for ($i = 1; $i <= 18; $i++) {
            Kelas::create([
                'nama' => "Kelas Dummy {$i}",
                'tingkat' => 'XI',
                'tahun_ajaran' => '2026/2027',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.kelas.index'));
        $response->assertStatus(200);
        $response->assertViewHas('kelas');
        $kelasPaginator = $response->viewData('kelas');
        $this->assertTrue($kelasPaginator->hasPages());
    }

    public function test_kepala_sekolah_dashboard_renders_card_grid_monitoring(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('kepala-sekolah.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Monitoring Kehadiran Real-Time');
        $response->assertSee('Jam Pelajaran');
        $response->assertSee('Tingkat Kelas');
        $response->assertSee('10-PPLG-1');
        $response->assertSee('Guru Penguji');
    }
}

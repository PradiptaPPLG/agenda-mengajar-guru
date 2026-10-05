<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Kelas;
use App\Models\KelasPkl;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KelasPklManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    private User $siswa;

    private Kelas $kelasDkv;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin PKL Test',
            'email' => 'admin.pkl@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru DKV Test',
            'email' => 'guru.dkv@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->kelasDkv = Kelas::create([
            'nama' => '12 DKV 1',
            'tingkat' => 'XII',
            'tahun_ajaran' => '2026/2027',
            'is_sistem_blok' => false,
        ]);

        $this->siswa = User::create([
            'name' => 'Siswa DKV',
            'email' => 'siswa.dkv@siswa.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        SiswaProfile::create([
            'user_id' => $this->siswa->id,
            'kelas_id' => $this->kelasDkv->id,
            'nis' => '123456',
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Desain Grafis',
            'kode' => 'DG-12',
        ]);

        $this->jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelasDkv->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1, // Senin
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:20:00',
            'kelompok_blok' => 'reguler',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }

    public function test_admin_can_view_kelas_pkl_index(): void
    {
        KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
            'keterangan' => 'PKL Gelombang 1 DKV',
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kelas-pkl.index'));

        $response->assertOk();
        $response->assertSee('Status & Jadwal PKL Kelas', false);
        $response->assertSee('12 DKV 1');
        $response->assertSee('PKL Gelombang 1 DKV');
    }

    public function test_admin_can_create_new_kelas_pkl_period(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.kelas-pkl.store'), [
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-12-31',
            'keterangan' => 'PKL Semester Ganjil 12 DKV',
            'is_aktif' => '1',
        ]);

        $response->assertRedirect(route('admin.kelas-pkl.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kelas_pkls', [
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-12-31',
            'keterangan' => 'PKL Semester Ganjil 12 DKV',
            'is_aktif' => 1,
        ]);
    }

    public function test_validation_requires_tanggal_selesai_after_or_equal_tanggal_mulai(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.kelas-pkl.store'), [
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-09-01', // Invalid: before start date
            'keterangan' => 'Tanggal Salah',
        ]);

        $response->assertSessionHasErrors(['tanggal_selesai']);
    }

    public function test_admin_can_update_and_delete_kelas_pkl(): void
    {
        $pkl = KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-10-31',
            'keterangan' => 'Awal',
            'is_aktif' => true,
        ]);

        // Update
        $response = $this->actingAs($this->admin)->put(route('admin.kelas-pkl.update', $pkl), [
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-15',
            'keterangan' => 'Diperpanjang',
            'is_aktif' => '1',
        ]);

        $response->assertRedirect(route('admin.kelas-pkl.index'));
        $this->assertEquals('2026-12-15', $pkl->fresh()->tanggal_selesai->toDateString());
        $this->assertEquals('Diperpanjang', $pkl->fresh()->keterangan);

        // Delete
        $delResponse = $this->actingAs($this->admin)->delete(route('admin.kelas-pkl.destroy', $pkl));
        $delResponse->assertRedirect(route('admin.kelas-pkl.index'));
        $this->assertSoftDeleted('kelas_pkls', ['id' => $pkl->id]);
    }

    public function test_admin_can_toggle_is_aktif(): void
    {
        $pkl = KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-10-31',
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.kelas-pkl.toggle', $pkl));
        $response->assertRedirect(route('admin.kelas-pkl.index'));

        $this->assertFalse($pkl->fresh()->is_aktif);

        // Toggle back to true
        $this->actingAs($this->admin)->post(route('admin.kelas-pkl.toggle', $pkl));
        $this->assertTrue($pkl->fresh()->is_aktif);
    }

    public function test_jadwal_resolver_excludes_schedules_when_class_is_in_pkl(): void
    {
        KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'is_aktif' => true,
        ]);

        $resolver = app(JadwalBlokResolverService::class);
        $targetDate = Carbon::parse('2026-10-05'); // Monday, within PKL date range

        // Single class resolve
        $resolvedSingle = $resolver->resolveJadwal($this->kelasDkv, $targetDate, '1');
        $this->assertCount(0, $resolvedSingle, 'Schedules should be empty when class is on PKL');

        // Multi class resolve
        $kelasList = collect([$this->kelasDkv]);
        $resolvedMulti = $resolver->resolveJadwalBanyakKelas($kelasList, $targetDate, '1');
        $this->assertCount(0, $resolvedMulti, 'Multi-class schedules should exclude classes on PKL');
    }

    public function test_jadwal_resolver_includes_schedules_when_outside_pkl_period_or_inactive(): void
    {
        $pkl = KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'is_aktif' => false, // Inactive
        ]);

        $resolver = app(JadwalBlokResolverService::class);
        $targetDate = Carbon::parse('2026-10-05'); // Monday

        // When inactive: schedule should resolve
        $resolvedInactive = $resolver->resolveJadwal($this->kelasDkv, $targetDate, '1');
        $this->assertCount(1, $resolvedInactive);

        // When active but target date is after PKL end date
        $pkl->update(['is_aktif' => true]);
        $afterPklDate = Carbon::parse('2026-12-07'); // Monday after PKL
        $resolvedAfter = $resolver->resolveJadwal($this->kelasDkv, $afterPklDate, '1');
        $this->assertCount(1, $resolvedAfter);
    }

    public function test_guru_dashboard_does_not_ruin_presence_percentage_for_pkl_meetings(): void
    {
        // Set PKL period
        KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'is_aktif' => true,
        ]);

        // Create a normal attended meeting for another class
        $kelasLain = Kelas::create([
            'nama' => '10 PPLG 1',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);
        $jadwalLain = JadwalPelajaran::create([
            'kelas_id' => $kelasLain->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 2,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:20:00',
        ]);
        $pertemuanNormal = Pertemuan::create([
            'jadwal_id' => $jadwalLain->id,
            'tanggal' => '2026-09-15',
            'status' => 'selesai',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pertemuanNormal->id,
            'guru_id' => $this->guru->id,
            'status' => 'hadir',
        ]);

        // Suppose an accidental "tidak_hadir" record was logged during 12 DKV PKL
        $pertemuanDkvPkl = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => '2026-09-21',
            'status' => 'selesai',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $pertemuanDkvPkl->id,
            'guru_id' => $this->guru->id,
            'status' => 'tidak_hadir',
        ]);

        // Guru views dashboard
        $response = $this->actingAs($this->guru)->get(route('guru.dashboard'));
        $response->assertOk();

        // Stats should exclude the DKV PKL absence, so presence is 100% (1 hadir, 0 tidak hadir)
        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(1, $stats['hadir']);
        $this->assertEquals(0, $stats['alpa']);
        $this->assertEquals(100.0, $stats['persentase']);
    }

    public function test_siswa_capture_is_blocked_during_pkl(): void
    {
        KelasPkl::create([
            'kelas_id' => $this->kelasDkv->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'is_aktif' => true,
        ]);

        $targetDate = '2026-10-05'; // Monday within PKL

        // Accessing capture show page during PKL redirects with error
        $response = $this->actingAs($this->siswa)->get(route('siswa.capture.show', [
            'jadwal' => $this->jadwal->id,
            'tanggal' => $targetDate,
        ]));

        $response->assertRedirect(route('siswa.dashboard'));
        $response->assertSessionHas('error', 'Kelas Anda sedang dalam masa Praktik Kerja Lapangan (PKL). Presensi ditiadakan.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\User;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SemesterLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $kepalaSekolah;

    private User $guru;

    private Kelas $kelas;

    private MataPelajaran $mapel1;

    private MataPelajaran $mapel2;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->admin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'ks@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'kepala_sekolah',
        ]);

        $this->guru = User::create([
            'name' => 'Guru Pengajar, S.Kom',
            'email' => 'guru@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $this->kelas = Kelas::create([
            'nama' => 'XII-RPL-1',
            'tingkat' => 'XII',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->mapel1 = MataPelajaran::create([
            'nama' => 'Pemrograman Web',
            'kode' => 'PW-12',
            'kelompok_blok' => 'reguler',
        ]);

        $this->mapel2 = MataPelajaran::create([
            'nama' => 'Basis Data',
            'kode' => 'BD-12',
            'kelompok_blok' => 'reguler',
        ]);
    }

    public function test_schedule_creation_with_specific_tahun_ajaran_and_semester(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.jadwal.index'));

        $this->assertDatabaseHas('jadwal_pelajarans', [
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }

    public function test_conflict_validation_is_isolated_between_different_semesters(): void
    {
        // Jadwal Semester Ganjil
        JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Input jadwal pada jam & hari sama tapi untuk Semester GENAP
        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel2->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'genap',
        ]);

        // Harus berhasil (TIDAK BENTROK karena beda semester)
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.jadwal.index'));

        $this->assertDatabaseCount('jadwal_pelajarans', 2);
    }

    public function test_salin_semester_duplicates_schedules_accurately(): void
    {
        // 2 Jadwal di Ganjil
        JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'kelompok_blok' => 'reguler',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel2->id,
            'hari' => 2,
            'jam_mulai' => '08:30',
            'jam_selesai' => '10:00',
            'kelompok_blok' => 'reguler',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.salin-semester'), [
            'sumber_tahun_ajaran' => '2026/2027',
            'sumber_semester' => 'ganjil',
            'tujuan_tahun_ajaran' => '2026/2027',
            'tujuan_semester' => 'genap',
            'hapus_tujuan_dulu' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.jadwal.index', [
            'tahun_ajaran' => '2026/2027',
            'semester' => 'genap',
        ]));

        // Total jadwal harus menjadi 4 (2 ganjil + 2 genap)
        $this->assertDatabaseCount('jadwal_pelajarans', 4);
        $this->assertEquals(2, JadwalPelajaran::where('semester', 'genap')->count());
    }

    public function test_resolver_respects_active_semester_and_school_year(): void
    {
        // Jadwal Ganjil
        JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        // Jadwal Genap
        JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel2->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'genap',
        ]);

        $resolver = app(JadwalBlokResolverService::class);

        // Saat semester aktif Ganjil (Setting semester = '1')
        Setting::set('semester', '1');
        $resolvedGanjil = $resolver->resolveJadwal($this->kelas, Carbon::today(), '1');
        $this->assertCount(1, $resolvedGanjil);
        $this->assertEquals($this->mapel1->id, $resolvedGanjil->first()->mata_pelajaran_id);

        // Ubah semester aktif ke Genap (Setting semester = '2')
        Setting::set('semester', '2');
        $resolvedGenap = $resolver->resolveJadwal($this->kelas, Carbon::today(), '1');
        $this->assertCount(1, $resolvedGenap);
        $this->assertEquals($this->mapel2->id, $resolvedGenap->first()->mata_pelajaran_id);
    }

    public function test_kepala_sekolah_reports_filter_by_semester(): void
    {
        $response = $this->actingAs($this->kepalaSekolah)->get(route('kepala-sekolah.report.guru', [
            'filter_mode' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));

        $response->assertOk();
        $response->assertSee('Laporan Kehadiran Guru');
        $response->assertSee('2026/2027');

        $responsePdf = $this->actingAs($this->kepalaSekolah)->get(route('kepala-sekolah.pdf.guru', [
            'filter_mode' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));

        $responsePdf->assertOk();
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));
    }
}

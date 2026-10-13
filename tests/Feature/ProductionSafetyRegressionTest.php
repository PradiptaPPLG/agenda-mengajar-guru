<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\KenaikanKelasController;
use App\Http\Controllers\Admin\UserController;
use App\Models\FotoBukti;
use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionSafetyRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private User $siswaActive;

    private User $siswaDeleted;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::create([
            'name' => 'Guru Penguji',
            'email' => 'guru.safety@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        GuruProfile::create([
            'user_id' => $this->guru->id,
            'nip' => '198501012010011001',
        ]);

        $this->kelas = Kelas::create([
            'nama' => '11PPLG',
            'tingkat' => '11',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->siswaActive = User::create([
            'name' => 'Budi Siswa Aktif',
            'email' => 'budi@siswa.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswaActive->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '21221001',
        ]);

        $this->siswaDeleted = User::create([
            'name' => 'Andi Siswa Terhapus',
            'email' => 'andi@siswa.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswaDeleted->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '21221002',
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'KK-PPLG 11',
            'kode' => 'KKPPLG-11',
            'jenis' => 'produktif',
        ]);

        $this->jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ]);
    }

    /**
     * A.1 Skenario Utama: Detail pertemuan dapat dirender ketika siswa berstatus soft-deleted memiliki presensi.
     */
    public function test_pertemuan_show_renders_safely_when_siswa_is_soft_deleted(): void
    {
        $today = Carbon::today()->toDateString();
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $today.' 00:00:00',
            'status' => 'berlangsung',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaActive->id,
            'status' => 'hadir',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaDeleted->id,
            'status' => 'izin',
        ]);

        // Soft delete siswa
        $this->siswaDeleted->delete();
        $this->assertSoftDeleted('users', ['id' => $this->siswaDeleted->id]);

        $response = $this->actingAs($this->guru)->get(
            route('guru.pertemuan.show', ['jadwal' => $this->jadwal->id, 'tanggal' => $today])
        );

        $response->assertStatus(200);
        $response->assertSee('Budi Siswa Aktif');
        $response->assertSee('Andi Siswa Terhapus');
        $response->assertSee('(Dihapus)');
    }

    /**
     * A.2 Skenario GET Idempotency: Buka halaman GET TIDAK BOLEH menghapus riwayat kehadiran di DB.
     */
    public function test_pertemuan_show_does_not_delete_attendance_records(): void
    {
        $today = Carbon::today()->toDateString();
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $today.' 00:00:00',
            'status' => 'berlangsung',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaActive->id,
            'status' => 'hadir',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaDeleted->id,
            'status' => 'sakit',
        ]);

        $this->siswaDeleted->delete();

        $countBefore = KehadiranSiswa::where('pertemuan_id', $pertemuan->id)->count();
        $this->assertEquals(2, $countBefore);

        // Akses via GET
        $this->actingAs($this->guru)->get(
            route('guru.pertemuan.show', ['jadwal' => $this->jadwal->id, 'tanggal' => $today])
        );

        // Data presensi histori tetap 2, tidak terhapus!
        $countAfter = KehadiranSiswa::where('pertemuan_id', $pertemuan->id)->count();
        $this->assertEquals(2, $countAfter);
        $this->assertDatabaseHas('kehadiran_siswas', [
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaDeleted->id,
            'status' => 'sakit',
        ]);
    }

    /**
     * A.3 Foto bukti dari siswa yang kemudian di-soft-delete tidak menyebabkan error 500.
     */
    public function test_foto_bukti_with_soft_deleted_siswa_renders_safely(): void
    {
        $today = Carbon::today()->toDateString();
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $today.' 00:00:00',
            'status' => 'berlangsung',
        ]);

        FotoBukti::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaDeleted->id,
            'foto_path' => 'images/sample.jpg',
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $this->siswaDeleted->delete();

        $response = $this->actingAs($this->guru)->get(
            route('guru.pertemuan.show', ['jadwal' => $this->jadwal->id, 'tanggal' => $today])
        );

        $response->assertStatus(200);
        $response->assertSee('Andi Siswa Terhapus');
    }

    /**
     * A.4 Siswa yang sudah soft-delete tidak dimasukkan otomatis ke pertemuan baru.
     */
    public function test_soft_deleted_siswa_is_not_added_to_subsequent_meetings(): void
    {
        $this->siswaDeleted->delete();

        $today = Carbon::today()->toDateString();
        $this->actingAs($this->guru)->get(
            route('guru.pertemuan.show', ['jadwal' => $this->jadwal->id, 'tanggal' => $today])
        );

        $pertemuan = Pertemuan::where('jadwal_id', $this->jadwal->id)->whereDate('tanggal', $today)->first();
        $this->assertNotNull($pertemuan);

        // Hanya siswa aktif yang dibuatkan kehadiran baru
        $this->assertDatabaseHas('kehadiran_siswas', [
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaActive->id,
        ]);
        $this->assertDatabaseMissing('kehadiran_siswas', [
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswaDeleted->id,
        ]);
    }

    /**
     * A.5 Soft-delete user tidak menghancurkan SiswaProfile dan memungkinkan restore.
     */
    public function test_soft_deleting_user_retains_siswa_profile(): void
    {
        $this->siswaDeleted->delete();

        $this->assertSoftDeleted('users', ['id' => $this->siswaDeleted->id]);
        $this->assertDatabaseHas('siswa_profiles', [
            'user_id' => $this->siswaDeleted->id,
            'nis' => '21221002',
            'kelas_id' => $this->kelas->id,
        ]);

        // Restore
        $this->siswaDeleted->restore();
        $this->assertDatabaseHas('users', ['id' => $this->siswaDeleted->id, 'deleted_at' => null]);
        $this->assertEquals('21221002', $this->siswaDeleted->siswaProfile->nis);
    }

    /**
     * B.1 Kelas::findByNameFlexible mendukung pencarian kanonis PPLG dan alias RPL dua arah.
     */
    public function test_find_by_name_flexible_matches_pplg_and_rpl_aliases(): void
    {
        // 1. Pencarian eksak
        $foundExact = Kelas::findByNameFlexible('11PPLG');
        $this->assertNotNull($foundExact);
        $this->assertEquals($this->kelas->id, $foundExact->id);

        // 2. Pencarian variasi spasi
        $foundSpace = Kelas::findByNameFlexible('11 PPLG');
        $this->assertNotNull($foundSpace);
        $this->assertEquals($this->kelas->id, $foundSpace->id);

        // 3. Pencarian nama lama RPL -> teresolusi ke 11PPLG
        $foundRpl = Kelas::findByNameFlexible('11RPL');
        $this->assertNotNull($foundRpl);
        $this->assertEquals($this->kelas->id, $foundRpl->id);

        $foundRplSpace = Kelas::findByNameFlexible('11 RPL');
        $this->assertNotNull($foundRplSpace);
        $this->assertEquals($this->kelas->id, $foundRplSpace->id);
    }

    /**
     * B.2 Kaprog jurusan PPLG dapat memantau seluruh tingkatan kelas PPLG, dan terisolasi dari jurusan lain.
     */
    public function test_kaprog_controller_monitors_all_pplg_grades(): void
    {
        // Buat kelas 10PPLG dan 12PPLG
        $kelas10 = Kelas::create(['nama' => '10PPLG', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $kelas12 = Kelas::create(['nama' => '12PPLG', 'tingkat' => '12', 'tahun_ajaran' => '2026/2027']);
        $kelasAkl = Kelas::create(['nama' => '10AKL1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);

        $kaprogPplg = User::create([
            'name' => 'Kaprog PPLG',
            'email' => 'kaprog.pplg@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        $kaprogPplg->assignRole(
            Role::firstOrCreate(['name' => 'Kaprog', 'guard_name' => 'web'])
        );
        GuruProfile::create(['user_id' => $kaprogPplg->id, 'nip' => '19820101', 'kaprog_jurusan' => 'PPLG']);

        $response = $this->actingAs($kaprogPplg)->get(route('guru.kaprog.index'));
        $response->assertStatus(200);
        $response->assertSee('10PPLG');
        $response->assertSee('11PPLG');
        $response->assertSee('12PPLG');
        $response->assertDontSee('10AKL1');
    }

    /**
     * B.3 getDaftarJurusan() menyajikan PPLG dan tidak menampilkan opsi ganda RPL.
     */
    public function test_daftar_jurusan_contains_pplg_and_excludes_rpl(): void
    {
        $userController = app(UserController::class);
        $method = new ReflectionMethod($userController, 'getDaftarJurusan');
        $method->setAccessible(true);
        $jurusans = $method->invoke($userController);

        $this->assertContains('PPLG', $jurusans);
        $this->assertNotContains('RPL', $jurusans);
    }

    /**
     * B.4 Kenaikan kelas memetakan 10PPLG -> 11PPLG secara otomatis.
     */
    public function test_kenaikan_kelas_promotes_10pplg_to_11pplg(): void
    {
        $kelas10 = Kelas::create(['nama' => '10PPLG', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $targetClasses = collect([$this->kelas]); // $this->kelas adalah 11PPLG

        $controller = app(KenaikanKelasController::class);
        $method = new ReflectionMethod($controller, 'suggestTargetClass');
        $method->setAccessible(true);

        $match = $method->invoke($controller, $kelas10, $targetClasses, '11');
        $this->assertNotNull($match);
        $this->assertEquals($this->kelas->id, $match->id);
    }

    /**
     * B.5 Resolusi mata pelajaran mengenali kode dan nama PPLG.
     */
    public function test_mapel_resolution_supports_pplg(): void
    {
        $allMapels = MataPelajaran::all();

        $match = MataPelajaran::findMatchingMapel('KK-PPLG 11', '11PPLG');
        $this->assertNotNull($match);
        $this->assertEquals('KKPPLG-11', $match->kode);
    }

    /**
     * C.1 Migration up() and down() test in isolated sqlite environment.
     */
    public function test_migration_unifies_rpl_classes_and_supports_rollback(): void
    {
        // 1. Pure rename scenario: 12RPL tidak ada duplikat 12PPLG
        $kelas12Rpl = Kelas::create(['nama' => '12RPL', 'tingkat' => '12', 'tahun_ajaran' => '2026/2027']);

        // 2. Conflict/merge scenario: 11RPL ada, dan 11PPLG (dari setUp) sudah ada
        $kelas11Rpl = Kelas::create(['nama' => '11RPL', 'tingkat' => '11', 'tahun_ajaran' => '2026/2027']);
        $siswaInRpl = User::create([
            'name' => 'Siswa RPL Lama',
            'email' => 'siswa.rpl@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);
        $profile = SiswaProfile::create([
            'user_id' => $siswaInRpl->id,
            'kelas_id' => $kelas11Rpl->id,
            'nis' => '110099',
        ]);

        // Mapel tanpa benturan: KKRPL-12 -> KKPPLG-12
        $mapelRpl12 = MataPelajaran::create(['kode' => 'KKRPL-12', 'nama' => 'KK-RPL 12', 'tingkat' => '12']);
        // Mapel dengan benturan: KKRPL-11 -> KKPPLG-11 (sudah ada di setUp id: 1)
        $mapelRpl11 = MataPelajaran::create(['kode' => 'KKRPL-11', 'nama' => 'KK-RPL 11', 'tingkat' => '11']);

        $migration = require database_path('migrations/2026_10_13_080000_unify_rpl_to_pplg_data_and_schema.php');

        // Jalankan UP
        $migration->up();

        // 12RPL di-rename ke 12PPLG (ID tetap sama)
        $this->assertDatabaseHas('kelas', ['id' => $kelas12Rpl->id, 'nama' => '12PPLG']);
        $this->assertDatabaseMissing('kelas', ['nama' => '12RPL']);

        // 11RPL di-merge ke 11PPLG (ID 1), profil siswa dialihkan ke kelas 11PPLG
        $this->assertDatabaseHas('siswa_profiles', ['id' => $profile->id, 'kelas_id' => $this->kelas->id]);
        $this->assertDatabaseMissing('kelas', ['nama' => '11RPL']);

        // Mapel 12 di-rename (ID tetap)
        $this->assertDatabaseHas('mata_pelajarans', ['id' => $mapelRpl12->id, 'kode' => 'KKPPLG-12']);
        // Mapel 11 yang duplikat digabung ke ID 1
        $this->assertDatabaseHas('mata_pelajarans', ['id' => $this->mapel->id, 'kode' => 'KKPPLG-11']);
        $this->assertDatabaseMissing('mata_pelajarans', ['kode' => 'KKRPL-11']);

        // Jalankan DOWN (Rollback)
        $migration->down();

        $this->assertDatabaseHas('kelas', ['id' => $kelas12Rpl->id, 'nama' => '12RPL']);
        $this->assertDatabaseHas('mata_pelajarans', ['id' => $mapelRpl12->id, 'kode' => 'KKRPL-12']);
    }
}

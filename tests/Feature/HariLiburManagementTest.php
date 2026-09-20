<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HariLiburManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    private User $siswa;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.libur@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->guru = User::create([
            'name' => 'Guru Test',
            'email' => 'guru.libur@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->kelas = Kelas::create([
            'nama' => '10-RPL-1',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->siswa = User::create([
            'name' => 'Siswa Test',
            'email' => 'siswa.libur@siswa.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '998877',
        ]);
    }

    public function test_admin_can_view_hari_libur_index_page(): void
    {
        HariLibur::create([
            'tanggal' => '2026-08-17',
            'keterangan' => 'HUT Kemerdekaan RI ke-81',
            'jenis' => 'nasional',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.hari-libur.index'));

        $response->assertOk();
        $response->assertSee('Kalender Hari Libur');
        $response->assertSee('HUT Kemerdekaan RI ke-81');
    }

    public function test_admin_can_create_new_hari_libur(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.store'), [
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh Internasional',
            'jenis' => 'nasional',
        ]);

        $response->assertRedirect(route('admin.hari-libur.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh Internasional',
            'jenis' => 'nasional',
        ]);
    }

    public function test_prevents_duplicate_date_for_hari_libur(): void
    {
        HariLibur::create([
            'tanggal' => '2026-05-01',
            'keterangan' => 'Hari Buruh Internasional',
            'jenis' => 'nasional',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.store'), [
            'tanggal' => '2026-05-01',
            'keterangan' => 'Libur Tambahan',
            'jenis' => 'cuti_bersama',
        ]);

        $response->assertSessionHasErrors(['tanggal']);
        $this->assertDatabaseCount('hari_liburs', 1);
    }

    public function test_admin_can_delete_hari_libur(): void
    {
        $libur = HariLibur::create([
            'tanggal' => '2026-12-25',
            'keterangan' => 'Hari Raya Natal',
            'jenis' => 'nasional',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.hari-libur.destroy', $libur));

        $response->assertRedirect(route('admin.hari-libur.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('hari_liburs', [
            'id' => $libur->id,
        ]);
    }

    public function test_hari_libur_helper_methods(): void
    {
        $libur = HariLibur::create([
            'tanggal' => '2026-01-01',
            'keterangan' => 'Tahun Baru 2026',
            'jenis' => 'nasional',
        ]);

        $this->assertTrue(HariLibur::isLibur('2026-01-01'));
        $this->assertFalse(HariLibur::isLibur('2026-01-02'));

        $record = HariLibur::getLibur('2026-01-01');
        $this->assertNotNull($record);
        $this->assertEquals('Tahun Baru 2026', $record->keterangan);
        $this->assertEquals('Libur Nasional', $record->jenis_label);
    }

    public function test_guru_dashboard_receives_holiday_data(): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        HariLibur::create([
            'tanggal' => $monday,
            'keterangan' => 'Libur Peringatan Sekolah',
            'jenis' => 'khusus',
        ]);

        $response = $this->actingAs($this->guru)->get(route('guru.dashboard'));

        $response->assertOk();
        $response->assertSee('Libur Peringatan Sekolah');
    }

    public function test_siswa_dashboard_shows_holiday_banner(): void
    {
        $today = Carbon::today()->toDateString();
        HariLibur::create([
            'tanggal' => $today,
            'keterangan' => 'Cuti Bersama Semester',
            'jenis' => 'cuti_bersama',
        ]);

        $response = $this->actingAs($this->siswa)->get(route('siswa.dashboard'));

        $response->assertOk();
        $response->assertSee('Cuti Bersama Semester');
        $response->assertSee('KBM hari ini ditiadakan');
    }
}

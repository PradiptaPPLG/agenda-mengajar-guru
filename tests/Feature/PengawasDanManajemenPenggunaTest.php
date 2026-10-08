<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengawasDanManajemenPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $pengawas;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->pengawas = User::create([
            'name' => 'Ika Juliatiningsih, S.Pd., M.Pd',
            'email' => 'ikajuliatiningsih@sekolah.sch.id',
            'password' => Hash::make('196907121998022001'),
            'role' => 'pengawas',
            'is_active' => true,
        ]);

        GuruProfile::create([
            'user_id' => $this->pengawas->id,
            'nip' => '196907121998022001',
            'kaprog_jurusan' => 'Pengawas Pembina/Cabang Dinas Pendidikan Wilayah XIII',
        ]);
    }

    public function test_superadmin_can_view_manajemen_pengguna_and_see_tambah_pengguna_button(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.pengguna.index'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Pengguna');
        $response->assertSee('Ika Juliatiningsih, S.Pd., M.Pd');
        $response->assertSee('196907121998022001');
        $response->assertSee('Pengawas Sekolah');
    }

    public function test_superadmin_can_access_create_pengguna_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.pengguna.create'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Akun Pengguna Baru');
        $response->assertSee('Pengawas Sekolah');
        $response->assertSee('Kepala Sekolah');
    }

    public function test_superadmin_can_store_new_pengguna_with_role_pengawas(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.pengguna.store'), [
            'name' => 'Drs. H. Pengawas Baru, M.M.',
            'nip' => '197001011995031001',
            'email' => 'pengawasbaru@sekolah.sch.id',
            'role' => 'pengawas',
            'keterangan_jabatan' => 'Pengawas Wilayah',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Drs. H. Pengawas Baru, M.M.',
            'email' => 'pengawasbaru@sekolah.sch.id',
            'role' => 'pengawas',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('guru_profiles', [
            'nip' => '197001011995031001',
            'kaprog_jurusan' => 'Pengawas Wilayah',
        ]);
    }

    public function test_superadmin_can_update_user_role_and_details(): void
    {
        $user = User::create([
            'name' => 'Guru Calon Kepsek',
            'email' => 'calon@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.pengguna.update', $user), [
            'name' => 'Guru Calon Kepsek, M.Pd.',
            'nip' => '198005052005011002',
            'email' => 'calon@sekolah.sch.id',
            'role' => 'kepala_sekolah',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.pengguna.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Guru Calon Kepsek, M.Pd.',
            'role' => 'kepala_sekolah',
        ]);

        $this->assertDatabaseHas('guru_profiles', [
            'user_id' => $user->id,
            'nip' => '198005052005011002',
        ]);
    }

    public function test_pengawas_can_login_with_nip_and_access_monitoring(): void
    {
        // 1. Login with NIP
        $response = $this->post(route('login.post'), [
            'identifier' => '196907121998022001',
            'password' => '196907121998022001',
        ]);

        $response->assertRedirect(route('kepala-sekolah.dashboard'));
        $this->assertAuthenticatedAs($this->pengawas);

        // 2. Access dashboard monitoring
        $dashResponse = $this->actingAs($this->pengawas)->get(route('kepala-sekolah.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Pengawas Sekolah');

        // 3. Access report guru
        $reportGuru = $this->actingAs($this->pengawas)->get(route('kepala-sekolah.report.guru'));
        $reportGuru->assertStatus(200);

        // 4. Access report siswa
        $reportSiswa = $this->actingAs($this->pengawas)->get(route('kepala-sekolah.report.siswa'));
        $reportSiswa->assertStatus(200);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.pengguna.destroy', $this->superAdmin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_non_superadmin_cannot_edit_or_delete_superadmin(): void
    {
        $admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regularadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Cannot edit superadmin
        $editResponse = $this->actingAs($admin)->get(route('admin.pengguna.edit', $this->superAdmin));
        $editResponse->assertStatus(403);

        // Cannot update superadmin
        $updateResponse = $this->actingAs($admin)->put(route('admin.pengguna.update', $this->superAdmin), [
            'name' => 'Hacked Name',
            'role' => 'admin',
        ]);
        $updateResponse->assertStatus(403);

        // Cannot delete superadmin
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.pengguna.destroy', $this->superAdmin));
        $deleteResponse->assertStatus(403);
    }

    public function test_pengawas_cannot_access_superadmin_dashboard(): void
    {
        $response = $this->actingAs($this->pengawas)->get(route('super-admin.dashboard'));
        $response->assertStatus(403);
    }
}

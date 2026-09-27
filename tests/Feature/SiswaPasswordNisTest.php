<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

class SiswaPasswordNisTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_resets_siswa_password_to_nis(): void
    {
        $siswa = User::factory()->create([
            'role' => 'siswa',
            'password' => Hash::make('oldpassword'),
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'nis' => '21221001',
        ]);

        $this->artisan('siswa:reset-password-nis')
            ->assertSuccessful();

        $siswa->refresh();
        $this->assertTrue(Hash::check('21221001', $siswa->password));

        $this->artisan('siswa:reset-password-nis --check')
            ->assertSuccessful();
    }

    public function test_admin_can_reset_single_siswa_password_to_nis_via_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create([
            'role' => 'siswa',
            'password' => Hash::make('oldpassword'),
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'nis' => '21221002',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.siswa.reset-password-nis', $siswa));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswa->refresh();
        $this->assertTrue(Hash::check('21221002', $siswa->password));
    }

    public function test_admin_can_reset_all_siswa_passwords_to_nis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $siswa1 = User::factory()->create(['role' => 'siswa', 'password' => Hash::make('pw1')]);
        SiswaProfile::create(['user_id' => $siswa1->id, 'nis' => '21221003']);

        $siswa2 = User::factory()->create(['role' => 'siswa', 'password' => Hash::make('pw2')]);
        SiswaProfile::create(['user_id' => $siswa2->id, 'nis' => '21221004']);

        $response = $this->actingAs($admin)
            ->post(route('admin.siswa.reset-all-password-nis'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswa1->refresh();
        $siswa2->refresh();

        $this->assertTrue(Hash::check('21221003', $siswa1->password));
        $this->assertTrue(Hash::check('21221004', $siswa2->password));
    }

    public function test_import_siswa_sets_default_password_to_nis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kelas = Kelas::create(['nama' => '10 PPLG 1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_import_nis_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);
        $writer->addRow([
            'Nama' => 'Siswa Baru NIS',
            'NIS' => '21221005',
            'Kelas' => '10 PPLG 1',
        ]);
        $writer->close();

        $uploadedFile = new UploadedFile($tempPath, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)->post(route('admin.siswa.import'), [
            'excel_file' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.siswa.index'));
        $response->assertSessionHas('success');

        $user = User::where('name', 'Siswa Baru NIS')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('21221005', $user->password));
    }

    public function test_creating_siswa_via_user_controller_sets_password_to_nis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Siswa Manual',
                'role' => 'siswa',
                'nis' => '21221006',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('name', 'Siswa Manual')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('21221006', $user->password));
    }

    public function test_super_admin_settings_can_reset_all_siswa_passwords_to_nis(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $siswa = User::factory()->create(['role' => 'siswa', 'password' => Hash::make('pw_old')]);
        SiswaProfile::create(['user_id' => $siswa->id, 'nis' => '21221007']);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.settings.update'), [
            'school_name' => 'SMK Testing',
            'school_year' => '2026/2027',
            'semester' => '1',
            'default_siswa_status' => 'aktif',
            'reset_passwords_to_nis' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswa->refresh();
        $this->assertTrue(Hash::check('21221007', $siswa->password));
    }
}

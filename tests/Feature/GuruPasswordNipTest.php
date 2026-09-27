<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuruPasswordNipTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_resets_guru_password_to_nip(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'password' => Hash::make('oldpassword'),
        ]);

        GuruProfile::create([
            'user_id' => $guru->id,
            'nip' => '198909242014012001',
        ]);

        $this->artisan('guru:reset-password-nip')
            ->assertSuccessful();

        $guru->refresh();
        $this->assertTrue(Hash::check('198909242014012001', $guru->password));

        $this->artisan('guru:reset-password-nip --check')
            ->assertSuccessful();
    }

    public function test_admin_can_reset_single_guru_password_to_nip_via_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create([
            'role' => 'guru',
            'password' => Hash::make('oldpassword'),
        ]);

        GuruProfile::create([
            'user_id' => $guru->id,
            'nip' => '198203152008011002',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.reset-password-nip', $guru));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $guru->refresh();
        $this->assertTrue(Hash::check('198203152008011002', $guru->password));
    }

    public function test_admin_can_reset_all_guru_passwords_to_nip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $guru1 = User::factory()->create(['role' => 'guru', 'password' => Hash::make('pw1')]);
        GuruProfile::create(['user_id' => $guru1->id, 'nip' => '197512252014081001']);

        $guru2 = User::factory()->create(['role' => 'guru', 'password' => Hash::make('pw2')]);
        GuruProfile::create(['user_id' => $guru2->id, 'nip' => '198009162014082003']);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.reset-all-password-nip'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $guru1->refresh();
        $guru2->refresh();

        $this->assertTrue(Hash::check('197512252014081001', $guru1->password));
        $this->assertTrue(Hash::check('198009162014082003', $guru2->password));
    }

    public function test_creating_guru_without_password_defaults_to_nip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Guru Baru S.Pd.',
                'email' => 'gurubaru@sekolah.sch.id',
                'role' => 'guru',
                'nip' => '199505122022031055',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $newGuru = User::where('email', 'gurubaru@sekolah.sch.id')->first();
        $this->assertNotNull($newGuru);
        $this->assertTrue(Hash::check('199505122022031055', $newGuru->password));
    }
}

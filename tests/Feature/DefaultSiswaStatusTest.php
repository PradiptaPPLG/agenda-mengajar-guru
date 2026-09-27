<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultSiswaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_default_siswa_status(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Create 3 siswa with active = false
        User::factory()->count(3)->create(['role' => 'siswa', 'is_active' => false]);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.settings.update'), [
            'school_name' => 'SMK Negeri 1 Test',
            'school_year' => '2026/2027',
            'semester' => '1',
            'default_siswa_status' => 'aktif',
            'apply_to_existing_siswa' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('aktif', Setting::getDefaultSiswaStatus());
        $this->assertTrue(Setting::isDefaultSiswaActive());

        // Verify all 3 existing siswa became active
        $this->assertEquals(3, User::where('role', 'siswa')->where('is_active', true)->count());
    }

    public function test_admin_can_activate_and_deactivate_all_siswa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(5)->create(['role' => 'siswa', 'is_active' => false]);

        // Activate all
        $response = $this->actingAs($admin)->post(route('admin.siswa.activate-all'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(5, User::where('role', 'siswa')->where('is_active', true)->count());

        // Deactivate all
        $responseDeactivate = $this->actingAs($admin)->post(route('admin.siswa.deactivate-all'));
        $responseDeactivate->assertRedirect();
        $responseDeactivate->assertSessionHas('success');
        $this->assertEquals(0, User::where('role', 'siswa')->where('is_active', true)->count());
    }
}

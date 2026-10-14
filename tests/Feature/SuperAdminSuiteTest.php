<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_academic_year_get_active_self_heals_when_table_is_empty(): void
    {
        $this->assertEquals(0, AcademicYear::count());

        $activeYear = AcademicYear::getActive();

        $this->assertNotNull($activeYear);
        $this->assertTrue($activeYear->is_active);
        $this->assertEquals(1, AcademicYear::count());
    }

    public function test_maintenance_mode_allows_login_page_and_super_admin(): void
    {
        Setting::set('maintenance_mode', '1');

        // /login must not return 503
        $response = $this->get('/login');
        $this->assertNotEquals(503, $response->status());

        // Super Admin accessing dashboard must not return 503
        $superAdmin = $this->createSuperAdmin();
        $response = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));
        $response->assertOk();

        // Normal guru accessing dashboard during maintenance must receive 503
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $response = $this->actingAs($guru)->get(route('guru.dashboard'));
        $response->assertStatus(503);
    }

    public function test_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $superAdmin1 = $this->createSuperAdmin();
        $superAdmin2 = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin1)->post(route('super-admin.impersonate', $superAdmin2));

        $response->assertSessionHas('error');
    }

    public function test_trash_empty_route_does_not_throw_method_not_found(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->delete(route('super-admin.trash.empty', ['type' => 'guru']));

        $response->assertRedirect();
    }
}

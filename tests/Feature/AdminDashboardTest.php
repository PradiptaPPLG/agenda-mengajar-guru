<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_unified_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertSee('Live Progress Pertemuan Hari Ini');
        $response->assertSee('Statistik Kehadiran Guru (7 Hari)');
        $response->assertSee('Statistik Kehadiran Siswa (7 Hari)');
        $response->assertSee('Top 5 Guru Terajin');
    }

    public function test_super_admin_can_access_dashboard_with_5_metrics_and_audit_trail(): void
    {
        ActivityLog::create([
            'user_id' => $this->superAdmin->id,
            'user_name' => $this->superAdmin->name,
            'role' => 'super_admin',
            'action' => 'LOGIN',
            'module' => 'Authentication',
            'description' => 'Super Admin logged in successfully',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Super Admin');
        $response->assertSee('Total Admin');
        $response->assertSee('Audit Trail / Log Aktivitas Terbaru');
        $response->assertSee('Super Admin logged in successfully');
    }

    public function test_super_admin_dashboard_route_delegates_smoothly(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Super Admin');
    }
}

<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogCategorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.com',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator Sekolah',
            'email' => 'admin@test.com',
            'username' => 'adminsekolah',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Pengajar',
            'email' => 'guru@test.com',
            'username' => 'gurupengajar',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Super Admin dan Admin dapat mengakses riwayat log aktivitas.
     */
    public function test_super_admin_and_admin_can_access_activity_logs(): void
    {
        ActivityLog::create([
            'user_name' => 'Admin Test',
            'role' => 'admin',
            'action' => 'LOGIN',
            'module' => 'Auth',
            'description' => 'User logged in',
        ]);

        // Super Admin access
        $resSuper = $this->actingAs($this->superAdmin)->get(route('super-admin.activity-logs.index'));
        $resSuper->assertOk();
        $resSuper->assertSee('Audit Trail & Log Aktivitas Sistem', false);

        // Admin access
        $resAdmin = $this->actingAs($this->admin)->get(route('super-admin.activity-logs.index'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Audit Trail & Log Aktivitas Sistem', false);

        // Non-admin (Guru) access should be forbidden (403)
        $resGuru = $this->actingAs($this->guru)->get(route('super-admin.activity-logs.index'));
        $resGuru->assertForbidden();
    }

    /**
     * Test 2: Admin biasa tidak dapat mengakses area eksklusif Super Admin (Settings, Trash, Impersonate).
     */
    public function test_admin_cannot_access_exclusive_super_admin_routes(): void
    {
        // Admin trying to access Settings
        $resSettings = $this->actingAs($this->admin)->get(route('super-admin.settings'));
        $resSettings->assertForbidden();

        // Admin trying to access Trash Manager
        $resTrash = $this->actingAs($this->admin)->get(route('super-admin.trash.index'));
        $resTrash->assertForbidden();
    }

    /**
     * Test 3: Log dikelompokkan dengan benar berdasarkan 4 kategori tematik.
     */
    public function test_activity_logs_filter_correctly_by_category(): void
    {
        // Create logs for each category
        ActivityLog::create([
            'user_name' => 'Budi',
            'role' => 'admin',
            'action' => 'LOGIN',
            'module' => 'Auth',
            'description' => 'Login aktivitas keamanan',
        ]);

        ActivityLog::create([
            'user_name' => 'Siti',
            'role' => 'guru',
            'action' => 'CREATE',
            'module' => 'Agenda Mengajar',
            'description' => 'Mengisi materi KBM RPL',
        ]);

        ActivityLog::create([
            'user_name' => 'Doni',
            'role' => 'admin',
            'action' => 'UPDATE',
            'module' => 'Siswa',
            'description' => 'Mutasi kelas siswa Ahmad',
        ]);

        ActivityLog::create([
            'user_name' => 'Root',
            'role' => 'super_admin',
            'action' => 'UPDATE',
            'module' => 'Settings',
            'description' => 'Perubahan geofencing sekolah',
        ]);

        // 1. Kategori Keamanan
        $resKeamanan = $this->actingAs($this->admin)->get(route('super-admin.activity-logs.index', ['kategori' => 'keamanan']));
        $resKeamanan->assertOk();
        $resKeamanan->assertSee('Login aktivitas keamanan');
        $resKeamanan->assertDontSee('Mengisi materi KBM RPL');
        $resKeamanan->assertDontSee('Perubahan geofencing sekolah');

        // 2. Kategori Akademik
        $resAkademik = $this->actingAs($this->admin)->get(route('super-admin.activity-logs.index', ['kategori' => 'akademik']));
        $resAkademik->assertOk();
        $resAkademik->assertSee('Mengisi materi KBM RPL');
        $resAkademik->assertDontSee('Login aktivitas keamanan');
        $resAkademik->assertDontSee('Mutasi kelas siswa Ahmad');

        // 3. Kategori Kesiswaan
        $resKesiswaan = $this->actingAs($this->admin)->get(route('super-admin.activity-logs.index', ['kategori' => 'kesiswaan']));
        $resKesiswaan->assertOk();
        $resKesiswaan->assertSee('Mutasi kelas siswa Ahmad');
        $resKesiswaan->assertDontSee('Mengisi materi KBM RPL');
        $resKesiswaan->assertDontSee('Perubahan geofencing sekolah');

        // 4. Kategori Sistem
        $resSistem = $this->actingAs($this->admin)->get(route('super-admin.activity-logs.index', ['kategori' => 'sistem']));
        $resSistem->assertOk();
        $resSistem->assertSee('Perubahan geofencing sekolah');
        $resSistem->assertDontSee('Login aktivitas keamanan');
        $resSistem->assertDontSee('Mutasi kelas siswa Ahmad');
    }

    /**
     * Test 4: Halaman log kebal terhadap cache lama yang korup atau berisi nested array.
     */
    public function test_activity_logs_index_resilient_against_corrupted_nested_cache_or_arrays(): void
    {
        // Simulasi cache lama yang berisi nested array
        Cache::put('activity_log:available_actions', [
            ['LOGIN', 'IMPERSONATE'],
            [],
            ['RESTORE'],
        ]);
        Cache::put('activity_log:available_actions_v3', [
            ['LOGIN', 'IMPERSONATE'],
            'UPDATE',
            ['RESTORE'],
        ]);
        Cache::put('activity_log:available_modules_v3', [
            ['Auth', 'Jadwal'],
            'Kelas',
        ]);

        ActivityLog::create([
            'user_name' => 'Budi',
            'role' => 'admin',
            'action' => 'LOGIN',
            'module' => 'Auth',
            'description' => 'Test log kebal error',
        ]);

        // Akses langsung tanpa parameter (seperti klik Semua Log di PWA / browser)
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.activity-logs.index'));
        $response->assertOk();
        $response->assertSee('Semua Aksi');
        $response->assertSee('Test log kebal error');

        // Akses dengan query array ganjil tidak boleh membuat TypeError
        $resQueryArray = $this->actingAs($this->admin)->get(route('super-admin.activity-logs.index', [
            'action' => ['LOGIN'],
            'module' => ['Auth'],
        ]));
        $resQueryArray->assertOk();
    }
}

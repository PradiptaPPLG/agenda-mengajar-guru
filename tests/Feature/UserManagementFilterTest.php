<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Kaprog', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
    }

    public function test_admin_can_filter_users_by_role_and_access(): void
    {
        $guruBiasa = User::factory()->create(['name' => 'Guru Biasa', 'role' => 'guru']);
        GuruProfile::create(['user_id' => $guruBiasa->id, 'nip' => '111111']);

        $guruWali = User::factory()->create(['name' => 'Guru Wali', 'role' => 'guru']);
        $guruWali->assignRole('Wali Kelas');
        GuruProfile::create(['user_id' => $guruWali->id, 'nip' => '222222']);

        $guruKaprog = User::factory()->create(['name' => 'Guru Kaprog', 'role' => 'guru']);
        $guruKaprog->assignRole('Kaprog');
        GuruProfile::create(['user_id' => $guruKaprog->id, 'nip' => '333333', 'kaprog_jurusan' => 'PPLG']);

        $guruBk = User::factory()->create(['name' => 'Guru BK', 'role' => 'guru']);
        $guruBk->assignRole('Guru BK');
        GuruProfile::create(['user_id' => $guruBk->id, 'nip' => '444444']);

        // Filter wali_kelas
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['role_filter' => 'wali_kelas']));
        $response->assertOk();
        $response->assertSee('Guru Wali');
        $response->assertDontSee('Guru Biasa');
        $response->assertDontSee('Guru Kaprog');

        // Filter kaprog
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['role_filter' => 'kaprog']));
        $response->assertOk();
        $response->assertSee('Guru Kaprog');
        $response->assertDontSee('Guru Biasa');
        $response->assertDontSee('Guru Wali');

        // Filter bk
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['role_filter' => 'bk']));
        $response->assertOk();
        $response->assertSee('Guru BK');
        $response->assertDontSee('Guru Biasa');

        // Search by NIP
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => '333333']));
        $response->assertOk();
        $response->assertSee('Guru Kaprog');
        $response->assertDontSee('Guru Biasa');
    }

    public function test_admin_can_filter_users_by_subject_taught(): void
    {
        $mapelMatematika = MataPelajaran::factory()->create(['nama' => 'Matematika']);
        $mapelInggris = MataPelajaran::factory()->create(['nama' => 'Bahasa Inggris']);

        $guruMat = User::factory()->create(['name' => 'Guru Matematika', 'role' => 'guru']);
        $guruMat->mapels()->attach($mapelMatematika->id);

        $guruIng = User::factory()->create(['name' => 'Guru Inggris', 'role' => 'guru']);
        $guruIng->mapels()->attach($mapelInggris->id);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['mapel_id' => $mapelMatematika->id]));
        $response->assertOk();
        $response->assertSee('Guru Matematika');
        $response->assertDontSee('Guru Inggris');
    }

    public function test_admin_can_export_users_to_excel_and_pdf(): void
    {
        $guru = User::factory()->create(['name' => 'Guru Penguji Export', 'role' => 'guru']);
        GuruProfile::create(['user_id' => $guru->id, 'nip' => '199001012022011001']);

        // Export Excel
        $excelResponse = $this->actingAs($this->admin)->get(route('admin.users.export.excel'));
        $excelResponse->assertOk();
        $excelResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Export PDF
        $pdfResponse = $this->actingAs($this->admin)->get(route('admin.users.export.pdf'));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}

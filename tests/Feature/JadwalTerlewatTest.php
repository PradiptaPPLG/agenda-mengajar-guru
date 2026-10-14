<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JadwalTerlewatTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $admin;

    private User $superAdmin;

    private User $piket;

    private User $guruBk;

    private User $guruReguler;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');

        $this->kepsek = User::create([
            'name' => 'Kepsek Test',
            'email' => 'ks@test.com',
            'password' => Hash::make('password'),
            'role' => 'kepala_sekolah',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'adm@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->piket = User::create([
            'name' => 'Piket Test',
            'email' => 'piket@test.com',
            'password' => Hash::make('password'),
            'role' => 'piket',
            'is_active' => true,
        ]);

        $this->guruBk = User::create([
            'name' => 'Guru BK Test',
            'email' => 'bk@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        Kelas::create([
            'nama' => '10PPLG',
            'tingkat' => '10',
            'bk_id' => $this->guruBk->id,
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->guruReguler = User::create([
            'name' => 'Guru Reguler Test',
            'email' => 'reg@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
    }

    public function test_kepsek_can_access_monitoring_jadwal_terlewat(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('monitoring.jadwal-terlewat'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring Jadwal KBM Belum Diisi');
        $response->assertSee('Total JP Terlewat');
    }

    public function test_admin_and_super_admin_can_access_monitoring_jadwal_terlewat(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('monitoring.jadwal-terlewat'));
        $responseAdmin->assertStatus(200);

        $responseSa = $this->actingAs($this->superAdmin)->get(route('monitoring.jadwal-terlewat'));
        $responseSa->assertStatus(200);
    }

    public function test_piket_and_guru_bk_can_access_monitoring_jadwal_terlewat(): void
    {
        $responsePiket = $this->actingAs($this->piket)->get(route('monitoring.jadwal-terlewat'));
        $responsePiket->assertStatus(200);

        $responseBk = $this->actingAs($this->guruBk)->get(route('monitoring.jadwal-terlewat'));
        $responseBk->assertStatus(200);
    }

    public function test_guru_reguler_without_bk_is_forbidden(): void
    {
        $response = $this->actingAs($this->guruReguler)->get(route('monitoring.jadwal-terlewat'));

        $response->assertStatus(403);
    }

    public function test_can_download_pdf_laporan_jadwal_terlewat(): void
    {
        $response = $this->actingAs($this->kepsek)->get(route('monitoring.jadwal-terlewat.export-pdf'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruBkKelasBinaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
    }

    public function test_guru_biasa_tanpa_bk_tidak_melihat_pengaturan_kelas_binaan_bk_dan_dilarang_update(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
        ]);
        $guru->assignRole('Guru');

        $kelas = Kelas::factory()->create(['nama' => 'X RPL 1', 'tingkat' => 10]);

        $this->actingAs($guru)
            ->get(route('profile.index'))
            ->assertStatus(200)
            ->assertDontSee('Atur Kelas Binaan Guru BK');

        $response = $this->actingAs($guru)
            ->post(route('profile.bk-kelas.update'), [
                'bk_kelas_ids' => [$kelas->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('kelas', [
            'id' => $kelas->id,
            'bk_id' => $guru->id,
        ]);
    }

    public function test_guru_dengan_role_bk_dapat_melihat_dan_memperbarui_kelas_binaan(): void
    {
        $guruBk = User::factory()->create([
            'role' => 'guru',
        ]);
        $guruBk->assignRole('Guru BK');
        GuruProfile::factory()->create(['user_id' => $guruBk->id]);

        $kelas1 = Kelas::factory()->create(['nama' => 'X PPLG 1', 'tingkat' => 10]);
        $kelas2 = Kelas::factory()->create(['nama' => 'X PPLG 2', 'tingkat' => 10]);
        $kelas3 = Kelas::factory()->create(['nama' => 'XI PPLG 1', 'tingkat' => 11, 'bk_id' => $guruBk->id]);

        // Cek halaman profil
        $this->actingAs($guruBk)
            ->get(route('profile.index'))
            ->assertStatus(200)
            ->assertSee('Atur Kelas Binaan Guru BK')
            ->assertSee('X PPLG 1')
            ->assertSee('XI PPLG 1');

        // Update: pilih kelas1 dan kelas2, lepas kelas3
        $response = $this->actingAs($guruBk)
            ->post(route('profile.bk-kelas.update'), [
                'bk_kelas_ids' => [$kelas1->id, $kelas2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals($guruBk->id, $kelas1->fresh()->bk_id);
        $this->assertEquals($guruBk->id, $kelas2->fresh()->bk_id);
        $this->assertNull($kelas3->fresh()->bk_id);
    }

    public function test_guru_pengampu_mapel_bp_bk_dapat_mengatur_kelas_binaan_bk(): void
    {
        $guru = User::factory()->create([
            'role' => 'guru',
        ]);
        $guru->assignRole('Guru');

        $mapelBk = MataPelajaran::factory()->create([
            'nama' => 'Bimbingan Konseling (BK)',
            'kode' => 'BP',
        ]);
        $guru->mapels()->attach($mapelBk->id);

        $this->assertTrue($guru->fresh()->isGuruBk());

        $kelas = Kelas::factory()->create(['nama' => 'XII DKV 1', 'tingkat' => 12]);

        $response = $this->actingAs($guru)
            ->post(route('profile.bk-kelas.update'), [
                'bk_kelas_ids' => [$kelas->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals($guru->id, $kelas->fresh()->bk_id);
    }

    public function test_dashboard_bk_menampilkan_kelas_binaan_yang_telah_diatur(): void
    {
        $guruBk = User::factory()->create([
            'role' => 'guru',
        ]);
        $guruBk->assignRole('Guru BK');

        $kelasBinaan = Kelas::factory()->create([
            'nama' => 'X Kuliner 1',
            'tingkat' => 10,
            'bk_id' => $guruBk->id,
        ]);
        $kelasLain = Kelas::factory()->create([
            'nama' => 'XII AKL 2',
            'tingkat' => 12,
            'bk_id' => null,
        ]);

        $this->actingAs($guruBk)
            ->get(route('guru.bk.index'))
            ->assertStatus(200)
            ->assertSee('X Kuliner 1')
            ->assertSee('Membina 1 Kelas Aktif')
            ->assertSee('Atur Kelas Binaan');
    }
}

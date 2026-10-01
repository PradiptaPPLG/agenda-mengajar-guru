<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruDetailAndClassAssignmentTest extends TestCase
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
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Kaprog', 'guard_name' => 'web']);

        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '1');
    }

    public function test_admin_can_view_guru_detail_page_with_complete_info(): void
    {
        $guru = User::factory()->create([
            'name' => 'Budi Santoso, S.Pd.',
            'email' => 'budi@smk.sch.id',
            'role' => 'guru',
        ]);
        GuruProfile::create([
            'user_id' => $guru->id,
            'nip' => '198501012010011005',
        ]);

        $kelasWali = Kelas::create(['nama' => '10AKL1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027', 'wali_kelas_id' => $guru->id]);
        $kelasBk = Kelas::create(['nama' => '10DKV', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027', 'bk_id' => $guru->id]);
        $kelasLain = Kelas::create(['nama' => '10PPLG', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);

        $guru->assignRole('Wali Kelas');
        $guru->assignRole('Guru BK');

        $mapel = MataPelajaran::create(['nama' => 'Dasar Akuntansi', 'kode' => 'AKL-101', 'jenis' => 'produktif']);
        $guru->mapels()->attach($mapel->id);

        JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelasWali->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:30:00',
            'jam_selesai' => '09:00:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'kelompok_blok' => 'reguler',
        ]);

        JadwalPelajaran::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelasLain->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => 2,
            'jam_mulai' => '09:15:00',
            'jam_selesai' => '11:00:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'kelompok_blok' => 'reguler',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.show', $guru));

        $response->assertOk();
        $response->assertSee('Budi Santoso, S.Pd.');
        $response->assertSee('198501012010011005');
        $response->assertSee('Dasar Akuntansi');
        $response->assertSee('10AKL1');
        $response->assertSee('10DKV');
        $response->assertSee('10PPLG');
        $response->assertSee('2 Kelas Diampu');
    }

    public function test_admin_can_edit_classes_assigned_to_mapel_for_guru(): void
    {
        $guru = User::factory()->create([
            'name' => 'Siti Rahmawati, S.Pd.',
            'email' => 'siti@smk.sch.id',
            'role' => 'guru',
        ]);
        GuruProfile::create([
            'user_id' => $guru->id,
            'nip' => '198702022011012003',
        ]);

        $kelas1 = Kelas::create(['nama' => '10AKL1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $kelas2 = Kelas::create(['nama' => '10AKL2', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $mapel = MataPelajaran::create(['nama' => 'Matematika', 'kode' => 'MAT-10', 'jenis' => 'umum']);

        // Update penugasan kelas: pilih kelas1 dan kelas2 untuk mapel Matematika
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $guru), [
            'name' => 'Siti Rahmawati, S.Pd.',
            'email' => 'siti@smk.sch.id',
            'role' => 'guru',
            'nip' => '198702022011012003',
            'mapel_ids' => [$mapel->id],
            'mapel_kelas' => [
                $mapel->id => [$kelas1->id, $kelas2->id],
            ],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('guru_mata_pelajaran', [
            'user_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);

        // Verifikasi jadwal terbentuk untuk kedua kelas
        $this->assertDatabaseHas('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas1->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);
        $this->assertDatabaseHas('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas2->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);

        // Sekarang uncheck kelas2, hanya pertahankan kelas1
        $response2 = $this->actingAs($this->admin)->put(route('admin.users.update', $guru), [
            'name' => 'Siti Rahmawati, S.Pd.',
            'email' => 'siti@smk.sch.id',
            'role' => 'guru',
            'nip' => '198702022011012003',
            'mapel_ids' => [$mapel->id],
            'mapel_kelas' => [
                $mapel->id => [$kelas1->id],
            ],
        ]);

        $response2->assertRedirect(route('admin.users.index'));

        // Jadwal kelas1 tetap ada, jadwal kelas2 sudah lepas/dihapus
        $this->assertDatabaseHas('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas1->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);
        $this->assertDatabaseMissing('jadwal_pelajarans', [
            'guru_id' => $guru->id,
            'kelas_id' => $kelas2->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);
    }
}

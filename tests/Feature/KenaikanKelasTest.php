<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KenaikanKelasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Kenaikan Kelas Test',
            'email' => 'admin.kenaikan@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Test',
            'email' => 'guru.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_kenaikan_kelas_page(): void
    {
        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '2');

        $k10 = Kelas::create(['nama' => '10 PPLG 1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $k11 = Kelas::create(['nama' => '11 RPL 1', 'tingkat' => '11', 'tahun_ajaran' => '2026/2027']);
        $k12 = Kelas::create(['nama' => '12 RPL 1', 'tingkat' => '12', 'tahun_ajaran' => '2026/2027']);

        $response = $this->actingAs($this->admin)->get(route('admin.kenaikan-kelas.index'));

        $response->assertStatus(200);
        $response->assertSee('Kenaikan Kelas');
        $response->assertSee('Tutup Tahun Ajaran');
        $response->assertSee('2026/2027');
        $response->assertSee('2027/2028');
        $response->assertSee('10 PPLG 1');
        $response->assertSee('11 RPL 1');
        $response->assertSee('12 RPL 1');
    }

    public function test_non_admin_cannot_access_kenaikan_kelas_page(): void
    {
        $response = $this->actingAs($this->guru)->get(route('admin.kenaikan-kelas.index'));
        $response->assertStatus(403);
    }

    public function test_get_siswa_kelas_returns_json_list(): void
    {
        $kelas = Kelas::create(['nama' => '11 DKV 1', 'tingkat' => '11', 'tahun_ajaran' => '2026/2027']);

        $user1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $user1->id,
            'kelas_id' => $kelas->id,
            'nis' => '12345',
        ]);

        $user2 = User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $user2->id,
            'kelas_id' => $kelas->id,
            'nis' => '12346',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.kenaikan-kelas.siswa', $kelas));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'kelas_id',
            'kelas_nama',
            'tingkat',
            'total',
            'siswa' => [
                '*' => ['profile_id', 'user_id', 'name', 'nis', 'is_active'],
            ],
        ]);
        $response->assertJson([
            'kelas_id' => $kelas->id,
            'total' => 2,
        ]);
    }

    public function test_process_promotes_students_and_graduates_class_12(): void
    {
        Setting::set('school_year', '2026/2027');
        Setting::set('semester', '2');

        $k10 = Kelas::create(['nama' => '10 PPLG 1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $k11 = Kelas::create(['nama' => '11 RPL 1', 'tingkat' => '11', 'tahun_ajaran' => '2026/2027']);
        $k12 = Kelas::create(['nama' => '12 RPL 1', 'tingkat' => '12', 'tahun_ajaran' => '2026/2027']);

        // Siswa 10
        $u10 = User::create(['name' => 'Siswa 10', 'email' => 's10@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $p10 = SiswaProfile::create(['user_id' => $u10->id, 'kelas_id' => $k10->id, 'nis' => '1001']);

        // Siswa 11
        $u11 = User::create(['name' => 'Siswa 11', 'email' => 's11@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $p11 = SiswaProfile::create(['user_id' => $u11->id, 'kelas_id' => $k11->id, 'nis' => '1101']);

        // Siswa 12
        $u12 = User::create(['name' => 'Siswa 12', 'email' => 's12@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $p12 = SiswaProfile::create(['user_id' => $u12->id, 'kelas_id' => $k12->id, 'nis' => '1201']);

        $response = $this->actingAs($this->admin)->post(route('admin.kenaikan-kelas.process'), [
            'mapping_11_to_12' => [$k11->id => $k12->id],
            'mapping_10_to_11' => [$k10->id => $k11->id],
            'luluskan_kelas_12' => '1',
            'update_school_year' => '1',
            'target_school_year' => '2027/2028',
            'reset_semester' => '1',
        ]);

        $response->assertRedirect(route('admin.kelas.index'));
        $response->assertSessionHas('success');

        // Siswa 12 lulus (user inactive dan kelas_id menjadi null)
        $this->assertFalse($u12->fresh()->is_active);
        $this->assertNull($p12->fresh()->kelas_id);

        // Siswa 11 naik ke kelas 12
        $this->assertEquals($k12->id, $p11->fresh()->kelas_id);

        // Siswa 10 naik ke kelas 11
        $this->assertEquals($k11->id, $p10->fresh()->kelas_id);

        // Setting tahun ajaran dan semester diperbarui
        $this->assertEquals('2027/2028', Setting::get('school_year'));
        $this->assertEquals('1', Setting::get('semester'));
    }

    public function test_retained_grade_12_student_does_not_graduate_and_retains_class(): void
    {
        $k12 = Kelas::create(['nama' => '12 RPL 1', 'tingkat' => '12', 'tahun_ajaran' => '2026/2027']);

        // Siswa 12 Lulus
        $uLulus = User::create(['name' => 'Siswa 12 Lulus', 'email' => 's12lulus@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $pLulus = SiswaProfile::create(['user_id' => $uLulus->id, 'kelas_id' => $k12->id, 'nis' => '1201']);

        // Siswa 12 Tidak Lulus (Tinggal Kelas)
        $uTinggal = User::create(['name' => 'Siswa 12 Tinggal', 'email' => 's12tinggal@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $pTinggal = SiswaProfile::create(['user_id' => $uTinggal->id, 'kelas_id' => $k12->id, 'nis' => '1202']);

        $response = $this->actingAs($this->admin)->post(route('admin.kenaikan-kelas.process'), [
            'luluskan_kelas_12' => '1',
            'tinggal_kelas_profiles' => [$pTinggal->id],
            'update_school_year' => '0',
            'reset_semester' => '0',
        ]);

        $response->assertRedirect(route('admin.kelas.index'));

        // Siswa Lulus: Akun nonaktif dan kelas_id null
        $this->assertFalse($uLulus->fresh()->is_active);
        $this->assertNull($pLulus->fresh()->kelas_id);

        // Siswa Tinggal Kelas 12: Akun tetap aktif dan kelas_id tetap di kelas 12
        $this->assertTrue($uTinggal->fresh()->is_active);
        $this->assertEquals($k12->id, $pTinggal->fresh()->kelas_id);
    }

    public function test_retained_students_remain_in_their_original_class(): void
    {
        Setting::set('school_year', '2026/2027');

        $k10 = Kelas::create(['nama' => '10 AKL 1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);
        $k11 = Kelas::create(['nama' => '11 AK 1', 'tingkat' => '11', 'tahun_ajaran' => '2026/2027']);

        // Siswa Naik
        $uNaik = User::create(['name' => 'Siswa Naik', 'email' => 'naik@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $pNaik = SiswaProfile::create(['user_id' => $uNaik->id, 'kelas_id' => $k10->id, 'nis' => '2001']);

        // Siswa Tinggal
        $uTinggal = User::create(['name' => 'Siswa Tinggal', 'email' => 'tinggal@sekolah.sch.id', 'password' => Hash::make('password'), 'role' => 'siswa', 'is_active' => true]);
        $pTinggal = SiswaProfile::create(['user_id' => $uTinggal->id, 'kelas_id' => $k10->id, 'nis' => '2002']);

        $response = $this->actingAs($this->admin)->post(route('admin.kenaikan-kelas.process'), [
            'mapping_10_to_11' => [$k10->id => $k11->id],
            'tinggal_kelas_profiles' => [$pTinggal->id],
            'luluskan_kelas_12' => '0',
            'update_school_year' => '0',
            'reset_semester' => '0',
        ]);

        $response->assertRedirect(route('admin.kelas.index'));

        // Siswa Naik pindah ke kelas 11
        $this->assertEquals($k11->id, $pNaik->fresh()->kelas_id);

        // Siswa Tinggal tetap berada di kelas 10
        $this->assertEquals($k10->id, $pTinggal->fresh()->kelas_id);
    }
}

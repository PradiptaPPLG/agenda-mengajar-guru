<?php

namespace Tests\Feature;

use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WaliKelasAndToleranceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_wali_kelas_can_manage_own_class_students_and_prevented_from_other_classes(): void
    {
        $waliA = User::factory()->create(['role' => 'guru']);
        $kelasA = Kelas::factory()->create(['wali_kelas_id' => $waliA->id]);

        $waliB = User::factory()->create(['role' => 'guru']);
        $kelasB = Kelas::factory()->create(['wali_kelas_id' => $waliB->id]);

        $siswaA = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        SiswaProfile::factory()->create(['user_id' => $siswaA->id, 'kelas_id' => $kelasA->id]);

        $siswaB = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        SiswaProfile::factory()->create(['user_id' => $siswaB->id, 'kelas_id' => $kelasB->id]);

        // 1. Wali A can toggle student in own class (Kelas A)
        $response = $this->actingAs($waliA)->post(route('guru.wali-kelas.siswa.toggle', $siswaA->id));
        $response->assertRedirect();
        $this->assertFalse($siswaA->fresh()->is_active);

        // 2. Wali A CANNOT toggle student in Kelas B (IDOR protection) -> 403
        $responseForbidden = $this->actingAs($waliA)->post(route('guru.wali-kelas.siswa.toggle', $siswaB->id));
        $responseForbidden->assertStatus(403);
        $this->assertTrue($siswaB->fresh()->is_active);

        // 3. Wali A can bulk deactivate all students in own class
        $resBulk = $this->actingAs($waliA)->post(route('guru.wali-kelas.siswa.bulk'), [
            'kelas_id' => $kelasA->id,
            'action' => 'nonaktifkan_semua',
        ]);
        $resBulk->assertRedirect();
        $this->assertFalse($siswaA->fresh()->is_active);

        // 4. Wali A CANNOT bulk deactivate students in Kelas B -> 403
        $resBulkForbidden = $this->actingAs($waliA)->post(route('guru.wali-kelas.siswa.bulk'), [
            'kelas_id' => $kelasB->id,
            'action' => 'nonaktifkan_semua',
        ]);
        $resBulkForbidden->assertStatus(403);
    }

    public function test_wali_kelas_cannot_toggle_non_siswa_role(): void
    {
        $wali = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create(['wali_kelas_id' => $wali->id]);

        $anotherGuru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($wali)->post(route('guru.wali-kelas.siswa.toggle', $anotherGuru->id));
        $response->assertStatus(403);
    }

    public function test_deactivated_student_is_denied_access(): void
    {
        $inactiveStudent = User::factory()->create(['role' => 'siswa', 'is_active' => false]);

        // Attempting to visit dashboard
        $response = $this->actingAs($inactiveStudent)->get(route('siswa.dashboard'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_super_admin_can_update_dual_tolerance_settings(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->post(route('super-admin.settings.update'), [
            'school_name' => 'SMK Negeri 1 Test',
            'school_year' => '2026/2027',
            'semester' => '1',
            'default_siswa_status' => 'aktif',
            'toleransi_mapel_pertama_menit' => 12,
            'toleransi_mapel_lanjutan_menit' => 20,
        ]);

        $response->assertRedirect();
        $this->assertEquals(12, Setting::get('toleransi_mapel_pertama_menit'));
        $this->assertEquals(20, Setting::get('toleransi_mapel_lanjutan_menit'));
    }

    public function test_student_cannot_mark_terlambat_during_tolerance_period(): void
    {
        Storage::fake('public');

        // Set current time to 07:05 WIB
        Carbon::setTestNow(Carbon::createFromTime(7, 5, 0));

        $kelas = Kelas::factory()->create();
        $guru = User::factory()->create(['role' => 'guru']);
        $mapel = MataPelajaran::factory()->create();

        $siswa = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        SiswaProfile::factory()->create(['user_id' => $siswa->id, 'kelas_id' => $kelas->id]);

        $dayOfWeek = now()->dayOfWeekIso; // 1 = Senin, etc.

        // Schedule starting at 07:00 (tolerance 10 min, so deadline is 07:10)
        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => $dayOfWeek,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '07:45:00',
        ]);

        $today = now()->format('Y-m-d');

        // Attempt to submit 'terlambat' at 07:05 (within 10-minute tolerance)
        $response = $this->actingAs($siswa)->post(route('siswa.capture.store', ['jadwal' => $jadwal->id, 'tanggal' => $today]), [
            'status_guru_dilaporkan' => 'terlambat',
            'foto' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        // Expect validation error preventing 'terlambat'
        $response->assertSessionHasErrors('status_guru_dilaporkan');

        Carbon::setTestNow(); // Reset time mock
    }

    public function test_student_can_report_absence_with_cuti_and_keterangan(): void
    {
        Storage::fake('public');

        $kelas = Kelas::factory()->create();
        $guru = User::factory()->create(['role' => 'guru']);
        $mapel = MataPelajaran::factory()->create();

        $siswa = User::factory()->create(['role' => 'siswa', 'is_active' => true]);
        SiswaProfile::factory()->create(['user_id' => $siswa->id, 'kelas_id' => $kelas->id]);

        $dayOfWeek = now()->dayOfWeekIso;

        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => $dayOfWeek,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '07:45:00',
        ]);

        $today = now()->format('Y-m-d');

        $response = $this->actingAs($siswa)->post(route('siswa.capture.store', ['jadwal' => $jadwal->id, 'tanggal' => $today]), [
            'status_guru_dilaporkan' => 'tidak_hadir',
            'alasan_tidak_hadir' => 'cuti',
            'keterangan' => 'Guru cuti tahunan selama 3 hari',
            'foto' => UploadedFile::fake()->image('bukti_cuti.jpg'),
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $response->assertSessionHas('success');

        // Verify FotoBukti has keterangan
        $fotoBukti = FotoBukti::latest('id')->first();
        $this->assertNotNull($fotoBukti);
        $this->assertEquals('cuti', $fotoBukti->alasan_tidak_hadir);
        $this->assertEquals('Guru cuti tahunan selama 3 hari', $fotoBukti->keterangan);

        // Verify KehadiranGuru
        $kehadiranGuru = KehadiranGuru::where('guru_id', $guru->id)->first();
        $this->assertNotNull($kehadiranGuru);
        $this->assertEquals('tidak_hadir', $kehadiranGuru->status);
        $this->assertEquals('cuti', $kehadiranGuru->alasan_tidak_hadir);
        $this->assertEquals('Cuti', $kehadiranGuru->alasan_tidak_hadir_label);
        $this->assertEquals('Guru cuti tahunan selama 3 hari', $kehadiranGuru->keterangan);
    }
}

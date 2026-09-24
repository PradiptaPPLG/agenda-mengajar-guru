<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

class GuruImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_guru_without_nip_and_update_nip_later(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create base kelas and mapel
        Kelas::create(['nama' => '11AK1', 'tingkat' => '11', 'tahun_ajaran' => '2026/2027']);
        MataPelajaran::create(['nama' => 'MATEMATIKA', 'kode' => 'MAT', 'jenis' => 'umum']);

        // 1. First Import: File without NIP
        $tempPath1 = tempnam(sys_get_temp_dir(), 'test_guru_1_').'.xlsx';
        $writer1 = SimpleExcelWriter::create($tempPath1);
        $writer1->addRow([
            'Nama Guru' => 'ELIN KARLINAH Dra.',
            'Mapel yang diampu' => 'MAT',
            'Kelas' => '11 AK1',
        ]);
        $writer1->close();

        $uploadedFile1 = new UploadedFile($tempPath1, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response1 = $this->actingAs($admin)
            ->post(route('admin.users.import'), [
                'excel_file' => $uploadedFile1,
            ]);

        $response1->assertRedirect(route('admin.users.index'));
        $response1->assertSessionHas('success');

        $user = User::where('name', 'ELIN KARLINAH Dra.')->first();
        $this->assertNotNull($user);
        $this->assertEquals('guru', $user->role);
        $this->assertNotNull($user->guruProfile);
        $this->assertNull($user->guruProfile->nip);
        $this->assertEquals(1, $user->mapels()->count());
        $this->assertEquals(0, $user->jadwalPelajarans()->count()); // Upload guru tidak membuat jadwal palsu lagi

        // 2. Second Import: File WITH NIP to update existing teacher
        $tempPath2 = tempnam(sys_get_temp_dir(), 'test_guru_2_').'.xlsx';
        $writer2 = SimpleExcelWriter::create($tempPath2);
        $writer2->addRow([
            'Nama' => 'Dra. ELIN KARLINAH', // slight variation in title order
            'NIP' => '196501011990032001',
        ]);
        $writer2->close();

        $uploadedFile2 = new UploadedFile($tempPath2, 'guru_nip.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response2 = $this->actingAs($admin)
            ->post(route('admin.users.import'), [
                'excel_file' => $uploadedFile2,
            ]);

        $response2->assertRedirect(route('admin.users.index'));

        // Verify that NO new user was created
        $this->assertEquals(2, User::count()); // 1 admin + 1 teacher

        // Verify that NIP is accurately updated under the teacher
        $user->refresh();
        $this->assertEquals('196501011990032001', $user->guruProfile->nip);

        @unlink($tempPath1);
        @unlink($tempPath2);
    }

    public function test_can_import_real_guru_xlsx_file(): void
    {
        $realFile = base_path('guru.xlsx');
        if (! file_exists($realFile)) {
            $this->markTestSkipped('guru.xlsx does not exist');
        }

        $admin = User::factory()->create(['role' => 'admin']);

        $uploadedFile = new UploadedFile($realFile, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.import'), [
                'excel_file' => $uploadedFile,
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // All 97 teachers imported
        $this->assertEquals(98, User::count()); // 1 admin + 97 teachers
        $gurus = User::where('role', 'guru')->get();
        $this->assertCount(97, $gurus);

        // Every guru has a GuruProfile
        foreach ($gurus as $guru) {
            $this->assertNotNull($guru->guruProfile, "Guru {$guru->name} should have a guruProfile");
        }
    }

    public function test_can_import_real_guru_dengan_nip_xlsx_file(): void
    {
        $realFile = base_path('guru_dengan_nip.xlsx');
        if (! file_exists($realFile)) {
            $this->markTestSkipped('guru_dengan_nip.xlsx does not exist');
        }

        $admin = User::factory()->create(['role' => 'admin']);

        $uploadedFile = new UploadedFile($realFile, 'guru_dengan_nip.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.import'), [
                'excel_file' => $uploadedFile,
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $gurus = User::where('role', 'guru')->get();
        $this->assertCount(97, $gurus);

        // In guru_dengan_nip.xlsx, exactly 9 distinct unique NIPs exist (the rest are duplicate/placeholder copy-paste)
        $gurusWithNip = $gurus->filter(fn ($g) => ! empty($g->guruProfile?->nip));
        $this->assertEquals(9, $gurusWithNip->count(), 'All distinct NIPs from file should be populated');
    }
}

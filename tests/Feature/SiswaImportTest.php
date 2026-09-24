<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

class SiswaImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_siswa_without_email_and_restore_soft_deleted_siswa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kelas = Kelas::create(['nama' => '10 PPLG 1', 'tingkat' => '10', 'tahun_ajaran' => '2026/2027']);

        // 1. First import: Abdul Rohimat without email
        $tempPath1 = tempnam(sys_get_temp_dir(), 'test_siswa_1_').'.xlsx';
        $writer1 = SimpleExcelWriter::create($tempPath1);
        $writer1->addRow([
            'Nama' => 'ABDUL ROHIMAT',
            'NIS' => '0108732718',
            'Kelas' => '10 PPLG 1',
        ]);
        $writer1->close();

        $uploadedFile1 = new UploadedFile($tempPath1, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response1 = $this->actingAs($admin)->post(route('admin.siswa.import'), [
            'excel_file' => $uploadedFile1,
        ]);

        $response1->assertRedirect(route('admin.siswa.index'));
        $response1->assertSessionHas('success');

        $user = User::where('name', 'ABDUL ROHIMAT')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email);
        $this->assertEquals('siswa', $user->role);

        $profile = SiswaProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('0108732718', $profile->nis);
        $this->assertEquals($kelas->id, $profile->kelas_id);

        // 2. Soft-delete the user (simulate admin deleting student in UI)
        $user->delete();
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // 3. Re-import the exact same file
        $tempPath2 = tempnam(sys_get_temp_dir(), 'test_siswa_2_').'.xlsx';
        $writer2 = SimpleExcelWriter::create($tempPath2);
        $writer2->addRow([
            'Nama' => 'ABDUL ROHIMAT',
            'NIS' => '0108732718',
            'Kelas' => '10 PPLG 1',
        ]);
        $writer2->close();

        $uploadedFile2 = new UploadedFile($tempPath2, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response2 = $this->actingAs($admin)->post(route('admin.siswa.import'), [
            'excel_file' => $uploadedFile2,
        ]);

        $response2->assertRedirect(route('admin.siswa.index'));
        $response2->assertSessionHas('success');

        // Verify the user is restored and not duplicate, and email remains null
        $userFresh = User::withTrashed()->where('name', 'ABDUL ROHIMAT')->first();
        $this->assertNotNull($userFresh);
        $this->assertFalse($userFresh->trashed());
        $this->assertNull($userFresh->email);
    }
}

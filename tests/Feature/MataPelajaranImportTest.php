<?php

namespace Tests\Feature;

use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MataPelajaranImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Penguji',
            'email' => 'admin.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_import_mapel_dari_file_excel_resmi(): void
    {
        $filePath = base_path('mapel_dengan_kategori.xlsx');
        $this->assertFileExists($filePath);

        $uploadedFile = new UploadedFile(
            $filePath,
            'mapel_dengan_kategori.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->post(route('admin.mata-pelajaran.import'), [
                'file' => $uploadedFile,
            ]);

        $response->assertRedirect(route('admin.mata-pelajaran.index'));
        $response->assertSessionHas('success');

        // Harus tepat 67 mata pelajaran, tanpa duplikat
        $this->assertCount(67, MataPelajaran::all());

        // Verifikasi nama dan kode bersih tanpa spasi aneh di kode
        $this->assertDatabaseHas('mata_pelajarans', [
            'nama' => 'KK-RPL 12',
            'kode' => 'KKRPL-12',
            'jenis' => 'produktif',
            'kelompok_blok' => 'kelompok_b',
        ]);

        $this->assertDatabaseHas('mata_pelajarans', [
            'nama' => 'MATEMATIKA',
            'kode' => 'MAT',
            'jenis' => 'umum',
            'kelompok_blok' => 'kelompok_a',
        ]);

        // Verifikasi multi-kejuruan kode MP-11 tidak bentrok dan tidak bersufiks -1, -2
        $this->assertDatabaseHas('mata_pelajarans', [
            'nama' => 'MP-RPL 11',
            'kode' => 'MP-11',
        ]);
        $this->assertDatabaseHas('mata_pelajarans', [
            'nama' => 'MP-DKV 11',
            'kode' => 'MP-11',
        ]);
        $this->assertDatabaseMissing('mata_pelajarans', [
            'kode' => 'MP-11-1',
        ]);
    }

    public function test_import_mapel_is_idempotent_no_duplicate_created(): void
    {
        $filePath = base_path('mapel_dengan_kategori.xlsx');

        $uploadedFile1 = new UploadedFile($filePath, 'mapel_dengan_kategori.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($this->admin)->post(route('admin.mata-pelajaran.import'), ['file' => $uploadedFile1]);
        $this->assertCount(67, MataPelajaran::all());

        // Re-import file yang sama
        $uploadedFile2 = new UploadedFile($filePath, 'mapel_dengan_kategori.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($this->admin)->post(route('admin.mata-pelajaran.import'), ['file' => $uploadedFile2]);

        // Tetap tepat 67 mata pelajaran, 0 duplikat!
        $this->assertCount(67, MataPelajaran::all());
    }
}

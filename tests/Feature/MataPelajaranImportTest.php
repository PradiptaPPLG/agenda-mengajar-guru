<?php

namespace Tests\Feature;

use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

class MataPelajaranImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_mapel_and_restore_soft_deleted_mapel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Create a mapel then soft-delete it
        $mapel = MataPelajaran::create([
            'nama' => 'Pendidikan Agama Islam',
            'kode' => 'PAI',
            'jenis' => 'umum',
        ]);
        $mapel->delete();
        $this->assertSoftDeleted('mata_pelajarans', ['id' => $mapel->id]);

        // 2. Import an excel containing PAI (previously failed with 1062 duplicate key)
        $tempPath = tempnam(sys_get_temp_dir(), 'test_mapel_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);
        $writer->addRow([
            'No' => '1',
            'Mata pelajaran' => 'Pendidikan Agama Islam',
            'Kode' => 'PAI',
        ]);
        $writer->addRow([
            'No' => '2',
            'Mata pelajaran' => 'Bahasa Indonesia',
            'Kode' => 'INDO',
        ]);
        $writer->close();

        $uploadedFile = new UploadedFile($tempPath, 'mapel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)->post(route('admin.mata-pelajaran.import'), [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.mata-pelajaran.index'));
        $response->assertSessionHas('success');

        // PAI should be restored
        $mapelFresh = MataPelajaran::where('kode', 'PAI')->first();
        $this->assertNotNull($mapelFresh);
        $this->assertFalse($mapelFresh->trashed());

        // INDO should be created
        $indo = MataPelajaran::where('kode', 'INDO')->first();
        $this->assertNotNull($indo);
    }

    public function test_can_import_from_real_excel_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $filePath = base_path('Daftar mapel dan guru.xlsx');

        if (! file_exists($filePath)) {
            $this->markTestSkipped('File Daftar mapel dan guru.xlsx not found.');
        }

        $uploadedFile = new UploadedFile($filePath, 'Daftar mapel dan guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)->post(route('admin.mata-pelajaran.import'), [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect(route('admin.mata-pelajaran.index'));
        $response->assertSessionHas('success');

        $this->assertGreaterThan(50, MataPelajaran::count());
    }
}

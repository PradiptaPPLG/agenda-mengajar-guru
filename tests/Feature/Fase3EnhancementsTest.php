<?php

namespace Tests\Feature;

use App\Models\FotoBukti;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use App\Services\ImageCompressor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase3EnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_compressor_resizes_and_converts_to_webp(): void
    {
        Storage::fake('public');

        $compressor = new ImageCompressor;
        $file = UploadedFile::fake()->image('camera_capture.jpg', 2400, 1600);

        $storedPath = $compressor->compressAndStore($file, 'foto-bukti', 1200, 80);

        $this->assertStringEndsWith('.webp', $storedPath);
        Storage::disk('public')->assertExists($storedPath);

        // Check dimensions of compressed file
        $fullPath = Storage::disk('public')->path($storedPath);
        $size = getimagesize($fullPath);
        $this->assertNotNull($size);
        $this->assertLessThanOrEqual(1200, $size[0]);
        $this->assertLessThanOrEqual(1200, $size[1]);
    }

    public function test_siswa_capture_store_saves_compressed_webp_photo(): void
    {
        Storage::fake('public');

        $kelas = Kelas::factory()->create();
        $siswa = User::factory()->create(['role' => 'siswa']);
        SiswaProfile::factory()->create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);

        $guru = User::factory()->create(['role' => 'guru']);
        $mapel = MataPelajaran::factory()->create(['jenis' => 'normatif']);

        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ]);

        // Fix time to today 08:00
        $now = Carbon::parse('next monday 08:00:00');
        Carbon::setTestNow($now);

        $fakePhoto = UploadedFile::fake()->image('selfie_evidence.jpg', 1800, 1200);

        $response = $this->actingAs($siswa)->post(route('siswa.capture.store', [
            'jadwal' => $jadwal->id,
            'tanggal' => $now->toDateString(),
        ]), [
            'foto' => $fakePhoto,
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('foto_buktis', [
            'siswa_id' => $siswa->id,
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $foto = FotoBukti::where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($foto);
        $this->assertStringEndsWith('.webp', $foto->foto_path);
        Storage::disk('public')->assertExists($foto->foto_path);
    }

    public function test_kehadiran_guru_discrepancy_attribute(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create();
        $mapel = MataPelajaran::factory()->create(['jenis' => 'normatif']);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);

        $pertemuan = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => now()->startOfDay(),
            'status' => 'selesai',
        ]);

        $kehadiranGuru = KehadiranGuru::create([
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $guru->id,
            'status' => 'hadir',
        ]);

        // Case 1: No student report yet
        $this->assertFalse($kehadiranGuru->has_discrepancy);

        // Case 2: Student reports 'hadir' (matches teacher)
        $fotoBukti = FotoBukti::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'foto_path' => 'foto-bukti/sample.webp',
            'status_guru_dilaporkan' => 'hadir',
        ]);
        $kehadiranGuru->unsetRelation('pertemuan');
        $this->assertFalse($kehadiranGuru->fresh()->has_discrepancy);

        // Case 3: Student reports 'alpa' (contradicts teacher)
        $fotoBukti->update(['status_guru_dilaporkan' => 'alpa']);
        $this->assertTrue($kehadiranGuru->fresh()->has_discrepancy);
    }

    public function test_kepala_sekolah_report_can_filter_by_discrepancy(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepala_sekolah']);
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create();
        $mapel = MataPelajaran::factory()->create(['jenis' => 'normatif']);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);

        $today = now()->startOfDay();

        // Meeting 1: Matches (Guru hadir, Siswa hadir)
        $p1 = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => $today,
            'status' => 'selesai',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $p1->id,
            'guru_id' => $guru->id,
            'status' => 'hadir',
        ]);
        FotoBukti::create([
            'pertemuan_id' => $p1->id,
            'siswa_id' => $siswa->id,
            'foto_path' => 'foto-bukti/p1.webp',
            'status_guru_dilaporkan' => 'hadir',
        ]);

        // Meeting 2: Discrepant (Guru hadir, Siswa alpa)
        $p2 = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => $today->copy()->addDay(),
            'status' => 'selesai',
        ]);
        KehadiranGuru::create([
            'pertemuan_id' => $p2->id,
            'guru_id' => $guru->id,
            'status' => 'hadir',
        ]);
        FotoBukti::create([
            'pertemuan_id' => $p2->id,
            'siswa_id' => $siswa->id,
            'foto_path' => 'foto-bukti/p2.webp',
            'status_guru_dilaporkan' => 'alpa',
        ]);

        // Request with only_discrepancy=1
        $response = $this->actingAs($kepsek)->get(route('kepala-sekolah.report.guru', [
            'start_date' => $today->toDateString(),
            'end_date' => $today->copy()->addDays(5)->toDateString(),
            'only_discrepancy' => 1,
        ]));

        $response->assertOk();
        $response->assertViewHas('kehadiran', function ($kehadiran) use ($p2) {
            return $kehadiran->count() === 1 && $kehadiran->first()->pertemuan_id === $p2->id;
        });
        $response->assertViewHas('discrepancyCount', 1);
    }
}

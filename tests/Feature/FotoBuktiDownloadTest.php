<?php

namespace Tests\Feature;

use App\Models\FotoBukti;
use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoBuktiDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private User $otherGuru;

    private User $siswa;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    private Pertemuan $pertemuan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->guru = User::create([
            'name' => 'Guru Penguji',
            'email' => 'guru.penguji@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        GuruProfile::create(['user_id' => $this->guru->id, 'nip' => '198001012005011001']);

        $this->otherGuru = User::create([
            'name' => 'Guru Lain',
            'email' => 'guru.lain@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        GuruProfile::create(['user_id' => $this->otherGuru->id, 'nip' => '198501012005011002']);

        $this->kelas = Kelas::create([
            'nama' => '10-PPLG-1',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->siswa = User::create([
            'name' => 'Siswa Penguji',
            'email' => 'siswa.penguji@siswa.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswa->id,
            'kelas_id' => $this->kelas->id,
            'nis' => '20260001',
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Dasar Pemrograman',
            'kode' => 'DP',
            'jenis' => 'adaptif',
        ]);

        $this->jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ]);

        $this->pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => Carbon::now()->startOfDay(),
            'status' => 'berlangsung',
        ]);
    }

    public function test_guru_can_download_foto_masuk(): void
    {
        $fakeFile = UploadedFile::fake()->image('masuk.jpg');
        $storedPath = $fakeFile->store('foto-bukti', 'public');

        $fotoBukti = FotoBukti::create([
            'pertemuan_id' => $this->pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'foto_path' => $storedPath,
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('guru.foto-bukti.download', ['fotoBukti' => $fotoBukti->id, 'type' => 'masuk']));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('Foto-Presensi', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Masuk', $response->headers->get('content-disposition'));
    }

    public function test_guru_can_download_foto_checkout(): void
    {
        $fakeMasuk = UploadedFile::fake()->image('masuk.jpg')->store('foto-bukti', 'public');
        $fakeCheckout = UploadedFile::fake()->image('checkout.jpg')->store('foto-bukti', 'public');

        $fotoBukti = FotoBukti::create([
            'pertemuan_id' => $this->pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'foto_path' => $fakeMasuk,
            'foto_checkout_path' => $fakeCheckout,
            'checkout_at' => now(),
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('guru.foto-bukti.download', ['fotoBukti' => $fotoBukti->id, 'type' => 'checkout']));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('Checkout', $response->headers->get('content-disposition'));
    }

    public function test_guru_can_download_all_photos_as_zip(): void
    {
        $fakeMasuk = UploadedFile::fake()->image('masuk.jpg')->store('foto-bukti', 'public');
        $fakeCheckout = UploadedFile::fake()->image('checkout.jpg')->store('foto-bukti', 'public');

        FotoBukti::create([
            'pertemuan_id' => $this->pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'foto_path' => $fakeMasuk,
            'foto_checkout_path' => $fakeCheckout,
            'checkout_at' => now(),
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('guru.pertemuan.download-foto-all', $this->pertemuan->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString('.zip', $response->headers->get('content-disposition'));
    }

    public function test_other_guru_cannot_download_unauthorized_foto(): void
    {
        $fakeFile = UploadedFile::fake()->image('masuk.jpg');
        $storedPath = $fakeFile->store('foto-bukti', 'public');

        $fotoBukti = FotoBukti::create([
            'pertemuan_id' => $this->pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'foto_path' => $storedPath,
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $response = $this->actingAs($this->otherGuru)
            ->get(route('guru.foto-bukti.download', ['fotoBukti' => $fotoBukti->id, 'type' => 'masuk']));

        $response->assertForbidden();
    }

    public function test_download_missing_file_redirects_with_error(): void
    {
        $fotoBukti = FotoBukti::create([
            'pertemuan_id' => $this->pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'foto_path' => 'foto-bukti/non_existent_file.webp',
            'status_guru_dilaporkan' => 'hadir',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('guru.foto-bukti.download', ['fotoBukti' => $fotoBukti->id, 'type' => 'masuk']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}

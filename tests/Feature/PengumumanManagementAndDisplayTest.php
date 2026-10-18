<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Pengumuman;
use App\Models\PengumumanRead;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengumumanManagementAndDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    private User $siswa10;

    private User $siswa12;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin.pengumuman@test.com',
            'username' => 'admin_pengumuman',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Budi Santoso, S.Pd',
            'email' => 'guru.budi@test.com',
            'username' => 'guru_budi',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $kelas10 = Kelas::create([
            'nama' => 'X RPL 1',
            'tingkat' => '10',
            'tahun_ajaran' => '2025/2026',
        ]);

        $kelas12 = Kelas::create([
            'nama' => 'XII RPL 2',
            'tingkat' => '12',
            'tahun_ajaran' => '2025/2026',
        ]);

        $this->siswa10 = User::create([
            'name' => 'Siswa Kelas Sepuluh',
            'email' => 'siswa10@test.com',
            'username' => 'siswa10',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswa10->id,
            'nis' => '10001',
            'kelas_id' => $kelas10->id,
        ]);

        $this->siswa12 = User::create([
            'name' => 'Siswa Kelas Duabelas',
            'email' => 'siswa12@test.com',
            'username' => 'siswa12',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        SiswaProfile::create([
            'user_id' => $this->siswa12->id,
            'nis' => '12001',
            'kelas_id' => $kelas12->id,
        ]);
    }

    /**
     * Test 1: Admin dapat mengakses form dan menyimpan pengumuman baru beserta lampiran.
     */
    public function test_admin_can_create_pengumuman_with_attachment(): void
    {
        $file = UploadedFile::fake()->create('surat_edaran.pdf', 250, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.pengumuman.store'), [
            'judul' => 'Sosialisasi Asesmen Nasional 2026',
            'tipe' => 'penting',
            'target_roles' => ['guru', 'siswa'],
            'target_tingkat' => 'all',
            'isi' => 'Diharapkan seluruh civitas akademika membaca jadwal dan prosedur berikut.',
            'tanggal_mulai' => now()->format('Y-m-d H:i'),
            'is_pinned' => '1',
            'is_active' => '1',
            'file_lampiran' => $file,
        ]);

        $response->assertRedirect(route('admin.pengumuman.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pengumumans', [
            'judul' => 'Sosialisasi Asesmen Nasional 2026',
            'tipe' => 'penting',
            'is_pinned' => true,
            'is_active' => true,
            'nama_file' => 'surat_edaran.pdf',
        ]);

        $pengumuman = Pengumuman::where('judul', 'Sosialisasi Asesmen Nasional 2026')->first();
        $this->assertNotNull($pengumuman->file_lampiran);
        Storage::disk('public')->assertExists($pengumuman->file_lampiran);
    }

    /**
     * Test 2: Admin dapat mengedit dan menghapus pengumuman.
     */
    public function test_admin_can_update_and_delete_pengumuman(): void
    {
        $file = UploadedFile::fake()->create('edaran_lama.pdf', 100);
        $path = $file->store('pengumuman', 'public');

        $pengumuman = Pengumuman::create([
            'judul' => 'Pengumuman Awal',
            'tipe' => 'info',
            'target_roles' => ['all'],
            'target_tingkat' => 'all',
            'isi' => 'Konten awal.',
            'tanggal_mulai' => now()->subDay(),
            'is_pinned' => false,
            'is_active' => true,
            'file_lampiran' => $path,
            'nama_file' => 'edaran_lama.pdf',
            'created_by' => $this->admin->id,
        ]);

        // Update
        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.pengumuman.update', $pengumuman), [
            'judul' => 'Pengumuman Telah Diperbarui',
            'tipe' => 'urgent',
            'target_roles' => ['all'],
            'target_tingkat' => 'all',
            'isi' => 'Konten revisi mendesak.',
            'tanggal_mulai' => now()->format('Y-m-d H:i'),
            'is_pinned' => '1',
            'is_active' => '1',
        ]);

        $responseUpdate->assertRedirect(route('admin.pengumuman.index'));
        $this->assertDatabaseHas('pengumumans', [
            'id' => $pengumuman->id,
            'judul' => 'Pengumuman Telah Diperbarui',
            'tipe' => 'urgent',
            'is_pinned' => true,
        ]);

        // Delete
        $responseDelete = $this->actingAs($this->admin)->delete(route('admin.pengumuman.destroy', $pengumuman));
        $responseDelete->assertRedirect(route('admin.pengumuman.index'));

        $this->assertDatabaseMissing('pengumumans', ['id' => $pengumuman->id]);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test 3: Pengumuman tampil tepat sasaran sesuai role & tingkat siswa.
     */
    public function test_pengumuman_scoped_to_target_role_and_student_tingkat(): void
    {
        // Pengumuman khusus Guru
        $pGuru = Pengumuman::create([
            'judul' => 'Rapat Pleno Dewan Guru',
            'tipe' => 'penting',
            'target_roles' => ['guru'],
            'target_tingkat' => 'all',
            'isi' => 'Rapat pleno hari Rabu jam 13.00.',
            'tanggal_mulai' => now()->subHour(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Pengumuman khusus Siswa Kelas 12
        $pSiswa12 = Pengumuman::create([
            'judul' => 'Tryout Uji Kompetensi Kejuruan Kelas 12',
            'tipe' => 'urgent',
            'target_roles' => ['siswa'],
            'target_tingkat' => '12',
            'isi' => 'Wajib hadir di lab komputer tepat pukul 07.30.',
            'tanggal_mulai' => now()->subHour(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Pengumuman Umum (Semua)
        $pSemua = Pengumuman::create([
            'judul' => 'Upacara Hari Pahlawan',
            'tipe' => 'info',
            'target_roles' => ['all'],
            'target_tingkat' => 'all',
            'isi' => 'Seluruh siswa dan guru wajib memakai seragam lengkap.',
            'tanggal_mulai' => now()->subHour(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // 1. Guru Dashboard: Melihat pGuru dan pSemua, TIDAK melihat pSiswa12
        $resGuru = $this->actingAs($this->guru)->get(route('guru.dashboard'));
        $resGuru->assertOk();
        $resGuru->assertSee('Rapat Pleno Dewan Guru');
        $resGuru->assertSee('Upacara Hari Pahlawan');
        $resGuru->assertDontSee('Tryout Uji Kompetensi Kejuruan Kelas 12');

        // 2. Siswa Kelas 10 Dashboard: Melihat pSemua, TIDAK melihat pGuru maupun pSiswa12
        $resSiswa10 = $this->actingAs($this->siswa10)->get(route('siswa.dashboard'));
        $resSiswa10->assertOk();
        $resSiswa10->assertSee('Upacara Hari Pahlawan');
        $resSiswa10->assertDontSee('Rapat Pleno Dewan Guru');
        $resSiswa10->assertDontSee('Tryout Uji Kompetensi Kejuruan Kelas 12');

        // 3. Siswa Kelas 12 Dashboard: Melihat pSiswa12 dan pSemua, TIDAK melihat pGuru
        $resSiswa12 = $this->actingAs($this->siswa12)->get(route('siswa.dashboard'));
        $resSiswa12->assertOk();
        $resSiswa12->assertSee('Tryout Uji Kompetensi Kejuruan Kelas 12');
        $resSiswa12->assertSee('Upacara Hari Pahlawan');
        $resSiswa12->assertDontSee('Rapat Pleno Dewan Guru');
    }

    /**
     * Test 4: Pengguna dapat menandai pengumuman sudah dibaca dan langsung hilang dari unread banner.
     */
    public function test_user_can_mark_pengumuman_as_read(): void
    {
        $pengumuman = Pengumuman::create([
            'judul' => 'Pengumuman Penting Pekan Ini',
            'tipe' => 'info',
            'target_roles' => ['guru'],
            'target_tingkat' => 'all',
            'isi' => 'Instruksi pengisian modul ajar.',
            'tanggal_mulai' => now()->subMinutes(10),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Sebelum dibaca, tampil di dashboard guru
        $resBefore = $this->actingAs($this->guru)->get(route('guru.dashboard'));
        $resBefore->assertSee('Pengumuman Penting Pekan Ini');

        // Tandai sudah dibaca via AJAX POST
        $resMark = $this->actingAs($this->guru)->postJson(route('pengumuman.mark-read', $pengumuman));
        $resMark->assertOk();
        $resMark->assertJson(['success' => true]);

        $this->assertDatabaseHas('pengumuman_reads', [
            'pengumuman_id' => $pengumuman->id,
            'user_id' => $this->guru->id,
        ]);

        // Setelah dibaca, pengumuman tidak lagi tampil di banner unread
        $resAfter = $this->actingAs($this->guru)->get(route('guru.dashboard'));
        $resAfter->assertDontSee('Pengumuman Penting Pekan Ini');
    }

    /**
     * Test 5: Admin dapat melihat daftar log pengguna yang telah membaca pengumuman.
     */
    public function test_admin_can_view_readers_list(): void
    {
        $pengumuman = Pengumuman::create([
            'judul' => 'Surat Edaran Libur Nasional',
            'tipe' => 'info',
            'target_roles' => ['all'],
            'target_tingkat' => 'all',
            'isi' => 'KBM ditiadakan selama libur bersama.',
            'tanggal_mulai' => now()->subDay(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Tandai terbaca oleh Guru Budi
        PengumumanRead::create([
            'pengumuman_id' => $pengumuman->id,
            'user_id' => $this->guru->id,
            'read_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.pengumuman.readers', $pengumuman));
        $res->assertOk();
        $res->assertSee('Surat Edaran Libur Nasional');
        $res->assertSee('Budi Santoso, S.Pd');
        $res->assertSee('guru');
    }

    /**
     * Test 6: Tata Usaha (TU) dapat mengakses dan membuat pengumuman sekolah.
     */
    public function test_tu_can_create_pengumuman(): void
    {
        $tu = User::create([
            'name' => 'Staf Tata Usaha',
            'email' => 'tu@sekolah.test',
            'username' => 'staf_tu',
            'password' => Hash::make('password'),
            'role' => 'tu',
            'is_active' => true,
        ]);

        $response = $this->actingAs($tu)->post(route('admin.pengumuman.store'), [
            'judul' => 'Edaran Administrasi Berkas Siswa',
            'tipe' => 'info',
            'target_roles' => ['all'],
            'target_tingkat' => 'all',
            'isi' => 'Diharapkan melengkapi fotokopi kartu keluarga ke loket TU.',
            'tanggal_mulai' => now()->format('Y-m-d H:i'),
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.pengumuman.index'));
        $this->assertDatabaseHas('pengumumans', [
            'judul' => 'Edaran Administrasi Berkas Siswa',
            'created_by' => $tu->id,
        ]);
    }

    /**
     * Test 7: Sistem login secara default mengaktifkan remember token agar pengguna tidak keluar sendiri.
     */
    public function test_login_defaults_to_remember_me_persistent(): void
    {
        $response = $this->post(route('login.post'), [
            'identifier' => 'guru.budi@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->guru);

        $this->guru->refresh();
        $this->assertNotNull($this->guru->remember_token);
    }
}

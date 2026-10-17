<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Setting;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerformanceOptimizationRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Menguji Cache Invalidation pada model Setting.
     * Memastikan cache terhapus otomatis saat nilai pengaturan diperbarui,
     * sehingga aplikasi langsung membaca nilai terbaru.
     */
    public function test_setting_cache_invalidation_works_correctly(): void
    {
        // Bersihkan cache awal
        Cache::flush();

        // Set nilai awal di database
        Setting::set('tahun_ajaran_aktif', '2025/2026');

        // Panggil pertama kali untuk memicu penyimpanan cache (rememberForever)
        $firstCall = Setting::getTahunAjaranAktif();
        $this->assertEquals('2025/2026', $firstCall);

        // Ubah nilai menggunakan method set() yang seharusnya mentrigger event/clear cache
        Setting::set('tahun_ajaran_aktif', '2026/2027');

        // Panggil kembali; pastikan mengembalikan nilai baru, bukan data basi dari cache lama
        $secondCall = Setting::getTahunAjaranAktif();
        $this->assertEquals('2026/2027', $secondCall);

        // Uji juga untuk alias school_year
        Setting::set('school_year', '2027/2028');
        $this->assertEquals('2027/2028', Setting::getTahunAjaranAktif());

        // Uji cache invalidation untuk semester
        Setting::set('semester', '1');
        $this->assertEquals('ganjil', Setting::getSemesterAktif());

        Setting::set('semester', '2');
        $this->assertEquals('genap', Setting::getSemesterAktif());
    }

    /**
     * Test 2: Menguji render Blade ActivityLog dengan simplePaginate.
     * Memastikan halaman index log aktivitas dapat diakses dengan sukses (Status 200)
     * tanpa error akibat pemanggilan method .total() yang tidak ada pada Paginator sederhana.
     */
    public function test_activity_log_index_renders_with_simple_paginate(): void
    {
        $admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Buat dummy activity logs lebih dari 25 data untuk memicu pagination
        for ($i = 1; $i <= 30; $i++) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'role' => 'super_admin',
                'action' => 'UPDATE',
                'module' => 'Settings',
                'description' => "Test audit trail #{$i}",
                'ip_address' => '127.0.0.1',
            ]);
        }

        // Akses route index log aktivitas (Halaman 1)
        $response = $this->actingAs($admin)->get(route('super-admin.activity-logs.index'));
        $response->assertStatus(200);
        $response->assertViewHas('logs');
        $response->assertSee('Test audit trail #30');

        // Akses Halaman 2
        $responsePage2 = $this->actingAs($admin)->get(route('super-admin.activity-logs.index', ['page' => 2]));
        $responsePage2->assertStatus(200);
        $responsePage2->assertViewHas('logs');
    }

    /**
     * Test 3: Menguji struktur direktori penyimpanan foto berbasis tanggal (Y/m/d) di CaptureController.
     * Memastikan file foto berhasil disimpan ke sub-folder tanggal hari ini.
     */
    public function test_capture_controller_saves_photo_with_date_subfolder_structure(): void
    {
        Storage::fake('public');

        $kelas = Kelas::create([
            'nama' => '10-PPLG-1',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $guru = User::create([
            'name' => 'Guru Pengajar',
            'email' => 'guru@test.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);

        $mapel = MataPelajaran::create([
            'nama' => 'Pemrograman Berorientasi Objek',
            'kode' => 'PBO',
            'jenis' => 'produktif',
        ]);

        $today = Carbon::today();

        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => $today->dayOfWeekIso,
            'jam_mulai' => '00:00:00',
            'jam_selesai' => '23:59:00',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $siswa = User::create([
            'name' => 'Siswa Test',
            'email' => 'siswa@test.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        SiswaProfile::create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'nis' => '123456',
        ]);

        $file = UploadedFile::fake()->image('bukti_kehadiran.jpg', 800, 600);

        $response = $this->actingAs($siswa)->post(
            route('siswa.capture.store', ['jadwal' => $jadwal->id, 'tanggal' => $today->toDateString()]),
            [
                'foto' => $file,
                'status_guru_dilaporkan' => 'hadir',
            ]
        );

        $response->assertRedirect();

        $expectedDateFolder = now()->format('Y/m/d');
        $files = Storage::disk('public')->allFiles();

        $this->assertNotEmpty($files, 'File foto bukti gagal disimpan ke storage.');

        $foundWithDateFolder = collect($files)->contains(function ($path) use ($expectedDateFolder) {
            return str_contains($path, $expectedDateFolder);
        });

        $this->assertTrue(
            $foundWithDateFolder,
            "Struktur folder penyimpanan foto tidak sesuai dengan format tanggal {$expectedDateFolder}. Daftar file: ".json_encode($files)
        );
    }

    /**
     * Test 4: Menguji cache pada Public Dashboard
     */
    public function test_public_dashboard_global_stats_cached_properly(): void
    {
        Cache::flush();
        $todayStr = Carbon::today()->toDateString();
        $cacheKey = "public_dashboard_stats_{$todayStr}";

        $this->assertFalse(Cache::has($cacheKey));

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Pastikan cache tersimpan setelah endpoint dipanggil
        $this->assertTrue(Cache::has($cacheKey));

        $stats = Cache::get($cacheKey);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('guru_hadir_hari_ini', $stats);
        $this->assertArrayHasKey('siswa_hadir_hari_ini', $stats);
        $this->assertArrayHasKey('total_guru', $stats);
        $this->assertArrayHasKey('total_siswa', $stats);
    }
}

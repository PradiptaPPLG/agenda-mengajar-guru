<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HariLiburAutoSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Hari Libur',
            'email' => 'admin.libur@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Admin dapat menambahkan rentang hari libur (multi-hari).
     */
    public function test_admin_can_store_holiday_range(): void
    {
        $startDate = Carbon::today()->addDays(10)->toDateString();
        $endDate = Carbon::today()->addDays(14)->toDateString(); // 5 hari total

        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.range'), [
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
            'keterangan' => 'Libur Akhir Semester Ganjil',
            'jenis' => 'khusus',
        ]);

        $response->assertRedirect(route('admin.hari-libur.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(5, HariLibur::where('keterangan', 'Libur Akhir Semester Ganjil')->count());
        $this->assertTrue(HariLibur::isLibur($startDate));
        $this->assertTrue(HariLibur::isLibur($endDate));
    }

    /**
     * Test 2: Admin dapat melakukan 1-klik sinkronisasi libur nasional dari API.
     */
    public function test_admin_can_sync_national_holidays_from_api(): void
    {
        // Mock Public API Response
        Http::fake([
            'https://dayoffapi.vercel.app/api?year=2026' => Http::response([
                [
                    'tanggal' => '2026-01-01',
                    'keterangan' => 'Tahun Baru 2026 Masehi',
                    'is_cuti' => false,
                ],
                [
                    'tanggal' => '2026-03-18',
                    'keterangan' => 'Cuti Bersama Nyepi',
                    'is_cuti' => true,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.hari-libur.sync'), [
            'tahun' => 2026,
        ]);

        $response->assertRedirect(route('admin.hari-libur.index', ['tahun' => 2026]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-01-01',
            'keterangan' => 'Tahun Baru 2026 Masehi',
            'jenis' => 'nasional',
        ]);

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-03-18',
            'keterangan' => 'Cuti Bersama Nyepi',
            'jenis' => 'cuti_bersama',
        ]);
    }

    /**
     * Test 3: Caching pada HariLibur dan auto-invalidation saat data berubah.
     */
    public function test_hari_libur_caching_and_invalidation(): void
    {
        Cache::flush();
        $date = '2026-05-01';

        // 1. Awalnya bukan hari libur
        $this->assertFalse(HariLibur::isLibur($date));

        // 2. Tambah hari libur
        HariLibur::create([
            'tanggal' => $date,
            'keterangan' => 'Hari Buruh Internasional',
            'jenis' => 'nasional',
        ]);

        // 3. Cache harus ter-invalidate dan sekarang bernilai true
        $this->assertTrue(HariLibur::isLibur($date));
        $this->assertNotNull(HariLibur::getLibur($date));
    }

    /**
     * Test 4: Artisan command libur:sync berjalan sukses.
     */
    public function test_artisan_libur_sync_command(): void
    {
        Http::fake([
            'https://dayoffapi.vercel.app/api?year=2027' => Http::response([
                [
                    'tanggal' => '2027-08-17',
                    'keterangan' => 'HUT Kemerdekaan RI ke-82',
                    'is_cuti' => false,
                ],
            ], 200),
        ]);

        $exitCode = Artisan::call('libur:sync', ['tahun' => 2027]);
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2027-08-17',
            'keterangan' => 'HUT Kemerdekaan RI ke-82',
        ]);
    }
}

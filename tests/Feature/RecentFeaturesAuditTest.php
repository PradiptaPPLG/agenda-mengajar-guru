<?php

namespace Tests\Feature;

use App\Http\Controllers\Guru\WaliKelasController;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecentFeaturesAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_dashboard_loads_successfully_with_unique_stats(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertViewHasAll(['stats', 'guruPie', 'siswaPie', 'monitoringCards']);
    }

    public function test_guru_dashboard_loads_without_html_breakage(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Rekap Kehadiran Kelas');
    }

    public function test_guru_wali_kelas_harian_and_bulanan_tabs_render_properly(): void
    {
        $wali = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create(['wali_kelas_id' => $wali->id]);

        $siswa = User::factory()->create(['role' => 'siswa']);
        SiswaProfile::factory()->create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);

        // Tab harian
        $resHarian = $this->actingAs($wali)->get(route('guru.wali-kelas.index', ['tab' => 'harian']));
        $resHarian->assertStatus(200);

        // Tab bulanan
        $resBulanan = $this->actingAs($wali)->get(route('guru.wali-kelas.index', [
            'tab' => 'bulanan',
            'kelas_id' => $kelas->id,
            'bulan' => now()->format('Y-m'),
        ]));
        $resBulanan->assertStatus(200);
        $resBulanan->assertViewHas('rekapBulanan');
    }

    public function test_guru_wali_kelas_pdf_and_excel_exports_succeed(): void
    {
        $wali = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create(['wali_kelas_id' => $wali->id]);

        $siswa = User::factory()->create(['role' => 'siswa']);
        SiswaProfile::factory()->create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);

        $resPdf = $this->actingAs($wali)->get(route('guru.wali-kelas.export-pdf', [
            'kelas_id' => $kelas->id,
            'bulan' => now()->format('Y-m'),
        ]));
        $resPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdf->headers->get('Content-Type'));

        $resExcel = $this->actingAs($wali)->get(route('guru.wali-kelas.export-excel', [
            'kelas_id' => $kelas->id,
            'bulan' => now()->format('Y-m'),
        ]));
        $resExcel->assertStatus(200);
    }

    public function test_guru_rekap_index_pdf_and_excel_exports(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create();
        $mapel = MataPelajaran::factory()->create();

        $jadwal = JadwalPelajaran::factory()->create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => (int) now()->format('N'),
        ]);

        $resIndex = $this->actingAs($guru)->get(route('guru.rekap.index', [
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
        ]));
        $resIndex->assertStatus(200);
        $resIndex->assertViewHas('rekapData');

        $resPdf = $this->actingAs($guru)->get(route('guru.rekap.export-pdf', [
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'periode_type' => 'bulanan',
            'bulan' => now()->format('Y-m'),
        ]));
        $resPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdf->headers->get('Content-Type'));

        $resExcel = $this->actingAs($guru)->get(route('guru.rekap.export-excel', [
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'periode_type' => 'bulanan',
            'bulan' => now()->format('Y-m'),
        ]));
        $resExcel->assertStatus(200);
    }

    public function test_siswa_dashboard_and_capture_view_with_lesson_material(): void
    {
        $kelas = Kelas::factory()->create();
        $siswa = User::factory()->create(['role' => 'siswa']);
        SiswaProfile::factory()->create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);

        $guru = User::factory()->create(['role' => 'guru']);
        $mapel = MataPelajaran::factory()->create();

        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => (int) now()->format('N'),
            'jam_mulai' => '00:00:00',
            'jam_selesai' => '23:59:00',
        ]);

        Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => now()->format('Y-m-d 00:00:00'),
            'materi_ajar' => 'Pengenalan Algoritma Dasar',
            'penugasan' => 'Mengerjakan studi kasus bab 1',
            'status' => 'berlangsung',
        ]);

        $resDash = $this->actingAs($siswa)->get(route('siswa.dashboard'));
        $resDash->assertStatus(200);
        $resDash->assertSee('Pengenalan Algoritma Dasar');
        $resDash->assertSee('Mengerjakan studi kasus bab 1');

        $resCapture = $this->actingAs($siswa)->get(route('siswa.capture.show', [
            'jadwal' => $jadwal->id,
            'tanggal' => now()->toDateString(),
        ]));
        $resCapture->assertStatus(200);
        $resCapture->assertSee('Pengenalan Algoritma Dasar');
    }

    public function test_student_terlambat_status_and_rekap_percentage_calculation(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $mapel = MataPelajaran::factory()->create();

        $siswa = User::factory()->create(['role' => 'siswa']);
        SiswaProfile::factory()->create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);

        $jadwal = JadwalPelajaran::factory()->create([
            'kelas_id' => $kelas->id,
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari' => (int) now()->format('N'),
        ]);

        $p1 = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => now()->format('Y-m-d 07:00:00'),
            'status' => 'selesai',
        ]);
        $p2 = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => now()->subDay()->format('Y-m-d 07:00:00'),
            'status' => 'selesai',
        ]);
        $p3 = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => now()->subDays(2)->format('Y-m-d 07:00:00'),
            'status' => 'selesai',
        ]);
        $p4 = Pertemuan::create([
            'jadwal_id' => $jadwal->id,
            'tanggal' => now()->subDays(3)->format('Y-m-d 07:00:00'),
            'status' => 'selesai',
        ]);

        // P1: Hadir, P2: Terlambat, P3: Dispensasi, P4: Sakit
        // Kehadiran sah = Hadir (1) + Terlambat (1) + Dispensasi (1) = 3 dari 4 pertemuan => 75%
        $k1 = KehadiranSiswa::create(['pertemuan_id' => $p1->id, 'siswa_id' => $siswa->id, 'status' => 'hadir']);
        $k2 = KehadiranSiswa::create(['pertemuan_id' => $p2->id, 'siswa_id' => $siswa->id, 'status' => 'terlambat']);
        $k3 = KehadiranSiswa::create(['pertemuan_id' => $p3->id, 'siswa_id' => $siswa->id, 'status' => 'dispensasi']);
        $k4 = KehadiranSiswa::create(['pertemuan_id' => $p4->id, 'siswa_id' => $siswa->id, 'status' => 'sakit']);

        // Test update via GuruPertemuanController save-all
        $resUpdate = $this->actingAs($guru)->patch(route('guru.pertemuan.save-all', $p1->id), [
            'materi_ajar' => 'Materi Tes',
            'siswa' => [
                $siswa->id => [
                    'status' => 'terlambat',
                ],
            ],
        ]);
        $resUpdate->assertSessionHasNoErrors();
        $this->assertEquals('terlambat', $k1->fresh()->status);

        // Put back to hadir for percentage verification
        $k1->refresh();
        $k1->update(['status' => 'hadir']);

        // Check WaliKelasController data
        $controller = new WaliKelasController;
        $rekapWali = $controller->getRekapData($kelas->id, 'bulanan', now()->format('Y-m'));
        $this->assertEquals(1, $rekapWali['rekapSiswa'][0]['hadir']);
        $this->assertEquals(1, $rekapWali['rekapSiswa'][0]['terlambat']);
        $this->assertEquals(1, $rekapWali['rekapSiswa'][0]['dispensasi']);
        $this->assertEquals(1, $rekapWali['rekapSiswa'][0]['sakit']);
        $this->assertEquals(4, $rekapWali['rekapSiswa'][0]['total']);
        $this->assertEquals(75.0, $rekapWali['rekapSiswa'][0]['persentase']);
    }

    public function test_wali_kelas_semester_filter_and_export(): void
    {
        $wali = User::factory()->create(['role' => 'guru']);
        $kelas = Kelas::factory()->create(['wali_kelas_id' => $wali->id]);

        $siswa = User::factory()->create(['role' => 'siswa']);
        SiswaProfile::factory()->create([
            'user_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);

        // Index with semester filter
        $res = $this->actingAs($wali)->get(route('guru.wali-kelas.index', [
            'tab' => 'bulanan',
            'kelas_id' => $kelas->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $res->assertStatus(200);
        $res->assertViewHas('rekapBulanan');

        // Export PDF with semester filter
        $resPdf = $this->actingAs($wali)->get(route('guru.wali-kelas.export-pdf', [
            'kelas_id' => $kelas->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $resPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdf->headers->get('Content-Type'));

        // Export Excel with semester filter
        $resExcel = $this->actingAs($wali)->get(route('guru.wali-kelas.export-excel', [
            'kelas_id' => $kelas->id,
            'periode_type' => 'semester',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
        ]));
        $resExcel->assertStatus(200);
    }
}

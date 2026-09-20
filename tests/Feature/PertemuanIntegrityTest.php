<?php

namespace Tests\Feature;

use App\Models\GuruProfile;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\SiswaProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PertemuanIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private User $siswa;

    private Kelas $kelas;

    private MataPelajaran $mapel;

    private JadwalPelajaran $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::create([
            'name' => 'Guru Penguji',
            'email' => 'guru.penguji@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'is_active' => true,
        ]);
        GuruProfile::create(['user_id' => $this->guru->id, 'nip' => '198001012005011001']);

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
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);
    }

    public function test_guru_can_save_all_pertemuan_data_atomically(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $today,
            'status' => 'menunggu',
        ]);

        $response = $this->actingAs($this->guru)->patch(route('guru.pertemuan.save-all', $pertemuan), [
            'materi_ajar' => 'Pengenalan Algoritma & Variabel',
            'penugasan' => 'Kerjakan Latihan 1.1 Halaman 15',
            'status' => 'hadir',
            'siswa' => [
                $this->siswa->id => [
                    'status' => 'hadir',
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pertemuans', [
            'id' => $pertemuan->id,
            'materi_ajar' => 'Pengenalan Algoritma & Variabel',
            'status' => 'berlangsung',
        ]);
        $this->assertDatabaseHas('kehadiran_gurus', [
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $this->guru->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('kehadiran_siswas', [
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'status' => 'hadir',
        ]);
    }

    public function test_prevents_creating_ghost_meetings_on_future_dates_by_guru(): void
    {
        $futureDate = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->guru)->get(
            route('guru.pertemuan.show', ['jadwal' => $this->jadwal->id, 'tanggal' => $futureDate])
        );

        $response->assertRedirect(route('guru.dashboard'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('pertemuans', [
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $futureDate.' 00:00:00',
        ]);
    }

    public function test_prevents_creating_ghost_meetings_on_future_dates_by_siswa(): void
    {
        $futureDate = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->siswa)->get(
            route('siswa.capture.show', ['jadwal' => $this->jadwal->id, 'tanggal' => $futureDate])
        );

        $response->assertRedirect(route('siswa.dashboard'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('pertemuans', [
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $futureDate.' 00:00:00',
        ]);
    }

    public function test_soft_deletes_preserve_historical_attendance_data(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $pertemuan = Pertemuan::create([
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $today,
            'status' => 'selesai',
        ]);

        KehadiranGuru::create([
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $this->guru->id,
            'status' => 'hadir',
        ]);

        KehadiranSiswa::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'status' => 'hadir',
        ]);

        // Soft delete the guru and jadwal
        $this->guru->delete();
        $this->jadwal->delete();

        // Ensure soft delete columns are populated
        $this->assertSoftDeleted('users', ['id' => $this->guru->id]);
        $this->assertSoftDeleted('jadwal_pelajarans', ['id' => $this->jadwal->id]);

        // Historical pertemuan and attendances MUST remain intact
        $this->assertDatabaseHas('pertemuans', ['id' => $pertemuan->id]);
        $this->assertDatabaseHas('kehadiran_gurus', [
            'pertemuan_id' => $pertemuan->id,
            'guru_id' => $this->guru->id,
        ]);
        $this->assertDatabaseHas('kehadiran_siswas', [
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswa->id,
        ]);
    }
}

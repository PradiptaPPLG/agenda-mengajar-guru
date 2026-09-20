<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JadwalConflictValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru1;

    private User $guru2;

    private Kelas $kelasA;

    private Kelas $kelasB;

    private MataPelajaran $mapel1;

    private MataPelajaran $mapel2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.test@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->guru1 = User::create([
            'name' => 'Budi Santoso, S.Pd',
            'email' => 'budi@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $this->guru2 = User::create([
            'name' => 'Dewi Sartika, M.Pd',
            'email' => 'dewi@sekolah.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $this->kelasA = Kelas::create([
            'nama' => 'X-RPL-1',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->kelasB = Kelas::create([
            'nama' => 'X-RPL-2',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->mapel1 = MataPelajaran::create([
            'nama' => 'Matematika',
            'kode' => 'MTK',
            'jenis' => 'normatif',
        ]);

        $this->mapel2 = MataPelajaran::create([
            'nama' => 'Pemrograman Web',
            'kode' => 'PWB',
            'jenis' => 'adaptif',
        ]);
    }

    public function test_prevents_teacher_schedule_conflict_on_overlapping_time(): void
    {
        // Existing schedule for Guru 1 in Kelas A on Senin (1) 07:00 - 08:30
        JadwalPelajaran::create([
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        // Attempt to assign Guru 1 to Kelas B on Senin (1) 08:00 - 09:30 (overlaps by 30 mins)
        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'kelas_id' => $this->kelasB->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel2->id,
            'hari' => 1,
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:30',
        ]);

        $response->assertSessionHasErrors(['guru_id']);
        $this->assertDatabaseCount('jadwal_pelajarans', 1);
    }

    public function test_prevents_class_schedule_conflict_on_overlapping_time(): void
    {
        // Existing schedule for Kelas A on Senin (1) 07:00 - 08:30
        JadwalPelajaran::create([
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        // Attempt to assign Guru 2 to the same Kelas A on Senin (1) 07:30 - 09:00 (overlaps)
        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guru2->id,
            'mata_pelajaran_id' => $this->mapel2->id,
            'hari' => 1,
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        $response->assertSessionHasErrors(['kelas_id']);
        $this->assertDatabaseCount('jadwal_pelajarans', 1);
    }

    public function test_allows_back_to_back_schedule_without_overlap(): void
    {
        // Schedule 1: 07:00 - 08:30
        JadwalPelajaran::create([
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        // Schedule 2: exactly starts at 08:30 - 10:00 (Guru 1 moves to Kelas B)
        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'kelas_id' => $this->kelasB->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel2->id,
            'hari' => 1,
            'jam_mulai' => '08:30',
            'jam_selesai' => '10:00',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.jadwal.index'));
        $this->assertDatabaseCount('jadwal_pelajarans', 2);
    }

    public function test_updating_schedule_does_not_conflict_with_itself(): void
    {
        $jadwal = JadwalPelajaran::create([
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.jadwal.update', $jadwal), [
            'kelas_id' => $this->kelasA->id,
            'guru_id' => $this->guru1->id,
            'mata_pelajaran_id' => $this->mapel1->id,
            'hari' => 1,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.jadwal.index'));
    }
}

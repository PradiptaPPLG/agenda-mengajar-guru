<?php

namespace App\Services;

use App\Models\JadwalPelajaran;
use App\Models\KalenderBlokMinggu;
use App\Models\Kelas;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JadwalBlokResolverService
{
    /**
     * Resolve jadwal pelajaran yang aktif untuk sebuah kelas pada tanggal tertentu.
     *
     * Logika:
     * - Jika kelas TIDAK sistem blok → kembalikan semua jadwal dengan kelompok_blok = 'reguler' (normal).
     * - Jika kelas IS sistem blok:
     *   1. Cari minggu aktif di kalender blok berdasarkan tanggal.
     *   2. Hitung kelompok aktif kelas ini minggu ini (A atau B).
     *   3. Ambil jadwal dengan filter: kelompok_blok IN ('reguler', $kelompokAktif).
     *   4. Untuk model split_harian: ambil semua (A + B + reguler) karena berjalan paralel.
     *
     * @param  Carbon|null  $tanggal  Tanggal yang dituju, default hari ini
     * @param  string|null  $hari  Filter hari (misal: 'Senin'), null = semua hari
     * @return Collection<JadwalPelajaran>
     */
    public function resolveJadwal(Kelas $kelas, ?Carbon $tanggal = null, ?string $hari = null): Collection
    {
        $tanggal ??= Carbon::today();

        $query = JadwalPelajaran::with(['mataPelajaran', 'guru.guruProfile'])
            ->where('kelas_id', $kelas->id);

        if ($hari) {
            $query->where('hari', $hari);
        }

        if (! $kelas->is_sistem_blok) {
            // Kelas reguler: ambil semua jadwal biasa
            return $query->get();
        }

        // Kelas sistem blok: tentukan kelompok aktif minggu ini
        $mingguAktif = KalenderBlokMinggu::aktif($tanggal)->first();

        if (! $mingguAktif) {
            // Tidak ada kalender yang dikonfigurasi, fallback ke semua jadwal
            return $query->get();
        }

        if ($kelas->model_rotasi === 'split_harian') {
            // Model split: semua kelompok berjalan paralel dalam sehari atau dibagi per siswa
            // Tidak perlu filter kelompok untuk sisi jadwal, karena guru mengajar bersamaan, kembalikan semua
            return $query->get();
        }

        // Model rotasi_minggu biasa: filter berdasarkan kelompok aktif
        $kelompokAktif = $mingguAktif->kelompokAktifUntukKelas($kelas);

        return $query->where(function (Builder $q) use ($kelompokAktif) {
            $q->where('kelompok_blok', 'reguler')
                ->orWhere('kelompok_blok', $kelompokAktif);
        })->get();
    }

    /**
     * Resolve jadwal untuk banyak kelas sekaligus (dipakai di dashboard piket / kepala sekolah).
     *
     * @param  \Illuminate\Support\Collection<Kelas>  $kelasList
     * @return Collection<JadwalPelajaran>
     */
    public function resolveJadwalBanyakKelas(
        \Illuminate\Support\Collection $kelasList,
        ?Carbon $tanggal = null,
        ?string $hari = null
    ): Collection {
        $tanggal ??= Carbon::today();
        $allJadwal = new Collection;

        foreach ($kelasList as $kelas) {
            $jadwal = $this->resolveJadwal($kelas, $tanggal, $hari);
            $allJadwal = $allJadwal->merge($jadwal);
        }

        return $allJadwal;
    }

    /**
     * Dapatkan info kelompok blok aktif untuk sebuah kelas hari ini.
     * Berguna untuk menampilkan badge/indikator di dashboard.
     *
     * @return array{kelompok: string|null, label: string, is_aktif_minggu_ini: bool}
     */
    public function infoBlokKelas(Kelas $kelas, ?Carbon $tanggal = null): array
    {
        $tanggal ??= Carbon::today();

        if (! $kelas->is_sistem_blok) {
            return [
                'kelompok' => null,
                'label' => 'Reguler',
                'is_aktif_minggu_ini' => true,
            ];
        }

        $mingguAktif = KalenderBlokMinggu::aktif($tanggal)->first();

        if (! $mingguAktif) {
            return [
                'kelompok' => null,
                'label' => 'Kalender belum dikonfigurasi',
                'is_aktif_minggu_ini' => false,
            ];
        }

        $kelompok = $mingguAktif->kelompokAktifUntukKelas($kelas);

        $labelMap = [
            'kelompok_a' => '📘 Kelompok A (Umum)',
            'kelompok_b' => '🔧 Kelompok B (Produktif)',
            'split' => '🔄 Split (A & B Paralel)',
        ];

        return [
            'kelompok' => $kelompok,
            'label' => $labelMap[$kelompok] ?? 'Reguler',
            'nomor_minggu' => $mingguAktif->nomor_minggu,
            'is_aktif_minggu_ini' => true,
        ];
    }
}

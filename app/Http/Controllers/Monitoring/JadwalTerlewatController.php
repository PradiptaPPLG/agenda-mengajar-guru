<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\User;
use App\Services\JadwalBlokResolverService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class JadwalTerlewatController extends Controller
{
    /**
     * Tampilkan halaman monitoring jadwal KBM yang terlewat / belum diisi presensinya.
     */
    public function index(Request $request): View
    {
        $data = $this->collectJadwalTerlewatData($request);

        // Pagination untuk tabel data terlewat (25 per halaman)
        $page = (int) $request->input('page', 1);
        $perPage = 25;
        $items = $data['missedItems'];
        $paginatedItems = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('monitoring.jadwal-terlewat', array_merge($data, [
            'paginatedItems' => $paginatedItems,
        ]));
    }

    /**
     * Cetak dokumen PDF rekap jadwal terlewat.
     */
    public function exportPdf(Request $request): Response
    {
        $data = $this->collectJadwalTerlewatData($request);

        $schoolName = Setting::get('school_name', 'SMKN 1 Ciamis');
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $missedItems = $data['missedItems'];
        $topGuruBelumIsi = $data['topGuruBelumIsi'];
        $totalTerlewat = $data['totalTerlewat'];

        $pdf = Pdf::loadView('monitoring.pdf.jadwal-terlewat', compact(
            'schoolName',
            'startDate',
            'endDate',
            'missedItems',
            'topGuruBelumIsi',
            'totalTerlewat'
        ))->setPaper('a4', 'landscape');

        $filename = 'laporan-jadwal-kbm-terlewat-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Kumpulkan dan olah data jadwal terlewat secara akurat & performan tinggi.
     *
     * @return array<string, mixed>
     */
    private function collectJadwalTerlewatData(Request $request): array
    {
        $user = auth()->user();
        if ($user && $user->role === 'guru') {
            $isAuthorized = $user->isGuruBk() || $user->isPiket() || $user->hasAnyRole(['Guru BK', 'Petugas Piket', 'Kesiswaan', 'Wali Kelas']);
            if (! $isAuthorized) {
                abort(403, 'Akses terbatas untuk Kepala Sekolah, Admin, Piket, dan Kesiswaan/BK.');
            }
        }

        // 1. Parsing Periode Tanggal
        $filterMode = $request->input('filter_mode', '7_hari'); // '7_hari', 'hari_ini', 'custom'

        if ($filterMode === 'hari_ini') {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
        } elseif ($filterMode === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            try {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                // Batas keamanan maksimal rentang 31 hari
                if ($startDate->diffInDays($endDate) > 31) {
                    $endDate = (clone $startDate)->addDays(31)->endOfDay();
                }
            } catch (\Throwable) {
                $startDate = Carbon::today()->subDays(6)->startOfDay();
                $endDate = Carbon::today()->endOfDay();
            }
        } else {
            // Default 7 hari ke belakang
            $startDate = Carbon::today()->subDays(6)->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        }

        // 2. Filter Tingkat, Jurusan, Kelas, Guru
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedJurusan = $request->input('jurusan', 'all');
        $selectedKelasId = $request->input('kelas_id');
        $selectedGuruId = $request->input('guru_id');

        $daftarJurusan = [
            'PPLG' => 'PPLG (Pengembangan Perangkat Lunak & Gim)',
            'AKL' => 'AKL (Akuntansi & Keuangan Lembaga)',
            'MPLB' => 'MPLB (Manajemen Perkantoran & Layanan Bisnis)',
            'PM' => 'PM (Pemasaran / Bisnis Retail)',
            'DKV' => 'DKV (Desain Komunikasi Visual)',
            'KLN' => 'KLN (Kuliner)',
            'HTL' => 'HTL (Perhotelan)',
            'PBS' => 'PBS (Perbankan Syariah)',
        ];

        $daftarTingkat = [
            'all' => 'Semua Angkatan',
            '10' => 'Kelas 10 (Fase E)',
            '11' => 'Kelas 11 (Fase F)',
            '12' => 'Kelas 12 (Fase F Lanjutan)',
        ];

        $kelasQuery = Kelas::query();
        if ($selectedTingkat !== 'all') {
            $kelasQuery->where(function ($q) use ($selectedTingkat) {
                if ($selectedTingkat === '10') {
                    $q->whereIn('tingkat', ['10', 'X'])->orWhere('nama', 'like', '10%')->orWhere('nama', 'like', 'X %');
                } elseif ($selectedTingkat === '11') {
                    $q->whereIn('tingkat', ['11', 'XI'])->orWhere('nama', 'like', '11%')->orWhere('nama', 'like', 'XI %');
                } elseif ($selectedTingkat === '12') {
                    $q->whereIn('tingkat', ['12', 'XII'])->orWhere('nama', 'like', '12%')->orWhere('nama', 'like', 'XII %');
                }
            });
        }

        if ($selectedJurusan !== 'all') {
            $jUpper = strtoupper(trim($selectedJurusan));
            $kelasQuery->where(function ($q) use ($jUpper) {
                match ($jUpper) {
                    'PPLG' => $q->where('nama', 'like', '%PPLG%')->orWhere('nama', 'like', '%RPL%'),
                    'AKL' => $q->where('nama', 'like', '%AKL%')->orWhere('nama', 'like', '%AK%'),
                    'MPLB' => $q->where('nama', 'like', '%MPLB%')->orWhere('nama', 'like', '%MP%'),
                    'PM' => $q->where('nama', 'like', '%PM%')->orWhere('nama', 'like', '%PBR%')->orWhere('nama', 'like', '%PBD%'),
                    'DKV' => $q->where('nama', 'like', '%DKV%'),
                    'KLN' => $q->where('nama', 'like', '%KLN%')->orWhere('nama', 'like', '%KULINER%'),
                    'HTL' => $q->where('nama', 'like', '%HTL%')->orWhere('nama', 'like', '%HOTEL%'),
                    'PBS' => $q->where('nama', 'like', '%PBS%'),
                    default => $q->where('nama', 'like', "%{$jUpper}%"),
                };
            });
        }

        $allKelasList = Kelas::orderBy('nama')->get();
        $matchingKelasList = (clone $kelasQuery)->orderBy('tingkat')->orderBy('nama')->get();

        if ($selectedKelasId) {
            $targetKelas = $allKelasList->where('id', $selectedKelasId);
        } else {
            $targetKelas = $matchingKelasList;
        }

        // 3. Preload Pertemuan & Hari Libur untuk efisiensi tinggi
        $liburDates = HariLibur::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('keterangan', 'tanggal')
            ->toArray();

        $existingPertemuans = Pertemuan::withCount('kehadiranSiswas')
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy(fn ($p) => Carbon::parse($p->tanggal)->toDateString().'_'.$p->jadwal_id);

        $now = Carbon::now();
        $resolver = app(JadwalBlokResolverService::class);
        $period = CarbonPeriod::create($startDate, $endDate);

        $missedList = collect();

        foreach ($period as $date) {
            $dateStr = $date->toDateString();

            // Hari Minggu tidak ada KBM
            if ($date->isSunday()) {
                continue;
            }

            // Hari Libur Resmi dilewati
            if (isset($liburDates[$dateStr])) {
                continue;
            }

            $hariIso = (string) $date->dayOfWeekIso;

            // Dapatkan seluruh jadwal aktif pada tanggal & kelas terkait
            $activeJadwals = $resolver->resolveJadwalBanyakKelas($targetKelas, $date, $hariIso)
                ->load(['kelas', 'guru', 'mataPelajaran']);

            if ($selectedGuruId) {
                $activeJadwals = $activeJadwals->where('guru_id', $selectedGuruId);
            }

            foreach ($activeJadwals as $jadwal) {
                // Jika tanggal adalah HARI INI, dan jam KBM belum selesai, jangan anggap terlewat
                if ($date->isToday()) {
                    $jamSelesaiStr = substr($jadwal->jam_selesai, 0, 5);
                    if ($jamSelesaiStr > $now->format('H:i')) {
                        continue;
                    }
                }

                $pertemuanKey = $dateStr.'_'.$jadwal->id;
                $pertemuan = $existingPertemuans->get($pertemuanKey)?->first();

                $isMissed = false;
                $statusPenyebab = '';

                if (! $pertemuan) {
                    $isMissed = true;
                    $statusPenyebab = 'Belum Ada Agenda Sama Sekali';
                } elseif ($pertemuan->status === 'menunggu' || $pertemuan->kehadiran_siswas_count === 0) {
                    $isMissed = true;
                    $statusPenyebab = 'Agenda Dibuat tapi Belum Diabsenkan ke Siswa';
                }

                if ($isMissed) {
                    $missedList->push([
                        'tanggal' => $dateStr,
                        'tanggal_formatted' => $date->translatedFormat('d M Y'),
                        'hari' => JadwalPelajaran::$namaHari[$jadwal->hari] ?? $date->translatedFormat('l'),
                        'jam_mulai' => substr($jadwal->jam_mulai, 0, 5),
                        'jam_selesai' => substr($jadwal->jam_selesai, 0, 5),
                        'jam_rentang' => substr($jadwal->jam_mulai, 0, 5).' - '.substr($jadwal->jam_selesai, 0, 5),
                        'guru_id' => $jadwal->guru_id,
                        'guru_nama' => $jadwal->guru?->name ?? 'Guru Tidak Ditemukan',
                        'guru_nip' => $jadwal->guru?->guruProfile?->nip ?? '-',
                        'kelas_id' => $jadwal->kelas_id,
                        'kelas_nama' => $jadwal->kelas?->nama ?? '-',
                        'tingkat' => $jadwal->kelas?->tingkat ?? '-',
                        'mapel_id' => $jadwal->mata_pelajaran_id,
                        'mapel_nama' => $jadwal->mataPelajaran?->nama ?? '-',
                        'status_penyebab' => $statusPenyebab,
                        'jadwal_id' => $jadwal->id,
                    ]);
                }
            }
        }

        // Urutkan dari tanggal terbaru, lalu jam mulai, lalu kelas
        $missedItems = $missedList->sortBy([
            ['tanggal', 'desc'],
            ['jam_mulai', 'asc'],
            ['kelas_nama', 'asc'],
        ])->values();

        // 4. Analisis & Agregasi Leaderboard Guru Belum Isi
        $topGuruBelumIsi = $missedItems->groupBy('guru_id')->map(function (Collection $group) {
            $first = $group->first();

            return [
                'guru_id' => $first['guru_id'],
                'guru_nama' => $first['guru_nama'],
                'guru_nip' => $first['guru_nip'],
                'total_terlewat' => $group->count(),
                'kelas_terkena' => $group->pluck('kelas_nama')->unique()->values()->all(),
            ];
        })->sortByDesc('total_terlewat')->take(10)->values();

        // Top Kelas yang Sering Kosong / Belum Diisi
        $topKelasKosong = $missedItems->groupBy('kelas_id')->map(function (Collection $group) {
            $first = $group->first();

            return [
                'kelas_id' => $first['kelas_id'],
                'kelas_nama' => $first['kelas_nama'],
                'total_terlewat' => $group->count(),
            ];
        })->sortByDesc('total_terlewat')->take(5)->values();

        $daftarGuru = User::where('role', 'guru')->orderBy('name')->get();

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterMode' => $filterMode,
            'selectedTingkat' => $selectedTingkat,
            'selectedJurusan' => $selectedJurusan,
            'selectedKelasId' => $selectedKelasId,
            'selectedGuruId' => $selectedGuruId,
            'daftarTingkat' => $daftarTingkat,
            'daftarJurusan' => $daftarJurusan,
            'allKelasList' => $allKelasList,
            'matchingKelasList' => $matchingKelasList,
            'daftarGuru' => $daftarGuru,
            'missedItems' => $missedItems,
            'totalTerlewat' => $missedItems->count(),
            'topGuruBelumIsi' => $topGuruBelumIsi,
            'topKelasKosong' => $topKelasKosong,
        ];
    }
}

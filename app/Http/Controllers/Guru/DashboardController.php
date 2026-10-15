<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
use App\Models\Kelas;
use App\Models\KelasPkl;
use App\Models\Setting;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        /** @var Carbon $weekStart */
        $weekStart = $request->filled('minggu')
            ? Carbon::parse($request->input('minggu'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);

        // Build a 6-day (Mon–Sat) x slots matrix, respecting sistem blok.
        // Strategy: for each class the guru teaches, resolve the active jadwal
        // for the selected week (using any day of the week as the date reference).
        // Then filter to only this guru's slots.
        $kelasIds = JadwalPelajaran::where('guru_id', $user->id)
            ->distinct('kelas_id')
            ->pluck('kelas_id');

        $kelasList = Kelas::whereIn('id', $kelasIds)->get();
        $resolver = app(JadwalBlokResolverService::class);

        // resolveJadwalBanyakKelas without hari filter returns ALL days for the week's blok
        $activeJadwals = $resolver->resolveJadwalBanyakKelas($kelasList, $weekStart)
            ->load(['kelas', 'mataPelajaran'])
            ->where('guru_id', $user->id);

        // Group active jadwal (blok-filtered) by hari (1-6)
        $jadwalByHari = $activeJadwals->groupBy('hari');

        $hariList = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        // Query holidays in this week
        $holidays = HariLibur::whereBetween('tanggal', [
            $weekStart->toDateString(),
            $weekEnd->toDateString(),
        ])->get()->keyBy(fn ($h) => $h->tanggal->toDateString());

        // Build days of this week
        $mingguIni = [];
        for ($i = 0; $i < 6; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $hariAngka = $i + 1; // 1=Senin
            $rawJadwals = $jadwalByHari->get($hariAngka, collect());
            $mingguIni[$hariAngka] = [
                'tanggal' => $date,
                'jadwals' => JadwalPelajaran::groupContinuousSchedules($rawJadwals),
                'hariLibur' => $holidays->get($date->toDateString()),
            ];
        }

        $activeTa = Setting::getTahunAjaranAktif();
        $activeSem = Setting::getSemesterAktif();

        // Attendance statistics for the logged-in teacher:
        // Hanya menghitung sesi pada jadwal aktif yang memang diampu oleh guru ini pada tahun ajaran & semester aktif.
        // Jadwal yang sudah dialihkan ke guru lain atau dihapus otomatis dikecualikan.
        $kehadiranList = KehadiranGuru::where('guru_id', $user->id)
            ->whereHas('pertemuan', function ($q) use ($user, $activeTa, $activeSem) {
                if ($activeTa) {
                    $q->where(fn ($sq) => $sq->where('tahun_ajaran', $activeTa)->orWhereNull('tahun_ajaran'));
                }
                if ($activeSem) {
                    $q->where(fn ($sq) => $sq->where('semester', $activeSem)->orWhereNull('semester'));
                }
                $q->whereHas('jadwal', function ($jq) use ($user, $activeTa, $activeSem) {
                    $jq->where('guru_id', $user->id);
                    if ($activeTa) {
                        $jq->where(fn ($sq) => $sq->where('tahun_ajaran', $activeTa)->orWhereNull('tahun_ajaran'));
                    }
                    if ($activeSem) {
                        $jq->where(fn ($sq) => $sq->where('semester', $activeSem)->orWhereNull('semester'));
                    }
                });
            })
            ->whereDoesntHave('pertemuan.jadwal.kelas.pkls', function ($q) {
                $q->where('is_aktif', true)
                    ->whereColumn('kelas_pkls.tanggal_mulai', '<=', 'pertemuans.tanggal')
                    ->whereColumn('kelas_pkls.tanggal_selesai', '>=', 'pertemuans.tanggal');
            })
            ->with([
                'pertemuan.jadwal.kelas',
                'pertemuan.jadwal.mataPelajaran',
            ])
            ->get();

        $recordsByCat = [
            'hadir' => collect(),
            'sakit' => collect(),
            'izin' => collect(),
            'alpa' => collect(),
        ];

        foreach ($kehadiranList as $k) {
            if ($k->isHadirCategory()) {
                $recordsByCat['hadir']->push($k);
            } elseif ($k->isSakitCategory()) {
                $recordsByCat['sakit']->push($k);
            } elseif ($k->isDispensasiCategory()) {
                $recordsByCat['izin']->push($k);
            } else {
                $recordsByCat['alpa']->push($k);
            }
        }

        $totalKehadiran = $kehadiranList->count();
        $totalHadir = $recordsByCat['hadir']->count();
        $totalSakit = $recordsByCat['sakit']->count();
        $totalIzin = $recordsByCat['izin']->count();
        $totalAlpa = $recordsByCat['alpa']->count();

        $persentaseHadir = $totalKehadiran > 0
            ? round(($totalHadir / $totalKehadiran) * 100, 1)
            : 0;

        $stats = [
            'total' => $totalKehadiran,
            'hadir' => $totalHadir,
            'sakit' => $totalSakit,
            'izin' => $totalIzin,
            'alpa' => $totalAlpa,
            'persentase' => $persentaseHadir,
        ];

        // Format detailed records for modal view
        $detailedAttendance = [];
        foreach (['alpa', 'izin', 'sakit', 'hadir'] as $cat) {
            $sorted = $recordsByCat[$cat]->sortByDesc(function ($item) {
                $tgl = $item->pertemuan?->tanggal ? Carbon::parse($item->pertemuan->tanggal)->format('Y-m-d') : '1970-01-01';
                $jam = $item->pertemuan?->jadwal?->jam_mulai ?? '00:00';

                return $tgl.' '.$jam;
            })->values();

            $grouped = [];
            foreach ($sorted as $item) {
                $p = $item->pertemuan;
                $j = $p?->jadwal;
                $tgl = $p?->tanggal ? Carbon::parse($p->tanggal) : null;
                $tglStr = $tgl ? $tgl->toDateString() : 'N/A';
                $kelasNama = $j?->kelas?->nama ?? '-';
                $mapelNama = $j?->mataPelajaran?->nama ?? '-';
                $statusLabel = $item->status_label;
                $alasanLabel = $item->alasan_tidak_hadir_label ?? ($item->status === 'alpa' ? 'Tanpa Keterangan' : '-');
                $keterangan = trim($item->keterangan ?? '');

                $groupKey = $tglStr.'|'.$kelasNama.'|'.$mapelNama.'|'.$statusLabel.'|'.$alasanLabel.'|'.$keterangan;

                $startMulai = $j?->jam_mulai ? substr($j->jam_mulai, 0, 5) : '-';
                $endSelesai = $j?->jam_selesai ? substr($j->jam_selesai, 0, 5) : '-';

                if (! isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'tanggal' => $tgl ? $tgl->translatedFormat('l, d F Y') : '-',
                        'tanggal_short' => $tgl ? $tgl->translatedFormat('d M Y') : '-',
                        'hari' => $tgl ? $tgl->translatedFormat('l') : '-',
                        'jam_mulai' => $startMulai,
                        'jam_selesai' => $endSelesai,
                        'kelas' => $kelasNama,
                        'mapel' => $mapelNama,
                        'status' => $statusLabel,
                        'alasan' => $alasanLabel,
                        'keterangan' => ($keterangan !== '' && $keterangan !== '-') ? $keterangan : null,
                        'total_jp' => 1,
                    ];
                } else {
                    $grouped[$groupKey]['total_jp']++;
                    if ($startMulai !== '-' && ($grouped[$groupKey]['jam_mulai'] === '-' || $startMulai < $grouped[$groupKey]['jam_mulai'])) {
                        $grouped[$groupKey]['jam_mulai'] = $startMulai;
                    }
                    if ($endSelesai !== '-' && ($grouped[$groupKey]['jam_selesai'] === '-' || $endSelesai > $grouped[$groupKey]['jam_selesai'])) {
                        $grouped[$groupKey]['jam_selesai'] = $endSelesai;
                    }
                }
            }

            $detailedAttendance[$cat] = array_values($grouped);
        }

        // Daftar kelas yang diampu guru ini yang sedang dalam masa PKL
        $kelasPklList = KelasPkl::with('kelas')
            ->whereIn('kelas_id', $kelasIds)
            ->sedangBerlangsung($weekStart)
            ->get();

        return view('guru.dashboard', [
            'mingguIni' => $mingguIni,
            'hariList' => $hariList,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'today' => Carbon::today(),
            'stats' => $stats,
            'detailedAttendance' => $detailedAttendance,
            'kelasPklList' => $kelasPklList,
        ]);
    }
}

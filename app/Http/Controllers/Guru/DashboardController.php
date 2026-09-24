<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\KehadiranGuru;
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

        // Build a 6-day (Mon–Sat) x slots matrix
        $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $user->id)
            ->orderBy('jam_mulai')
            ->get();

        // Group jadwal by hari (1-6)
        $jadwalByHari = $jadwals->groupBy('hari');

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
            $mingguIni[$hariAngka] = [
                'tanggal' => $date,
                'jadwals' => $jadwalByHari->get($hariAngka, collect()),
                'hariLibur' => $holidays->get($date->toDateString()),
            ];
        }

        // Attendance statistics for the logged-in teacher
        $kehadiranStats = KehadiranGuru::where('guru_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalKehadiran = $kehadiranStats->sum();
        $totalHadir = $kehadiranStats->get('hadir', 0) + $kehadiranStats->get('terlambat', 0);
        $persentaseHadir = $totalKehadiran > 0
            ? round(($totalHadir / $totalKehadiran) * 100, 1)
            : 0;

        $stats = [
            'total' => $totalKehadiran,
            'hadir' => $totalHadir,
            'sakit' => $kehadiranStats->get('sakit', 0),
            'izin' => KehadiranGuru::where('guru_id', $user->id)->where('alasan_tidak_hadir', 'izin')->count(),
            'alpa' => $kehadiranStats->get('alpa', 0) + $kehadiranStats->get('tidak_hadir', 0),
            'persentase' => $persentaseHadir,
        ];

        return view('guru.dashboard', [
            'mingguIni' => $mingguIni,
            'hariList' => $hariList,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'today' => Carbon::today(),
            'stats' => $stats,
        ]);
    }
}

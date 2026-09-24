<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WaliKelasController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $user = auth()->user();

        $kelasBinaan = Kelas::where('wali_kelas_id', $userId)->get();

        if ($kelasBinaan->isEmpty()) {
            if (in_array($user->role, ['admin', 'super_admin'])) {
                $kelasBinaan = Kelas::orderBy('tingkat')->orderBy('nama')->get();
            } else {
                return redirect()->route('guru.dashboard')->with('error', 'Anda belum ditugaskan sebagai Wali Kelas.');
            }
        }

        $kelasIds = $kelasBinaan->pluck('id');

        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $date = Carbon::parse($tanggal);

        $kehadiran = KehadiranSiswa::with(['siswa.siswaProfile.kelas', 'pertemuan.jadwal.mataPelajaran'])
            ->whereHas('siswa.siswaProfile', function ($q) use ($kelasIds) {
                $q->whereIn('kelas_id', $kelasIds);
            })
            ->whereHas('pertemuan', function ($q) use ($tanggal) {
                $q->where('tanggal', $tanggal);
            })
            ->get();

        $rekapKelas = [];
        foreach ($kelasBinaan as $kelas) {
            $kehadiranKelas = $kehadiran->filter(function ($item) use ($kelas) {
                return $item->siswa?->siswaProfile?->kelas_id === $kelas->id;
            });

            $rekapKelas[$kelas->id] = [
                'kelas' => $kelas,
                'hadir' => $kehadiranKelas->where('status', 'hadir')->count(),
                'terlambat' => $kehadiranKelas->where('status', 'terlambat')->count(),
                'sakit' => $kehadiranKelas->where('status', 'sakit')->count(),
                'izin' => $kehadiranKelas->where('status', 'izin')->count(),
                'alpa' => $kehadiranKelas->where('status', 'alpa')->count(),
            ];
        }

        return view('guru.wali-kelas.index', compact('kelasBinaan', 'rekapKelas', 'tanggal', 'date', 'kehadiran'));
    }
}

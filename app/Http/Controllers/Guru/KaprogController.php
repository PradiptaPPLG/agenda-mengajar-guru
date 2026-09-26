<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Services\JadwalBlokResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KaprogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $jurusan = $user->guruProfile?->kaprog_jurusan ?? $request->get('jurusan');

        $hasKaprogAccess = $user->can('kaprog.view_rekap')
            || $user->hasAnyRole(['Kaprog', 'kaprog', 'Kepala Program'])
            || $user->roles->contains(fn ($r) => str_contains(strtolower($r->name), 'kaprog'))
            || in_array($user->role, ['admin', 'super_admin']);

        if (! $hasKaprogAccess) {
            return redirect()->route('guru.dashboard')->with('error', 'Anda belum memiliki hak akses sebagai Kaprog.');
        }

        // Ambil kelas yang sesuai jurusan Kaprog (misal: 10AKL1, 11AKL2, 12AKL3 untuk jurusan AKL)
        if ($jurusan) {
            $kelasJurusan = Kelas::where('nama', 'like', "%{$jurusan}%")
                ->orderBy('tingkat')
                ->orderBy('nama')
                ->get();
        } else {
            $kelasJurusan = Kelas::orderBy('tingkat')->orderBy('nama')->get();
        }

        $kelasIds = $kelasJurusan->pluck('id');

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
        foreach ($kelasJurusan as $kelas) {
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

        // Pemantauan Guru di Jurusan ini (berdasarkan jadwal KBM kelas jurusan pada tanggal target)
        $hariIso = (string) $date->dayOfWeekIso;
        $resolver = app(JadwalBlokResolverService::class);
        $jadwalJurusan = $resolver->resolveJadwalBanyakKelas($kelasJurusan, $date, $hariIso)
            ->load(['guru.guruProfile', 'mataPelajaran', 'kelas'])
            ->sortBy(['jam_mulai', 'kelas.nama']);

        $pertemuanGuru = Pertemuan::with(['kehadiranGuru', 'fotoBuktis'])
            ->whereDate('tanggal', $tanggal)
            ->whereIn('jadwal_id', $jadwalJurusan->pluck('id'))
            ->get()
            ->keyBy('jadwal_id');

        $monitoringGuru = $jadwalJurusan->map(function ($jadwal) use ($pertemuanGuru) {
            $pertemuan = $pertemuanGuru->get($jadwal->id);
            $kh = $pertemuan?->kehadiranGuru;

            return [
                'jadwal' => $jadwal,
                'pertemuan' => $pertemuan,
                'guru' => $jadwal->guru,
                'kelas' => $jadwal->kelas,
                'mapel' => $jadwal->mataPelajaran,
                'status' => $kh ? $kh->status : 'belum_hadir',
                'status_label' => $kh ? $kh->status_label : 'Belum Mulai',
                'keterangan' => $kh?->keterangan ?? $pertemuan?->materi_ajar,
                'waktu_hadir' => $kh?->waktu_hadir,
                'foto' => $pertemuan?->fotoBuktis?->first()?->foto_url,
            ];
        });

        $rekapGuru = [
            'total' => $monitoringGuru->count(),
            'hadir' => $monitoringGuru->where('status', 'hadir')->count(),
            'terlambat' => $monitoringGuru->where('status', 'terlambat')->count(),
            'tidak_hadir' => $monitoringGuru->whereIn('status', ['tidak_hadir', 'sakit', 'alpa', 'dispensasi'])->count(),
            'belum_hadir' => $monitoringGuru->where('status', 'belum_hadir')->count(),
        ];

        return view('guru.kaprog.index', compact('jurusan', 'kelasJurusan', 'rekapKelas', 'tanggal', 'date', 'kehadiran', 'monitoringGuru', 'rekapGuru'));
    }
}

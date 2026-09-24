<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\KehadiranGuru;
use App\Models\KehadiranSiswa;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    public function guru(Request $request): Response
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        $selectedGuruId = $request->input('guru_id');
        $selectedKelasId = $request->input('kelas_id');

        $query = KehadiranGuru::with([
            'guru',
            'pertemuan.jadwal.kelas',
            'pertemuan.jadwal.mataPelajaran',
        ])
            ->whereHas('pertemuan', function ($q) use ($startDate, $endDate, $selectedKelasId) {
                $q->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
                if ($selectedKelasId) {
                    $q->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $selectedKelasId));
                }
            });

        if ($selectedGuruId) {
            $query->where('guru_id', $selectedGuruId);
        }

        $kehadiran = $query->orderBy(
            Pertemuan::select('tanggal')
                ->whereColumn('pertemuans.id', 'kehadiran_gurus.pertemuan_id')
                ->limit(1)
        )->get();

        $summary = $kehadiran->groupBy('guru_id')->map(function ($items) {
            return [
                'guru' => $items->first()->guru,
                'hadir' => $items->where('status', 'hadir')->count(),
                'sakit' => $items->where('status', 'sakit')->count(),
                'alpa' => $items->where('status', 'alpa')->count(),
                'total' => $items->count(),
            ];
        })->values();

        $schoolName = Setting::get('school_name', 'Nama Sekolah');
        $selectedGuru = $selectedGuruId ? User::find($selectedGuruId) : null;
        $selectedKelas = $selectedKelasId ? Kelas::find($selectedKelasId) : null;

        $pdf = Pdf::loadView('kepala-sekolah.pdf.guru', compact(
            'kehadiran', 'summary', 'startDate', 'endDate',
            'schoolName', 'selectedGuru', 'selectedKelas'
        ))->setPaper('a4', 'landscape');

        $filename = 'laporan-kehadiran-guru-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function siswa(Request $request): Response
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfMonth();

        $selectedKelasId = $request->input('kelas_id');

        $pertemuanIds = Pertemuan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($selectedKelasId, fn ($q) => $q->whereHas('jadwal', fn ($j) => $j->where('kelas_id', $selectedKelasId)))
            ->pluck('id');

        $kehadiranData = KehadiranSiswa::with(['siswa', 'pertemuan.jadwal.mataPelajaran'])
            ->whereIn('pertemuan_id', $pertemuanIds)
            ->get();

        $summary = $kehadiranData->groupBy('siswa_id')->map(function ($items) {
            return [
                'siswa' => $items->first()->siswa,
                'hadir' => $items->where('status', 'hadir')->count(),
                'sakit' => $items->where('status', 'sakit')->count(),
                'izin' => $items->where('status', 'izin')->count(),
                'alpa' => $items->where('status', 'alpa')->count(),
                'dispensasi' => $items->where('status', 'dispensasi')->count(),
                'total' => $items->count(),
            ];
        })->values();

        $schoolName = Setting::get('school_name', 'Nama Sekolah');
        $selectedKelas = $selectedKelasId ? Kelas::find($selectedKelasId) : null;

        $pdf = Pdf::loadView('kepala-sekolah.pdf.siswa', compact(
            'summary', 'startDate', 'endDate', 'schoolName', 'selectedKelas'
        ))->setPaper('a4', 'landscape');

        $filename = 'laporan-kehadiran-siswa-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}

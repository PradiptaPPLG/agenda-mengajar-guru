<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        // Inisialisasi awal jika belum ada data sama sekali
        if (AcademicYear::count() === 0) {
            $schoolYear = Setting::get('school_year', '2024/2025');
            $semester = (int) Setting::get('semester', 1);

            AcademicYear::create([
                'tahun_ajaran' => $schoolYear,
                'semester' => $semester,
                'is_active' => true,
                'is_locked' => false,
                'keterangan' => 'Periode Akademik Default Sistem',
            ]);
        }

        $academicYears = AcademicYear::orderByDesc('tahun_ajaran')
            ->orderByDesc('semester')
            ->get();

        $activeYear = AcademicYear::getActive();

        return view('super-admin.academic-years.index', compact('academicYears', 'activeYear'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:1,2'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = AcademicYear::where('tahun_ajaran', $validated['tahun_ajaran'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            return back()->with('error', "Periode {$validated['tahun_ajaran']} Semester {$validated['semester']} sudah ada.")->withInput();
        }

        $isActive = $request->boolean('is_active');
        if ($isActive) {
            AcademicYear::query()->update(['is_active' => false]);
            Setting::set('school_year', $validated['tahun_ajaran']);
            Setting::set('semester', (string) $validated['semester']);
        }

        $period = AcademicYear::create([
            'tahun_ajaran' => $validated['tahun_ajaran'],
            'semester' => (int) $validated['semester'],
            'is_active' => $isActive,
            'is_locked' => false,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        ActivityLogger::log(
            'CREATE',
            'AcademicYear',
            "Menambahkan periode akademik baru: {$period->full_period_label}",
            null,
            $period->toArray()
        );

        return back()->with('success', "Periode akademik {$period->full_period_label} berhasil ditambahkan.");
    }

    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        AcademicYear::query()->update(['is_active' => false]);

        $academicYear->update(['is_active' => true]);

        // Sinkronisasi ke tabel settings
        Setting::set('school_year', $academicYear->tahun_ajaran);
        Setting::set('semester', (string) $academicYear->semester);

        ActivityLogger::log(
            'UPDATE',
            'AcademicYear',
            "Mengaktifkan periode akademik: {$academicYear->full_period_label}"
        );

        return back()->with('success', "Periode akademik aktif sekarang: {$academicYear->full_period_label}.");
    }

    public function toggleLock(AcademicYear $academicYear): RedirectResponse
    {
        $newStatus = ! $academicYear->is_locked;
        $academicYear->update(['is_locked' => $newStatus]);

        $statusLabel = $newStatus ? 'DIKUNCI (Cut-Off: Read-Only)' : 'DIBUKA (Dapat Diedit)';

        ActivityLogger::log(
            'UPDATE',
            'AcademicYear',
            "Mengubah status kunci periode {$academicYear->full_period_label} menjadi: {$statusLabel}"
        );

        return back()->with('success', "Periode {$academicYear->full_period_label} berhasil {$statusLabel}.");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->is_active) {
            return back()->with('error', 'Periode akademik yang sedang aktif tidak dapat dihapus.');
        }

        $label = $academicYear->full_period_label;
        $old = $academicYear->toArray();
        $academicYear->delete();

        ActivityLogger::log(
            'DELETE',
            'AcademicYear',
            "Menghapus periode akademik: {$label}",
            $old
        );

        return back()->with('success', "Periode akademik {$label} berhasil dihapus.");
    }
}

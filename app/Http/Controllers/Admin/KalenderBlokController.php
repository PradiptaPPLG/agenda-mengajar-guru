<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlokMinggu;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KalenderBlokController extends Controller
{
    public function index(): View
    {
        $kalenders = KalenderBlokMinggu::orderBy('tahun_ajaran')
            ->orderBy('semester')
            ->orderBy('nomor_minggu')
            ->get()
            ->groupBy(fn ($k) => "{$k->tahun_ajaran} - Semester ".ucfirst($k->semester));

        $mingguAktif = KalenderBlokMinggu::aktif()->first();

        return view('admin.kalender-blok.index', compact('kalenders', 'mingguAktif'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'jumlah_minggu' => ['required', 'integer', 'min:1', 'max:30'],
            'tahun_ajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:ganjil,genap'],
        ]);

        $tanggalMulai = Carbon::parse($validated['tanggal_mulai']);

        // Pastikan dimulai dari hari Senin
        if (! $tanggalMulai->isMonday()) {
            $tanggalMulai = $tanggalMulai->next(Carbon::MONDAY);
        }

        KalenderBlokMinggu::generateSemester(
            tanggalMulai: $tanggalMulai,
            jumlahMinggu: (int) $validated['jumlah_minggu'],
            tahunAjaran: $validated['tahun_ajaran'],
            semester: $validated['semester'],
        );

        return redirect()->route('admin.kalender-blok.index')
            ->with('success', "Kalender blok {$validated['jumlah_minggu']} minggu berhasil di-generate untuk Semester ".ucfirst($validated['semester'])." {$validated['tahun_ajaran']}.");
    }

    public function destroy(KalenderBlokMinggu $kalenderBlok): RedirectResponse
    {
        $label = $kalenderBlok->label;
        $kalenderBlok->delete();

        return redirect()->route('admin.kalender-blok.index')
            ->with('success', "{$label} berhasil dihapus.");
    }

    public function destroyGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran' => ['required', 'string'],
            'semester' => ['required', 'in:ganjil,genap'],
        ]);

        KalenderBlokMinggu::where('tahun_ajaran', $validated['tahun_ajaran'])
            ->where('semester', $validated['semester'])
            ->delete();

        return redirect()->route('admin.kalender-blok.index')
            ->with('success', 'Semua kalender blok untuk Semester '.ucfirst($validated['semester'])." {$validated['tahun_ajaran']} berhasil dihapus.");
    }
}

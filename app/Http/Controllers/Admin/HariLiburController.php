<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Services\HariLiburSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    public function index(Request $request): View
    {
        $query = HariLibur::query();

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->input('tahun'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        $hariLiburs = $query->orderBy('tanggal', 'desc')->paginate(20)->withQueryString();

        // Get list of unique years present in holidays for filter (DB-agnostic)
        $years = HariLibur::orderBy('tanggal', 'desc')
            ->pluck('tanggal')
            ->map(fn ($d) => $d->format('Y'))
            ->unique()
            ->values();

        return view('admin.hari-libur.index', compact('hariLiburs', 'years'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'unique:hari_liburs,tanggal'],
            'keterangan' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:nasional,cuti_bersama,khusus'],
        ]);

        DB::transaction(function () use ($validated) {
            HariLibur::create($validated);
        });

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    /**
     * Store a range of dates as holidays (e.g. semester break / school agenda).
     */
    public function storeRange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:nasional,cuti_bersama,khusus'],
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);

        $count = 0;
        DB::transaction(function () use ($start, $end, $validated, &$count) {
            $curr = $start->copy();
            while ($curr->lte($end)) {
                $dateStr = $curr->toDateString();
                HariLibur::updateOrCreate(
                    ['tanggal' => $dateStr],
                    [
                        'keterangan' => $validated['keterangan'],
                        'jenis' => $validated['jenis'],
                    ]
                );
                $count++;
                $curr->addDay();
            }
        });

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Berhasil menambahkan rentang libur sebanyak {$count} hari ({$start->format('d/m/Y')} s.d. {$end->format('d/m/Y')}).");
    }

    /**
     * Trigger 1-click automatic synchronization with Indonesian public holidays API.
     */
    public function syncNasional(Request $request, HariLiburSyncService $syncService): RedirectResponse
    {
        $tahun = (int) ($request->input('tahun') ?: now()->year);

        $result = $syncService->syncTahun($tahun);

        if ($result['success']) {
            return redirect()->route('admin.hari-libur.index', ['tahun' => $tahun])
                ->with('success', $result['message']);
        }

        return redirect()->route('admin.hari-libur.index', ['tahun' => $tahun])
            ->with('error', $result['message']);
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        DB::transaction(function () use ($hariLibur) {
            $hariLibur->delete();
        });

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}

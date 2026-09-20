<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
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

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        DB::transaction(function () use ($hariLibur) {
            $hariLibur->delete();
        });

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KelasPkl;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KelasPklController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $query = KelasPkl::with('kelas');

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->input('kelas_id'));
        }

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'sedang_berlangsung' => $query->sedangBerlangsung($today),
                'akan_datang' => $query->akanDatang($today),
                'selesai' => $query->selesai($today),
                'nonaktif' => $query->where('is_aktif', false),
                default => null,
            };
        }

        $pklList = $query->orderByDesc('tanggal_mulai')->paginate(15)->withQueryString();

        $stats = [
            'total' => KelasPkl::count(),
            'sedang_berlangsung' => KelasPkl::sedangBerlangsung($today)->count(),
            'akan_datang' => KelasPkl::akanDatang($today)->count(),
            'selesai' => KelasPkl::selesai($today)->count(),
        ];

        $kelasList = Kelas::where(function ($q) {
            $q->whereIn('tingkat', ['12', 'XII'])
                ->orWhere('nama', 'like', '12%')
                ->orWhere('nama', 'like', 'XII%');
        })->orderBy('nama')->get();

        if ($kelasList->isEmpty()) {
            $kelasList = Kelas::orderBy('nama')->get();
        }

        return view('admin.kelas-pkl.index', compact('pklList', 'stats', 'kelasList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        DB::transaction(function () use ($validated) {
            KelasPkl::create($validated);
        });

        return redirect()->route('admin.kelas-pkl.index')
            ->with('success', 'Periode PKL kelas berhasil ditambahkan. Jadwal KBM pada rentang tanggal tersebut otomatis dinonaktifkan.');
    }

    public function update(Request $request, KelasPkl $kelasPkl): RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        DB::transaction(function () use ($kelasPkl, $validated) {
            $kelasPkl->update($validated);
        });

        return redirect()->route('admin.kelas-pkl.index')
            ->with('success', 'Periode PKL kelas berhasil diperbarui.');
    }

    public function destroy(KelasPkl $kelasPkl): RedirectResponse
    {
        DB::transaction(function () use ($kelasPkl) {
            $kelasPkl->delete();
        });

        return redirect()->route('admin.kelas-pkl.index')
            ->with('success', 'Periode PKL kelas berhasil dihapus.');
    }

    public function toggleAktif(KelasPkl $kelasPkl): RedirectResponse
    {
        $newStatus = ! $kelasPkl->is_aktif;
        DB::transaction(function () use ($kelasPkl, $newStatus) {
            $kelasPkl->update(['is_aktif' => $newStatus]);
        });

        $statusStr = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.kelas-pkl.index')
            ->with('success', "Periode PKL kelas berhasil {$statusStr}.");
    }
}

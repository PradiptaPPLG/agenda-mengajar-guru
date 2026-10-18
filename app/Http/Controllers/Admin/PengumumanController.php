<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Pengumuman::with(['author', 'reads'])
            ->withCount('reads');

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->input('tipe'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->active();
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $pengumumans = $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pengumuman.index', compact('pengumumans'));
    }

    public function create(): View
    {
        return view('admin.pengumuman.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'tipe' => ['required', 'in:info,penting,urgent'],
            'target_roles' => ['required', 'array'],
            'target_roles.*' => ['string'],
            'target_tingkat' => ['nullable', 'string', 'in:all,10,11,12'],
            'file_lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xlsx', 'max:5120'], // Max 5MB
            'is_pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('file_lampiran')) {
            $uploaded = $request->file('file_lampiran');
            $filePath = $uploaded->store('pengumuman-lampiran', 'public');
            $fileName = $uploaded->getClientOriginalName();
        }

        $tanggalSelesai = ! empty($validated['tanggal_selesai']) ? Carbon::parse($validated['tanggal_selesai']) : null;
        if ($tanggalSelesai && $tanggalSelesai->format('H:i:s') === '00:00:00') {
            $tanggalSelesai = $tanggalSelesai->endOfDay();
        }

        Pengumuman::create([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tipe' => $validated['tipe'],
            'target_roles' => $validated['target_roles'],
            'target_tingkat' => $validated['target_tingkat'] ?? 'all',
            'file_lampiran' => $filePath,
            'nama_file' => $fileName,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_active' => $request->boolean('is_active', true),
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $tanggalSelesai,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman sekolah berhasil diterbitkan.');
    }

    public function edit(Pengumuman $pengumuman): View
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $pengumuman->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengubah pengumuman yang Anda buat sendiri.');
        }

        return view('admin.pengumuman.edit', compact('pengumuman'));
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $pengumuman->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat memperbarui pengumuman yang Anda buat sendiri.');
        }

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'tipe' => ['required', 'in:info,penting,urgent'],
            'target_roles' => ['required', 'array'],
            'target_roles.*' => ['string'],
            'target_tingkat' => ['nullable', 'string', 'in:all,10,11,12'],
            'file_lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xlsx', 'max:5120'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $filePath = $pengumuman->file_lampiran;
        $fileName = $pengumuman->nama_file;
        if ($request->hasFile('file_lampiran')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $uploaded = $request->file('file_lampiran');
            $filePath = $uploaded->store('pengumuman-lampiran', 'public');
            $fileName = $uploaded->getClientOriginalName();
        }

        $tanggalSelesai = ! empty($validated['tanggal_selesai']) ? Carbon::parse($validated['tanggal_selesai']) : null;
        if ($tanggalSelesai && $tanggalSelesai->format('H:i:s') === '00:00:00') {
            $tanggalSelesai = $tanggalSelesai->endOfDay();
        }

        $pengumuman->update([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tipe' => $validated['tipe'],
            'target_roles' => $validated['target_roles'],
            'target_tingkat' => $validated['target_tingkat'] ?? 'all',
            'file_lampiran' => $filePath,
            'nama_file' => $fileName,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_active' => $request->boolean('is_active'),
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $tanggalSelesai,
        ]);

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $pengumuman->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat menghapus pengumuman yang Anda buat sendiri.');
        }

        if ($pengumuman->file_lampiran && Storage::disk('public')->exists($pengumuman->file_lampiran)) {
            Storage::disk('public')->delete($pengumuman->file_lampiran);
        }

        $pengumuman->delete();

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * Show reader statistics and list of users who read this announcement.
     */
    public function readers(Pengumuman $pengumuman): View
    {
        $reads = $pengumuman->reads()
            ->with(['user.siswaProfile.kelas'])
            ->orderBy('read_at', 'desc')
            ->paginate(30);

        return view('admin.pengumuman.readers', compact('pengumuman', 'reads'));
    }
}

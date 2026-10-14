<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pertemuan;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrashController extends Controller
{
    /**
     * Tipe modul yang didukung dalam trash manager.
     */
    protected array $supportedTypes = ['guru', 'siswa', 'kelas', 'jadwal', 'mapel', 'agenda'];

    public function index(Request $request): View
    {
        $type = $request->query('type', 'guru');
        if (! in_array($type, $this->supportedTypes)) {
            $type = 'guru';
        }

        $search = $request->query('q');

        // Hitung total trashed untuk masing-masing modul
        $counts = [
            'guru' => User::onlyTrashed()->where('role', 'guru')->count(),
            'siswa' => User::onlyTrashed()->where('role', 'siswa')->count(),
            'kelas' => Kelas::onlyTrashed()->count(),
            'jadwal' => JadwalPelajaran::onlyTrashed()->count(),
            'mapel' => MataPelajaran::onlyTrashed()->count(),
            'agenda' => Pertemuan::onlyTrashed()->count(),
        ];

        $items = match ($type) {
            'guru' => User::onlyTrashed()
                ->where('role', 'guru')
                ->with('guruProfile')
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('guruProfile', fn ($gp) => $gp->where('nip', 'like', "%{$search}%"));
                    });
                })
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString(),

            'siswa' => User::onlyTrashed()
                ->where('role', 'siswa')
                ->with(['siswaProfile.kelas'])
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('siswaProfile', fn ($sp) => $sp->where('nis', 'like', "%{$search}%")->orWhere('nisn', 'like', "%{$search}%"));
                    });
                })
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString(),

            'kelas' => Kelas::onlyTrashed()
                ->with(['waliKelas'])
                ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%"))
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString(),

            'jadwal' => JadwalPelajaran::onlyTrashed()
                ->with(['kelas', 'guru', 'mataPelajaran'])
                ->when($search, function ($q) use ($search) {
                    $q->whereHas('kelas', fn ($k) => $k->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('guru', fn ($g) => $g->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('mataPelajaran', fn ($m) => $m->where('nama', 'like', "%{$search}%"));
                })
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString(),

            'mapel' => MataPelajaran::onlyTrashed()
                ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('kode', 'like', "%{$search}%"))
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString(),

            'agenda' => Pertemuan::onlyTrashed()
                ->with(['jadwal.kelas', 'jadwal.guru', 'jadwal.mataPelajaran'])
                ->when($search, function ($q) use ($search) {
                    $q->where('materi_ajar', 'like', "%{$search}%")
                        ->orWhere('tanggal', 'like', "%{$search}%")
                        ->orWhereHas('jadwal.guru', fn ($g) => $g->where('name', 'like', "%{$search}%"));
                })
                ->latest('deleted_at')
                ->paginate(15)
                ->withQueryString(),
        };

        return view('super-admin.trash.index', compact('items', 'type', 'counts', 'search'));
    }

    public function restore(string $type, int|string $id): RedirectResponse
    {
        $model = $this->resolveTrashedModel($type, $id);
        if (! $model) {
            return back()->with('error', 'Data tidak ditemukan di keranjang sampah.');
        }

        $label = $this->getModelLabel($type, $model);
        $model->restore();

        ActivityLogger::log(
            'RESTORE',
            ucfirst($type),
            "Memulihkan data {$type}: {$label} dari sampah",
            null,
            $model->toArray()
        );

        return back()->with('success', "Data {$label} berhasil dipulihkan.");
    }

    public function forceDelete(string $type, int|string $id): RedirectResponse
    {
        $model = $this->resolveTrashedModel($type, $id);
        if (! $model) {
            return back()->with('error', 'Data tidak ditemukan di keranjang sampah.');
        }

        $label = $this->getModelLabel($type, $model);
        $oldData = $model->toArray();

        // Jika user, hapus foto jika ada dan profile terkait jika force delete
        if ($model instanceof User) {
            if ($model->foto && file_exists(storage_path('app/public/'.$model->foto))) {
                @unlink(storage_path('app/public/'.$model->foto));
            }
            if ($model->role === 'guru') {
                $model->guruProfile()->delete();
            } elseif ($model->role === 'siswa') {
                $model->siswaProfile()->delete();
            }
        }

        $model->forceDelete();

        ActivityLogger::log(
            'DELETE',
            ucfirst($type),
            "Hapus PERMANEN data {$type}: {$label} dari sistem",
            $oldData
        );

        return back()->with('success', "Data {$label} berhasil dihapus permanen.");
    }

    public function restoreAll(string $type): RedirectResponse
    {
        if (! in_array($type, $this->supportedTypes)) {
            return back()->with('error', 'Tipe data tidak valid.');
        }

        $query = $this->getQueryForType($type);
        $count = $query->count();

        if ($count === 0) {
            return back()->with('error', "Tidak ada data {$type} di keranjang sampah.");
        }

        $query->restore();

        ActivityLogger::log(
            'RESTORE',
            ucfirst($type),
            "Memulihkan SEMUA ({$count}) data {$type} dari keranjang sampah."
        );

        return back()->with('success', "Berhasil memulihkan {$count} data {$type}.");
    }

    public function empty(string $type): RedirectResponse
    {
        return $this->emptyTrash($type);
    }

    public function emptyTrash(string $type): RedirectResponse
    {
        if (! in_array($type, $this->supportedTypes)) {
            return back()->with('error', 'Tipe data tidak valid.');
        }

        $query = $this->getQueryForType($type);
        $count = $query->count();

        if ($count === 0) {
            return back()->with('error', "Keranjang sampah {$type} sudah kosong.");
        }

        $query->forceDelete();

        ActivityLogger::log(
            'DELETE',
            ucfirst($type),
            "Mengosongkan sampah {$type}: {$count} data dihapus secara permanen."
        );

        return back()->with('success', "{$count} data {$type} telah dihapus secara permanen.");
    }

    protected function resolveTrashedModel(string $type, int|string $id): mixed
    {
        return match ($type) {
            'guru' => User::onlyTrashed()->where('role', 'guru')->find($id),
            'siswa' => User::onlyTrashed()->where('role', 'siswa')->find($id),
            'kelas' => Kelas::onlyTrashed()->find($id),
            'jadwal' => JadwalPelajaran::onlyTrashed()->find($id),
            'mapel' => MataPelajaran::onlyTrashed()->find($id),
            'agenda' => Pertemuan::onlyTrashed()->find($id),
            default => null,
        };
    }

    protected function getQueryForType(string $type): mixed
    {
        return match ($type) {
            'guru' => User::onlyTrashed()->where('role', 'guru'),
            'siswa' => User::onlyTrashed()->where('role', 'siswa'),
            'kelas' => Kelas::onlyTrashed(),
            'jadwal' => JadwalPelajaran::onlyTrashed(),
            'mapel' => MataPelajaran::onlyTrashed(),
            'agenda' => Pertemuan::onlyTrashed(),
            default => null,
        };
    }

    protected function getModelLabel(string $type, mixed $model): string
    {
        return match ($type) {
            'guru', 'siswa' => $model->name ?? "User #{$model->id}",
            'kelas' => $model->nama ?? "Kelas #{$model->id}",
            'jadwal' => ($model->kelas?->nama ?? '-').' | '.($model->mataPelajaran?->nama ?? '-'),
            'mapel' => $model->nama ?? "Mapel #{$model->id}",
            'agenda' => ($model->tanggal ?? '').' - '.($model->materi_ajar ?? "Agenda #{$model->id}"),
            default => "#{$model->id}",
        };
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MataPelajaranController extends Controller
{
    public function index(Request $request): View
    {
        $query = MataPelajaran::with('kelas')->orderBy('nama');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('kode', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('jenis')) {
            if ($request->jenis === 'umum') {
                $query->whereIn('jenis', ['umum', 'normatif']);
            } elseif ($request->jenis === 'produktif') {
                $query->whereIn('jenis', ['produktif', 'adaptif', 'kejuruan']);
            }
        }

        $mataPelajarans = $query->paginate(20)->withQueryString();

        return view('admin.mata-pelajaran.index', compact('mataPelajarans'));
    }

    public function create(): View
    {
        $kelasList = Kelas::orderBy('nama')->get();

        return view('admin.mata-pelajaran.create', compact('kelasList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_pelajarans'],
            'jenis' => ['required', 'in:umum,produktif,normatif,adaptif,kejuruan'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $mataPelajaran = MataPelajaran::create([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
        ]);

        if (in_array($validated['jenis'], ['produktif', 'adaptif']) && isset($validated['kelas_ids'])) {
            $mataPelajaran->kelas()->sync($validated['kelas_ids']);
        }

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(MataPelajaran $mataPelajaran): View
    {
        $mataPelajaran->load('kelas');
        $kelasList = Kelas::orderBy('nama')->get();

        return view('admin.mata-pelajaran.edit', compact('mataPelajaran', 'kelasList'));
    }

    public function update(Request $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('mata_pelajarans')->ignore($mataPelajaran->id)],
            'jenis' => ['required', 'in:umum,produktif,normatif,adaptif,kejuruan'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['exists:kelas,id'],
        ]);

        $mataPelajaran->update([
            'nama' => $validated['nama'],
            'kode' => $validated['kode'],
            'jenis' => $validated['jenis'],
        ]);

        if (in_array($validated['jenis'], ['produktif', 'adaptif']) && isset($validated['kelas_ids'])) {
            $mataPelajaran->kelas()->sync($validated['kelas_ids']);
        } else {
            $mataPelajaran->kelas()->detach();
        }

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mataPelajaran): RedirectResponse
    {
        $mataPelajaran->delete();

        return redirect()->route('admin.mata-pelajaran.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all')) {
            $query = MataPelajaran::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', '%'.$search.'%')
                        ->orWhere('kode', 'like', '%'.$search.'%');
                });
            }

            if ($request->filled('jenis')) {
                if ($request->jenis === 'umum') {
                    $query->whereIn('jenis', ['umum', 'normatif']);
                } elseif ($request->jenis === 'produktif') {
                    $query->whereIn('jenis', ['produktif', 'adaptif', 'kejuruan']);
                }
            }

            $count = $query->count();
            DB::transaction(function () use ($query) {
                $query->delete();
            });

            return redirect()->route('admin.mata-pelajaran.index')
                ->with('success', $count.' mata pelajaran berhasil dihapus.');
        }

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:mata_pelajarans,id'],
        ]);

        DB::transaction(function () use ($request) {
            MataPelajaran::whereIn('id', $request->ids)->delete();
        });

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('success', count($request->ids).' mata pelajaran berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $reader = SimpleExcelReader::create($filePath, $file->getClientOriginalExtension());
        $spoutReader = $reader->getReader();

        $successCount = 0;
        $updatedCount = 0;
        $errorCount = 0;

        $processRow = function (array $rowProperties, &$headerFound, &$namaIndex, &$kodeIndex, &$jenisIndex) use (
            &$successCount, &$updatedCount, &$errorCount
        ) {
            if (! $headerFound) {
                foreach ($rowProperties as $index => $value) {
                    if (is_string($value)) {
                        $lowerVal = trim(preg_replace('/\s+/', ' ', strtolower($value)));
                        if (in_array($lowerVal, ['nama mata pelajaran', 'mata pelajaran', 'nama mapel', 'mapel', 'nama'])) {
                            $namaIndex = $index;
                        } elseif (in_array($lowerVal, ['kode mata pelajaran', 'kode mapel', 'kode'])) {
                            $kodeIndex = $index;
                        } elseif (in_array($lowerVal, ['jenis mata pelajaran', 'jenis', 'kategori'])) {
                            $jenisIndex = $index;
                        }
                    }
                }

                if ($namaIndex !== -1) {
                    $headerFound = true;
                }

                return;
            }

            $nama = isset($rowProperties[$namaIndex]) ? trim((string) $rowProperties[$namaIndex]) : null;
            if (! $nama || $nama === '-' || in_array(strtolower($nama), ['nama', 'mata pelajaran', 'nama mata pelajaran', 'mapel'])) {
                return;
            }

            $kode = ($kodeIndex !== -1 && isset($rowProperties[$kodeIndex])) ? trim((string) $rowProperties[$kodeIndex]) : null;
            if ($kode === '' || $kode === '-') {
                $kode = null;
            }

            $rawJenis = ($jenisIndex !== -1 && isset($rowProperties[$jenisIndex])) ? trim((string) $rowProperties[$jenisIndex]) : 'umum';
            $lowerJenis = strtolower($rawJenis);
            $jenis = in_array($lowerJenis, ['umum', 'produktif', 'normatif', 'adaptif', 'kejuruan']) ? $lowerJenis : 'umum';

            try {
                $mapel = null;

                if ($kode) {
                    $mapel = MataPelajaran::withTrashed()->where('kode', $kode)->first();
                }

                if (! $mapel) {
                    $mapel = MataPelajaran::withTrashed()->whereRaw('LOWER(TRIM(nama)) = ?', [strtolower(trim($nama))])->first();
                }

                if ($mapel) {
                    $isRestored = false;
                    if ($mapel->trashed()) {
                        $mapel->restore();
                        $isRestored = true;
                    }
                    $mapel->update([
                        'nama' => $nama,
                        'kode' => $kode ?: $mapel->kode,
                        'jenis' => $jenis,
                    ]);

                    if ($isRestored) {
                        $successCount++;
                    } else {
                        $updatedCount++;
                    }
                } else {
                    if (! $kode) {
                        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nama), 0, 5));
                        $kode = ($prefix ?: 'MPL').'-'.rand(100, 999);
                    }

                    $counter = 1;
                    $baseKode = $kode;
                    while (MataPelajaran::withTrashed()->where('kode', $kode)->exists()) {
                        $kode = "{$baseKode}-{$counter}";
                        $counter++;
                    }

                    MataPelajaran::create([
                        'nama' => $nama,
                        'kode' => $kode,
                        'jenis' => $jenis,
                    ]);
                    $successCount++;
                }
            } catch (\Throwable $e) {
                $errorCount++;
            }
        };

        if (method_exists($spoutReader, 'getSheetIterator')) {
            $spoutReader->open($filePath);
            foreach ($spoutReader->getSheetIterator() as $sheet) {
                $sheetName = strtolower(trim($sheet->getName()));
                // If there are multiple sheets, skip sheets that are clearly teachers or schedule
                if (in_array($sheetName, ['available teachers', 'daftar guru', 'teachers', 'guru'])) {
                    continue;
                }

                $headerFound = false;
                $namaIndex = $kodeIndex = $jenisIndex = -1;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowProperties = [];
                    foreach ($row->getCells() as $cell) {
                        $rowProperties[] = $cell->getValue();
                    }
                    $processRow($rowProperties, $headerFound, $namaIndex, $kodeIndex, $jenisIndex);
                }
            }
            $spoutReader->close();
        } else {
            // For CSV
            $headerFound = false;
            $namaIndex = $kodeIndex = $jenisIndex = -1;
            $reader->noHeaderRow()->getRows()->each(function (array $rowProperties) use ($processRow, &$headerFound, &$namaIndex, &$kodeIndex, &$jenisIndex) {
                $processRow($rowProperties, $headerFound, $namaIndex, $kodeIndex, $jenisIndex);
            });
        }

        $message = "Import selesai. {$successCount} mata pelajaran berhasil diimport";
        if ($updatedCount > 0) {
            $message .= ", {$updatedCount} data mata pelajaran diperbarui";
        }
        $message .= '.';
        if ($errorCount > 0) {
            $message .= " ({$errorCount} baris tidak valid dilewati).";
        }

        return redirect()->route('admin.mata-pelajaran.index')->with('success', $message);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_mapel_').'.xlsx';
        $writer = SimpleExcelWriter::create($tempPath);

        $writer->addRow([
            'Nama' => 'Matematika',
            'Kode' => 'MTK',
            'Jenis' => 'umum',
        ]);

        $writer->addRow([
            'Nama' => 'Rekayasa Perangkat Lunak',
            'Kode' => 'RPL-01',
            'Jenis' => 'produktif',
        ]);

        $writer->close();

        return response()->download($tempPath, 'template_mata_pelajaran.xlsx')->deleteFileAfterSend(true);
    }
}

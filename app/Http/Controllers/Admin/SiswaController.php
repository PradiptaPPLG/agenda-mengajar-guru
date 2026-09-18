<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiswaProfile;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = SiswaProfile::with(['user', 'kelas']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('nis', 'like', "%{$search}%");
        }

        if ($request->filled('kelas_id')) {
            if ($request->kelas_id === 'null') {
                $query->whereNull('kelas_id');
            } else {
                $query->where('kelas_id', $request->kelas_id);
            }
        }

        $siswa = $query->paginate(20)->withQueryString();
        $kelasList = Kelas::orderBy('nama')->get();

        return view('admin.siswa.index', compact('siswa', 'kelasList'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $file = $request->file('excel_file');
        
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return back()->with('error', 'File kosong.');
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map('strtolower', $header);
        $header = array_map('trim', $header);

        $nameIndex = -1;
        $emailIndex = -1;
        $nisIndex = -1;

        foreach ($header as $index => $colName) {
            if (str_contains($colName, 'nama')) $nameIndex = $index;
            if (str_contains($colName, 'email')) $emailIndex = $index;
            if (str_contains($colName, 'nis')) $nisIndex = $index;
        }

        if ($nameIndex === -1) {
            return back()->with('error', 'Tidak ditemukan kolom dengan kata "nama".');
        }

        $imported = 0;
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (empty(array_filter($data))) continue;

            $nama = trim($data[$nameIndex] ?? '');
            if (!$nama) continue;

            $email = $emailIndex !== -1 ? trim($data[$emailIndex] ?? '') : '';
            if (!$email) {
                $email = Str::slug($nama) . rand(100, 999) . '@siswa.sekolah.sch.id';
            }

            $nis = $nisIndex !== -1 ? trim($data[$nisIndex] ?? '') : null;

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    'password' => Hash::make('password'),
                    'role' => 'siswa'
                ]
            );

            SiswaProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $nis
                ]
            );
            $imported++;
        }

        return redirect()->route('admin.siswa.index')->with('success', "Berhasil mengimpor $imported siswa.");
    }
}

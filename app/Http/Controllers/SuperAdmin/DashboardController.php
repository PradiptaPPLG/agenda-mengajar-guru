<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_guru' => User::where('role', 'guru')->count(),
            'total_siswa' => User::where('role', 'siswa')->count(),
            'total_kelas' => Kelas::count(),
            'total_admin' => User::where('role', 'admin')->count(),
            'pertemuan_hari_ini' => Pertemuan::whereDate('tanggal', Carbon::today())->count(),
        ];

        return view('super-admin.dashboard', compact('stats'));
    }
}

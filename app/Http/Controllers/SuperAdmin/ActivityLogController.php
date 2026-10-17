<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', strtoupper($request->action));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->simplePaginate(25)->withQueryString();

        $availableActions = Cache::remember('activity_log:available_actions', 3600, function () {
            $actions = ActivityLog::select('action')->distinct()->pluck('action');

            return $actions->isNotEmpty()
                ? $actions
                : collect(['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'IMPERSONATE', 'RESTORE']);
        });

        $availableModules = Cache::remember('activity_log:available_modules', 3600, function () {
            $modules = ActivityLog::select('module')->distinct()->pluck('module');

            return $modules->isNotEmpty()
                ? $modules
                : collect(['Auth', 'Jadwal', 'Kelas', 'Siswa', 'Guru', 'Agenda Mengajar', 'Settings']);
        });

        $availableRoles = ['super_admin', 'admin', 'guru', 'siswa', 'piket', 'tu', 'kepala_sekolah', 'pengawas'];

        return view('super-admin.activity-logs.index', compact(
            'logs',
            'availableActions',
            'availableModules',
            'availableRoles'
        ));
    }
}

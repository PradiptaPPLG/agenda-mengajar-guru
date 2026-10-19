<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Category definitions mapping to modules and actions.
     *
     * @var array<string, array{key: string, label: string, badge: string, dot: string, description: string, modules: array<int, string>, actions: array<int, string>}>
     */
    public const CATEGORIES = [
        'keamanan' => [
            'key' => 'keamanan',
            'label' => 'Keamanan & Akun',
            'badge' => 'bg-blue-100 text-blue-700 border-blue-200',
            'dot' => 'bg-blue-500',
            'description' => 'Log aktivitas login, logout, impersonasi, dan mutasi akun/hak akses pengguna.',
            'modules' => ['Auth', 'Pengguna', 'Role', 'Permission', 'User'],
            'actions' => ['LOGIN', 'IMPERSONATE'],
        ],
        'akademik' => [
            'key' => 'akademik',
            'label' => 'Akademik & KBM',
            'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
            'description' => 'Log jadwal pelajaran, kalender pendidikan, hari libur, sistem blok, dan jurnal KBM guru.',
            'modules' => ['Jadwal', 'Agenda Mengajar', 'Pertemuan', 'Hari Libur', 'Kalender Blok', 'Pemetaan Blok', 'Mata Pelajaran', 'Guru'],
            'actions' => [],
        ],
        'kesiswaan' => [
            'key' => 'kesiswaan',
            'label' => 'Kesiswaan & Presensi',
            'badge' => 'bg-amber-100 text-amber-700 border-amber-200',
            'dot' => 'bg-amber-500',
            'description' => 'Log data peserta didik, kenaikan kelas, presensi kehadiran, teguran piket, dan broadcast sekolah.',
            'modules' => ['Siswa', 'Kelas', 'Kenaikan Kelas', 'Pengumuman', 'Teguran', 'Presensi'],
            'actions' => [],
        ],
        'sistem' => [
            'key' => 'sistem',
            'label' => 'Pengaturan & Sistem',
            'badge' => 'bg-purple-100 text-purple-700 border-purple-200',
            'dot' => 'bg-purple-500',
            'description' => 'Log konfigurasi sekolah, penetapan tahun ajaran, lock cut-off, dan pemulihan data dari sampah.',
            'modules' => ['Settings', 'Tahun Ajaran', 'Trash', 'System'],
            'actions' => ['RESTORE'],
        ],
    ];

    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->latest('created_at');

        $activeCategory = null;
        if ($request->filled('kategori') && isset(self::CATEGORIES[$request->kategori])) {
            $activeCategory = self::CATEGORIES[$request->kategori];
            $catModules = $activeCategory['modules'];
            $catActions = $activeCategory['actions'];

            $query->where(function ($q) use ($catModules, $catActions) {
                if (! empty($catModules)) {
                    $q->whereIn('module', $catModules);
                }
                if (! empty($catActions)) {
                    $q->orWhereIn('action', $catActions);
                }
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            if (is_string($request->action)) {
                $query->where('action', strtoupper($request->action));
            } elseif (is_array($request->action)) {
                $actions = array_filter(array_map('strtoupper', array_filter($request->action, 'is_string')));
                if (! empty($actions)) {
                    $query->whereIn('action', $actions);
                }
            }
        }

        if ($request->filled('module')) {
            if (is_string($request->module)) {
                $query->where('module', $request->module);
            } elseif (is_array($request->module)) {
                $modules = array_filter($request->module, 'is_string');
                if (! empty($modules)) {
                    $query->whereIn('module', $modules);
                }
            }
        }

        if ($request->filled('role')) {
            if (is_string($request->role)) {
                $query->where('role', $request->role);
            } elseif (is_array($request->role)) {
                $roles = array_filter($request->role, 'is_string');
                if (! empty($roles)) {
                    $query->whereIn('role', $roles);
                }
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->simplePaginate(25)->withQueryString();

        // Purge legacy cache keys that may contain corrupted or nested structures
        Cache::forget('activity_log:available_actions');
        Cache::forget('activity_log:available_modules');

        $availableActions = Cache::remember('activity_log:available_actions_v3', 1800, function () {
            $actions = ActivityLog::select('action')->distinct()->pluck('action');

            $list = $actions->isNotEmpty()
                ? $actions
                : collect(['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'IMPERSONATE', 'RESTORE']);

            return collect($list)
                ->flatten()
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->unique()
                ->values()
                ->all();
        });

        $availableActions = collect($availableActions)
            ->flatten()
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->unique()
            ->values();

        if ($availableActions->isEmpty()) {
            $availableActions = collect(['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'IMPERSONATE', 'RESTORE']);
        }

        $availableModules = Cache::remember('activity_log:available_modules_v3', 1800, function () {
            $modules = ActivityLog::select('module')->distinct()->pluck('module');

            $list = $modules->isNotEmpty()
                ? $modules
                : collect(['Auth', 'Jadwal', 'Kelas', 'Siswa', 'Guru', 'Agenda Mengajar', 'Settings']);

            return collect($list)
                ->flatten()
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->unique()
                ->values()
                ->all();
        });

        $availableModules = collect($availableModules)
            ->flatten()
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->unique()
            ->values();

        if ($availableModules->isEmpty()) {
            $availableModules = collect(['Auth', 'Jadwal', 'Kelas', 'Siswa', 'Guru', 'Agenda Mengajar', 'Settings']);
        }

        $availableRoles = ['super_admin', 'admin', 'guru', 'siswa', 'piket', 'tu', 'kepala_sekolah', 'pengawas'];
        $categories = self::CATEGORIES;

        return view('super-admin.activity-logs.index', compact(
            'logs',
            'availableActions',
            'availableModules',
            'availableRoles',
            'categories',
            'activeCategory'
        ));
    }
}

<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HariLiburController as AdminHariLiburController;
use App\Http\Controllers\Admin\JadwalController as AdminJadwalController;
use App\Http\Controllers\Admin\KalenderBlokController as AdminKalenderBlokController;
use App\Http\Controllers\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Admin\KelasSiswaController;
use App\Http\Controllers\Admin\MataPelajaranController as AdminMataPelajaranController;
use App\Http\Controllers\Admin\PemetaanBlokController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PermissionCategoryController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Guru\BkController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\KaprogController;
use App\Http\Controllers\Guru\NotificationController;
use App\Http\Controllers\Guru\PertemuanController as GuruPertemuanController;
use App\Http\Controllers\Guru\WaliKelasController;
use App\Http\Controllers\KepalaSekolah\DashboardController as KsDashboardController;
use App\Http\Controllers\KepalaSekolah\PdfController as KsPdfController;
use App\Http\Controllers\KepalaSekolah\ReportController as KsReportController;
use App\Http\Controllers\Piket\DashboardController;
use App\Http\Controllers\Piket\TeguranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\Siswa\CaptureController as SiswaCaptureController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\SuperAdmin\DashboardController as SaDashboardController;
use App\Http\Controllers\SuperAdmin\SettingsController as SaSettingsController;
use Illuminate\Support\Facades\Route;

// Root landing public
Route::get('/', [PublicDashboardController::class, 'index'])->name('home');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Profile & Panduan
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/panduan', function () {
        return view('panduan');
    })->name('panduan');
});

// ─── Guru ───────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');

    // Pertemuan: show by jadwal + date, update materi/penugasan
    Route::get('/jadwal/{jadwal}/pertemuan/{tanggal}', [GuruPertemuanController::class, 'show'])
        ->name('pertemuan.show');
    Route::patch('/pertemuan/{pertemuan}/save-all', [GuruPertemuanController::class, 'saveAll'])
        ->name('pertemuan.save-all');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Multi-Role untuk Guru
    Route::get('/bk', [BkController::class, 'index'])->name('bk.index');
    Route::get('/wali-kelas', [WaliKelasController::class, 'index'])->name('wali-kelas.index');
    Route::get('/kaprog', [KaprogController::class, 'index'])->name('kaprog.index');
});

// ─── Siswa ───────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');

    Route::get('/jadwal/{jadwal}/capture/{tanggal}', [SiswaCaptureController::class, 'show'])
        ->name('capture.show');
    Route::post('/jadwal/{jadwal}/capture/{tanggal}', [SiswaCaptureController::class, 'store'])
        ->name('capture.store');
});

// ─── Admin ───────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('users/template', [AdminUserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('users/import', [AdminUserController::class, 'import'])->name('users.import');
    Route::post('users/bulk-destroy', [AdminUserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::resource('users', AdminUserController::class)->except(['show']);

    // Manajemen Semua Pengguna (Role & Spatie Role)
    Route::resource('pengguna', PenggunaController::class)->only(['index', 'edit', 'update']);

    // Role & Permission Management
    Route::resource('permission-categories', PermissionCategoryController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);

    // Kelas Management
    Route::post('/kelas/bulk-destroy', [AdminKelasController::class, 'bulkDestroy'])->name('kelas.bulk-destroy');
    Route::resource('kelas', AdminKelasController::class)
        ->parameters(['kelas' => 'kelas']);
    Route::post('/kelas/{kelas}/sync-siswa', [KelasSiswaController::class, 'sync'])->name('kelas.siswa.sync');
    Route::delete('/kelas/{kelas}/remove-siswa/{siswa}', [KelasSiswaController::class, 'remove'])->name('kelas.siswa.remove');
    Route::get('/kelas/{kelas}/siswa/template', [KelasSiswaController::class, 'downloadTemplate'])->name('kelas.siswa.template');
    Route::post('/kelas/{kelas}/import-siswa', [KelasSiswaController::class, 'import'])->name('kelas.siswa.import');

    // Siswa Management
    Route::post('siswa/bulk-destroy', [SiswaController::class, 'bulkDestroy'])->name('siswa.bulk-destroy');
    Route::get('siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('siswa/template', [SiswaController::class, 'downloadTemplate'])->name('siswa.template');
    Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
    Route::post('siswa/deactivate-all', [SiswaController::class, 'deactivateAll'])->name('siswa.deactivate-all');
    Route::post('siswa/{user}/toggle-active', [SiswaController::class, 'toggleActive'])->name('siswa.toggle-active');

    Route::post('mata-pelajaran/bulk-destroy', [AdminMataPelajaranController::class, 'bulkDestroy'])->name('mata-pelajaran.bulk-destroy');
    Route::resource('mata-pelajaran', AdminMataPelajaranController::class)
        ->parameters(['mata-pelajaran' => 'mataPelajaran'])
        ->except(['show']);
    Route::get('jadwal/export/excel', [AdminJadwalController::class, 'exportExcel'])->name('jadwal.export.excel');
    Route::get('jadwal/export/pdf', [AdminJadwalController::class, 'exportPdf'])->name('jadwal.export.pdf');
    Route::get('jadwal/template', [AdminJadwalController::class, 'downloadTemplate'])->name('jadwal.template');
    Route::post('jadwal/import', [AdminJadwalController::class, 'import'])->name('jadwal.import');
    Route::post('jadwal/bulk-destroy', [AdminJadwalController::class, 'bulkDestroy'])->name('jadwal.bulk-destroy');
    Route::resource('jadwal', AdminJadwalController::class)->except(['show']);
    Route::resource('hari-libur', AdminHariLiburController::class)->only(['index', 'store', 'destroy']);

    // Kalender Blok Sistem Jadwal A/B
    Route::prefix('kalender-blok')->name('kalender-blok.')->group(function () {
        Route::get('/', [AdminKalenderBlokController::class, 'index'])->name('index');
        Route::post('/generate', [AdminKalenderBlokController::class, 'generate'])->name('generate');
        Route::delete('/destroy-group', [AdminKalenderBlokController::class, 'destroyGroup'])->name('destroy-group');
        Route::delete('/{kalenderBlok}', [AdminKalenderBlokController::class, 'destroy'])->name('destroy');
    });

    // Pemetaan Blok Terpusat
    Route::prefix('pemetaan-blok')->name('pemetaan-blok.')->group(function () {
        Route::get('/', [PemetaanBlokController::class, 'index'])->name('index');
        Route::post('/update', [PemetaanBlokController::class, 'update'])->name('update');
        Route::get('/{kelas}/siswa', [PemetaanBlokController::class, 'getSiswa'])->name('siswa');
        Route::post('/{kelas}/siswa', [PemetaanBlokController::class, 'updateSiswa'])->name('update-siswa');
    });

    // Toggle sistem blok untuk kelas
    Route::match(['post', 'patch'], '/kelas/{kelas}/toggle-blok', [AdminKelasController::class, 'toggleBlok'])->name('kelas.toggle-blok');
    Route::match(['post', 'patch'], '/kelas/{kelas}/update-blok', [AdminKelasController::class, 'updateBlok'])->name('kelas.update-blok');
});

// ─── Guru / Petugas Piket ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:piket,admin,super_admin'])
    ->prefix('piket')
    ->name('piket.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/tegur', [TeguranController::class, 'store'])->name('teguran.store');
    });

// ─── Tata Usaha (TU) ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:tu,super_admin,admin'])
    ->prefix('tu')
    ->name('tu.')
    ->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Tu\DashboardController::class, 'index'])->name('dashboard');
        // Bisa tambahkan route export disini
    });

// ─── Kepala Sekolah ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:kepala_sekolah,super_admin'])
    ->prefix('kepala-sekolah')
    ->name('kepala-sekolah.')
    ->group(function () {
        Route::get('/dashboard', [KsDashboardController::class, 'index'])->name('dashboard');

        Route::get('/report/guru', [KsReportController::class, 'guru'])->name('report.guru');
        Route::get('/report/siswa', [KsReportController::class, 'siswa'])->name('report.siswa');

        Route::get('/pdf/guru', [KsPdfController::class, 'guru'])->name('pdf.guru');
        Route::get('/pdf/siswa', [KsPdfController::class, 'siswa'])->name('pdf.siswa');
    });

// ─── Super Admin ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [SaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [SaSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SaSettingsController::class, 'update'])->name('settings.update');
});

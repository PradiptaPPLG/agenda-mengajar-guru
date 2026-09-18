<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JadwalController as AdminJadwalController;
use App\Http\Controllers\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Admin\MataPelajaranController as AdminMataPelajaranController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\KehadiranController as GuruKehadiranController;
use App\Http\Controllers\Guru\KehadiranSiswaController as GuruKehadiranSiswaController;
use App\Http\Controllers\Guru\PertemuanController as GuruPertemuanController;
use App\Http\Controllers\KepalaSekolah\DashboardController as KsDashboardController;
use App\Http\Controllers\KepalaSekolah\PdfController as KsPdfController;
use App\Http\Controllers\KepalaSekolah\ReportController as KsReportController;
use App\Http\Controllers\Siswa\CaptureController as SiswaCaptureController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\SuperAdmin\DashboardController as SaDashboardController;
use App\Http\Controllers\SuperAdmin\SettingsController as SaSettingsController;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', fn () => redirect()->route('login'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Profile & Panduan
Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    
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

    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::post('users/import', [AdminUserController::class, 'import'])->name('users.import');
    
    // Kelas Management
    Route::resource('kelas', AdminKelasController::class)
        ->parameters(['kelas' => 'kelas'])
        ->except(['show']);
    Route::post('/kelas/{kelas}/sync-siswa', [\App\Http\Controllers\Admin\KelasSiswaController::class, 'sync'])->name('kelas.siswa.sync');
    Route::delete('/kelas/{kelas}/remove-siswa/{siswa}', [\App\Http\Controllers\Admin\KelasSiswaController::class, 'remove'])->name('kelas.siswa.remove');
    Route::post('/kelas/{kelas}/import-siswa', [\App\Http\Controllers\Admin\KelasSiswaController::class, 'import'])->name('kelas.siswa.import');

    // Siswa Management
    Route::get('siswa', [\App\Http\Controllers\Admin\SiswaController::class, 'index'])->name('siswa.index');
    Route::post('siswa/import', [\App\Http\Controllers\Admin\SiswaController::class, 'import'])->name('siswa.import');
    Route::post('siswa/deactivate-all', [\App\Http\Controllers\Admin\SiswaController::class, 'deactivateAll'])->name('siswa.deactivate-all');
    Route::post('siswa/{user}/toggle-active', [\App\Http\Controllers\Admin\SiswaController::class, 'toggleActive'])->name('siswa.toggle-active');

    Route::resource('mata-pelajaran', AdminMataPelajaranController::class)
        ->parameters(['mata-pelajaran' => 'mataPelajaran'])
        ->except(['show']);
    Route::resource('jadwal', AdminJadwalController::class)->except(['show']);
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

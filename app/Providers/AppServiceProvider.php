<?php

namespace App\Providers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use App\Observers\AuditTrailObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }

        // Audit Trail Observers
        User::observe(AuditTrailObserver::class);
        Kelas::observe(AuditTrailObserver::class);
        JadwalPelajaran::observe(AuditTrailObserver::class);
        Pertemuan::observe(AuditTrailObserver::class);
    }
}

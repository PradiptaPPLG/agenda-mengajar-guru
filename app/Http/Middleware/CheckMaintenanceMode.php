<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * Cek apakah sistem dalam Maintenance Mode (pemeliharaan/tutup tahun ajaran).
     * Jika aktif, hanya Admin & Super Admin yang diperbolehkan mengakses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = (string) Setting::get('maintenance_mode', '0') === '1';

        if (! $isMaintenance) {
            return $next($request);
        }

        // Izinkan route login, logout, dan area administrasi agar Admin tidak terkunci (lockout)
        if (
            $request->is('login') ||
            $request->is('login/*') ||
            $request->is('logout') ||
            $request->is('super-admin*') ||
            $request->is('admin*')
        ) {
            return $next($request);
        }

        // Jika sedang dalam sesi impersonasi oleh Super Admin, izinkan akses
        if ($request->hasSession() && $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        $user = $request->user();

        // Jika user adalah super admin atau admin, biarkan lewat
        if ($user && ($user->isSuperAdmin() || $user->role === 'admin' || $user->hasRole('admin'))) {
            return $next($request);
        }

        $maintenanceMessage = Setting::get(
            'maintenance_message',
            'Sistem sedang dalam masa pemeliharaan / proses pergantian semester. Akses guru dan siswa ditutup sementara hingga proses selesai.'
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $maintenanceMessage,
                'maintenance_mode' => true,
            ], 503);
        }

        return response()->view('errors.maintenance', [
            'message' => $maintenanceMessage,
        ], 503);
    }
}

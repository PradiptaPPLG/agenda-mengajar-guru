<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAcademicPeriodLock
{
    /**
     * Handle an incoming request.
     * Cegah mutasi data (POST, PUT, PATCH, DELETE) pada agenda mengajar & presensi
     * jika periode akademik aktif sedang dikunci (Cut-Off), kecuali Super Admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya cek untuk metode mutasi data
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $user = $request->user();

            // Super Admin memiliki hak bypass kuncian cut-off
            if ($user && $user->isSuperAdmin()) {
                return $next($request);
            }

            $activePeriod = AcademicYear::getActive();

            if ($activePeriod && $activePeriod->is_locked) {
                $message = "Periode Akademik {$activePeriod->full_period_label} telah dikunci (Cut-Off) oleh Super Admin. Seluruh data presensi dan agenda mengajar hanya bersifat Read-Only.";

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => $message,
                        'is_locked' => true,
                    ], 403);
                }

                return back()->with('error', $message);
            }
        }

        return $next($request);
    }
}

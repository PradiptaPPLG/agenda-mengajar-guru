<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'identifier' => 'Akun Anda sedang dinonaktifkan. Hubungi Administrator atau Wali Kelas.',
            ]);
        }

        if (! in_array($user->role, $roles)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki hak untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}

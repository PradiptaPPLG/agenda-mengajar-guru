<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $identifier = trim($credentials['identifier']);
        $password = $credentials['password'];

        $user = User::where(function ($query) use ($identifier) {
            $query->whereHas('guruProfile', function ($q) use ($identifier) {
                $q->where('nip', $identifier);
            })
                ->orWhere('email', $identifier)
                ->orWhereHas('siswaProfile', function ($q) use ($identifier) {
                    $q->where('nis', $identifier);
                });
        })->first();

        if ($user && Hash::check($password, $user->password)) {
            if (! $user->is_active) {
                return back()->withErrors([
                    'identifier' => 'Akun Anda sedang dinonaktifkan.',
                ])->onlyInput('identifier');
            }

            // Jika sistem sedang mode pemeliharaan, hanya admin & super_admin yang dapat login
            $isMaintenance = (string) Setting::get('maintenance_mode', '0') === '1';
            $isAdmin = $user->isSuperAdmin() || $user->role === 'admin' || (method_exists($user, 'hasRole') && $user->hasRole('admin'));
            if ($isMaintenance && ! $isAdmin) {
                $maintenanceMsg = Setting::get('maintenance_message', 'Sistem sedang dalam masa pemeliharaan. Akses sementara ditutup.');

                return back()->withErrors([
                    'identifier' => $maintenanceMsg,
                ])->onlyInput('identifier');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            ActivityLogger::log(
                'LOGIN',
                'Auth',
                "Pengguna {$user->name} ({$user->role_label}) berhasil masuk ke sistem."
            );

            return $this->redirectByRole($user->role);
        }

        return back()->withErrors([
            'identifier' => 'Email/NIP/NIS atau password salah.',
        ])->onlyInput('identifier');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole(string $role): RedirectResponse
    {
        return match ($role) {
            'super_admin' => redirect()->route('super-admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'kepala_sekolah', 'pengawas' => redirect()->route('kepala-sekolah.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            'siswa' => redirect()->route('siswa.dashboard'),
            'piket' => redirect()->route('piket.dashboard'),
            'tu' => redirect()->route('tu.dashboard'),
            default => redirect()->route('login'),
        };
    }
}

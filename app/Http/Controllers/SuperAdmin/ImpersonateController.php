<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Super Admin mulai login sebagai user lain.
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $superAdmin = Auth::user();

        // Keamanan: Hanya Super Admin asli yang bisa melakukan impersonasi
        if (! $superAdmin || ! $superAdmin->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang diizinkan menggunakan fitur penyamaran akun.');
        }

        // Cegah impersonasi diri sendiri atau sesama Super Admin
        if ($superAdmin->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menyamar sebagai akun Anda sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Tidak dapat menyamar sebagai sesama Super Admin.');
        }

        ActivityLogger::log(
            'IMPERSONATE',
            'Auth',
            "Super Admin {$superAdmin->name} masuk sebagai pengguna: {$user->name} ({$user->role_label})"
        );

        // Login sebagai target user dan pastikan impersonator_id tersimpan aman di sesi baru
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $superAdmin->id);

        // Redirect sesuai role user target
        return match ($user->role) {
            'guru' => redirect()->route('guru.dashboard')->with('success', "Berhasil masuk sebagai {$user->name}."),
            'siswa' => redirect()->route('siswa.dashboard')->with('success', "Berhasil masuk sebagai {$user->name}."),
            'kepala_sekolah', 'pengawas' => redirect()->route('kepala-sekolah.dashboard')->with('success', "Berhasil masuk sebagai {$user->name}."),
            'piket' => redirect()->route('piket.dashboard')->with('success', "Berhasil masuk sebagai {$user->name}."),
            'tu' => redirect()->route('tu.dashboard')->with('success', "Berhasil masuk sebagai {$user->name}."),
            default => redirect()->route('admin.dashboard')->with('success', "Berhasil masuk sebagai {$user->name}."),
        };
    }

    /**
     * Keluar dari mode penyamaran dan kembali ke akun Super Admin.
     */
    public function leave(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('home')->with('error', 'Sesi penyamaran tidak aktif.');
        }

        $superAdmin = User::find($impersonatorId);
        if (! $superAdmin || ! $superAdmin->isSuperAdmin()) {
            $request->session()->forget('impersonator_id');
            Auth::logout();

            return redirect()->route('login')->with('error', 'Akun Super Admin tidak valid.');
        }

        $impersonatedUser = Auth::user();

        // Kembalikan login ke Super Admin
        Auth::login($superAdmin);
        $request->session()->forget('impersonator_id');
        $request->session()->regenerate();

        ActivityLogger::log(
            'IMPERSONATE',
            'Auth',
            "Super Admin {$superAdmin->name} keluar dari penyamaran akun {$impersonatedUser?->name} dan kembali ke sesi Super Admin."
        );

        return redirect()->route('super-admin.dashboard')->with('success', 'Kembali ke sesi Super Admin.');
    }
}

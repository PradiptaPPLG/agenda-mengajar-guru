<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /** @var array<int, string> */
    private array $settingKeys = [
        // Identitas Lembaga
        'school_name',
        'school_address',
        'principal_name',
        'school_year',
        'semester',
        'phone',

        // Presensi & Agenda
        'enable_checkout_foto',
        'default_siswa_status',
        'toleransi_keterlambatan_menit',
        'toleransi_mapel_pertama_menit',
        'toleransi_mapel_lanjutan_menit',

        // Aturan Pengisian Agenda & Susulan (Backdate)
        'allow_backdate_agenda',
        'max_backdate_days',

        // Geofencing & Jaringan Wi-Fi Sekolah
        'enable_geofencing',
        'school_latitude',
        'school_longitude',
        'school_radius_meter',
        'allowed_school_ips',

        // Mode Pemeliharaan (Maintenance)
        'maintenance_mode',
        'maintenance_message',
    ];

    public function index(): View
    {
        $settings = [];
        foreach ($this->settingKeys as $key) {
            $defaultVal = match ($key) {
                'default_siswa_status' => 'aktif',
                'toleransi_keterlambatan_menit' => '10',
                'toleransi_mapel_pertama_menit' => '10',
                'toleransi_mapel_lanjutan_menit' => '15',
                'allow_backdate_agenda' => '1',
                'max_backdate_days' => '3',
                'enable_geofencing' => '0',
                'school_latitude' => '-6.914744',
                'school_longitude' => '107.609810',
                'school_radius_meter' => '150',
                'allowed_school_ips' => '',
                'maintenance_mode' => '0',
                'maintenance_message' => 'Sistem sedang dalam masa pemeliharaan data / transisi semester. Akses guru dan siswa ditutup sementara.',
                default => '',
            };
            $settings[$key] = Setting::get($key, $defaultVal);
        }

        $totalSiswa = User::where('role', 'siswa')->count();
        $totalSiswaAktif = User::where('role', 'siswa')->where('is_active', true)->count();

        return view('super-admin.settings', compact('settings', 'totalSiswa', 'totalSiswaAktif'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Identitas Lembaga
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:1,2'],
            'phone' => ['nullable', 'string', 'max:20'],

            // Siswa & Toleransi
            'enable_checkout_foto' => ['nullable', 'in:0,1'],
            'default_siswa_status' => ['required', 'in:aktif,nonaktif'],
            'toleransi_keterlambatan_menit' => ['nullable', 'integer', 'min:0', 'max:60'],
            'toleransi_mapel_pertama_menit' => ['nullable', 'integer', 'min:0', 'max:60'],
            'toleransi_mapel_lanjutan_menit' => ['nullable', 'integer', 'min:0', 'max:60'],
            'apply_to_existing_siswa' => ['nullable', 'in:0,1'],

            // Aturan Pengisian Agenda & Backdate
            'allow_backdate_agenda' => ['nullable', 'in:0,1'],
            'max_backdate_days' => ['required', 'integer', 'min:0', 'max:30'],

            // Geofencing & IP
            'enable_geofencing' => ['nullable', 'in:0,1'],
            'school_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'school_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'school_radius_meter' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'allowed_school_ips' => ['nullable', 'string', 'max:1000'],

            // Mode Pemeliharaan
            'maintenance_mode' => ['nullable', 'in:0,1'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['enable_checkout_foto'] = $request->has('enable_checkout_foto') ? '1' : '0';
        $validated['allow_backdate_agenda'] = $request->has('allow_backdate_agenda') ? '1' : '0';
        $validated['enable_geofencing'] = $request->has('enable_geofencing') ? '1' : '0';
        $validated['maintenance_mode'] = $request->has('maintenance_mode') ? '1' : '0';

        // Sync legacy toleransi_keterlambatan_menit with toleransi_mapel_pertama_menit if provided
        if (isset($validated['toleransi_mapel_pertama_menit'])) {
            $validated['toleransi_keterlambatan_menit'] = $validated['toleransi_mapel_pertama_menit'];
        } elseif (isset($validated['toleransi_keterlambatan_menit'])) {
            $validated['toleransi_mapel_pertama_menit'] = $validated['toleransi_keterlambatan_menit'];
        }

        $applyToExisting = $request->has('apply_to_existing_siswa');
        $resetPasswords = $request->has('reset_passwords_to_nis');
        unset($validated['apply_to_existing_siswa']);

        $oldSettings = [];
        foreach ($validated as $key => $value) {
            $oldSettings[$key] = Setting::get($key);
            Setting::set($key, (string) $value);
        }

        // Catat Audit Log untuk perubahan konfigurasi sistem
        ActivityLogger::log(
            'UPDATE',
            'Settings',
            'Memperbarui konfigurasi global sistem Super Admin',
            $oldSettings,
            $validated
        );

        $messages = [];

        if ($applyToExisting) {
            $isActive = $validated['default_siswa_status'] === 'aktif';
            $count = User::where('role', 'siswa')->update(['is_active' => $isActive]);
            $statusLabel = $isActive ? 'diaktifkan' : 'dinonaktifkan';
            $messages[] = "seluruh {$count} akun siswa berhasil {$statusLabel}";
        }

        if ($resetPasswords) {
            set_time_limit(600);
            $siswas = User::where('role', 'siswa')->with('siswaProfile')->get();
            $pwCount = 0;
            foreach ($siswas as $siswa) {
                $nis = trim((string) ($siswa->siswaProfile?->nis ?? ''));
                if ($nis !== '' && $nis !== '-') {
                    $cleanNis = preg_replace('/\s+/', '', $nis);
                    $siswa->password = Hash::make($cleanNis);
                    $siswa->save();
                    $pwCount++;
                }
            }
            $messages[] = "password {$pwCount} siswa berhasil direset ke NIS masing-masing";
        }

        if (! empty($messages)) {
            return back()->with('success', 'Pengaturan berhasil disimpan dan '.implode(' serta ', $messages).'.');
        }

        return back()->with('success', 'Konfigurasi sistem berhasil diperbarui.');
    }
}

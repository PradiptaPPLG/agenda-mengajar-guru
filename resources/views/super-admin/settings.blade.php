<x-layouts.admin title="Konfigurasi Global Sistem">
    <div class="space-y-6 max-w-5xl">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-sky-600 uppercase tracking-wider mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Pengaturan Parameter & Kebijakan Aplikasi</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Konfigurasi Global Sistem</h1>
                <p class="text-sm text-slate-500 mt-0.5">Atur aturan bisnis presensi, batasan lokasi/jaringan, izin pengisian agenda susulan, serta mode pemeliharaan.</p>
            </div>
        </div>

        <!-- Alert Notification -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-xs font-semibold">Tutup</button>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Tutup</button>
            </div>
        @endif

        <form action="{{ route('super-admin.settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 1. MODE PEMELIHARAAN (MAINTENANCE MODE) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-500 text-white rounded-xl shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Mode Pemeliharaan (Maintenance Mode)</h2>
                            <p class="text-xs text-slate-500">Tutup sementara akses login Guru & Siswa selama pemrosesan kenaikan kelas atau rotasi blok</p>
                        </div>
                    </div>
                    @if ($settings['maintenance_mode'] === '1')
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                            SEDANG AKTIF
                        </span>
                    @else
                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600">
                            Normal (Nonaktif)
                        </span>
                    @endif
                </div>

                <div class="p-6 space-y-4">
                    <div class="p-4 bg-amber-50/60 border border-amber-200/80 rounded-xl">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="maintenance_mode" value="1"
                                   {{ old('maintenance_mode', $settings['maintenance_mode']) == '1' ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-bold text-amber-950">Aktifkan Mode Pemeliharaan Sistem</span>
                                <p class="text-xs text-amber-800/90 mt-0.5 leading-relaxed">
                                    Ketika aktif, guru dan siswa yang mengakses aplikasi akan langsung dialihkan ke halaman pemeliharaan (HTTP 503). Hanya akun <strong>Super Admin</strong> dan <strong>Admin</strong> yang dapat login dan melakukan konfigurasi.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pesan Pengumuman Pemeliharaan</label>
                        <textarea name="maintenance_message" rows="2" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition resize-none">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                        <span class="text-[11px] text-slate-400 mt-1 block">Pesan ini akan ditampilkan kepada Guru & Siswa pada layar informasi pemeliharaan.</span>
                    </div>
                </div>
            </div>

            <!-- 2. ATURAN PENGISIAN AGENDA MENGAJAR & SUSULAN (BACKDATE) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center gap-3">
                    <div class="p-2 bg-sky-600 text-white rounded-xl shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Kebijakan Pengisian Agenda & Susulan (Backdate)</h2>
                        <p class="text-xs text-slate-500">Kontrol apakah guru diizinkan mengisi agenda mengajar pada hari yang telah lewat</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="allow_backdate_agenda" value="1"
                                   {{ old('allow_backdate_agenda', $settings['allow_backdate_agenda']) == '1' ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <div>
                                <span class="text-sm font-bold text-slate-900">Izinkan Guru Mengisi Agenda Susulan (Backdate)</span>
                                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                    Jika diaktifkan, guru dapat membuka dan mengisi agenda atau presensi untuk tanggal pertemuan yang sudah lewat (sesuai batas toleransi maksimal di bawah). Jika dinonaktifkan, guru hanya dapat mengisi agenda tepat pada hari H jadwal berlangsung.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                            <label class="block text-xs font-bold text-slate-800 mb-1">Maksimal Toleransi Susulan</label>
                            <div class="flex items-center gap-2 mt-2">
                                <input type="number" name="max_backdate_days" min="0" max="30" value="{{ old('max_backdate_days', $settings['max_backdate_days']) }}" required class="w-24 px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                <span class="text-xs text-slate-600 font-medium">Hari ke belakang</span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">Contoh: 3 hari (Senin s/d Kamis)</span>
                        </div>

                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                            <label class="block text-xs font-bold text-slate-800 mb-1">Toleransi Mapel Pertama</label>
                            <div class="flex items-center gap-2 mt-2">
                                <input type="number" name="toleransi_mapel_pertama_menit" min="0" max="60" value="{{ old('toleransi_mapel_pertama_menit', $settings['toleransi_mapel_pertama_menit']) }}" required class="w-24 px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                <span class="text-xs text-slate-600 font-medium">Menit awal KBM</span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">Batas toleransi jam ke-1 pagi</span>
                        </div>

                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                            <label class="block text-xs font-bold text-slate-800 mb-1">Toleransi Mapel Lanjutan</label>
                            <div class="flex items-center gap-2 mt-2">
                                <input type="number" name="toleransi_mapel_lanjutan_menit" min="0" max="60" value="{{ old('toleransi_mapel_lanjutan_menit', $settings['toleransi_mapel_lanjutan_menit']) }}" required class="w-24 px-3 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                <span class="text-xs text-slate-600 font-medium">Menit pergantian jam</span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">Batas toleransi pergantian jam</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. GEOFENCING & VALIDASI JARINGAN SEKOLAH -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-600 text-white rounded-xl shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Geofencing & Validasi Presensi Lokasi</h2>
                            <p class="text-xs text-slate-500">Batasi pengisian absensi & foto bukti hanya di dalam koordinat sekolah atau IP Wi-Fi sekolah</p>
                        </div>
                    </div>
                    @if ($settings['enable_geofencing'] === '1')
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                            GEOFENCING AKTIF
                        </span>
                    @else
                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-500">
                            Nonaktif (Bebas)
                        </span>
                    @endif
                </div>

                <div class="p-6 space-y-4">
                    <div class="p-4 bg-emerald-50/60 border border-emerald-200/80 rounded-xl">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="enable_geofencing" value="1"
                                   {{ old('enable_geofencing', $settings['enable_geofencing']) == '1' ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-sm font-bold text-emerald-950">Wajibkan Verifikasi Radius Lokasi (Geofencing GPS)</span>
                                <p class="text-xs text-emerald-800/90 mt-0.5 leading-relaxed">
                                    Ketika aktif, browser perangkat siswa/guru akan meminta izin akses GPS saat mengambil foto/mengisi presensi. Jika posisi berada di luar radius koordinat sekolah, presensi akan ditolak.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Latitude Sekolah</label>
                            <input type="text" name="school_latitude" value="{{ old('school_latitude', $settings['school_latitude']) }}" placeholder="-6.914744" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                            <span class="text-[11px] text-slate-400 mt-1 block">Garis Lintang GPS</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Longitude Sekolah</label>
                            <input type="text" name="school_longitude" value="{{ old('school_longitude', $settings['school_longitude']) }}" placeholder="107.609810" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                            <span class="text-[11px] text-slate-400 mt-1 block">Garis Bujur GPS</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Radius Maksimal (Meter)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="school_radius_meter" min="10" max="5000" value="{{ old('school_radius_meter', $settings['school_radius_meter']) }}" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                <span class="text-xs text-slate-500 font-medium shrink-0">Meter</span>
                            </div>
                            <span class="text-[11px] text-slate-400 mt-1 block">Rekomendasi: 100 - 250 m</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">IP Wi-Fi Sekolah yang Diizinkan (Opsional)</label>
                        <input type="text" name="allowed_school_ips" value="{{ old('allowed_school_ips', $settings['allowed_school_ips']) }}" placeholder="Contoh: 192.168.1.1, 10.10.0.0/16, 202.152.1.20" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        <span class="text-[11px] text-slate-400 mt-1 block">Pisahkan dengan koma jika ada lebih dari satu IP publik / gateway router sekolah. Kosongkan jika tidak dibatasi IP.</span>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="enable_checkout_foto" value="1"
                                   {{ old('enable_checkout_foto', $settings['enable_checkout_foto']) == '1' ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <div>
                                <span class="text-sm font-bold text-slate-900">Wajibkan Foto Bukti Check-Out (Di Akhir KBM)</span>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Jika diaktifkan, siswa diwajibkan mengambil foto guru kembali saat jam pelajaran berakhir sebelum kelas selesai.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 4. IDENTITAS LEMBAGA & PERIODE AKADEMIK -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center gap-3">
                    <div class="p-2 bg-indigo-600 text-white rounded-xl shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Identitas Sekolah & Periode Aktif</h2>
                        <p class="text-xs text-slate-500">Profil institusi dan konfigurasi periode akademik default</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Sekolah <span class="text-rose-500">*</span></label>
                            <input type="text" name="school_name" value="{{ old('school_name', $settings['school_name']) }}" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Sekolah</label>
                            <textarea name="school_address" rows="2" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 resize-none">{{ old('school_address', $settings['school_address']) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Kepala Sekolah</label>
                            <input type="text" name="principal_name" value="{{ old('principal_name', $settings['principal_name']) }}" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">No. Telepon / Fax</label>
                            <input type="text" name="phone" value="{{ old('phone', $settings['phone']) }}" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Ajaran Aktif <span class="text-rose-500">*</span></label>
                            <input type="text" name="school_year" value="{{ old('school_year', $settings['school_year'] ?: \App\Models\Setting::getTahunAjaranAktif()) }}" pattern="\d{4}\/\d{4}" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Semester Aktif <span class="text-rose-500">*</span></label>
                            <select name="semester" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                <option value="1" {{ old('semester', $settings['semester']) == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                                <option value="2" {{ old('semester', $settings['semester']) == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. KEBIJAKAN AKUN SISWA & OPERASI MASSAL -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-600 text-white rounded-xl shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Kebijakan Akun Siswa</h2>
                            <p class="text-xs text-slate-500">Status bawaan akun dan sinkronisasi kredensial massal</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $totalSiswaAktif }} Aktif dari {{ $totalSiswa }} Siswa
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="relative flex items-center p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer transition-colors has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50/50 has-[:checked]:ring-1 has-[:checked]:ring-sky-600">
                            <input type="radio" name="default_siswa_status" value="aktif" {{ old('default_siswa_status', $settings['default_siswa_status']) === 'aktif' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Langsung Aktif
                                </span>
                                <span class="block text-xs text-slate-500 mt-0.5">Siswa baru otomatis dapat langsung login ke aplikasi.</span>
                            </div>
                        </label>

                        <label class="relative flex items-center p-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer transition-colors has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50/50 has-[:checked]:ring-1 has-[:checked]:ring-sky-600">
                            <input type="radio" name="default_siswa_status" value="nonaktif" {{ old('default_siswa_status', $settings['default_siswa_status']) === 'nonaktif' ? 'checked' : '' }} class="h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Nonaktif
                                </span>
                                <span class="block text-xs text-slate-500 mt-0.5">Akun dalam keadaan tertutup hingga diaktifkan admin/wali kelas.</span>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="p-3.5 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="apply_to_existing_siswa" value="1" class="mt-1 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <span class="text-sm font-bold text-amber-900">Terapkan status ke seluruh {{ $totalSiswa }} siswa yang ada</span>
                                    <p class="text-xs text-amber-700 mt-0.5">
                                        Centang jika ingin menyamakan status semua siswa terdaftar sekaligus dengan opsi di atas.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-xl">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="reset_passwords_to_nis" value="1" class="mt-1 h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-bold text-blue-900">Reset password seluruh {{ $totalSiswa }} siswa menjadi NIS masing-masing</span>
                                    <p class="text-xs text-blue-700 mt-0.5">
                                        Mempermudah siswa yang lupa kata sandi dengan mengembalikannya ke nomor induk siswa.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white text-sm font-bold rounded-xl shadow-sm shadow-sky-600/20 transition">
                    Simpan Semua Perubahan
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Mengajar — {{ \App\Models\Setting::get('school_name', 'Sekolah') }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased flex flex-col min-h-screen">
    {{-- Header Topbar Public --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs">
        <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 leading-tight">{{ \App\Models\Setting::get('school_name', 'SMK Negeri 1 Ciamis') }}</h1>
                </div>
            </div>

            {{-- Right Navigation / Menu 3-Dots --}}
            <div class="flex items-center gap-3" x-data="{ openMenu: false }">
                @auth
                    @php
                        $user = auth()->user();
                        $dashRoute = match($user->role) {
                            'admin', 'super_admin' => route('admin.dashboard'),
                            'guru' => route('guru.dashboard'),
                            'siswa' => route('siswa.dashboard'),
                            'kepala_sekolah' => route('kepala-sekolah.dashboard'),
                            'piket' => route('piket.dashboard'),
                            default => route('login'),
                        };
                    @endphp
                    <a href="{{ $dashRoute }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition-colors shadow-xs flex items-center gap-2">
                        <span>Ke Dashboard ({{ strtoupper($user->role) }})</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                @endauth

                {{-- Dropdown Menu Titik Tiga (3 Dots) --}}
                <div class="relative">
                    <button @click="openMenu = !openMenu" @click.away="openMenu = false" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none" title="Menu Options">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>

                    <div x-show="openMenu" x-transition style="display: none;" class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800">Akses Sistem</p>
                            <p class="text-[11px] text-slate-500">Masuk sesuai hak akses</p>
                        </div>

                        @guest
                        <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            <span>Masuk / Login Akun</span>
                        </a>
                        @else
                        <a href="{{ $dashRoute }}" class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Dashboard Utama</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors text-left">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span>Keluar / Logout</span>
                            </button>
                        </form>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="w-full px-4 sm:px-6 lg:px-8 pt-6 pb-6 space-y-6 flex-1">
        
        {{-- Hero Header Section (Logo & App Description) --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-60 h-60 bg-blue-500/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row items-center sm:items-start md:items-center gap-4 sm:gap-6 relative z-10">
                <div class="w-20 h-20 sm:w-24 sm:h-24 p-2 rounded-2xl bg-slate-50 border border-slate-200/80 shadow-xs flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/logo_new.png') }}" alt="Logo SOPAN" class="w-full h-full object-contain">
                </div>

                <div class="flex-1 text-center sm:text-left min-w-0">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200/70 text-blue-700 text-[11px] font-bold tracking-wide uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                        Portal Publik & Monitoring Real-Time
                    </div>
                    
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight mt-1.5">
                        Aplikasi Sistem Operasi Presensi Kehadiran Mengajar Guru
                    </h2>
                    
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-3xl leading-relaxed">
                        Sistem informasi dan monitoring keterlaksanaan Kegiatan Belajar Mengajar (KBM) guru dan siswa di <span class="font-bold text-slate-800">{{ \App\Models\Setting::get('school_name', 'SMK Negeri 1 Ciamis') }}</span> secara transparan, akurat, dan real-time.
                    </p>

                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mt-3 pt-3 border-t border-slate-100 text-xs">
                        <div class="inline-flex items-center gap-1.5 font-medium text-slate-600 bg-slate-50 border border-slate-200/60 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>{{ $today->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 font-semibold text-slate-700 bg-slate-50 border border-slate-200/60 px-2.5 py-1 rounded-lg">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Pukul {{ $nowTime }} WIB</span>
                        </div>
                        <div class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Monitoring Aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['guru_hadir_hari_ini'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Guru Hadir</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['guru_terlambat_hari_ini'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Guru Terlambat</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['guru_tidak_hadir_hari_ini'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Tidak Hadir / Izin</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['total_guru'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Total Tenaga Pengajar</p>
                </div>
            </div>
        </div>

        {{-- Row 2: KPI Ringkasan Statistik Siswa --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['siswa_hadir_hari_ini'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Siswa Hadir</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['siswa_terlambat_hari_ini'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Siswa Terlambat</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['siswa_tidak_hadir_hari_ini'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Siswa Alpa / Izin / Sakit</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                <div class="shrink-0 w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-slate-900 leading-none">{{ $stats['total_siswa'] }}</p>
                    <p class="text-xs text-slate-500 mt-1 font-medium truncate">Total Siswa</p>
                </div>
            </div>
        </div>

        {{-- ═══ SECTION: MONITORING REAL-TIME BERBASIS KARTU & JAM BERJALAN ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-xs">
            
            {{-- Header Bar: Title + Dual Pie Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center pb-4 border-b border-slate-100">
                <div class="lg:col-span-5 space-y-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">Monitoring Kehadiran Real-Time</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold flex items-center gap-1 shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Status ruang kelas & kehadiran pengajar saat ini (Pukul {{ $nowTime }} WIB)</p>
                </div>

                <div class="lg:col-span-7 bg-slate-50/80 border border-slate-200 rounded-xl p-3 space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-1.5">
                        <span class="text-xs font-bold text-slate-800">Distribusi Kehadiran (7 Hari Terakhir)</span>
                        <span class="text-[10px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">Guru & Siswa</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Guru Pie Chart --}}
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/80 flex items-center gap-3">
                            <div class="w-20 h-20 relative shrink-0">
                                <canvas id="guruPieChartPublic"></canvas>
                            </div>
                            <div class="space-y-1 w-full min-w-0">
                                <p class="text-[11px] font-bold text-slate-900 border-b border-slate-100 pb-0.5">Kehadiran Guru</p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>Hadir</span>
                                    <span class="font-bold text-slate-900">{{ $guruPiePct['hadir'] }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>Terlambat</span>
                                    <span class="font-bold text-slate-900">{{ $guruPiePct['terlambat'] }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>Alpa</span>
                                    <span class="font-bold text-slate-900">{{ $guruPiePct['tidak_hadir'] }}%</span>
                                </div>
                            </div>
                        </div>

                        {{-- Siswa Pie Chart --}}
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/80 flex items-center gap-3">
                            <div class="w-20 h-20 relative shrink-0">
                                <canvas id="siswaPieChartPublic"></canvas>
                            </div>
                            <div class="space-y-1 w-full min-w-0">
                                <p class="text-[11px] font-bold text-slate-900 border-b border-slate-100 pb-0.5">Kehadiran Siswa</p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>Hadir</span>
                                    <span class="font-bold text-slate-900">{{ $siswaPiePct['hadir'] }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>Sakit / Izin</span>
                                    <span class="font-bold text-slate-900">{{ $siswaPiePct['sakit'] + $siswaPiePct['izin'] }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>Alpa</span>
                                    <span class="font-bold text-slate-900">{{ $siswaPiePct['alpa'] }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div id="monitoring-kbm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 scroll-mt-20">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Jam Pelajaran</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-semibold bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-2xs">
                        <option value="{{ request()->fullUrlWithQuery(['slot' => 'all']) }}#monitoring-kbm" {{ $selectedSlotKey === 'all' ? 'selected' : '' }}>Semua Jam</option>
                        @foreach($timeSlots as $idx => $slot)
                        @php
                            $isActiveNow = ($idx === $currentActiveSlotIndex);
                            $isSelected = ($selectedSlotKey === (string) $idx);
                        @endphp
                        <option value="{{ request()->fullUrlWithQuery(['slot' => $idx]) }}#monitoring-kbm" {{ $isSelected ? 'selected' : '' }}>
                            Jam {{ $slot['jam_ke'] }} ({{ $slot['label'] }}) {{ $isActiveNow ? '⚡ [Sedang Berjalan]' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tingkat Kelas</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => 'all']) }}#monitoring-kbm" {{ $selectedTingkat === 'all' ? 'selected' : '' }}>Semua Tingkat</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '10']) }}#monitoring-kbm" {{ $selectedTingkat === '10' ? 'selected' : '' }}>Kelas 10 (X)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '11']) }}#monitoring-kbm" {{ $selectedTingkat === '11' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '12']) }}#monitoring-kbm" {{ $selectedTingkat === '12' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Kehadiran</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'all']) }}#monitoring-kbm" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'hadir']) }}#monitoring-kbm" {{ $selectedStatus === 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'terlambat']) }}#monitoring-kbm" {{ $selectedStatus === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'belum_hadir']) }}#monitoring-kbm" {{ $selectedStatus === 'belum_hadir' ? 'selected' : '' }}>Belum Hadir</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'tidak_hadir']) }}#monitoring-kbm" {{ $selectedStatus === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alasan Tidak Hadir</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'all']) }}#monitoring-kbm" {{ $selectedAlasan === 'all' ? 'selected' : '' }}>Semua Alasan</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'sakit']) }}#monitoring-kbm" {{ $selectedAlasan === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'izin']) }}#monitoring-kbm" {{ $selectedAlasan === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'rapat_dinas']) }}#monitoring-kbm" {{ $selectedAlasan === 'rapat_dinas' ? 'selected' : '' }}>Rapat Dinas</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'dinas_luar']) }}#monitoring-kbm" {{ $selectedAlasan === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'tugas_luar']) }}#monitoring-kbm" {{ $selectedAlasan === 'tugas_luar' ? 'selected' : '' }}>Tugas Luar</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'tanpa_keterangan']) }}#monitoring-kbm" {{ $selectedAlasan === 'tanpa_keterangan' ? 'selected' : '' }}>Alpa</option>
                    </select>
                </div>
            </div>

            {{-- Card Grid Monitoring Kehadiran --}}
            @if($monitoringCards->count() > 0)
                @if($selectedSlotKey === 'all')
                    @php
                        $groupedBySlot = $monitoringCards->groupBy('slot_index')->sortKeys();
                    @endphp
                    <div class="space-y-6 pt-2">
                        @foreach($groupedBySlot as $sKey => $slotCards)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-600 text-white text-xs font-bold tracking-wide shadow-2xs">
                                            Jam Ke-{{ $slotCards->first()['jam_ke'] }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-800">
                                            {{ $slotCards->first()['slot_label'] }}
                                        </span>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-500">
                                        {{ $slotCards->count() }} Kelas
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                    @foreach($slotCards as $card)
                                    @php
                                        $theme = match($card['status']) {
                                            'hadir' => [
                                                'border' => 'border-emerald-200',
                                                'top' => 'bg-emerald-500',
                                                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'dot' => 'bg-emerald-500',
                                                'text' => 'Sudah Hadir',
                                            ],
                                            'terlambat' => [
                                                'border' => 'border-amber-200',
                                                'top' => 'bg-amber-500',
                                                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'dot' => 'bg-amber-500',
                                                'text' => 'Terlambat',
                                            ],
                                            'tidak_hadir' => [
                                                'border' => 'border-red-200',
                                                'top' => 'bg-red-500',
                                                'badge' => 'bg-red-50 text-red-700 border-red-200',
                                                'dot' => 'bg-red-500',
                                                'text' => 'Tidak Hadir',
                                            ],
                                            default => [
                                                'border' => 'border-rose-200',
                                                'top' => 'bg-rose-400',
                                                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'dot' => 'bg-rose-500 animate-pulse',
                                                'text' => 'Belum Hadir',
                                            ],
                                        };
                                    @endphp
                                    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-xs transition-shadow flex flex-col">
                                        <div class="flex flex-1">
                                            <div class="w-1 flex-shrink-0 {{ $theme['top'] }}"></div>
                                            <div class="flex-1 p-3 flex flex-col gap-2 min-w-0">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 text-white text-xs font-extrabold tracking-wide shrink-0">
                                                        {{ $card['kelas_nama'] }}
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold shrink-0" title="{{ $card['slot_label'] }}">
                                                        Jam {{ $card['jam_ke'] }}
                                                    </span>
                                                </div>

                                                <div class="min-w-0">
                                                    <h3 class="font-semibold text-slate-900 text-xs leading-snug truncate">{{ $card['mapel_nama'] }}</h3>
                                                    <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1 min-w-0">
                                                        <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                        <span class="truncate">{{ $card['guru_nama'] }}</span>
                                                    </p>
                                                </div>

                                                <div class="flex items-center justify-between pt-2 border-t border-slate-100 mt-auto">
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $theme['badge'] }} truncate">
                                                        <span class="w-1.5 h-1.5 rounded-full {{ $theme['dot'] }} shrink-0"></span>
                                                        <span class="truncate">{{ $theme['text'] }}</span>
                                                    </span>
                                                    @if($card['waktu_hadir'])
                                                        <span class="text-[10px] text-slate-400 tabular-nums shrink-0">{{ \Carbon\Carbon::parse($card['waktu_hadir'])->format('H:i') }}</span>
                                                    @endif
                                                </div>

                                                @if($card['alasan_label'])
                                                    <div class="px-2 py-1.5 rounded bg-red-50 border border-red-100">
                                                        <p class="text-[10px] text-red-700 font-medium leading-snug truncate">{{ $card['alasan_label'] }}</p>
                                                        @if($card['guru_pengganti'])
                                                            <p class="text-[10px] text-blue-600 font-medium mt-0.5 truncate">↳ {{ $card['guru_pengganti'] }}</p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 pt-2">
                        @foreach($monitoringCards as $card)
                        @php
                            $theme = match($card['status']) {
                                'hadir' => [
                                    'border' => 'border-emerald-200',
                                    'top' => 'bg-emerald-500',
                                    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'dot' => 'bg-emerald-500',
                                    'text' => 'Sudah Hadir',
                                ],
                                'terlambat' => [
                                    'border' => 'border-amber-200',
                                    'top' => 'bg-amber-500',
                                    'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'dot' => 'bg-amber-500',
                                    'text' => 'Terlambat',
                                ],
                                'tidak_hadir' => [
                                    'border' => 'border-red-200',
                                    'top' => 'bg-red-500',
                                    'badge' => 'bg-red-50 text-red-700 border-red-200',
                                    'dot' => 'bg-red-500',
                                    'text' => 'Tidak Hadir',
                                ],
                                default => [
                                    'border' => 'border-rose-200',
                                    'top' => 'bg-rose-400',
                                    'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'dot' => 'bg-rose-500 animate-pulse',
                                    'text' => 'Belum Hadir',
                                ],
                            };
                        @endphp
                        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-xs transition-shadow flex flex-col">
                            <div class="flex flex-1">
                                <div class="w-1 flex-shrink-0 {{ $theme['top'] }}"></div>
                                <div class="flex-1 p-3 flex flex-col gap-2 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-white text-xs font-extrabold tracking-wide shrink-0">
                                            {{ $card['kelas_nama'] }}
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold shrink-0" title="{{ $card['slot_label'] }}">
                                            Jam {{ $card['jam_ke'] }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-900 text-xs leading-snug truncate">{{ $card['mapel_nama'] }}</h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1 min-w-0">
                                            <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span class="truncate">{{ $card['guru_nama'] }}</span>
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 mt-auto">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $theme['badge'] }} truncate">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $theme['dot'] }} shrink-0"></span>
                                            <span class="truncate">{{ $theme['text'] }}</span>
                                        </span>
                                        @if($card['waktu_hadir'])
                                            <span class="text-[10px] text-slate-400 tabular-nums shrink-0">{{ \Carbon\Carbon::parse($card['waktu_hadir'])->format('H:i') }}</span>
                                        @endif
                                    </div>

                                    @if($card['alasan_label'])
                                        <div class="px-2 py-1.5 rounded bg-red-50 border border-red-100">
                                            <p class="text-[10px] text-red-700 font-medium leading-snug truncate">{{ $card['alasan_label'] }}</p>
                                            @if($card['guru_pengganti'])
                                                <p class="text-[10px] text-blue-600 font-medium mt-0.5 truncate">↳ {{ $card['guru_pengganti'] }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            @else
            <div class="p-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                <p class="text-sm font-semibold text-slate-700">Tidak ada jadwal yang sesuai kriteria filter.</p>
                <p class="text-xs text-slate-400 mt-1">Coba ganti filter jam pelajaran, tingkat kelas, atau status kehadiran.</p>
            </div>
            @endif
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} {{ \App\Models\Setting::get('school_name', 'Sekolah') }}. All rights reserved.
    </footer>

    <script>
        const slicePercentagePlugin = {
            id: 'slicePercentagePublic',
            afterDraw(chart) {
                const { ctx, data } = chart;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data) return;

                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                if (total === 0) return;

                meta.data.forEach((element, index) => {
                    const val = data.datasets[0].data[index];
                    if (!val || val === 0) return;
                    const pct = Math.round((val / total) * 100);
                    if (pct < 4) return;

                    const { x, y } = element.tooltipPosition();
                    ctx.save();
                    ctx.font = 'bold 11px Inter, system-ui, sans-serif';
                    ctx.fillStyle = '#ffffff';
                    ctx.shadowColor = 'rgba(0,0,0,0.4)';
                    ctx.shadowBlur = 3;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(`${pct}%`, x, y);
                    ctx.restore();
                });
            }
        };

        const pieOpts = {
            responsive: true, maintainAspectRatio: false, cutout: '55%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                            const pct = Math.round(ctx.parsed / total * 100);
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        };

        const guruPie = @json($guruPie);
        new Chart(document.getElementById('guruPieChartPublic'), {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Tidak Hadir'],
                datasets: [{ data: [guruPie.hadir, guruPie.terlambat, guruPie.tidak_hadir], backgroundColor: ['#10b981', '#fbbf24', '#f87171'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: pieOpts,
            plugins: [slicePercentagePlugin]
        });

        const siswaPie = @json($siswaPie);
        new Chart(document.getElementById('siswaPieChartPublic'), {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Dispensasi'],
                datasets: [{ data: [siswaPie.hadir, siswaPie.sakit, siswaPie.izin, siswaPie.alpa, siswaPie.dispensasi], backgroundColor: ['#10b981', '#fbbf24', '#38bdf8', '#f87171', '#a78bfa'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: pieOpts,
            plugins: [slicePercentagePlugin]
        });
    </script>
</body>
</html>

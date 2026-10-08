<x-layouts.kepala-sekolah>
    <x-slot:title>Dashboard Kepala Sekolah</x-slot:title>

    <div class="space-y-6">
        {{-- ═══ PANDUAN PERHITUNGAN & TRANSPARANSI STATISTIK KEHADIRAN ═══ --}}
        <div class="bg-gradient-to-r from-blue-50/90 via-indigo-50/80 to-emerald-50/80 border border-blue-200/80 rounded-2xl p-4 shadow-xs" x-data="{ openDetail: false }">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-xs font-bold text-slate-900">Penjelasan & Transparansi Angka Statistik Kehadiran</h3>
                            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[10px] font-bold">Basis Log Sesi KBM (Jam Pelajaran)</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Angka kehadiran siswa & guru dihitung dari <strong>akumulasi log presensi per sesi/jam pelajaran (KBM)</strong>. Karena 1 siswa mengikuti rata-rata <strong>4–5 sesi mapel/hari</strong>, maka {{ number_format($stats['total_siswa'], 0, ',', '.') }} siswa fisik menghasilkan total <strong>{{ number_format($stats['siswa_total_log'], 0, ',', '.') }} log presensi</strong> hari ini.
                        </p>
                    </div>
                </div>
                <button type="button" @click="openDetail = !openDetail" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-semibold shadow-2xs transition-all shrink-0 self-start md:self-center cursor-pointer">
                    <span x-text="openDetail ? 'Tutup Rincian Hitungan' : 'Lihat Rincian Rumus & Hitungan'">Lihat Rincian Rumus & Hitungan</span>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openDetail }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            {{-- Detail Rincian Rumus & Simulasi Hitungan --}}
            <div x-show="openDetail" x-transition class="mt-4 pt-4 border-t border-blue-200/60 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                {{-- Card 1: Siswa --}}
                <div class="bg-white/95 rounded-xl border border-slate-200 p-3.5 space-y-2 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Hitungan Log Siswa Hadir
                        </span>
                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Presensi Sesi</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        Guru mapel mengabsen siswa setiap jam pertemuan KBM. Total log adalah hasil kali jumlah siswa dengan jumlah sesi mapel yang berlangsung hari ini.
                    </p>
                    <div class="bg-slate-50 rounded-lg p-2 font-mono text-[11px] text-slate-800 space-y-1 border border-slate-100">
                        <div class="flex justify-between"><span>• Total Log Hari Ini:</span><span class="font-bold">{{ number_format($stats['siswa_total_log'], 0, ',', '.') }} presensi</span></div>
                        <div class="flex justify-between"><span>• Siswa Hadir (Sesi):</span><span class="font-bold text-emerald-600">{{ number_format($stats['siswa_hadir_hari_ini'], 0, ',', '.') }} ({{ $stats['siswa_persen_sesi_hadir'] }}%)</span></div>
                        <div class="flex justify-between"><span>• Siswa Alpa/Izin/Sakit:</span><span class="font-bold text-red-600">{{ number_format($stats['siswa_tidak_hadir_hari_ini'], 0, ',', '.') }}</span></div>
                        <div class="flex justify-between border-t border-slate-200 pt-1 text-slate-700"><span>• Siswa Fisik Hadir:</span><span class="font-bold text-blue-700">{{ number_format($stats['siswa_fisik_hadir'], 0, ',', '.') }} / {{ number_format($stats['total_siswa'], 0, ',', '.') }} ({{ $stats['siswa_fisik_persen'] }}%)</span></div>
                    </div>
                </div>

                {{-- Card 2: Guru --}}
                <div class="bg-white/95 rounded-xl border border-slate-200 p-3.5 space-y-2 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Hitungan Sesi Guru Hadir
                        </span>
                        <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">Sesi Mengajar</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        1 guru mengajar di lebih dari 1 rombel/jam kelas per hari. Angka dihitung dari akumulasi sesi mengajar yang diampu guru hari ini.
                    </p>
                    <div class="bg-slate-50 rounded-lg p-2 font-mono text-[11px] text-slate-800 space-y-1 border border-slate-100">
                        <div class="flex justify-between"><span>• Total Sesi KBM:</span><span class="font-bold">{{ number_format($stats['guru_total_sesi'], 0, ',', '.') }} sesi</span></div>
                        <div class="flex justify-between"><span>• Guru Hadir (Sesi):</span><span class="font-bold text-emerald-600">{{ number_format($stats['guru_hadir_hari_ini'], 0, ',', '.') }} ({{ $stats['guru_persen_sesi_hadir'] }}%)</span></div>
                        <div class="flex justify-between"><span>• Tidak Hadir / Izin:</span><span class="font-bold text-red-600">{{ number_format($stats['guru_tidak_hadir_hari_ini'], 0, ',', '.') }} sesi</span></div>
                        <div class="flex justify-between border-t border-slate-200 pt-1 text-slate-700"><span>• Guru Fisik Hadir:</span><span class="font-bold text-blue-700">{{ $stats['guru_fisik_hadir'] }} / {{ $stats['total_guru'] }} ({{ $stats['guru_fisik_persen'] }}%)</span></div>
                    </div>
                </div>

                {{-- Card 3: Distribusi 7 Hari --}}
                <div class="bg-white/95 rounded-xl border border-slate-200 p-3.5 space-y-2 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                            Rumus Persentase (7 Hari)
                        </span>
                        <span class="text-[10px] font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">Rasio Pembagian</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        Persentase di grafik pie dihitung dari total log status dibagi total keseluruhan akumulasi sesi dalam 7 hari terakhir:
                    </p>
                    <div class="bg-slate-50 rounded-lg p-2 font-mono text-[11px] text-slate-800 space-y-1 border border-slate-100">
                        <div>• Guru: <span class="font-bold text-emerald-700">({{ number_format($guruPie['hadir'], 0, ',', '.') }} ÷ {{ number_format($guruPieTotalReal, 0, ',', '.') }}) × 100% = {{ $guruPiePct['hadir'] }}%</span></div>
                        <div>• Siswa: <span class="font-bold text-emerald-700">({{ number_format($siswaPie['hadir'], 0, ',', '.') }} ÷ {{ number_format($siswaPieTotalReal, 0, ',', '.') }}) × 100% = {{ $siswaPiePct['hadir'] }}%</span></div>
                        <div class="text-[10px] text-slate-500 pt-1 border-t border-slate-200">Total data: {{ number_format($guruPieTotalReal, 0, ',', '.') }} sesi guru & {{ number_format($siswaPieTotalReal, 0, ',', '.') }} log siswa.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards: Guru & Siswa --}}
        <div class="space-y-4">
            {{-- Row 1: Guru --}}
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                    <p class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        Statistik Kehadiran Guru Hari Ini
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 normal-case">Dihitung per Sesi Mengajar</span>
                    </p>
                    <span class="text-[11px] font-medium text-slate-500">
                        Guru Fisik Aktif: <strong class="text-blue-700">{{ $stats['guru_fisik_hadir'] }}</strong> dari {{ $stats['total_guru'] }} guru ({{ $stats['guru_fisik_persen'] }}%)
                    </span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Sesi Hadir --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-1">
                                <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['guru_hadir_hari_ini'], 0, ',', '.') }}</p>
                                @if($stats['guru_total_sesi'] > 0)
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">{{ $stats['guru_persen_sesi_hadir'] }}%</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Sesi Guru Hadir</p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5" title="Akumulasi jam mengajar terlaksana hari ini">
                                @if($stats['guru_total_sesi'] > 0)
                                    Dari total {{ number_format($stats['guru_total_sesi'], 0, ',', '.') }} sesi KBM
                                @else
                                    Sesi jam mengajar hadir
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Terlambat --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['guru_terlambat_hari_ini'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Sesi Terlambat</p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">Sesi dimulai terlambat</p>
                        </div>
                    </div>

                    {{-- Tidak Hadir --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['guru_tidak_hadir_hari_ini'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Sesi Tidak Hadir / Izin</p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">Sakit, izin, alpa, dinas luar</p>
                        </div>
                    </div>

                    {{-- Total Guru --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['total_guru'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Total Guru (Fisik Unik)</p>
                            <p class="text-[10px] text-blue-700 font-semibold truncate mt-0.5">{{ $stats['guru_fisik_hadir'] }} guru mengajar hari ini</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 2: Murid / Siswa --}}
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                    <p class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        Statistik Kehadiran Murid Hari Ini
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 normal-case">Dihitung per Log Sesi Mapel (~{{ $stats['siswa_rata_sesi'] ?: '4-5' }} sesi/siswa)</span>
                    </p>
                    <span class="text-[11px] font-medium text-slate-500">
                        Siswa Fisik Hadir: <strong class="text-emerald-700">{{ number_format($stats['siswa_fisik_hadir'], 0, ',', '.') }}</strong> dari {{ number_format($stats['total_siswa'], 0, ',', '.') }} siswa ({{ $stats['siswa_fisik_persen'] }}%)
                    </span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Siswa Hadir --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-1">
                                <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['siswa_hadir_hari_ini'], 0, ',', '.') }}</p>
                                @if($stats['siswa_total_log'] > 0)
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">{{ $stats['siswa_persen_sesi_hadir'] }}%</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Log Sesi Siswa Hadir</p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5" title="Akumulasi presensi per jam pelajaran">
                                @if($stats['siswa_total_log'] > 0)
                                    Dari total {{ number_format($stats['siswa_total_log'], 0, ',', '.') }} log presensi
                                @else
                                    Akumulasi presensi KBM hadir
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Siswa Terlambat --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['siswa_terlambat_hari_ini'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Log Sesi Terlambat</p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">Terlambat di jam pertemuan</p>
                        </div>
                    </div>

                    {{-- Siswa Alpa / Izin / Sakit --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['siswa_tidak_hadir_hari_ini'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Log Alpa / Izin / Sakit</p>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">Akumulasi sesi tidak hadir</p>
                        </div>
                    </div>

                    {{-- Total Siswa --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3 shadow-xs">
                        <div class="shrink-0 w-10 h-10 aspect-square rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold text-slate-900 leading-none">{{ number_format($stats['total_siswa'], 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-700 mt-1 font-semibold truncate">Total Siswa (Fisik Unik)</p>
                            <p class="text-[10px] text-emerald-700 font-semibold truncate mt-0.5">{{ number_format($stats['siswa_fisik_hadir'], 0, ',', '.') }} siswa hadir fisik ({{ $stats['siswa_fisik_persen'] }}%)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ SECTION: MONITORING REAL-TIME BERBASIS KARTU & JAM BERJALAN ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-xs">
            
            {{-- Header Bar: Title (Kiri) + Dual Pie Charts Guru & Siswa (Kanan) --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center pb-4 border-b border-slate-100">
                {{-- Left Side: Header Title --}}
                <div class="lg:col-span-5 space-y-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">Monitoring Kehadiran Real-Time</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold flex items-center gap-1 shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Memantau kehadiran per ruang kelas sesuai jam pelajaran aktif (Pukul {{ $nowTime }} WIB)</p>
                </div>

                {{-- Right Side: Dual Pie Charts Widget (Guru & Siswa side-by-side) --}}
                <div class="lg:col-span-7 bg-slate-50/80 border border-slate-200 rounded-xl p-3 space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-1.5">
                        <div>
                            <span class="text-xs font-bold text-slate-800">Distribusi Kehadiran (7 Hari Terakhir)</span>
                            <span class="text-[10px] text-slate-500 block">Dihitung dari persentase log sesi per status terhadap total akumulasi sesi</span>
                        </div>
                        <span class="text-[10px] font-semibold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200 shadow-2xs">Guru & Siswa</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Guru Pie Chart Card --}}
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/80 flex items-center gap-3">
                            <div class="w-20 h-20 relative shrink-0">
                                <canvas id="guruPieChartHeader"></canvas>
                            </div>
                            <div class="space-y-1 w-full min-w-0">
                                <div class="border-b border-slate-100 pb-1 flex items-center justify-between">
                                    <p class="text-[11px] font-bold text-slate-900">Kehadiran Guru</p>
                                    <span class="text-[9px] text-slate-500 font-semibold" title="Total akumulasi sesi mengajar guru 7 hari terakhir">Total: {{ number_format($guruPieTotalReal, 0, ',', '.') }} Sesi</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>Hadir</span>
                                    <span class="font-bold text-slate-900" title="{{ number_format($guruPie['hadir'], 0, ',', '.') }} dari {{ number_format($guruPieTotalReal, 0, ',', '.') }} sesi mengajar">{{ $guruPiePct['hadir'] }}% <span class="text-[9px] font-normal text-slate-400">({{ number_format($guruPie['hadir'], 0, ',', '.') }})</span></span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>Terlambat</span>
                                    <span class="font-bold text-slate-900" title="{{ number_format($guruPie['terlambat'], 0, ',', '.') }} dari {{ number_format($guruPieTotalReal, 0, ',', '.') }} sesi mengajar">{{ $guruPiePct['terlambat'] }}% <span class="text-[9px] font-normal text-slate-400">({{ number_format($guruPie['terlambat'], 0, ',', '.') }})</span></span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>Alpa/Izin</span>
                                    <span class="font-bold text-slate-900" title="{{ number_format($guruPie['tidak_hadir'], 0, ',', '.') }} dari {{ number_format($guruPieTotalReal, 0, ',', '.') }} sesi mengajar">{{ $guruPiePct['tidak_hadir'] }}% <span class="text-[9px] font-normal text-slate-400">({{ number_format($guruPie['tidak_hadir'], 0, ',', '.') }})</span></span>
                                </div>
                                <div class="text-[9px] text-slate-400 pt-0.5 border-t border-slate-50 font-mono text-right" title="Formula pembagian data">
                                    Rumus: (Sesi ÷ {{ number_format($guruPieTotalReal, 0, ',', '.') }}) × 100%
                                </div>
                            </div>
                        </div>

                        {{-- Siswa Pie Chart Card --}}
                        <div class="bg-white p-2.5 rounded-lg border border-slate-200/80 flex items-center gap-3">
                            <div class="w-20 h-20 relative shrink-0">
                                <canvas id="siswaPieChartHeader"></canvas>
                            </div>
                            <div class="space-y-1 w-full min-w-0">
                                <div class="border-b border-slate-100 pb-1 flex items-center justify-between">
                                    <p class="text-[11px] font-bold text-slate-900">Kehadiran Siswa</p>
                                    <span class="text-[9px] text-slate-500 font-semibold" title="Total catatan presensi per sesi mapel 7 hari terakhir">Total: {{ number_format($siswaPieTotalReal, 0, ',', '.') }} Log</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>Hadir</span>
                                    <span class="font-bold text-slate-900" title="{{ number_format($siswaPie['hadir'], 0, ',', '.') }} dari {{ number_format($siswaPieTotalReal, 0, ',', '.') }} log presensi">{{ $siswaPiePct['hadir'] }}% <span class="text-[9px] font-normal text-slate-400">({{ number_format($siswaPie['hadir'], 0, ',', '.') }})</span></span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>Sakit/Izin</span>
                                    <span class="font-bold text-slate-900" title="{{ number_format($siswaPie['sakit'] + $siswaPie['izin'], 0, ',', '.') }} dari {{ number_format($siswaPieTotalReal, 0, ',', '.') }} log presensi">{{ round($siswaPiePct['sakit'] + $siswaPiePct['izin'], 1) }}% <span class="text-[9px] font-normal text-slate-400">({{ number_format($siswaPie['sakit'] + $siswaPie['izin'], 0, ',', '.') }})</span></span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="flex items-center gap-1 truncate"><span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>Alpa</span>
                                    <span class="font-bold text-slate-900" title="{{ number_format($siswaPie['alpa'], 0, ',', '.') }} dari {{ number_format($siswaPieTotalReal, 0, ',', '.') }} log presensi">{{ $siswaPiePct['alpa'] }}% <span class="text-[9px] font-normal text-slate-400">({{ number_format($siswaPie['alpa'], 0, ',', '.') }})</span></span>
                                </div>
                                <div class="text-[9px] text-slate-400 pt-0.5 border-t border-slate-50 font-mono text-right" title="Formula pembagian data">
                                    Rumus: (Log ÷ {{ number_format($siswaPieTotalReal, 0, ',', '.') }}) × 100%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Banner Kelas PKL --}}
            @if(isset($kelasPklHariIni) && $kelasPklHariIni->isNotEmpty())
                <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full bg-indigo-200/70 text-indigo-800 text-[10px] font-bold">INFO PKL</span>
                                <h4 class="text-xs font-bold text-indigo-950">{{ $kelasPklHariIni->count() }} Kelas Sedang Melaksanakan PKL</h4>
                            </div>
                            <p class="text-xs text-indigo-800 mt-0.5">
                                Kelas: <span class="font-bold">{{ $kelasPklHariIni->pluck('kelas.nama')->unique()->join(', ') }}</span>. 
                                Jadwal KBM dinonaktifkan sementara dan tidak dihitung ke status belum hadir.
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-700 self-start sm:self-auto">
                        Jadwal Di-Bypass
                    </span>
                </div>
            @endif

            {{-- Filter Bar: 4 Dropdowns Sejajar (Di Bawah Pie Chart, Di Atas Card Grid) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                {{-- Dropdown Jam Pelajaran --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Jam Pelajaran</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-semibold bg-blue-50 border border-blue-200 text-blue-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-2xs">
                        <option value="{{ request()->fullUrlWithQuery(['slot' => 'all']) }}" {{ $selectedSlotKey === 'all' ? 'selected' : '' }}>Semua Jam</option>
                        @foreach($timeSlots as $idx => $slot)
                        @php
                            $isActiveNow = ($idx === $currentActiveSlotIndex);
                            $isSelected = ($selectedSlotKey === (string) $idx);
                        @endphp
                        <option value="{{ request()->fullUrlWithQuery(['slot' => $idx]) }}" {{ $isSelected ? 'selected' : '' }}>
                            Jam {{ $slot['jam_ke'] }} ({{ $slot['label'] }}) {{ $isActiveNow ? '⚡ [Sedang Berjalan]' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Tingkat --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tingkat Kelas</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => 'all']) }}" {{ $selectedTingkat === 'all' ? 'selected' : '' }}>Semua Tingkat</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '10']) }}" {{ $selectedTingkat === '10' ? 'selected' : '' }}>Kelas 10 (X)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '11']) }}" {{ $selectedTingkat === '11' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '12']) }}" {{ $selectedTingkat === '12' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                    </select>
                </div>

                {{-- Filter Status Utama --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Kehadiran</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'all']) }}" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'hadir']) }}" {{ $selectedStatus === 'hadir' ? 'selected' : '' }}>🟢 Hadir</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'terlambat']) }}" {{ $selectedStatus === 'terlambat' ? 'selected' : '' }}>🟠 Terlambat</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'belum_hadir']) }}" {{ $selectedStatus === 'belum_hadir' ? 'selected' : '' }}>🔴 Belum Hadir</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'tidak_hadir']) }}" {{ $selectedStatus === 'tidak_hadir' ? 'selected' : '' }}>🔴 Tidak Hadir</option>
                    </select>
                </div>

                {{-- Filter Sub-Alasan --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alasan Tidak Hadir</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'all']) }}" {{ $selectedAlasan === 'all' ? 'selected' : '' }}>Semua Alasan</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'sakit']) }}" {{ $selectedAlasan === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'izin']) }}" {{ $selectedAlasan === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'cuti']) }}" {{ $selectedAlasan === 'cuti' ? 'selected' : '' }}>Cuti</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'rapat_dinas']) }}" {{ $selectedAlasan === 'rapat_dinas' ? 'selected' : '' }}>Rapat Dinas</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'dinas_luar']) }}" {{ $selectedAlasan === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'tugas_luar']) }}" {{ $selectedAlasan === 'tugas_luar' ? 'selected' : '' }}>Tugas Luar</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'tanpa_keterangan']) }}" {{ $selectedAlasan === 'tanpa_keterangan' ? 'selected' : '' }}>Alpa</option>
                    </select>
                </div>
            </div>

            {{-- ═══ CARD GRID MONITORING KEHADIRAN ═══ --}}
            @if($monitoringCards->count() > 0)
            <div class="grid grid-cols-6 gap-2 pt-2">
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
                <div class="bg-white rounded-lg border border-slate-200 overflow-hidden hover:shadow-sm transition-shadow flex flex-col">
                    {{-- Accent strip kiri berdasarkan status --}}
                    <div class="flex flex-1">
                        <div class="w-1 flex-shrink-0 {{ $theme['top'] }}"></div>
                        <div class="flex-1 p-3 flex flex-col gap-2">

                            {{-- Baris atas: Kelas --}}
                            <span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 text-xs font-extrabold tracking-wide self-start">
                                {{ $card['kelas_nama'] }}
                            </span>

                            {{-- Mapel & Guru --}}
                            <div>
                                <h3 class="font-semibold text-slate-900 text-xs leading-snug">{{ $card['mapel_nama'] }}</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span class="truncate">{{ $card['guru_nama'] }}</span>
                                </p>
                            </div>

                            {{-- Footer: Status + Waktu --}}
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 mt-auto">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $theme['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $theme['dot'] }}"></span>
                                    {{ $theme['text'] }}
                                </span>
                                @if($card['waktu_hadir'])
                                    <span class="text-[10px] text-slate-400 tabular-nums">{{ \Carbon\Carbon::parse($card['waktu_hadir'])->format('H:i') }}</span>
                                @endif
                            </div>

                            {{-- Alasan tidak hadir --}}
                            @if($card['alasan_label'])
                                <div class="px-2 py-1.5 rounded bg-red-50 border border-red-100">
                                    <p class="text-[10px] text-red-700 font-medium leading-snug">{{ $card['alasan_label'] }}</p>
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
            @else
            <div class="p-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                <p class="text-sm font-semibold text-slate-700">Tidak ada jadwal yang sesuai kriteria filter.</p>
                <p class="text-xs text-slate-400 mt-1">Coba ganti filter jam pelajaran, tingkat kelas, atau status kehadiran.</p>
            </div>
            @endif
        </div>



        {{-- ═══ SECTION: KEHADIRAN TERBARU (7 HARI) ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Catatan Kehadiran Guru Terkini (7 Hari)</h3>
                <a href="{{ route('kepala-sekolah.report.guru') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Laporan Lengkap →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[600px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Guru</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell whitespace-nowrap">Mata Pelajaran</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell whitespace-nowrap">Kelas</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Status</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell whitespace-nowrap">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentKehadiran as $kh)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">{{ $kh->guru->name }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $kh->pertemuan?->jadwal?->kelas?->nama ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full
                                        {{ $kh->status === 'hadir' ? 'badge-hadir' : ($kh->status === 'terlambat' ? 'badge-sakit' : 'badge-alpa') }}">
                                        {{ $kh->status_label }}
                                    </span>
                                    @if($kh->alasan_tidak_hadir)
                                        <span class="text-[11px] text-slate-500">({{ $kh->alasan_tidak_hadir_label }})</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs hidden lg:table-cell whitespace-nowrap">{{ $kh->created_at->format('d/m H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 text-sm">Belum ada data kehadiran</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recentKehadiran->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $recentKehadiran->links() }}
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        // Plugin custom untuk menggambar teks persentase (%) langsung di atas slice pie/doughnut chart
        const slicePercentagePlugin = {
            id: 'slicePercentage',
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
                    if (pct < 4) return; // Sembunyikan jika terlalu kecil agar tidak menumpuk

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
                            const parsedFmt = new Intl.NumberFormat('id-ID').format(ctx.parsed);
                            const totalFmt = new Intl.NumberFormat('id-ID').format(total);
                            return ` ${ctx.label}: ${parsedFmt} data (${pct}% dari total ${totalFmt})`;
                        }
                    }
                }
            }
        };

        const guruPie = @json($guruPie);
        // ── Guru Pie Header (Top Right) ──
        new Chart(document.getElementById('guruPieChartHeader'), {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Terlambat', 'Tidak Hadir'],
                datasets: [{ data: [guruPie.hadir, guruPie.terlambat, guruPie.tidak_hadir], backgroundColor: ['#10b981', '#fbbf24', '#f87171'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: pieOpts,
            plugins: [slicePercentagePlugin]
        });

        const siswaPie = @json($siswaPie);
        // ── Siswa Pie Header (Top Right) ──
        new Chart(document.getElementById('siswaPieChartHeader'), {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Dispensasi'],
                datasets: [{ data: [siswaPie.hadir, siswaPie.sakit, siswaPie.izin, siswaPie.alpa, siswaPie.dispensasi], backgroundColor: ['#10b981', '#fbbf24', '#38bdf8', '#f87171', '#a78bfa'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: pieOpts,
            plugins: [slicePercentagePlugin]
        });
    </script>
    @endpush
</x-layouts.kepala-sekolah>

<x-layouts.admin>
    <x-slot:title>Dashboard Super Admin</x-slot:title>

    <div class="space-y-6">
        <!-- 1. Top Metrics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
            @foreach([
                ['label' => 'Total Guru', 'value' => $stats['total_guru'], 'color' => 'blue', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'href' => route('admin.users.index', ['role' => 'guru'])],
                ['label' => 'Total Siswa', 'value' => $stats['total_siswa'], 'color' => 'emerald', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'href' => route('admin.siswa.index')],
                ['label' => 'Total Kelas', 'value' => $stats['total_kelas'], 'color' => 'violet', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'href' => route('admin.kelas.index')],
                ['label' => 'Total Admin', 'value' => $stats['total_admin'], 'color' => 'amber', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'href' => route('admin.users.index', ['role' => 'admin'])],
                ['label' => 'Pertemuan Hari Ini', 'value' => $stats['pertemuan_hari_ini'], 'color' => 'indigo', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'href' => route('admin.jadwal.index')],
            ] as $stat)
            <a href="{{ $stat['href'] }}" class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 hover:shadow-md hover:border-slate-300 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider truncate">{{ $stat['label'] }}</span>
                    <span class="p-2 rounded-xl bg-{{ $stat['color'] }}-50 text-{{ $stat['color'] }}-600 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/></svg>
                    </span>
                </div>
                <div>
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ number_format($stat['value']) }}</p>
                </div>
            </a>
            @endforeach
        </div>

        <!-- 2. Live Progress Pertemuan Hari Ini -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Progress Pertemuan Hari Ini
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pantauan status keterisian agenda mengajar secara berkala pada hari ini ({{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}).</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span class="text-slate-600">Total: <strong class="text-slate-900 font-bold">{{ $progressPertemuan['total'] }}</strong> Sesi</span>
                </div>
            </div>

            <!-- Segmented Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-3.5 overflow-hidden flex shadow-inner">
                <div style="width: {{ $progressPertemuan['pct_selesai'] }}%" class="bg-emerald-500 transition-all duration-500" title="Selesai Diisi ({{ $progressPertemuan['selesai'] }})"></div>
                <div style="width: {{ $progressPertemuan['pct_berlangsung'] }}%" class="bg-blue-500 transition-all duration-500" title="Sedang Berlangsung ({{ $progressPertemuan['berlangsung'] }})"></div>
                <div style="width: {{ $progressPertemuan['pct_menunggu'] }}%" class="bg-amber-400 transition-all duration-500" title="Belum Diisi / Menunggu ({{ $progressPertemuan['menunggu'] }})"></div>
            </div>

            <!-- Legend Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-medium text-slate-700">Selesai Diisi</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-emerald-700">{{ $progressPertemuan['selesai'] }}</span>
                        <span class="text-[11px] text-slate-500">({{ $progressPertemuan['pct_selesai'] }}%)</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50/60 border border-blue-100">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-xs font-medium text-slate-700">Sedang Berlangsung</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-blue-700">{{ $progressPertemuan['berlangsung'] }}</span>
                        <span class="text-[11px] text-slate-500">({{ $progressPertemuan['pct_berlangsung'] }}%)</span>
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50/60 border border-amber-100">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                        <span class="text-xs font-medium text-slate-700">Belum Diisi / Menunggu</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-amber-700">{{ $progressPertemuan['menunggu'] }}</span>
                        <span class="text-[11px] text-slate-500">({{ $progressPertemuan['pct_menunggu'] }}%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Grid: Deteksi Anomali & Log Aktivitas Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Deteksi Anomali & Peringatan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-rose-50 text-rose-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </span>
                            <h3 class="font-bold text-slate-900 text-sm">Deteksi Anomali & Peringatan Sistem</h3>
                        </div>
                        <span class="text-[11px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                            {{ $anomaliJadwalTerlewat->count() + $kelasBelumTerpetakan->count() }} Peringatan
                        </span>
                    </div>

                    <div class="space-y-3">
                        @if($anomaliJadwalTerlewat->isEmpty() && $kelasBelumTerpetakan->isEmpty())
                        <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-100">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-xs font-bold text-slate-800">Semua Operasional Terkendali</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Tidak ditemukan agenda terlambat melewati jam belajar atau kelas blok tanpa konfigurasi.</p>
                        </div>
                        @endif

                        @foreach($anomaliJadwalTerlewat as $anomali)
                        <div class="p-3 rounded-xl bg-rose-50/50 border border-rose-100 flex items-start justify-between gap-3 text-xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px]">Agenda Belum Terisi</span>
                                    <span class="font-bold text-slate-900">{{ $anomali->jadwal?->guru?->name ?? 'Guru Tidak Diketahui' }}</span>
                                </div>
                                <p class="text-slate-600">
                                    Kelas: <strong>{{ $anomali->jadwal?->kelas?->nama ?? '-' }}</strong> • 
                                    Mapel: {{ $anomali->jadwal?->mataPelajaran?->nama ?? '-' }}
                                </p>
                                <p class="text-[11px] text-rose-700 font-medium">
                                    Jadwal: {{ \Carbon\Carbon::parse($anomali->jadwal?->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($anomali->jadwal?->jam_selesai)->format('H:i') }} (Waktu KBM telah selesai)
                                </p>
                            </div>
                            <span class="text-[10px] text-slate-400 shrink-0">Hari ini</span>
                        </div>
                        @endforeach

                        @foreach($kelasBelumTerpetakan as $kelasBlok)
                        <div class="p-3 rounded-xl bg-amber-50/50 border border-amber-100 flex items-start justify-between gap-3 text-xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">Konfigurasi Blok</span>
                                    <span class="font-bold text-slate-900">{{ $kelasBlok->nama }}</span>
                                </div>
                                <p class="text-slate-600">Kelas berstatus sistem blok tetapi belum memiliki rotasi / blok awal.</p>
                            </div>
                            <a href="{{ route('admin.pemetaan-blok.index') }}" class="text-[11px] font-bold text-blue-600 hover:underline shrink-0">Atur</a>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 mt-4 flex items-center justify-between text-xs text-slate-500">
                    <span>Sistem audit otomatis SMKN 1 Ciamis</span>
                    <a href="{{ route('admin.jadwal.index') }}" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">Kelola Jadwal &rarr;</a>
                </div>
            </div>

            <!-- Log Aktivitas Terbaru -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <h3 class="font-bold text-slate-900 text-sm">Audit Trail / Log Aktivitas Terbaru</h3>
                        </div>
                        <a href="{{ route('super-admin.activity-logs.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline">
                            Lihat Semua &rarr;
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse($latestActivities as $act)
                        <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-100 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($act->user_name ?? 'S', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-slate-900 truncate">
                                        {{ $act->user_name ?? 'Sistem' }}
                                        <span class="text-[10px] font-normal text-slate-500">({{ ucfirst($act->role ?? 'system') }})</span>
                                    </p>
                                    <span class="text-[10px] text-slate-400 shrink-0">{{ $act->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-0.5 line-clamp-1">{{ $act->description }}</p>
                                <div class="flex items-center gap-1.5 mt-1.5">
                                    @php
                                        $actionColor = match(strtoupper($act->action)) {
                                            'CREATE' => 'emerald',
                                            'UPDATE' => 'blue',
                                            'DELETE' => 'red',
                                            'LOGIN' => 'violet',
                                            'IMPERSONATE' => 'amber',
                                            'RESTORE' => 'teal',
                                            default => 'slate',
                                        };
                                    @endphp
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-{{ $actionColor }}-50 text-{{ $actionColor }}-700 border border-{{ $actionColor }}-200">
                                        {{ $act->action }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">•</span>
                                    <span class="text-[10px] font-medium text-slate-500">{{ $act->module }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-100">
                            <p class="text-xs font-bold text-slate-700">Belum Ada Aktivitas Tercatat</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Aktivitas CRUD dan Login akan otomatis terdata di sini.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 mt-4 flex items-center justify-between text-xs text-slate-500">
                    <span>Audit trail tersimpan aman dan terenkripsi</span>
                    <a href="{{ route('super-admin.activity-logs.index') }}" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">Semua Log Aktivitas &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

<x-layouts.guru>
    <x-slot:title>Jadwal Saya</x-slot:title>

    <div class="px-4 py-4">
        {{-- Week navigation --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('guru.dashboard', ['minggu' => $prevWeek]) }}"
               class="p-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="text-center">
                <p class="text-sm font-semibold text-slate-900">
                    {{ $weekStart->translatedFormat('d M') }} – {{ $weekEnd->translatedFormat('d M Y') }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5">Minggu ini</p>
            </div>
            <a href="{{ route('guru.dashboard', ['minggu' => $nextWeek]) }}"
               class="p-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        {{-- Today shortcut banner --}}
        @php $todayHari = (int) $today->format('N'); @endphp
        <div class="mb-4 px-4 py-3 bg-blue-600 rounded-2xl flex items-center gap-3 text-white">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                <span class="text-lg font-bold">{{ $today->format('d') }}</span>
            </div>
            <div>
                <p class="font-semibold text-sm">Hari ini, {{ $today->translatedFormat('l, d F Y') }}</p>
                <p class="text-xs text-blue-100">
                    @if(isset($mingguIni[$todayHari]) && $mingguIni[$todayHari]['jadwals']->count() > 0)
                        {{ $mingguIni[$todayHari]['jadwals']->count() }} jadwal mengajar
                    @else
                        Tidak ada jadwal hari ini
                    @endif
                </p>
            </div>
        </div>

        {{-- Attendance Stats --}}
        <div class="mb-4 bg-white border border-slate-200 rounded-2xl p-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">Statistik Kehadiran Saya</h3>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="bg-green-50 rounded-xl p-3 border border-green-100 flex flex-col justify-center">
                    <p class="text-xs text-green-600 font-medium">Hadir & Terlambat</p>
                    <p class="text-xl font-bold text-green-900">{{ $stats['hadir'] }}</p>
                </div>
                <div class="bg-rose-50 rounded-xl p-3 border border-rose-100 flex flex-col justify-center">
                    <p class="text-xs text-rose-600 font-medium">Alpa / Tanpa Ket.</p>
                    <p class="text-xl font-bold text-rose-900">{{ $stats['alpa'] }}</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 border border-amber-100 flex flex-col justify-center">
                    <p class="text-xs text-amber-600 font-medium">Sakit</p>
                    <p class="text-xl font-bold text-amber-900">{{ $stats['sakit'] }}</p>
                </div>
                <div class="bg-purple-50 rounded-xl p-3 border border-purple-100 flex flex-col justify-center">
                    <p class="text-xs text-purple-600 font-medium">Izin</p>
                    <p class="text-xl font-bold text-purple-900">{{ $stats['izin'] }}</p>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="font-medium text-slate-600">Persentase Kehadiran</span>
                    <span class="font-bold {{ $stats['persentase'] < 80 ? 'text-rose-600' : 'text-green-600' }}">{{ $stats['persentase'] }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="h-2.5 rounded-full {{ $stats['persentase'] < 80 ? 'bg-rose-500' : 'bg-green-500' }} transition-all duration-500" style="width: {{ $stats['persentase'] }}%"></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-2 text-center">Berdasarkan total {{ $stats['total'] }} jadwal mengajar yang sudah direkap</p>
            </div>
        </div>

        {{-- Schedule by day --}}
        @forelse($mingguIni as $hariAngka => $dayData)
        @php
            $tanggal = $dayData['tanggal'];
            $jadwals = $dayData['jadwals'];
            $isToday = $tanggal->isSameDay($today);
            $hariLibur = $dayData['hariLibur'] ?? null;
        @endphp

        @if($jadwals->count() > 0 || $isToday || $hariLibur)
        <div class="mb-4">
            {{-- Day header --}}
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                            {{ $hariLibur ? 'bg-rose-100 text-rose-700 border border-rose-200' : ($isToday ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600') }}">
                    <span class="text-xs font-bold">{{ $tanggal->format('d') }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-sm font-semibold {{ $hariLibur ? 'text-rose-700' : ($isToday ? 'text-blue-700' : 'text-slate-700') }}">
                        {{ $hariList[$hariAngka] }}
                    </span>
                    @if($isToday)
                    <span class="text-xs bg-blue-100 text-blue-700 font-medium px-2 py-0.5 rounded-full">Hari ini</span>
                    @endif
                    @if($hariLibur)
                    <span class="text-xs bg-rose-50 text-rose-700 font-medium px-2.5 py-0.5 rounded-full border border-rose-200">
                        {{ $hariLibur->keterangan }}
                    </span>
                    @endif
                </div>
            </div>

            @if($hariLibur)
            <div class="ml-10 mb-2 p-3 bg-rose-50/70 border border-rose-100 rounded-xl text-xs text-rose-700 font-medium flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Hari libur terdaftar: <strong>{{ $hariLibur->keterangan }}</strong> ({{ $hariLibur->jenis_label }}). Presensi KBM ditiadakan.</span>
            </div>
            @endif

            @if($jadwals->count() > 0)
                <div class="space-y-2 ml-10">
                    @foreach($jadwals as $jadwal)
                    @php
                        $tanggalStr = $tanggal->toDateString();
                        $pertemuan = \App\Models\Pertemuan::where('jadwal_id', $jadwal->id)
                            ->where('tanggal', $tanggalStr)->first();
                        $statusPertemuan = $pertemuan?->status ?? 'belum';
                        $kehadiranGuru = $pertemuan?->kehadiranGuru;
                    @endphp
                    <a href="{{ route('guru.pertemuan.show', [$jadwal->id, $tanggalStr]) }}"
                       class="flex items-center gap-3 bg-white rounded-2xl border border-slate-200 p-3.5 hover:border-blue-300 hover:shadow-sm transition-all group">
                        {{-- Time column --}}
                        <div class="shrink-0 text-center w-14">
                            <p class="text-xs font-semibold text-slate-900">{{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }}</p>
                            <p class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}</p>
                        </div>
                        <div class="w-px h-10 bg-slate-200"></div>
                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $jadwal->mataPelajaran->nama }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $jadwal->kelas->nama }}</p>
                        </div>
                        {{-- Status badge --}}
                        <div class="shrink-0">
                            @if($kehadiranGuru)
                                <span class="text-xs font-medium px-2 py-1 rounded-full
                                    {{ $kehadiranGuru->status === 'hadir' ? 'badge-hadir' : ($kehadiranGuru->status === 'sakit' ? 'badge-sakit' : 'badge-alpa') }}">
                                    {{ ucfirst($kehadiranGuru->status) }}
                                </span>
                            @elseif($pertemuan)
                                <span class="text-xs font-medium px-2 py-1 rounded-full badge-menunggu">Belum absen</span>
                            @else
                                <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            @else
                <p class="ml-10 text-xs text-slate-400 italic py-1">Tidak ada jadwal</p>
            @endif
        </div>
        @endif
        @empty
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm font-medium">Belum ada jadwal mengajar</p>
            <p class="text-xs text-slate-400 mt-1">Hubungi admin untuk mengatur jadwal</p>
        </div>
        @endforelse
    </div>
</x-layouts.guru>

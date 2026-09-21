<x-layouts.siswa>
    <x-slot:title>Pelajaran Hari Ini</x-slot:title>

    <div class="px-4 py-4 space-y-4">
        {{-- Today banner --}}
        <div class="bg-emerald-600 rounded-2xl p-4 text-white flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-100">Hari ini</p>
                <p class="text-xl font-bold mt-0.5">{{ $today->translatedFormat('l, d F Y') }}</p>
                @php $siswaProfile = auth()->user()->siswaProfile; @endphp
                @if($siswaProfile?->kelas)
                <p class="text-sm text-emerald-100 mt-1">Kelas {{ $siswaProfile->kelas->nama }}</p>
                @endif
            </div>
            <div class="text-right bg-white/15 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-white/20">
                <span class="text-[11px] text-emerald-100 block font-medium">Jam Sekarang</span>
                <span class="text-base font-extrabold text-white tabular-nums">{{ $nowTime }} WIB</span>
            </div>
        </div>

        @if(isset($hariLiburHariIni) && $hariLiburHariIni)
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-rose-900">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-sm">Hari Ini Libur: {{ $hariLiburHariIni->keterangan }}</p>
                    <p class="text-xs text-rose-600 mt-0.5">{{ $hariLiburHariIni->jenis_label }} — KBM hari ini ditiadakan. Tidak ada kewajiban presensi.</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Jadwal list --}}
        @if($jadwalsWithStatus->count() > 0)
        <div class="space-y-3">
            <h2 class="text-sm font-semibold text-slate-700">Daftar Pelajaran</h2>
            @foreach($jadwalsWithStatus as $item)
            @php
                $jadwal = $item['jadwal'];
                $pertemuan = $item['pertemuan'];
                $sudahCapture = $item['sudahCapture'];
                $isStarted = $item['isStarted'];
                $isActiveNow = $item['isActiveNow'];
                $jamMulai = $item['jamMulai'];
                $jamSelesai = $item['jamSelesai'];
            @endphp
            <div class="bg-white rounded-2xl border {{ $sudahCapture ? 'border-emerald-200' : ($isActiveNow ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200') }} overflow-hidden transition-all">
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-slate-900">{{ $jadwal->mataPelajaran->nama }}</p>
                                @if($isActiveNow && !$sudahCapture)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center gap-1 shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    Sedang Berlangsung
                                </span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-500 mt-0.5">
                                {{ $jamMulai }} – {{ $jamSelesai }}
                            </p>
                            <p class="text-xs text-slate-400 mt-1">{{ $jadwal->guru->name }}</p>
                        </div>
                        @if($sudahCapture)
                        <div class="shrink-0 flex items-center gap-1 text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs font-medium">Terkirim</span>
                        </div>
                        @elseif(!$isStarted)
                        <div class="shrink-0 flex items-center gap-1 text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg text-xs font-medium">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Belum Buka</span>
                        </div>
                        @endif
                    </div>

                    <div class="mt-3">
                        @if($sudahCapture)
                        {{-- Already captured --}}
                        <a href="{{ route('siswa.capture.show', ['jadwal' => $jadwal->id, 'tanggal' => $today->toDateString()]) }}"
                           class="flex items-center justify-center gap-2 w-full py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium rounded-xl hover:bg-emerald-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ubah Foto Bukti
                        </a>
                        @elseif($isStarted)
                        {{-- Jam pelajaran sudah dimulai --}}
                        <a href="{{ route('siswa.capture.show', ['jadwal' => $jadwal->id, 'tanggal' => $today->toDateString()]) }}"
                           class="flex items-center justify-center gap-2 w-full py-2.5 {{ $isActiveNow ? 'bg-emerald-600 hover:bg-emerald-700 shadow-sm' : 'bg-slate-800 hover:bg-slate-900' }} text-white text-sm font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ambil Foto Bukti {{ $isActiveNow ? '(Sedang Berlangsung)' : '' }}
                        </a>
                        @else
                        {{-- Jam pelajaran belum dimulai --}}
                        <button type="button" disabled
                                class="flex items-center justify-center gap-2 w-full py-2.5 bg-slate-100 text-slate-400 text-xs font-semibold rounded-xl border border-slate-200 cursor-not-allowed opacity-80">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Belum Jam Pelajaran (Mulai {{ $jamMulai }} WIB)
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-slate-500 text-sm font-medium">Tidak ada pelajaran hari ini</p>
            <p class="text-xs text-slate-400 mt-1">Selamat beristirahat!</p>
        </div>
        @endif
    </div>
</x-layouts.siswa>

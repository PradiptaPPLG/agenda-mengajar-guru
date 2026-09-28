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
                $sudahCheckout = $item['sudahCheckout'];
                $isStarted = $item['isStarted'];
                $isActiveNow = $item['isActiveNow'];
                $jamMulai = $item['jamMulai'];
                $jamSelesai = $item['jamSelesai'];
                $totalJp = $item['totalJp'];
                $isMultiJam = $item['isMultiJam'];
                $canCheckout = $item['canCheckout'] ?? true;
                $jamCheckoutMulai = $item['jamCheckoutMulai'] ?? $jamSelesai;
            @endphp
            <div class="bg-white rounded-2xl border {{ $sudahCheckout ? 'border-emerald-300 ring-1 ring-emerald-400/30' : ($sudahCapture ? 'border-blue-200' : ($isActiveNow ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200')) }} overflow-hidden transition-all">
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-slate-900">{{ $jadwal->mataPelajaran->nama }}</p>
                                @if($isMultiJam)
                                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold shrink-0">
                                        {{ $totalJp }} JP
                                    </span>
                                @endif
                                @if($isActiveNow && !$sudahCapture)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center gap-1 shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    Sedang Berlangsung
                                </span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-500 mt-0.5">
                                {{ $jamMulai }} – {{ $jamSelesai }} WIB
                            </p>
                            <p class="text-xs text-slate-400 mt-1">{{ $jadwal->guru->name }}</p>
                        </div>

                        @if($enableCheckout && $sudahCheckout)
                        <div class="shrink-0 flex items-center gap-1 text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs font-bold">Check-out Selesai</span>
                        </div>
                        @elseif($sudahCapture)
                        <div class="shrink-0 flex items-center gap-1 text-emerald-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs font-medium">{{ $enableCheckout ? 'Masuk Terkirim' : 'Terkirim' }}</span>
                        </div>
                        @elseif(!$isStarted)
                        <div class="shrink-0 flex items-center gap-1 text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg text-xs font-medium">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Belum Buka</span>
                        </div>
                        @endif
                    </div>

                    <div class="mt-3 space-y-2">
                        @if($sudahCapture)
                            <div class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-800 bg-emerald-50/80 px-2.5 py-1.5 rounded-lg border border-emerald-200/80">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>
                                    @if($item['isMyCapture'])
                                        Anda telah melaporkan kehadiran guru
                                    @else
                                        Sudah dilaporkan oleh <strong class="font-semibold">{{ $item['reportedBy'] ?? 'Teman Sekelas' }}</strong>@if($item['reportedAt']) ({{ $item['reportedAt'] }} WIB)@endif
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- Materi & Tugas dari Guru (Point 5) --}}
                        @if($item['pertemuan'] && ($item['pertemuan']->materi_ajar || $item['pertemuan']->penugasan))
                            <div class="p-3 bg-blue-50/80 border border-blue-200/80 rounded-xl space-y-2 text-left">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-blue-900">
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    Materi & Tugas dari Guru
                                </div>
                                @if($item['pertemuan']->materi_ajar)
                                    <div>
                                        <p class="text-[11px] font-semibold text-blue-800">Materi Pembelajaran:</p>
                                        <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ $item['pertemuan']->materi_ajar }}</p>
                                    </div>
                                @endif
                                @if($item['pertemuan']->penugasan)
                                    <div class="pt-2 border-t border-blue-100">
                                        <p class="text-[11px] font-semibold text-amber-900 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            Tugas / Instruksi:
                                        </p>
                                        <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ $item['pertemuan']->penugasan }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($enableCheckout && $sudahCapture && !$sudahCheckout)
                            {{-- Foto Masuk Sudah, Foto Checkout Belum --}}
                            @if($canCheckout)
                                <a href="{{ route('siswa.capture.show', ['jadwal' => $jadwal->id, 'tanggal' => $today->toDateString()]) }}#section-checkout"
                                   class="flex items-center justify-center gap-2 w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Ambil Foto Check-out (Akhir Jam)
                                </a>
                            @else
                                <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-amber-800 text-xs font-semibold">
                                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Check-out Buka Pukul {{ $jamCheckoutMulai }} WIB
                                    </div>
                                    <p class="text-[10px] text-amber-600 mt-0.5">Dapat dilakukan 15 menit sebelum jam pelajaran berakhir</p>
                                </div>
                            @endif
                            <a href="{{ route('siswa.capture.show', ['jadwal' => $jadwal->id, 'tanggal' => $today->toDateString()]) }}"
                               class="flex items-center justify-center gap-1.5 w-full py-1.5 text-xs text-slate-500 hover:text-slate-700 font-medium">
                                {{ $item['isMyCapture'] ? 'Ubah Foto Masuk' : 'Lihat / Perbarui Foto Masuk' }}
                            </a>
                        @elseif($sudahCapture)
                            {{-- Already captured or Checkout complete --}}
                            <a href="{{ route('siswa.capture.show', ['jadwal' => $jadwal->id, 'tanggal' => $today->toDateString()]) }}"
                               class="flex items-center justify-center gap-2 w-full py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium rounded-xl hover:bg-emerald-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $sudahCheckout ? 'Lihat / Perbarui Foto Bukti' : ($item['isMyCapture'] ? 'Ubah Foto Bukti' : 'Lihat Bukti Foto Guru') }}
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

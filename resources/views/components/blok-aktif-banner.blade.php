{{--
    Komponen: Banner Blok Aktif Hari Ini
    Menampilkan informasi minggu dan kelompok blok yang sedang aktif.

    Props:
        $mingguAktif  — instance KalenderBlokMinggu|null
        $infoBlok     — array dari JadwalBlokResolverService->infoBlokKelas() per kelas (optional)
        $kelasList    — collection Kelas yang is_sistem_blok (optional, untuk menampilkan ringkasan per kelas)
--}}

@if ($mingguAktif ?? false)
    <div class="bg-blue-600 rounded-2xl p-4 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-blue-200 uppercase tracking-wide">Sistem Jadwal Blok — Aktif</p>
                <p class="text-sm font-bold">
                    {{ $mingguAktif->label }}
                    <span class="font-normal text-blue-200 text-xs ml-1">
                        ({{ $mingguAktif->tanggal_mulai->translatedFormat('d M') }} –
                        {{ $mingguAktif->tanggal_selesai->translatedFormat('d M Y') }})
                    </span>
                </p>
            </div>
        </div>

        @if(!empty($kelasList) && $kelasList->isNotEmpty())
            @php
                $countA = 0;
                $countB = 0;
                foreach($kelasList as $kelas) {
                    $kelompok = $mingguAktif->kelompokAktifUntukKelas($kelas);
                    if($kelompok === 'kelompok_a') $countA++;
                    if($kelompok === 'kelompok_b') $countB++;
                }
            @endphp
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-white/15 border border-white/10 rounded-xl px-4 py-2 flex flex-col items-center justify-center">
                    <span class="text-white/70 text-[10px] font-semibold uppercase tracking-wider mb-0.5">Berjalan di Kelompok A</span>
                    <span class="text-white font-bold text-sm">📘 {{ $countA }} <span class="font-normal text-white/80">Kelas</span></span>
                </div>
                <div class="bg-white/15 border border-white/10 rounded-xl px-4 py-2 flex flex-col items-center justify-center">
                    <span class="text-white/70 text-[10px] font-semibold uppercase tracking-wider mb-0.5">Berjalan di Kelompok B</span>
                    <span class="text-orange-200 font-bold text-sm">🔧 {{ $countB }} <span class="font-normal text-white/80">Kelas</span></span>
                </div>
                <a href="{{ route('admin.kelas.index') }}" class="ml-1 px-3 py-1.5 rounded-lg hover:bg-white/10 text-xs font-medium text-white transition-colors" title="Lihat detail pengaturan blok kelas">
                    Lihat Detail &rarr;
                </a>
            </div>
        @endif
    </div>
@elseif(isset($mingguAktif) && $mingguAktif === null)
    {{-- Kalender dikonfigurasi tapi tidak ada minggu aktif hari ini --}}
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M12 3a9 9 0 110 18A9 9 0 0112 3z" />
        </svg>
        <p class="text-xs text-amber-700">Sistem jadwal blok aktif, namun hari ini tidak masuk dalam kalender blok yang terdaftar.
            <a href="{{ route('admin.kalender-blok.index') }}" class="font-semibold underline">Kelola kalender blok.</a>
        </p>
    </div>
@endif

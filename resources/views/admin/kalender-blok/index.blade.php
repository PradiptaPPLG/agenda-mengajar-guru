<x-layouts.admin>
    <x-slot:title>Kalender Blok Mingguan</x-slot:title>
    <x-slot:subtitle>Atur rotasi mingguan Kelompok A (Umum) & Kelompok B (Kejuruan) per semester</x-slot:subtitle>
    <x-slot:actions>
        <a href="{{ route('admin.kelas.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-xl border border-blue-200 transition-colors shadow-2xs">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Tentukan Kelas Sistem Blok &rarr;
        </a>
    </x-slot:actions>

    {{-- 1. Card Generate Otomatis (Lebar Penuh ke Kanan / Horizontal) --}}
    <div class="w-full bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 pb-4 mb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Generate Otomatis Kalender Blok</h2>
                    <p class="text-xs text-slate-500">Buat jadwal rotasi mingguan satu semester secara otomatis dalam 1 baris formulir</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.kalender-blok.generate') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                {{-- Field 1: Tahun Ajaran --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tahun Ajaran <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="tahun_ajaran" value="{{ old('tahun_ajaran', '2025/2026') }}"
                        placeholder="2025/2026"
                        class="w-full h-11 px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50 hover:bg-white transition-colors @error('tahun_ajaran') border-rose-500 @enderror">
                    @error('tahun_ajaran')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field 2: Semester --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Semester <span class="text-rose-500">*</span>
                    </label>
                    <select name="semester"
                        class="w-full h-11 px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50 hover:bg-white transition-colors">
                        <option value="ganjil" @selected(old('semester') === 'ganjil')>Semester Ganjil</option>
                        <option value="genap" @selected(old('semester') === 'genap')>Semester Genap</option>
                    </select>
                </div>

                {{-- Field 3: Tanggal Mulai --}}
                <div class="lg:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tanggal Mulai (Senin) <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', now()->startOfWeek()->toDateString()) }}"
                        class="w-full h-11 px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50 hover:bg-white transition-colors @error('tanggal_mulai') border-rose-500 @enderror">
                    @error('tanggal_mulai')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field 4: Jumlah Minggu --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Jumlah Minggu <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="jumlah_minggu" value="{{ old('jumlah_minggu', 18) }}" min="1" max="30"
                        class="w-full h-11 px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50 hover:bg-white transition-colors @error('jumlah_minggu') border-rose-500 @enderror">
                    @error('jumlah_minggu')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field 5: Tombol Generate --}}
                <div class="sm:col-span-2 lg:col-span-3">
                    <button type="submit"
                        class="w-full h-11 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-xl transition-all shadow-sm hover:shadow flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Generate Kalender
                    </button>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Sistem secara otomatis menghitung 6 hari belajar (Senin s/d Sabtu). Jika minggu telah ada sebelumnya, data kalender akan diperbarui.</span>
            </div>
        </form>
    </div>

    {{-- 2. Card Tabel Kalender Blok (Full Width Lebar ke Kanan, Berada di Bawah Generate Otomatis) --}}
    <div class="w-full space-y-6">
        @forelse ($kalenders as $grupLabel => $mingguList)
            @php
                $mingguAktifGrup = $mingguList->first(fn($m) => $mingguAktif && $m->id === $mingguAktif->id);
            @endphp
            <div class="w-full bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                {{-- Header Group Semester dengan Fusion Info Minggu Aktif --}}
                <div class="bg-blue-600 text-white px-6 py-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white tracking-wide flex items-center gap-2">
                                {{ $grupLabel }}
                            </h3>
                            <p class="text-xs text-blue-100 mt-0.5">
                                Total <strong>{{ $mingguList->count() }} minggu</strong> terdaftar &bull; Rentang: {{ $mingguList->first()->tanggal_mulai->translatedFormat('d M Y') }} s/d {{ $mingguList->last()->tanggal_selesai->translatedFormat('d M Y') }}
                            </p>
                        </div>
                    </div>

                    {{-- Fusion: Info Status Minggu Aktif di Sisi Kanan Header --}}
                    <div class="flex items-center gap-3">
                        @if ($mingguAktifGrup)
                            <div class="inline-flex items-center gap-2 bg-emerald-500/25 border border-emerald-300/40 text-white px-3.5 py-2 rounded-xl shadow-xs">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                <div class="text-xs">
                                    <span class="font-bold">Minggu Aktif Hari Ini: Minggu ke-{{ $mingguAktifGrup->nomor_minggu }}</span>
                                    <span class="text-emerald-100 font-normal ml-1 hidden md:inline">({{ $mingguAktifGrup->tanggal_mulai->translatedFormat('d M') }} &ndash; {{ $mingguAktifGrup->tanggal_selesai->translatedFormat('d M Y') }})</span>
                                </div>
                            </div>
                        @else
                            <div class="text-xs text-blue-100 font-medium bg-blue-700/60 border border-blue-400/30 px-3 py-1.5 rounded-lg">
                                Semester Ini Belum / Tidak Sedang Aktif Hari Ini
                            </div>
                        @endif

                        {{-- Tombol Hapus Semua (Bulk Delete) --}}
                        @php
                            $firstMinggu = $mingguList->first();
                        @endphp
                        <form method="POST" action="{{ route('admin.kalender-blok.destroy-group') }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA minggu pada Semester {{ ucfirst($firstMinggu->semester) }} {{ $firstMinggu->tahun_ajaran }}? Aksi ini tidak dapat dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tahun_ajaran" value="{{ $firstMinggu->tahun_ajaran }}">
                            <input type="hidden" name="semester" value="{{ $firstMinggu->semester }}">
                            <button type="submit" title="Hapus Semua Kalender Semester Ini" class="inline-flex items-center justify-center p-2 bg-white/10 hover:bg-rose-500 text-white border border-white/20 hover:border-rose-400 rounded-xl transition-colors shadow-xs cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Tabel Data --}}
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider w-40">No. Minggu</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Tanggal Mulai</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Tanggal Selesai</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider">Rotasi Acuan</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider w-36">Status</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($mingguList as $minggu)
                                @php
                                    $isAktif = $mingguAktif && $mingguAktif->id === $minggu->id;
                                    $isPast = $minggu->tanggal_selesai->isPast() && ! $isAktif;
                                @endphp
                                <tr class="{{ $isAktif ? 'bg-blue-50/60 font-medium' : ($loop->even ? 'bg-slate-50/30' : 'bg-white') }} hover:bg-blue-50/30 transition-colors">
                                    {{-- Kolom No. Minggu --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-7 h-7 rounded-lg {{ $isAktif ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 font-semibold' }} flex items-center justify-center text-xs">
                                                {{ $minggu->nomor_minggu }}
                                            </span>
                                            <span class="font-bold text-slate-900">
                                                Minggu ke-{{ $minggu->nomor_minggu }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Kolom Tanggal Mulai --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-slate-800 font-medium flex items-center gap-1.5">
                                            <span class="text-xs text-slate-400 font-normal">Senin,</span>
                                            {{ $minggu->tanggal_mulai->translatedFormat('d M Y') }}
                                        </div>
                                    </td>

                                    {{-- Kolom Tanggal Selesai --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-slate-800 font-medium flex items-center gap-1.5">
                                            <span class="text-xs text-slate-400 font-normal">Sabtu,</span>
                                            {{ $minggu->tanggal_selesai->translatedFormat('d M Y') }}
                                        </div>
                                    </td>

                                    {{-- Kolom Rotasi Acuan --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($minggu->nomor_minggu % 2 !== 0)
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60 px-2.5 py-1 rounded-lg">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                                Pekan Ganjil (Blok 1)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/60 px-2.5 py-1 rounded-lg">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                Pekan Genap (Blok 2)
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Kolom Status --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($isAktif)
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-full shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Aktif
                                            </span>
                                        @elseif ($isPast)
                                            <span class="inline-flex items-center text-xs font-medium text-slate-400 bg-slate-100 px-2.5 py-0.5 rounded-full">
                                                Selesai
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-xs font-medium text-slate-600 bg-slate-100/80 px-2.5 py-0.5 rounded-full">
                                                Mendatang
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Kolom Aksi --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <form method="POST"
                                            action="{{ route('admin.kalender-blok.destroy', $minggu) }}"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ $minggu->label }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs text-rose-600 hover:text-white hover:bg-rose-600 border border-rose-200 hover:border-rose-600 font-semibold rounded-lg transition-colors cursor-pointer shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Helper Text --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 rounded-b-2xl">
                    <span class="font-semibold text-slate-700">Catatan:</span> Rotasi "Kelompok A" atau "Kelompok B" yang aktual pada pekan berjalan akan mengikuti pengaturan <a href="{{ route('admin.kelas.index') }}" class="text-blue-600 hover:underline">Blok Awal kelas masing-masing</a>. Jika Blok Awal = A, maka Pekan Ganjil = Kelompok A.
                </div>
            </div>
        @empty
            <div class="w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Belum Ada Kalender Blok</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                    Kalender blok rotasi mingguan belum dibuat untuk semester ini. Silakan tentukan semester dan tanggal mulai pada form di atas, lalu klik tombol <strong>Generate Kalender</strong>.
                </p>
            </div>
        @endforelse
    </div>
</x-layouts.admin>


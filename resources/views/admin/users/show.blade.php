<x-layouts.admin>
    <x-slot:title>Detail Guru - {{ $user->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto space-y-6 pb-12">
        {{-- Breadcrumb & Top Navigation --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('admin.users.index') }}" class="hover:text-blue-600 font-medium transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Daftar Guru
                </a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Detail Guru</span>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Data Guru
                </a>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl transition-colors">
                    Kembali
                </a>
            </div>
        </div>

        {{-- Hero Profile Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-bl from-blue-50/60 via-indigo-50/30 to-transparent rounded-full -mr-20 -mt-20 pointer-events-none"></div>

            <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center font-bold text-3xl shadow-md shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $user->name }}</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                {{ $user->is_active ? 'Akun Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                <span>NIP: <strong class="text-slate-800">{{ $user->guruProfile?->nip ?: 'Belum diisi' }}</strong></span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>{{ $user->email ?: 'Tidak ada email' }}</span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>Role Utama: <strong class="text-slate-800">{{ Str::title(str_replace('_', ' ', $user->role)) }}</strong></span>
                            </div>
                        </div>

                        {{-- Badges Peran Tambahan --}}
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @forelse($user->roles as $r)
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg {{ str_contains(strtolower($r->name), 'kaprog') ? 'bg-purple-100 text-purple-800 border border-purple-200' : (str_contains(strtolower($r->name), 'bk') ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : (str_contains(strtolower($r->name), 'wali') ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200')) }}">
                                    {{ $r->name }}
                                    @if(str_contains(strtolower($r->name), 'kaprog') && $user->guruProfile?->kaprog_jurusan)
                                        ({{ $user->guruProfile->kaprog_jurusan }})
                                    @endif
                                </span>
                            @empty
                                <span class="text-xs text-slate-400 italic">Tidak ada tugas tambahan</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Statistik Guru Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kelas Diampu</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalKelasDiampu }} <span class="text-xs font-normal text-slate-400">Kelas</span></p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mata Pelajaran</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalMapelDiajar }} <span class="text-xs font-normal text-slate-400">Mapel</span></p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jadwal Mengajar</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalJadwal }} <span class="text-xs font-normal text-slate-400">Pertemuan / Pekan</span></p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tugas Khusus</p>
                        <p class="text-base font-bold text-slate-900 mt-1 truncate">
                            @if($waliKelas)
                                Wali: {{ $waliKelas->nama }}
                            @elseif($user->guruProfile?->kaprog_jurusan)
                                Kaprog {{ $user->guruProfile->kaprog_jurusan }}
                            @elseif($bkKelas->count() > 0)
                                BK ({{ $bkKelas->count() }} Kelas)
                            @else
                                Guru Pengajar
                            @endif
                        </p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tugas Tambahan Details (Wali Kelas, BK, Kaprog) --}}
        @if($waliKelas || $bkKelas->count() > 0 || $user->guruProfile?->kaprog_jurusan)
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-xs">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Informasi Penugasan Binaan</h2>
                        <p class="text-xs text-slate-500">Kelas dan jurusan binaan khusus yang diemban guru</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($waliKelas)
                        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/40 space-y-1.5">
                            <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Wali Kelas</span>
                            <p class="text-lg font-bold text-slate-900">Kelas {{ $waliKelas->nama }}</p>
                            <p class="text-xs text-slate-600">{{ $waliKelas->siswa_profiles_count ?? 0 }} Siswa terdaftar &bull; Tahun Ajaran: {{ $waliKelas->tahun_ajaran }}</p>
                        </div>
                    @endif

                    @if($bkKelas->count() > 0)
                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40 space-y-1.5 md:col-span-2">
                            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Guru Bimbingan Konseling (BK)</span>
                            <p class="text-xs text-slate-600">Membina <strong>{{ $bkKelas->count() }} Kelas</strong>:</p>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach($bkKelas as $bkKls)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-emerald-200 text-emerald-800 shadow-2xs">
                                        {{ $bkKls->nama }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($user->guruProfile?->kaprog_jurusan)
                        <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/40 space-y-1.5">
                            <span class="text-xs font-bold text-purple-800 uppercase tracking-wider">Kepala Program Keahlian</span>
                            <p class="text-lg font-bold text-slate-900">{{ $user->guruProfile->kaprog_jurusan }}</p>
                            <p class="text-xs text-slate-600">Memantau seluruh kelas pada jurusan keahlian ini</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Section Utama: Pemetaan Mata Pelajaran & SEMUA Kelas yang Diampu --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6 shadow-xs">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-base">Mata Pelajaran & Seluruh Kelas yang Diampu</h2>
                        <p class="text-xs text-slate-500">Rincian lengkap setiap mata pelajaran dan kelas binaan yang diajar oleh guru ini</p>
                    </div>
                </div>

                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                    <span>Atur Kelas Diampu</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            @if(count($mapelMengajar) > 0)
                <div class="space-y-4">
                    @foreach($mapelMengajar as $item)
                        <div class="p-5 border border-slate-200/90 rounded-2xl bg-gradient-to-r from-slate-50/50 to-white hover:border-blue-200 transition-colors">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-sm shrink-0">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                                            {{ $item['mapel']->nama }}
                                            @if($item['mapel']->kode)
                                                <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ $item['mapel']->kode }}
                                                </span>
                                            @endif
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Kelompok: <span class="font-medium text-slate-700 capitalize">{{ $item['mapel']->jenis ?? 'Umum' }}</span>
                                            @if($item['mapel']->kelompok_blok)
                                                &bull; Blok: <span class="font-medium text-slate-700 uppercase">{{ str_replace('_', ' ', $item['mapel']->kelompok_blok) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-xs text-slate-600 self-end md:self-auto">
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 font-semibold rounded-lg border border-blue-100">
                                        {{ count($item['kelas']) }} Kelas Diampu
                                    </span>
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-semibold rounded-lg border border-slate-200">
                                        {{ $item['total_jadwal'] }} Sesi Jadwal / Pekan
                                    </span>
                                </div>
                            </div>

                            {{-- List Semua Kelas Yang Diampu --}}
                            <div class="mt-4">
                                <p class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Daftar Kelas Binaan ({{ count($item['kelas']) }} Kelas):
                                </p>

                                @if(count($item['kelas']) > 0)
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                                        @foreach($item['kelas'] as $kls)
                                            @php
                                                $jadwalDiKelas = $item['jadwals']->where('kelas_id', $kls->id);
                                            @endphp
                                            <div class="p-3 bg-white border border-slate-200 rounded-xl hover:shadow-xs transition-shadow flex flex-col justify-between">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-bold text-slate-900 text-sm">{{ $kls->nama }}</span>
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                </div>
                                                <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between items-center">
                                                    <span>{{ $jadwalDiKelas->count() }} Sesi</span>
                                                    @if($kls->tingkat)
                                                        <span class="text-slate-400">Tk. {{ $kls->tingkat }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center justify-between">
                                        <span>Mapel ini diampu oleh guru, namun belum ada kelas yang ditugaskan.</span>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="underline font-semibold hover:text-amber-900">Pilih Kelas di Edit Guru</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <p class="font-bold text-slate-700 text-sm">Belum Ada Mata Pelajaran & Kelas yang Diampu</p>
                    <p class="text-xs text-slate-400 mt-1">Silakan atur mata pelajaran dan kelas binaan melalui tombol edit di bawah.</p>
                    <a href="{{ route('admin.users.edit', $user) }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors">
                        Atur Mata Pelajaran & Kelas
                    </a>
                </div>
            @endif
        </div>

        {{-- Jadwal Mengajar Mingguan (Per Hari) --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-xs" x-data="{ activeHari: 1 }">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-base">Jadwal Mengajar Mingguan</h2>
                        <p class="text-xs text-slate-500">Rincian jam mengajar dan ruang kelas per hari (Senin s.d. Sabtu)</p>
                    </div>
                </div>

                {{-- Tab Hari --}}
                <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl overflow-x-auto max-w-full">
                    @foreach($hariNames as $hNum => $hName)
                        @php
                            $countHari = count($jadwalPerHari[$hNum]['list'] ?? []);
                        @endphp
                        <button type="button" @click="activeHari = {{ $hNum }}"
                                :class="activeHari === {{ $hNum }} ? 'bg-white text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                class="px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap">
                            <span>{{ $hName }}</span>
                            @if($countHari > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">{{ $countHari }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Isi Jadwal Per Hari --}}
            @foreach($jadwalPerHari as $hNum => $hData)
                <div x-show="activeHari === {{ $hNum }}" class="space-y-2">
                    @if(count($hData['list']) > 0)
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-sm min-w-[600px]">
                                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider font-semibold border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12">No</th>
                                        <th class="px-4 py-3 text-left">Waktu / Jam</th>
                                        <th class="px-4 py-3 text-left">Kelas</th>
                                        <th class="px-4 py-3 text-left">Mata Pelajaran</th>
                                        <th class="px-4 py-3 text-left">Kelompok Blok</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($hData['list'] as $j)
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="px-4 py-3 text-slate-400 font-medium">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200/60 font-mono text-xs font-bold">
                                                    {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 font-bold text-slate-900">
                                                <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-slate-800 border border-slate-200 text-xs">
                                                    {{ $j->kelas->nama ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-slate-800">
                                                {{ $j->mataPelajaran->nama ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($j->kelompok_blok && $j->kelompok_blok !== 'reguler')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold {{ $j->kelompok_blok === 'kelompok_a' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-purple-100 text-purple-800 border border-purple-200' }}">
                                                        {{ strtoupper(str_replace('_', ' ', $j->kelompok_blok)) }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400">Reguler</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('admin.jadwal.edit', $j) }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                                                    Edit Jadwal
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-8 text-center bg-slate-50/60 rounded-xl border border-slate-200/70">
                            <p class="text-sm font-semibold text-slate-600">Tidak ada jadwal mengajar pada hari {{ $hData['name'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Hari libur atau tidak ada jam pelajaran yang dijadwalkan.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>

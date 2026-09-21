<x-layouts.admin>
    <x-slot:title>Detail Kelas {{ $kelas->nama }}</x-slot:title>

    <div class="max-w-7xl mx-auto space-y-6 pb-12">
        {{-- Top Bar & Action Buttons --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.kelas.index') }}" class="p-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 leading-tight">Kelas {{ $kelas->nama }}</h1>
                    <p class="text-xs text-slate-500">Tingkat {{ $kelas->tingkat }} • Tahun Ajaran {{ $kelas->tahun_ajaran }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.kelas.edit', $kelas) }}" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Kelas
                </a>
            </div>
        </div>

        {{-- Row 1: Informasi Pembina Kelas (Wali Kelas & Guru BK) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Card 1: Summary --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0l-2 2m2-2l2 2"/></svg>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Ringkasan Binaan</span>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight">Kelas {{ $kelas->nama }}</h3>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-slate-100">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-[11px] font-medium text-slate-500 block">Total Siswa</span>
                        <span class="text-xl font-bold text-slate-900 mt-0.5 block">{{ $totalSiswa }} Siswa</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-[11px] font-medium text-slate-500 block">Total Jadwal KBM</span>
                        <span class="text-xl font-bold text-slate-900 mt-0.5 block">{{ $kelas->jadwalPelajarans->count() }} Sesi</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Wali Kelas --}}
            <div class="bg-white rounded-2xl border border-amber-200/80 p-5 shadow-xs relative overflow-hidden bg-gradient-to-br from-amber-50/30 to-white">
                <div class="flex items-center justify-between pb-3 border-b border-amber-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-900">Wali Kelas</span>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md">Pembina Akademik</span>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center text-lg border-2 border-amber-300 shrink-0">
                        {{ $kelas->waliKelas ? substr($kelas->waliKelas->name, 0, 1) : '?' }}
                    </div>
                    <div class="min-w-0">
                        @if($kelas->waliKelas)
                            <h4 class="font-bold text-slate-900 text-sm truncate leading-tight">{{ $kelas->waliKelas->name }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">NIP: {{ $kelas->waliKelas->guruProfile?->nip ?? '—' }}</p>
                            <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $kelas->waliKelas->email }}</p>
                        @else
                            <p class="text-sm font-semibold text-slate-400 italic">Belum Ditugaskan</p>
                            <p class="text-xs text-slate-400 mt-0.5">Atur Wali Kelas pada menu edit</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card 3: Guru BK --}}
            <div class="bg-white rounded-2xl border border-emerald-200/80 p-5 shadow-xs relative overflow-hidden bg-gradient-to-br from-emerald-50/30 to-white">
                <div class="flex items-center justify-between pb-3 border-b border-emerald-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-900">Guru BK</span>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md">Bimbingan Konseling</span>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-lg border-2 border-emerald-300 shrink-0">
                        {{ $kelas->guruBk ? substr($kelas->guruBk->name, 0, 1) : '?' }}
                    </div>
                    <div class="min-w-0">
                        @if($kelas->guruBk)
                            <h4 class="font-bold text-slate-900 text-sm truncate leading-tight">{{ $kelas->guruBk->name }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">NIP: {{ $kelas->guruBk->guruProfile?->nip ?? '—' }}</p>
                            <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $kelas->guruBk->email }}</p>
                        @else
                            <p class="text-sm font-semibold text-slate-400 italic">Belum Ditugaskan</p>
                            <p class="text-xs text-slate-400 mt-0.5">Atur Guru BK pada menu edit</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Tabs Content (Jadwal Pelajaran & Daftar Siswa) --}}
        <div x-data="{ tab: 'jadwal' }" class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            {{-- Navigation Tabs --}}
            <div class="flex border-b border-slate-200 bg-slate-50/80 px-4 pt-3 gap-2">
                <button @click="tab = 'jadwal'" 
                        :class="tab === 'jadwal' ? 'bg-white text-blue-600 border-t-2 border-blue-600 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        class="px-5 py-3 text-sm rounded-t-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Jadwal & Guru Mengajar
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">{{ $kelas->jadwalPelajarans->count() }}</span>
                </button>
                <button @click="tab = 'siswa'" 
                        :class="tab === 'siswa' ? 'bg-white text-blue-600 border-t-2 border-blue-600 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900 font-semibold'"
                        class="px-5 py-3 text-sm rounded-t-xl transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Daftar Siswa Binaan
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">{{ $totalSiswa }}</span>
                </button>
            </div>

            {{-- Tab 1: Jadwal & Guru Mengajar --}}
            <div x-show="tab === 'jadwal'" class="p-6">
                @php
                    $namaHari = [
                        1 => 'Senin',
                        2 => 'Selasa',
                        3 => 'Rabu',
                        4 => 'Kamis',
                        5 => 'Jumat',
                        6 => 'Sabtu',
                        7 => 'Minggu',
                    ];
                @endphp

                @if($jadwalGrouped->count() > 0)
                    <div class="space-y-6">
                        @foreach($namaHari as $hariNum => $hariLabel)
                            @if(isset($jadwalGrouped[$hariNum]))
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                        <h3 class="font-bold text-slate-800 text-sm">Hari {{ $hariLabel }}</h3>
                                    </div>
                                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                                                <tr>
                                                    <th class="px-4 py-2.5">Jam Pelajaran</th>
                                                    <th class="px-4 py-2.5">Mata Pelajaran</th>
                                                    <th class="px-4 py-2.5">Kategori</th>
                                                    <th class="px-4 py-2.5">Guru Pengajar</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($jadwalGrouped[$hariNum] as $j)
                                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                                        <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }} WIB
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 font-medium text-slate-900">
                                                            {{ $j->mataPelajaran->nama ?? '—' }}
                                                            @if($j->mataPelajaran?->kode)
                                                                <span class="ml-1 text-[10px] font-mono text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">({{ $j->mataPelajaran->kode }})</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            @if(in_array($j->mataPelajaran?->jenis, ['produktif', 'adaptif', 'kejuruan']))
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                                                    Kejuruan (Produktif)
                                                                </span>
                                                            @else
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                                    Umum (Normatif & Adaptif)
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            @if($j->guru)
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                                                                        {{ substr($j->guru->name, 0, 1) }}
                                                                    </div>
                                                                    <div>
                                                                        <span class="font-bold text-slate-900 block leading-tight">{{ $j->guru->name }}</span>
                                                                        @if($j->guru->guruProfile?->nip)
                                                                            <span class="text-[10px] text-slate-500 block">NIP. {{ $j->guru->guruProfile->nip }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <span class="text-slate-400 italic">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h4 class="font-bold text-slate-700 text-sm">Belum Ada Jadwal Pelajaran</h4>
                        <p class="text-xs text-slate-500 mt-1">Jadwal KBM untuk kelas ini belum diatur di sistem.</p>
                    </div>
                @endif
            </div>

            {{-- Tab 2: Daftar Siswa Binaan --}}
            <div x-show="tab === 'siswa'" class="p-6" style="display: none;">
                @if($kelas->siswaProfiles->count() > 0)
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">No</th>
                                    <th class="px-4 py-3">Nama Siswa</th>
                                    <th class="px-4 py-3">NIS</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3 text-right">Status Akun</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($kelas->siswaProfiles as $idx => $sp)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-4 py-3 text-slate-400 font-semibold">{{ $idx + 1 }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-900">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                                    {{ substr($sp->user->name ?? 'S', 0, 1) }}
                                                </div>
                                                <span>{{ $sp->user->name ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-slate-600">{{ $sp->nis ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ $sp->user->email ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h4 class="font-bold text-slate-700 text-sm">Belum Ada Siswa Terdaftar</h4>
                        <p class="text-xs text-slate-500 mt-1">Belum ada siswa yang ditempatkan di kelas ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>

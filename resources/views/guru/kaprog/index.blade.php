<x-layouts.guru>
    <x-slot:title>Dashboard Kaprog</x-slot:title>

    <div class="px-4 py-4 space-y-6">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-2xl p-5 text-white shadow-md">
            <h1 class="text-xl font-bold">Dashboard Kepala Program (Kaprog)</h1>
            <p class="text-sm opacity-90 mt-1">Pemantauan KBM & Kehadiran Jurusan {{ $jurusan ?? 'Keseluruhan' }} (Kelas 10–12)</p>
        </div>

        <form method="GET" class="flex flex-col sm:flex-row gap-2">
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="flex-1 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition-colors">Tampilkan Laporan</button>
        </form>

        {{-- Pemantauan Guru Mengajar di Jurusan --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Pemantauan Guru Mengajar Jurusan {{ $jurusan ?? '' }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Kehadiran dan agenda mengajar guru di seluruh kelas jurusan Anda hari ini</p>
                </div>
                <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full border border-indigo-200">
                    {{ $rekapGuru['total'] }} Sesi KBM
                </span>
            </div>

            {{-- Summary Cards Guru --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs">
                    <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Guru Hadir</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1">{{ $rekapGuru['hadir'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs">
                    <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Terlambat</p>
                    <p class="text-2xl font-black text-amber-700 mt-1">{{ $rekapGuru['terlambat'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs">
                    <p class="text-[11px] font-bold text-red-600 uppercase tracking-wider">Tidak Hadir / Izin</p>
                    <p class="text-2xl font-black text-red-700 mt-1">{{ $rekapGuru['tidak_hadir'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Belum Mulai</p>
                    <p class="text-2xl font-black text-slate-700 mt-1">{{ $rekapGuru['belum_hadir'] }}</p>
                </div>
            </div>

            {{-- Tabel Pemantauan Guru --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Guru & NIP</th>
                                <th class="px-4 py-3">Kelas & Mapel</th>
                                <th class="px-4 py-3">Jam KBM</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Materi / Keterangan</th>
                                <th class="px-4 py-3 text-center">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($monitoringGuru as $item)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900 leading-tight">{{ $item['guru']->name ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono mt-0.5">NIP: {{ $item['guru']?->guruProfile?->nip ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 bg-purple-50 text-purple-700 text-xs font-semibold rounded border border-purple-200 mb-0.5">
                                        {{ $item['kelas']->nama }}
                                    </span>
                                    <p class="text-xs text-slate-700">{{ $item['mapel']->nama }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-xs font-medium text-slate-800">
                                        {{ substr($item['jadwal']->jam_mulai, 0, 5) }} – {{ substr($item['jadwal']->jam_selesai, 0, 5) }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($item['status'] === 'hadir')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Hadir
                                        </span>
                                    @elseif($item['status'] === 'terlambat')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Terlambat
                                        </span>
                                    @elseif(in_array($item['status'], ['tidak_hadir', 'sakit', 'alpa', 'dispensasi']))
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-700 bg-red-50 border border-red-200 px-2.5 py-1 rounded-lg">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> {{ $item['status_label'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
                                            Belum Mulai
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-slate-700 line-clamp-2 max-w-xs">{{ $item['keterangan'] ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if(!empty($item['foto']))
                                        <a href="{{ $item['foto'] }}" target="_blank" class="inline-block hover:opacity-80 transition-opacity">
                                            <img src="{{ $item['foto'] }}" alt="Bukti" class="w-8 h-8 rounded-lg object-cover border border-slate-200 mx-auto">
                                        </a>
                                    @else
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-sm">Tidak ada jadwal KBM guru pada tanggal ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800 text-lg">Rekap KBM Jurusan {{ $jurusan ?? '' }} ({{ $date->translatedFormat('d F Y') }})</h2>
                <span class="text-xs font-medium bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full border border-purple-200">{{ $kelasJurusan->count() }} Kelas</span>
            </div>
            
            @forelse($rekapKelas as $kelasId => $rekap)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                    <h3 class="font-bold text-slate-900">{{ $rekap['kelas']->nama }}</h3>
                    <span class="text-xs font-semibold px-2 py-1 bg-purple-100 text-purple-700 rounded-lg">Kelas {{ $rekap['kelas']->tingkat }}</span>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                        <p class="text-[10px] font-bold text-emerald-600 uppercase">Hadir</p>
                        <p class="text-xl font-black text-emerald-700">{{ $rekap['hadir'] }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 border border-amber-100">
                        <p class="text-[10px] font-bold text-amber-600 uppercase">Terlambat</p>
                        <p class="text-xl font-black text-amber-700">{{ $rekap['terlambat'] }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="text-[10px] font-bold text-blue-600 uppercase">Sakit/Izin</p>
                        <p class="text-xl font-black text-blue-700">{{ $rekap['sakit'] + $rekap['izin'] }}</p>
                    </div>
                    <div class="bg-rose-50 rounded-xl p-3 border border-rose-100">
                        <p class="text-[10px] font-bold text-rose-600 uppercase">Alpa</p>
                        <p class="text-xl font-black text-rose-700">{{ $rekap['alpa'] }}</p>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-sm text-slate-500 text-center py-4">Belum ada data kelas jurusan ini.</p>
            @endforelse
        </div>

        <div class="space-y-4">
            <h2 class="font-bold text-slate-800 text-lg">Detail Kehadiran Siswa Jurusan</h2>
            
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Siswa & Kelas</th>
                                <th class="px-4 py-3">Mapel</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($kehadiran as $kh)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900 leading-tight">{{ $kh->siswa->name ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $kh->siswa?->siswaProfile?->kelas?->nama ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-slate-700">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $kh->pertemuan?->jadwal?->jam_mulai ? \Carbon\Carbon::parse($kh->pertemuan->jadwal->jam_mulai)->format('H:i') . ' - ' . \Carbon\Carbon::parse($kh->pertemuan->jadwal->jam_selesai)->format('H:i') : '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if($kh->status === 'hadir')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-md">Hadir</span>
                                    @elseif($kh->status === 'terlambat')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded-md">Terlambat</span>
                                    @elseif(in_array($kh->status, ['sakit', 'izin']))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-1 rounded-md">{{ ucfirst($kh->status) }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-1 rounded-md">Alpa</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500 text-sm">Tidak ada catatan presensi pada tanggal ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guru>

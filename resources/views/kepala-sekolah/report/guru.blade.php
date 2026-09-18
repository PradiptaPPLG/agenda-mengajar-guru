<x-dynamic-component :component="auth()->user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.kepala-sekolah'">
    <x-slot:title>Laporan Kehadiran Guru</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('kepala-sekolah.pdf.guru', request()->query()) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </a>
    </x-slot:actions>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5 flex flex-wrap gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
                   class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
                   class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Guru</label>
            <select name="guru_id" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Guru</option>
                @foreach($guruList as $g)
                <option value="{{ $g->id }}" {{ $selectedGuruId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
            <select name="kelas_id" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)
                <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors">Tampilkan</button>
        </div>
    </form>

    {{-- Summary cards --}}
    @if($summary->count() > 0)
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Ringkasan Per Guru</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</p>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Guru</th>
                    <th class="text-center px-4 py-3 font-semibold text-emerald-600">Hadir</th>
                    <th class="text-center px-4 py-3 font-semibold text-amber-600">Sakit</th>
                    <th class="text-center px-4 py-3 font-semibold text-purple-600">Dispensasi</th>
                    <th class="text-center px-4 py-3 font-semibold text-red-600">Alpa</th>
                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Total</th>
                    <th class="text-center px-4 py-3 font-semibold text-blue-600">% Hadir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($summary as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $item['guru']->name }}</td>
                    <td class="px-4 py-3 text-center text-emerald-700 font-semibold">{{ $item['hadir'] }}</td>
                    <td class="px-4 py-3 text-center text-amber-700 font-semibold">{{ $item['sakit'] }}</td>
                    <td class="px-4 py-3 text-center text-purple-700 font-semibold">{{ $item['dispensasi'] }}</td>
                    <td class="px-4 py-3 text-center text-red-700 font-semibold">{{ $item['alpa'] }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $item['total'] }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $pct = $item['total'] > 0 ? round($item['hadir'] / $item['total'] * 100) : 0; @endphp
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $pct >= 80 ? 'bg-emerald-100 text-emerald-800' : ($pct >= 60 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                            {{ $pct }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Detail table --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Detail Kehadiran ({{ $kehadiran->count() }} data)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Tanggal</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Guru</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Mata Pelajaran</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Kelas</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Status (Guru)</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Status (Siswa)</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kehadiran as $kh)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $kh->pertemuan?->tanggal?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $kh->guru->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $kh->pertemuan?->jadwal?->kelas?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full inline-block w-fit
                                    {{ match($kh->status) {
                                        'hadir' => 'badge-hadir',
                                        'sakit' => 'badge-sakit',
                                        'dispensasi' => 'badge-dispensasi',
                                        default => 'badge-alpa',
                                    } }}">
                                    {{ $kh->status_label }}
                                </span>
                                @if($kh->jenis_alpa)
                                <span class="text-xs text-slate-400">{{ $kh->jenis_alpa_label }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $siswaReport = $kh->pertemuan?->fotoBuktis?->first();
                            @endphp
                            @if($siswaReport && $siswaReport->status_guru_dilaporkan)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full inline-block w-fit
                                    {{ match($siswaReport->status_guru_dilaporkan) {
                                        'hadir' => 'badge-hadir',
                                        'sakit' => 'badge-sakit',
                                        'dispensasi' => 'badge-dispensasi',
                                        default => 'badge-alpa',
                                    } }}">
                                    {{ ucfirst($siswaReport->status_guru_dilaporkan) }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400 italic">Belum Lapor</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs max-w-xs">
                            {{ $kh->guru_pengganti_nama ? 'Pengganti: '.$kh->guru_pengganti_nama : ($kh->keterangan ?? '—') }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm">Tidak ada data kehadiran</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-dynamic-component>

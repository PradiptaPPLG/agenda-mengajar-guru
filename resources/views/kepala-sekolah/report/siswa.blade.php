<x-dynamic-component :component="auth()->user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.kepala-sekolah'">
    <x-slot:title>Laporan Kehadiran Siswa</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('kepala-sekolah.pdf.siswa', request()->query()) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </a>
    </x-slot:actions>

    <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5 space-y-3" id="siswa-report-form">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mode Filter:</span>
                <div class="inline-flex p-1 bg-slate-100 rounded-xl">
                    <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'semester' ? 'bg-white text-blue-600 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                        <input type="radio" name="filter_mode" value="semester" {{ ($filterMode ?? 'semester') === 'semester' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('siswa-report-form').submit();">
                        Per Semester
                    </label>
                    <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'custom' ? 'bg-white text-blue-600 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                        <input type="radio" name="filter_mode" value="custom" {{ ($filterMode ?? 'semester') === 'custom' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('siswa-report-form').submit();">
                        Rentang Tanggal
                    </label>
                </div>
            </div>

            @if(($filterMode ?? 'semester') === 'semester')
            <div class="text-xs text-slate-500 font-medium">
                Rentang Tanggal: <span class="text-slate-800 font-semibold">{{ $startDate->format('d M Y') }} – {{ $endDate->format('d M Y') }}</span>
            </div>
            @endif
        </div>

        <div class="flex flex-wrap items-end gap-3">
            @if(($filterMode ?? 'semester') === 'semester')
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tahun Ajaran</label>
                    <select name="tahun_ajaran" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="document.getElementById('siswa-report-form').submit()">
                        <option value="all" {{ ($selectedTahunAjaran ?? '') === 'all' ? 'selected' : '' }}>Semua Tahun Ajaran</option>
                        @foreach($daftarTahunAjaran as $ta)
                        <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Semester</label>
                    <select name="semester" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="document.getElementById('siswa-report-form').submit()">
                        <option value="all" {{ ($selectedSemester ?? '') === 'all' ? 'selected' : '' }}>Semua Semester</option>
                        <option value="ganjil" {{ ($selectedSemester ?? '') === 'ganjil' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                        <option value="genap" {{ ($selectedSemester ?? '') === 'genap' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                    </select>
                </div>
            @else
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
            @endif

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                <select name="kelas_id" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="document.getElementById('siswa-report-form').submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2.5 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors">Tampilkan</button>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Rekap Kehadiran Siswa</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ ($selectedTahunAjaran && $selectedTahunAjaran !== 'all') ? $selectedTahunAjaran.' (Semester '.ucfirst($selectedSemester).') • ' : '' }}{{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Siswa</th>
                        <th class="text-center px-4 py-3 font-semibold text-emerald-600">Hadir</th>
                        <th class="text-center px-4 py-3 font-semibold text-amber-600">Sakit</th>
                        <th class="text-center px-4 py-3 font-semibold text-sky-600">Izin</th>
                        <th class="text-center px-4 py-3 font-semibold text-red-600">Alpa</th>
                        <th class="text-center px-4 py-3 font-semibold text-purple-600">Disp.</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600">Total</th>
                        <th class="text-center px-4 py-3 font-semibold text-blue-600">% Hadir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item['siswa']->name }}</td>
                        <td class="px-4 py-3 text-center text-emerald-700 font-semibold">{{ $item['hadir'] }}</td>
                        <td class="px-4 py-3 text-center text-amber-700 font-semibold">{{ $item['sakit'] }}</td>
                        <td class="px-4 py-3 text-center text-sky-700 font-semibold">{{ $item['izin'] }}</td>
                        <td class="px-4 py-3 text-center text-red-700 font-semibold">{{ $item['alpa'] }}</td>
                        <td class="px-4 py-3 text-center text-purple-700 font-semibold">{{ $item['dispensasi'] }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $item['total'] }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $pct = $item['total'] > 0 ? round($item['hadir'] / $item['total'] * 100) : 0;
                                $heatmapClass = match (true) {
                                    $pct >= 90 => 'bg-emerald-600 text-white font-bold border-emerald-700 shadow-2xs',
                                    $pct >= 80 => 'bg-emerald-100 text-emerald-900 font-bold border-emerald-300',
                                    $pct >= 70 => 'bg-amber-100 text-amber-900 font-bold border-amber-300',
                                    $pct >= 55 => 'bg-orange-100 text-orange-900 font-bold border-orange-300',
                                    default => 'bg-red-600 text-white font-bold border-red-700 shadow-2xs',
                                };
                            @endphp
                            <span class="text-xs px-2.5 py-1 rounded-lg border {{ $heatmapClass }} inline-block w-14 text-center">
                                {{ $pct }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">Tidak ada data kehadiran siswa</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($summary->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $summary->links() }}
        </div>
        @endif
    </div>
</x-dynamic-component>

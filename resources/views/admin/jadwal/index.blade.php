<x-layouts.admin>
    <x-slot:title>Jadwal Pelajaran</x-slot:title>
    <x-slot:actions>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.jadwal.export.excel', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
            <a href="{{ route('admin.jadwal.export.pdf', request()->query()) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
            <a href="{{ route('admin.jadwal.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Jadwal
            </a>
        </div>
    </x-slot:actions>

    <form method="GET" class="flex flex-wrap gap-3 mb-5" id="jadwal-filter-form">
        <select name="kelas_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="document.getElementById('jadwal-filter-form').submit()">
            <option value="">Semua Kelas</option>
            @foreach($kelasList as $k)
            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
            @endforeach
        </select>
        <select name="guru_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="document.getElementById('jadwal-filter-form').submit()">
            <option value="">Semua Guru</option>
            @foreach($guruList as $g)
            <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Hari</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Waktu</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Guru</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $hariNames = \App\Models\JadwalPelajaran::$namaHari; @endphp
                @forelse($jadwals as $j)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">{{ $hariNames[$j->hari] ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                        {{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $j->mataPelajaran->nama }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $j->kelas->nama }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $j->guru->name }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end">
                            <x-action-dropdown 
                                :editUrl="route('admin.jadwal.edit', $j)" 
                                :deleteUrl="route('admin.jadwal.destroy', $j)" 
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada jadwal</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($jadwals->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $jadwals->links() }}</div>
        @endif
    </div>
</x-layouts.admin>

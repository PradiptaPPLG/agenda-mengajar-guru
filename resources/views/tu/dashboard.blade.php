<x-layouts.admin>
    <x-slot:title>Dashboard TU - Rekapitulasi Presensi Siswa</x-slot:title>
    
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Filter Data Siswa</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kelas</label>
                    <select name="kelas_id" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $kelas)
                        <option value="{{ $kelas->id }}" {{ $selectedKelasId == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Rekapitulasi Kehadiran Siswa</h3>
                <p class="text-sm text-slate-500">Periode: {{ $startDate->translatedFormat('d F Y') }} - {{ $endDate->translatedFormat('d F Y') }}</p>
            </div>
            
            <a href="{{ route('kepala-sekolah.pdf.siswa', request()->all()) }}" target="_blank" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Nama Siswa</th>
                        <th class="px-6 py-4 font-semibold">Kelas</th>
                        <th class="px-6 py-4 text-center font-semibold text-emerald-600">Hadir</th>
                        <th class="px-6 py-4 text-center font-semibold text-blue-600">Sakit</th>
                        <th class="px-6 py-4 text-center font-semibold text-amber-600">Izin</th>
                        <th class="px-6 py-4 text-center font-semibold text-indigo-600">Dispensasi</th>
                        <th class="px-6 py-4 text-center font-semibold text-rose-600">Alpa</th>
                        <th class="px-6 py-4 text-center font-semibold">Total Pertemuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary as $row)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $row['siswa']->user->name }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $row['siswa']->kelas->nama }}</td>
                        <td class="px-6 py-4 text-center font-bold text-emerald-600">{{ $row['hadir'] }}</td>
                        <td class="px-6 py-4 text-center font-bold text-blue-600">{{ $row['sakit'] }}</td>
                        <td class="px-6 py-4 text-center font-bold text-amber-600">{{ $row['izin'] }}</td>
                        <td class="px-6 py-4 text-center font-bold text-indigo-600">{{ $row['dispensasi'] }}</td>
                        <td class="px-6 py-4 text-center font-bold text-rose-600">{{ $row['alpa'] }}</td>
                        <td class="px-6 py-4 text-center font-medium text-slate-900">{{ $row['total'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                            Tidak ada data siswa untuk filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($summary->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $summary->links() }}
        </div>
        @endif
    </div>
</x-layouts.admin>

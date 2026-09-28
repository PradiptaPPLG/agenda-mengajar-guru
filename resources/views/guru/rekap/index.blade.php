<x-layouts.guru>
    <x-slot:title>Rekap Kehadiran Kelas</x-slot:title>

    <div class="px-4 py-4 space-y-6">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-5 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold">Rekapitulasi Kehadiran Kelas</h1>
                <p class="text-sm opacity-90 mt-1">Unduh & pantau rekap presensi siswa per kelas dan mata pelajaran yang Anda ampu</p>
            </div>
            <a href="{{ route('guru.dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-semibold backdrop-blur-xs transition-colors self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Jadwal
            </a>
        </div>

        {{-- Form Filter --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-4">
            <form method="GET" class="space-y-4" id="filter-form">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    {{-- Pilih Kelas --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Kelas</label>
                        <select name="kelas_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @forelse($kelasList as $k)
                                <option value="{{ $k->id }}" {{ $selectedKelasId === $k->id ? 'selected' : '' }}>
                                    {{ $k->nama }}
                                </option>
                            @empty
                                <option value="">Tidak ada kelas</option>
                            @endforelse
                        </select>
                    </div>

                    {{-- Pilih Mapel --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Mata Pelajaran</label>
                        <select name="mata_pelajaran_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @forelse($mapelList as $m)
                                <option value="{{ $m->id }}" {{ $selectedMapelId === $m->id ? 'selected' : '' }}>
                                    {{ $m->nama }} ({{ $m->kode }})
                                </option>
                            @empty
                                <option value="">Tidak ada mapel</option>
                            @endforelse
                        </select>
                    </div>

                    {{-- Tipe Periode --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Periode</label>
                        <select name="periode_type" id="periode_type" onchange="togglePeriodeInputs()" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="bulanan" {{ $periodeType === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                            <option value="semester" {{ $periodeType === 'semester' ? 'selected' : '' }}>Per Semester</option>
                        </select>
                    </div>

                    {{-- Periode Bulanan Input --}}
                    <div id="wrapper-bulan" class="{{ $periodeType === 'bulanan' ? '' : 'hidden' }}">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Bulan</label>
                        <input type="month" name="bulan" value="{{ $bulan }}"
                               class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Periode Semester Inputs --}}
                    <div id="wrapper-semester" class="{{ $periodeType === 'semester' ? '' : 'hidden' }} sm:col-span-2 md:col-span-1 grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">T.A</label>
                            <select name="tahun_ajaran" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($daftarTahunAjaran as $ta)
                                    <option value="{{ $ta }}" {{ $tahunAjaran === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
                            <select name="semester" class="w-full px-2 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($daftarSemester as $kSem => $vSem)
                                    <option value="{{ $kSem }}" {{ $semester === $kSem ? 'selected' : '' }}>{{ $vSem }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-xs">
                        Tampilkan Data
                    </button>

                    @if($rekapData && $selectedKelasId && $selectedMapelId)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('guru.rekap.export-pdf', [
                            'kelas_id' => $selectedKelasId,
                            'mata_pelajaran_id' => $selectedMapelId,
                            'periode_type' => $periodeType,
                            'bulan' => $bulan,
                            'tahun_ajaran' => $tahunAjaran,
                            'semester' => $semester,
                        ]) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 rounded-xl text-xs font-semibold transition-colors shadow-xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Unduh PDF
                        </a>
                        <a href="{{ route('guru.rekap.export-excel', [
                            'kelas_id' => $selectedKelasId,
                            'mata_pelajaran_id' => $selectedMapelId,
                            'periode_type' => $periodeType,
                            'bulan' => $bulan,
                            'tahun_ajaran' => $tahunAjaran,
                            'semester' => $semester,
                        ]) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded-xl text-xs font-semibold transition-colors shadow-xs">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Unduh Excel
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </div>

        @if($rekapData)
            {{-- KPI Summary Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase">Total Siswa</p>
                    <p class="text-lg font-black text-slate-900 mt-0.5">{{ count($rekapData['rekapSiswa']) }}</p>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-emerald-600 uppercase">Hadir</p>
                    <p class="text-lg font-black text-emerald-700 mt-0.5">{{ number_format($rekapData['totalHadir']) }}</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 border border-amber-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-amber-600 uppercase">Terlambat</p>
                    <p class="text-lg font-black text-amber-700 mt-0.5">{{ number_format($rekapData['totalTerlambat']) }}</p>
                </div>
                <div class="bg-orange-50 rounded-xl p-3 border border-orange-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-orange-600 uppercase">Sakit</p>
                    <p class="text-lg font-black text-orange-700 mt-0.5">{{ number_format($rekapData['totalSakit']) }}</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-3 border border-blue-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-blue-600 uppercase">Izin</p>
                    <p class="text-lg font-black text-blue-700 mt-0.5">{{ number_format($rekapData['totalIzin']) }}</p>
                </div>
                <div class="bg-purple-50 rounded-xl p-3 border border-purple-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-purple-600 uppercase">Dispensasi</p>
                    <p class="text-lg font-black text-purple-700 mt-0.5">{{ number_format($rekapData['totalDispensasi']) }}</p>
                </div>
                <div class="bg-rose-50 rounded-xl p-3 border border-rose-200 shadow-2xs text-center">
                    <p class="text-[10px] font-bold text-rose-600 uppercase">Alpa</p>
                    <p class="text-lg font-black text-rose-700 mt-0.5">{{ number_format($rekapData['totalAlpa']) }}</p>
                </div>
            </div>

            {{-- Table Rekap --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">
                            {{ $rekapData['mataPelajaran']->nama }} &mdash; Kelas {{ $rekapData['kelas']->nama }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Periode: {{ $periodeType === 'bulanan' ? \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y') : \App\Models\Setting::getSemesterLabel($semester).' TA '.$tahunAjaran }} &bull; Total {{ $rekapData['totalPertemuanKelas'] }} Pertemuan KBM
                        </p>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">{{ count($rekapData['rekapSiswa']) }} siswa</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 text-xs">
                            <tr>
                                <th class="px-3 py-3 w-10 text-center">No</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-3 py-3 text-center">NIS</th>
                                <th class="px-3 py-3 text-center text-emerald-700">Hadir</th>
                                <th class="px-3 py-3 text-center text-amber-700">Terlambat</th>
                                <th class="px-3 py-3 text-center text-orange-700">Sakit</th>
                                <th class="px-3 py-3 text-center text-blue-700">Izin</th>
                                <th class="px-3 py-3 text-center text-purple-700">Disp.</th>
                                <th class="px-3 py-3 text-center text-rose-700">Alpa</th>
                                <th class="px-3 py-3 text-center">Total</th>
                                <th class="px-4 py-3 text-center">% Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rekapData['rekapSiswa'] as $i => $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-3 py-3 text-center text-xs text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900 leading-tight">{{ $row['siswa']->name }}</p>
                                    @if($row['nisn'] && $row['nisn'] !== '-')
                                        <p class="text-[10px] text-slate-400 mt-0.5">NISN: {{ $row['nisn'] }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center text-xs text-slate-600 font-mono">{{ $row['nis'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-emerald-700">{{ $row['hadir'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-amber-700">{{ $row['terlambat'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-orange-700">{{ $row['sakit'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-blue-700">{{ $row['izin'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-purple-700">{{ $row['dispensasi'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-rose-700">{{ $row['alpa'] }}</td>
                                <td class="px-3 py-3 text-center font-bold text-slate-800">{{ $row['total'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold {{ $row['persentase'] >= 85 ? 'bg-emerald-100 text-emerald-800' : ($row['persentase'] >= 70 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $row['persentase'] }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-slate-500 text-sm">
                                    Belum ada data presensi pada periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-base">Pilih Kelas & Mata Pelajaran</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Silakan pilih kelas dan mata pelajaran yang Anda ampu di atas, lalu klik <strong>Tampilkan Data</strong> untuk melihat dan mengunduh rekap.
                </p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function togglePeriodeInputs() {
            const val = document.getElementById('periode_type').value;
            const wBulan = document.getElementById('wrapper-bulan');
            const wSemester = document.getElementById('wrapper-semester');
            if (val === 'bulanan') {
                wBulan?.classList.remove('hidden');
                wSemester?.classList.add('hidden');
            } else {
                wBulan?.classList.add('hidden');
                wSemester?.classList.remove('hidden');
            }
        }
    </script>
    @endpush
</x-layouts.guru>

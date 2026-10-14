@php
    $layout = match(auth()->user()->role) {
        'super_admin', 'admin' => 'layouts.admin',
        'piket' => 'layouts.piket',
        'guru' => 'layouts.guru',
        default => 'layouts.kepala-sekolah',
    };
@endphp

<x-dynamic-component :component="$layout">
    <x-slot:title>Laporan Jadwal KBM Terlewat (Belum Diisi Guru)</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('monitoring.jadwal-terlewat.export-pdf', request()->query()) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </a>
    </x-slot:actions>

    <div class="space-y-6">
        <!-- Header Banner & Ringkasan -->
        <div class="bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 rounded-3xl p-6 text-white shadow-sm border border-rose-900/40 relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/20 border border-rose-500/30 text-rose-300 text-xs font-semibold mb-2">
                        <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                        Audit & Pengawasan KBM Terpadu
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight">Monitoring Jadwal KBM Belum Diisi</h1>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl">
                        Mendeteksi seluruh jam pelajaran (JP) aktif yang belum diisi agendanya atau belum diabsenkan oleh guru pada rentang waktu terpilih.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('monitoring.jadwal-terlewat.export-pdf', request()->query()) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Laporan PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Form Terpadu -->
        <form method="GET" action="{{ route('monitoring.jadwal-terlewat') }}" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4" id="form-jadwal-terlewat">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mode Periode:</span>
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/60">
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? '7_hari') === '7_hari' ? 'bg-white text-rose-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="7_hari" {{ ($filterMode ?? '7_hari') === '7_hari' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('form-jadwal-terlewat').submit();">
                            7 Hari Terakhir
                        </label>
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? '7_hari') === 'hari_ini' ? 'bg-white text-rose-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="hari_ini" {{ ($filterMode ?? '7_hari') === 'hari_ini' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('form-jadwal-terlewat').submit();">
                            Hari Ini
                        </label>
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? '7_hari') === 'custom' ? 'bg-white text-rose-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="custom" {{ ($filterMode ?? '7_hari') === 'custom' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('form-jadwal-terlewat').submit();">
                            Rentang Tanggal
                        </label>
                    </div>
                </div>

                <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Cakupan: <strong class="text-slate-800">{{ $startDate->translatedFormat('d M Y') }} &ndash; {{ $endDate->translatedFormat('d M Y') }}</strong></span>
                </div>
            </div>

            <!-- Tab Pemilihan Angkatan / Tingkat Kelas -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Angkatan (Tingkat Kelas):</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach($daftarTingkat as $keyTingkat => $labelTingkat)
                        @php
                            $isSelectedTingkat = ($selectedTingkat ?? 'all') === (string)$keyTingkat;
                        @endphp
                        <label class="relative flex items-center justify-center p-2.5 rounded-xl border text-xs font-semibold cursor-pointer transition-all {{ $isSelectedTingkat ? 'bg-rose-50 border-rose-500 text-rose-800 ring-1 ring-rose-500 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <input type="radio" name="tingkat" value="{{ $keyTingkat }}" {{ $isSelectedTingkat ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('form-jadwal-terlewat').submit();">
                            <span>{{ $labelTingkat }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Filter Baris: Tanggal Custom, Jurusan, Kelas, Guru -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                @if(($filterMode ?? '7_hari') === 'custom')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-rose-500" onchange="document.getElementById('form-jadwal-terlewat').submit()">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-rose-500" onchange="document.getElementById('form-jadwal-terlewat').submit()">
                    </div>
                @endif

                <!-- Filter Jurusan -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Filter Jurusan</label>
                    <select name="jurusan" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-rose-500" onchange="document.getElementById('form-jadwal-terlewat').submit()">
                        <option value="all" {{ ($selectedJurusan ?? 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                        @foreach($daftarJurusan as $kodeJ => $namaJ)
                            <option value="{{ $kodeJ }}" {{ ($selectedJurusan ?? '') === $kodeJ ? 'selected' : '' }}>{{ $kodeJ }} - {{ explode('(', $namaJ)[0] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ruang / Kelas</label>
                    <select name="kelas_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-rose-500" onchange="document.getElementById('form-jadwal-terlewat').submit()">
                        <option value="">Semua Kelas ({{ $matchingKelasList->count() }} Kelas)</option>
                        @foreach($matchingKelasList as $k)
                            <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Guru -->
                <div class="{{ ($filterMode ?? '7_hari') === 'custom' ? 'sm:col-span-2 lg:col-span-2' : '' }}">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pilih Guru Tertentu</label>
                    <select name="guru_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-rose-500" onchange="document.getElementById('form-jadwal-terlewat').submit()">
                        <option value="">Semua Guru ({{ $daftarGuru->count() }} Guru Terdaftar)</option>
                        @foreach($daftarGuru as $g)
                            <option value="{{ $g->id }}" {{ $selectedGuruId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <a href="{{ route('monitoring.jadwal-terlewat') }}" class="text-slate-500 hover:text-slate-800 font-medium">Reset Semua Filter</a>
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold shadow-2xs">Terapkan Filter</button>
            </div>
        </form>

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-rose-200/80 p-5 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Total JP Terlewat</span>
                    <p class="text-3xl font-black text-rose-700 tracking-tight mt-1">{{ number_format($totalTerlewat) }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Sesi KBM tanpa agenda/presensi</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-amber-200/80 p-5 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Guru Belum Mengisi</span>
                    <p class="text-3xl font-black text-amber-700 tracking-tight mt-1">{{ number_format($topGuruBelumIsi->count()) }}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Guru memiliki jam tertunda</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-indigo-200/80 p-5 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Kelas Paling Terdampak</span>
                    <p class="text-xl font-black text-indigo-700 tracking-tight mt-1 truncate max-w-[180px]">
                        {{ $topKelasKosong->first()['kelas_nama'] ?? '-' }}
                    </p>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        {{ $topKelasKosong->first() ? $topKelasKosong->first()['total_terlewat'].' JP kosong' : 'Tidak ada kelas kosong' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
        </div>

        <!-- Leaderboard Guru dengan JP Terlewat Terbanyak -->
        @if($topGuruBelumIsi->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <h3 class="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
                <span class="p-1.5 bg-rose-50 text-rose-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                Guru dengan Jam KBM Terlewat Terbanyak (Perlu Follow-up)
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($topGuruBelumIsi->take(6) as $idx => $tg)
                <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/60 flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-bold flex items-center justify-center shrink-0">
                                {{ $idx + 1 }}
                            </span>
                            <p class="text-xs font-bold text-slate-900 truncate">{{ $tg['guru_nama'] }}</p>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 truncate">
                            Kelas: {{ implode(', ', array_slice($tg['kelas_terkena'], 0, 3)) }}
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="px-2 py-1 rounded-lg bg-rose-100 text-rose-800 font-black text-xs">
                            {{ $tg['total_terlewat'] }} JP
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Main Data Table: Rincian Jam Pelajaran Terlewat -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Rincian Jam Pelajaran & Agenda yang Belum Diisi</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar lengkap sesi belajar terjadwal yang tidak tercatat presensinya pada sistem.</p>
                </div>
                <div class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                    Total: <strong class="text-slate-900 font-bold">{{ $missedItems->count() }}</strong> Sesi JP Terdeteksi
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500 font-bold">
                        <tr>
                            <th class="px-4 py-3 text-center" style="width: 50px;">No</th>
                            <th class="px-4 py-3">Hari & Tanggal</th>
                            <th class="px-4 py-3 text-center">Waktu KBM</th>
                            <th class="px-4 py-3">Nama Guru Pengajar</th>
                            <th class="px-4 py-3">Ruang / Kelas</th>
                            <th class="px-4 py-3">Mata Pelajaran</th>
                            <th class="px-4 py-3">Status Indikator</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($paginatedItems as $index => $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 text-center font-medium text-slate-400">
                                {{ $paginatedItems->firstItem() + $index }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">
                                <div>{{ $item['tanggal_formatted'] }}</div>
                                <div class="text-[11px] font-normal text-slate-400">{{ $item['hari'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 font-bold text-[11px] border border-slate-200 font-mono">
                                    {{ $item['jam_rentang'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $item['guru_nama'] }}</div>
                                <div class="text-[10px] text-slate-400">NIP: {{ $item['guru_nip'] }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-bold text-xs border border-blue-200">
                                    {{ $item['kelas_nama'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $item['mapel_nama'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if($item['status_penyebab'] === 'Belum Ada Agenda Sama Sekali')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 font-semibold text-[11px] border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Belum Ada Agenda
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 font-semibold text-[11px] border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Siswa Belum Diabsen
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-800">Luar Biasa! Tidak Ada Jam KBM yang Terlewat</p>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    Seluruh jadwal pelajaran pada periode filter ini telah lengkap diisi agendanya dan diabsenkan oleh bapak/ibu guru.
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginatedItems->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $paginatedItems->links() }}
            </div>
            @endif
        </div>
    </div>
</x-dynamic-component>

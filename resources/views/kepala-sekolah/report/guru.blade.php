<x-dynamic-component :component="auth()->user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.kepala-sekolah'">
    <x-slot:title>Laporan Kehadiran Guru & Analisis Alasan</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('kepala-sekolah.pdf.guru', request()->query()) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </a>
    </x-slot:actions>

    <div class="space-y-6">
        {{-- Form Filter Komprehensif --}}
        <form method="GET" action="{{ route('kepala-sekolah.report.guru') }}" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4" id="guru-report-form">
            <!-- Mode Periode Switcher -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mode Periode:</span>
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/60">
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'semester' ? 'bg-white text-sky-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="semester" {{ ($filterMode ?? 'semester') === 'semester' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('guru-report-form').submit();">
                            Per Semester
                        </label>
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'custom' ? 'bg-white text-sky-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="custom" {{ ($filterMode ?? 'semester') === 'custom' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('guru-report-form').submit();">
                            Rentang Tanggal
                        </label>
                    </div>
                </div>

                <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Cakupan Tanggal: <strong class="text-slate-800">{{ $startDate->translatedFormat('d M Y') }} &ndash; {{ $endDate->translatedFormat('d M Y') }}</strong></span>
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
                        <label class="relative flex items-center justify-center p-2.5 rounded-xl border text-xs font-semibold cursor-pointer transition-all {{ $isSelectedTingkat ? 'bg-sky-50 border-sky-500 text-sky-800 ring-1 ring-sky-500 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <input type="radio" name="tingkat" value="{{ $keyTingkat }}" {{ $isSelectedTingkat ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('guru-report-form').submit();">
                            <span>{{ $labelTingkat }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Filter Baris: Tahun Ajaran, Semester / Rentang Tanggal, Jurusan, Guru, Kelas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-1">
                @if(($filterMode ?? 'semester') === 'semester')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajaran" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('guru-report-form').submit()">
                            <option value="all" {{ ($selectedTahunAjaran ?? '') === 'all' ? 'selected' : '' }}>Semua Tahun Ajaran</option>
                            @foreach($daftarTahunAjaran as $ta)
                                <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Semester</label>
                        <select name="semester" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('guru-report-form').submit()">
                            <option value="all" {{ ($selectedSemester ?? '') === 'all' ? 'selected' : '' }}>Semua Semester</option>
                            <option value="ganjil" {{ ($selectedSemester ?? '') === 'ganjil' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                            <option value="genap" {{ ($selectedSemester ?? '') === 'genap' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500">
                    </div>
                @endif

                <!-- Filter Jurusan -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Filter Jurusan</label>
                    <select name="jurusan" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('guru-report-form').submit()">
                        <option value="all" {{ ($selectedJurusan ?? 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                        @foreach($daftarJurusan as $kodeJ => $namaJ)
                            <option value="{{ $kodeJ }}" {{ ($selectedJurusan ?? '') === $kodeJ ? 'selected' : '' }}>{{ $kodeJ }} - {{ explode('(', $namaJ)[0] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Guru -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pilih Guru</label>
                    <select name="guru_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('guru-report-form').submit()">
                        <option value="">Semua Guru ({{ $guruList->count() }} Guru)</option>
                        @foreach($guruList as $g)
                            <option value="{{ $g->id }}" {{ $selectedGuruId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ruang / Kelas</label>
                    <select name="kelas_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('guru-report-form').submit()">
                        <option value="">Semua Kelas ({{ $matchingKelasList->count() }} Kelas Terpilih)</option>
                        @foreach($matchingKelasList as $k)
                            <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Baris Filter Bawah: Checkbox Selisih Data & Tombol Aksi -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3 py-2 rounded-xl transition-colors">
                        <input type="checkbox" name="only_discrepancy" value="1" {{ !empty($onlyDiscrepancy) ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500" onchange="document.getElementById('guru-report-form').submit()">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Hanya Selisih Data
                            @if(($discrepancyCount ?? 0) > 0)
                                <span class="bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.5 rounded-full font-bold">{{ $discrepancyCount }}</span>
                            @endif
                        </span>
                    </label>

                    @if($selectedTingkat !== 'all' || $selectedJurusan !== 'all' || $selectedKelasId || $selectedGuruId || !empty($onlyDiscrepancy) || ($filterMode === 'custom'))
                        <span class="text-xs text-sky-700 font-semibold bg-sky-50 px-2.5 py-1 rounded-md border border-sky-100">
                            Filter aktif: {{ $selectedTingkat !== 'all' ? 'Kelas '.$selectedTingkat : '' }} {{ $selectedJurusan !== 'all' ? '&bull; Jurusan '.$selectedJurusan : '' }} {{ $selectedGuruId ? '&bull; Guru Terpilih' : '' }} {{ $selectedKelasId ? '&bull; Kelas Terpilih' : '' }}
                        </span>
                    @else
                        <span class="text-xs text-slate-400">Menampilkan data seluruh guru, angkatan, & jurusan</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @if($selectedTingkat !== 'all' || $selectedJurusan !== 'all' || $selectedKelasId || $selectedGuruId || !empty($onlyDiscrepancy) || ($filterMode === 'custom'))
                        <a href="{{ route('kepala-sekolah.report.guru') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
                            Reset Filter
                        </a>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition shadow-xs">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- PANEL PIVOT ANALISIS PERSENTASE KEHADIRAN & ALASAN KETIDAKHADIRAN -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Header Panel -->
            <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-sky-50/50 to-transparent flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="p-1.5 bg-sky-600 text-white rounded-lg shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        </span>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Distribusi Persentase Kehadiran & Alasan Ketidakhadiran Guru</h2>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Analisis pivot rincian kehadiran: Hadir (Tepat Waktu) serta alasan spesifik dinas luar, cuti, rapat dinas, sakit, izin, atau alpa yang tercatat pada log agenda KBM guru.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200">
                        Total Rekaman: {{ number_format($grandTotalCount, 0, ',', '.') }} Log
                    </span>
                    @php
                        $hadirRow = $alasanBreakdown->firstWhere('label', '-');
                        $hadirPct = $hadirRow['percentage'] ?? 0;
                    @endphp
                    <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Hadir: {{ $hadirPct }}%
                    </span>
                </div>
            </div>

            <!-- Content: Table (Left) + Pie Chart (Right) -->
            <div class="p-5">
                @if($alasanBreakdown->isEmpty() || $grandTotalCount === 0)
                    <div class="py-12 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-sm font-semibold text-slate-700">Belum ada rekaman kehadiran guru pada kriteria filter ini.</p>
                        <p class="text-xs text-slate-400 mt-1">Coba sesuaikan periode tanggal, tahun ajaran, atau pilihan kelas di atas.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Kolom Kiri: Tabel Format Pivot Excel (7 Cols) -->
                        <div class="lg:col-span-7 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Tabel Rincian Alasan (Row Labels)
                                </span>
                                <!-- Quick Client Filter Input -->
                                <div class="relative w-48">
                                    <input type="text" id="filterReasonTableGuru" placeholder="Cari keterangan..." class="w-full pl-7 pr-2.5 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-sky-500">
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="w-full text-xs text-left" id="tableBreakdownAlasanGuru">
                                        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                                            <tr>
                                                <th class="py-2.5 px-3">Row Labels (Keterangan Kehadiran Guru)</th>
                                                <th class="py-2.5 px-3 text-right">Count of Status</th>
                                                <th class="py-2.5 px-3 text-right">Persentase</th>
                                                <th class="py-2.5 px-3 w-28 text-center">Distribusi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($alasanBreakdown as $row)
                                                @php
                                                    $isHadir = ($row['label'] === '-' || strtolower($row['label']) === 'hadir');
                                                @endphp
                                                <tr class="hover:bg-slate-50 transition reason-row {{ $isHadir ? 'bg-sky-50/20 font-semibold' : '' }}">
                                                    <td class="py-2 px-3 text-slate-800">
                                                        <div class="flex items-center gap-2">
                                                            @if($isHadir)
                                                                <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                                                                <span class="font-bold text-blue-900">- (Hadir / Tepat Waktu)</span>
                                                            @else
                                                                @php
                                                                    $badgeDot = match($row['category']) {
                                                                        'sakit' => 'bg-amber-400',
                                                                        'izin', 'cuti' => 'bg-sky-400',
                                                                        'alpa' => 'bg-red-500',
                                                                        'dispensasi', 'rapat_dinas', 'dinas_luar', 'tugas_luar' => 'bg-purple-500',
                                                                        'terlambat' => 'bg-orange-500',
                                                                        default => 'bg-slate-400',
                                                                    };
                                                                @endphp
                                                                <span class="w-2 h-2 rounded-full {{ $badgeDot }} shrink-0"></span>
                                                                <span class="truncate max-w-xs text-slate-700" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="py-2 px-3 text-right font-mono font-medium text-slate-700">
                                                        {{ number_format($row['count'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="py-2 px-3 text-right font-mono font-bold {{ $isHadir ? 'text-blue-700' : 'text-slate-800' }}">
                                                        {{ number_format($row['percentage'], 2, '.', '') }}%
                                                    </td>
                                                    <td class="py-2 px-3">
                                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                            <div class="{{ $isHadir ? 'bg-blue-600' : 'bg-slate-400' }} h-1.5 rounded-full" style="width: {{ min(100, max(2, $row['percentage'])) }}%"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-slate-100/90 font-bold text-slate-900 border-t-2 border-slate-300 sticky bottom-0">
                                            <tr>
                                                <td class="py-2.5 px-3 uppercase text-[11px] tracking-wider">Grand Total</td>
                                                <td class="py-2.5 px-3 text-right font-mono">{{ number_format($grandTotalCount, 0, ',', '.') }}</td>
                                                <td class="py-2.5 px-3 text-right font-mono text-blue-700">{{ number_format($grandTotalPercentage, 2, '.', '') }}%</td>
                                                <td class="py-2.5 px-3 text-center">
                                                    <span class="text-[10px] text-emerald-700 font-bold bg-emerald-100 px-1.5 py-0.5 rounded">100%</span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 italic">
                                * Keterangan baru yang diinputkan saat presensi / agenda KBM guru akan otomatis masuk dan terhitung persentasenya secara dinamis.
                            </p>
                        </div>

                        <!-- Kolom Kanan: Pie / Donut Chart Interaktif (5 Cols) -->
                        <div class="lg:col-span-5 bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 flex flex-col items-center">
                            <div class="w-full flex items-center justify-between border-b border-slate-200/60 pb-2 mb-3">
                                <span class="text-xs font-bold text-slate-800">Visualisasi Pie Chart</span>
                                <span class="text-[11px] text-slate-500 font-medium">Persentase Kehadiran</span>
                            </div>

                            <div class="w-full max-w-[280px] aspect-square relative flex items-center justify-center my-2">
                                <canvas id="alasanKehadiranGuruPieChart"></canvas>
                            </div>

                            <!-- Legend List Singkat (Top 6 Alasan Terbanyak) -->
                            <div class="w-full mt-3 pt-3 border-t border-slate-200/80 space-y-1.5">
                                <span class="text-[11px] font-bold text-slate-700 block mb-1">Rincian Komposisi Utama:</span>
                                @foreach($alasanBreakdown->take(6) as $idx => $leg)
                                    <div class="flex items-center justify-between text-[11px]">
                                        <div class="flex items-center gap-1.5 truncate max-w-[200px]">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $pieColors[$idx] ?? '#94a3b8' }}"></span>
                                            <span class="truncate text-slate-700 font-medium">{{ $leg['label'] === '-' ? 'Hadir (-)' : $leg['label'] }}</span>
                                        </div>
                                        <div class="text-right font-mono font-bold text-slate-800">
                                            {{ number_format($leg['percentage'], 2, '.', '') }}%
                                            <span class="text-[10px] text-slate-400 font-normal">({{ number_format($leg['count'], 0, ',', '.') }})</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if($alasanBreakdown->count() > 6)
                                    <div class="text-[10px] text-slate-400 text-center pt-1 font-medium">
                                        + {{ $alasanBreakdown->count() - 6 }} alasan spesifik lainnya tertera pada tabel
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer: Transparansi & Sumber Data -->
            <div class="px-5 py-3.5 bg-slate-50/75 border-t border-slate-100 flex items-start gap-2.5 text-xs text-slate-600">
                <svg class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="leading-relaxed">
                    <strong class="text-slate-800">Transparansi Sumber Data:</strong>
                    Data diagregasi secara otomatis dari tabel <code class="px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-800 font-mono text-[11px]">kehadiran_gurus</code> pada agenda mengajar guru yang terlaksana di sistem. Mencakup kehadiran tepat waktu (<code class="font-mono text-[11px]">-</code>) serta seluruh keterangan dinas luar, cuti, rapat dinas, sakit, izin, atau keperluan yang diinputkan guru.
                </div>
            </div>
        </div>

        {{-- Ringkasan Per Guru --}}
        @if($summary->count() > 0)
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-slate-900">Ringkasan Per Guru</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ ($selectedTahunAjaran && $selectedTahunAjaran !== 'all') ? $selectedTahunAjaran.' (Semester '.ucfirst($selectedSemester).') • ' : '' }}{{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</p>
                </div>
                <span class="text-xs text-slate-500 font-medium">
                    Total: {{ $summary->total() }} Guru Terdaftar
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[650px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">Guru</th>
                            <th class="text-center px-4 py-3 font-semibold text-emerald-600 whitespace-nowrap">Hadir</th>
                            <th class="text-center px-4 py-3 font-semibold text-amber-600 whitespace-nowrap">Sakit</th>
                            <th class="text-center px-4 py-3 font-semibold text-purple-600 whitespace-nowrap">Dispensasi</th>
                            <th class="text-center px-4 py-3 font-semibold text-red-600 whitespace-nowrap">Alpa</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Total Log</th>
                            <th class="text-center px-4 py-3 font-semibold text-blue-600 whitespace-nowrap">% Hadir</th>
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
                            <td class="px-4 py-3 text-center text-slate-600 font-mono">{{ $item['total'] }}</td>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($summary->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $summary->links() }}
            </div>
            @endif
        </div>
        @endif

        {{-- Detail Kehadiran Guru --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <h3 class="font-semibold text-slate-900">Detail Kehadiran ({{ $kehadiran->total() }} Log Pertemuan)</h3>
                    @if(($discrepancyCount ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ $discrepancyCount }} Selisih Data Siswa vs Guru
                    </span>
                    @endif
                </div>
                @if(!empty($onlyDiscrepancy))
                <a href="{{ request()->fullUrlWithQuery(['only_discrepancy' => null]) }}" class="text-xs font-medium text-blue-600 hover:underline">
                    Tampilkan Semua Data
                </a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">Tanggal</th>
                            <th class="text-left px-4 py-3 font-semibold">Guru</th>
                            <th class="text-left px-4 py-3 font-semibold">Mata Pelajaran</th>
                            <th class="text-left px-4 py-3 font-semibold">Kelas</th>
                            <th class="text-left px-4 py-3 font-semibold">Status (Guru)</th>
                            <th class="text-left px-4 py-3 font-semibold">Status (Siswa)</th>
                            <th class="text-left px-4 py-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($kehadiran as $kh)
                        @php
                            $siswaReport = $kh->pertemuan?->fotoBuktis?->first();
                            $isDiscrepant = $kh->has_discrepancy;
                            $fotoSrc = null;
                            if ($siswaReport && $siswaReport->foto_path) {
                                $fotoSrc = str_starts_with($siswaReport->foto_path, 'images/')
                                    ? asset($siswaReport->foto_path)
                                    : \Illuminate\Support\Facades\Storage::url($siswaReport->foto_path);
                            }
                            $fotoCheckoutSrc = null;
                            if ($siswaReport && $siswaReport->foto_checkout_path) {
                                $fotoCheckoutSrc = str_starts_with($siswaReport->foto_checkout_path, 'images/')
                                    ? asset($siswaReport->foto_checkout_path)
                                    : \Illuminate\Support\Facades\Storage::url($siswaReport->foto_checkout_path);
                            }
                            $waktuMasuk = $siswaReport?->created_at ? $siswaReport->created_at->format('H:i') . ' WIB' : null;
                            $waktuCheckout = $siswaReport?->checkout_at ? $siswaReport->checkout_at->format('H:i') . ' WIB' : null;
                        @endphp
                        <tr class="transition-colors {{ $isDiscrepant ? 'bg-amber-50/60 hover:bg-amber-100/60' : 'hover:bg-slate-50' }}">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ $kh->pertemuan?->tanggal?->format('d/m/Y') ?? '—' }}
                                @if($isDiscrepant)
                                <span class="inline-block w-2 h-2 rounded-full bg-amber-500 ml-1" title="Ada selisih data laporan"></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $kh->guru->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $kh->pertemuan?->jadwal?->kelas?->nama ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full inline-block w-fit
                                            {{ match($kh->status) {
                                                'hadir' => 'badge-hadir',
                                                'sakit' => 'badge-sakit',
                                                'dispensasi' => 'badge-dispensasi',
                                                default => 'badge-alpa',
                                            } }}">
                                            {{ $kh->status_label }}
                                        </span>

                                        {{-- Thumbnails foto bukti siswa inline --}}
                                        <div class="flex items-center gap-1.5">
                                            @if($fotoSrc)
                                            <button type="button"
                                                onclick="openFotoModal('{{ $fotoSrc }}', '{{ addslashes($siswaReport->siswa->name ?? 'Siswa') }}', '{{ ucfirst($siswaReport->status_guru_dilaporkan) }}', 'Masuk (Awal KBM): {{ $waktuMasuk }}')"
                                                class="w-7 h-7 rounded-md overflow-hidden border border-slate-200 hover:border-blue-400 hover:scale-110 transition-all shrink-0 relative group"
                                                title="Foto Masuk ({{ $waktuMasuk }})">
                                                <img src="{{ $fotoSrc }}" alt="Foto Masuk" class="w-full h-full object-cover">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </div>
                                            </button>
                                            @endif
                                            @if($fotoCheckoutSrc)
                                            <button type="button"
                                                onclick="openFotoModal('{{ $fotoCheckoutSrc }}', '{{ addslashes($siswaReport->siswa->name ?? 'Siswa') }}', 'Check-out Selesai', 'Check-out (Akhir KBM): {{ $waktuCheckout }}')"
                                                class="w-7 h-7 rounded-md overflow-hidden border border-emerald-300 hover:border-emerald-500 hover:scale-110 transition-all shrink-0 relative group"
                                                title="Foto Check-out ({{ $waktuCheckout }})">
                                                <img src="{{ $fotoCheckoutSrc }}" alt="Foto Check-out" class="w-full h-full object-cover">
                                                <div class="absolute inset-0 bg-emerald-900/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Waktu Upload Foto Bukti --}}
                                    @if($waktuMasuk || $waktuCheckout)
                                    <div class="text-[10px] text-slate-500 leading-tight space-y-0.5">
                                        @if($waktuMasuk)
                                        <div>Foto Masuk: <span class="font-medium text-slate-700">{{ $waktuMasuk }}</span></div>
                                        @endif
                                        @if($waktuCheckout)
                                        <div>Foto Keluar: <span class="font-medium text-emerald-700">{{ $waktuCheckout }}</span></div>
                                        @endif
                                    </div>
                                    @endif

                                    @if($kh->jenis_alpa)
                                    <span class="text-xs text-slate-400">{{ $kh->jenis_alpa_label }}</span>
                                    @endif
                                    @if($isDiscrepant)
                                    <div class="flex items-center gap-1 text-[11px] font-semibold text-amber-800 bg-amber-100 border border-amber-300 px-1.5 py-0.5 rounded-md w-fit mt-0.5">
                                        <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Selisih: Guru [{{ ucfirst($kh->status) }}] vs Siswa [{{ ucfirst($siswaReport->status_guru_dilaporkan) }}]
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
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
            @if($kehadiran->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $kehadiran->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Lightbox Modal untuk Foto Bukti Siswa --}}
    <div id="foto-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onclick="closeFotoModal(event)">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden relative" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                <div>
                    <p id="foto-modal-nama" class="text-sm font-bold text-slate-900"></p>
                    <p id="foto-modal-status" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button onclick="document.getElementById('foto-modal').classList.add('hidden')" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="bg-slate-100">
                <img id="foto-modal-img" src="" alt="Foto bukti" class="w-full object-contain max-h-72">
            </div>
        </div>
    </div>

    <!-- Chart.js CDN & Inisialisasi Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Client-side quick search untuk tabel alasan guru
            const searchInput = document.getElementById('filterReasonTableGuru');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#tableBreakdownAlasanGuru .reason-row');
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }

            // Inisialisasi Pie Chart Alasan Kehadiran Guru
            const ctxPie = document.getElementById('alasanKehadiranGuruPieChart');
            if (ctxPie) {
                const labels = @json($pieLabels);
                const percentages = @json($piePercentages);
                const counts = @json($pieCounts);
                const colors = @json($pieColors);

                if (labels && labels.length > 0) {
                    new Chart(ctxPie, {
                        type: 'pie',
                        data: {
                            labels: labels.map(l => l === '-' ? 'Hadir / Tepat Waktu (-)' : l),
                            datasets: [{
                                data: percentages,
                                backgroundColor: colors,
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                    titleFont: { size: 12, weight: 'bold' },
                                    bodyFont: { size: 12 },
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function (context) {
                                            const idx = context.dataIndex;
                                            const count = counts[idx] || 0;
                                            const pct = context.parsed;
                                            return ` ${context.label}: ${pct.toFixed(2)}% (${count} log)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });

        function openFotoModal(src, nama, status, waktu = '') {
            document.getElementById('foto-modal-img').src = src;
            document.getElementById('foto-modal-nama').textContent = nama;
            document.getElementById('foto-modal-status').textContent = 'Dilaporkan: ' + status + (waktu ? ' • ' + waktu : '');
            document.getElementById('foto-modal').classList.remove('hidden');
        }
        function closeFotoModal(e) {
            document.getElementById('foto-modal').classList.add('hidden');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') document.getElementById('foto-modal').classList.add('hidden');
        });
    </script>
</x-dynamic-component>

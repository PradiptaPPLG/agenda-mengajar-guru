<x-dynamic-component :component="auth()->user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.kepala-sekolah'">
    <x-slot:title>Laporan Kehadiran Siswa & Analisis Alasan</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('kepala-sekolah.pdf.siswa', request()->query()) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </a>
    </x-slot:actions>

    <div class="space-y-6">
        <!-- Filter Form Terpadu -->
        <form method="GET" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4" id="siswa-report-form">
            <!-- Mode Filter Header & Periode Waktu -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mode Periode:</span>
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/60">
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'semester' ? 'bg-white text-sky-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="semester" {{ ($filterMode ?? 'semester') === 'semester' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('siswa-report-form').submit();">
                            Per Semester
                        </label>
                        <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'custom' ? 'bg-white text-sky-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                            <input type="radio" name="filter_mode" value="custom" {{ ($filterMode ?? 'semester') === 'custom' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('siswa-report-form').submit();">
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
                            <input type="radio" name="tingkat" value="{{ $keyTingkat }}" {{ $isSelectedTingkat ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('siswa-report-form').submit();">
                            <span>{{ $labelTingkat }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Filter Baris: Jurusan, Kelas, Siswa, Tanggal/Semester -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                @if(($filterMode ?? 'semester') === 'semester')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajaran" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('siswa-report-form').submit()">
                            <option value="all" {{ ($selectedTahunAjaran ?? '') === 'all' ? 'selected' : '' }}>Semua Tahun Ajaran</option>
                            @foreach($daftarTahunAjaran as $ta)
                                <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Semester</label>
                        <select name="semester" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('siswa-report-form').submit()">
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
                    <select name="jurusan" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('siswa-report-form').submit()">
                        <option value="all" {{ ($selectedJurusan ?? 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                        @foreach($daftarJurusan as $kodeJ => $namaJ)
                            <option value="{{ $kodeJ }}" {{ ($selectedJurusan ?? '') === $kodeJ ? 'selected' : '' }}>{{ $kodeJ }} - {{ explode('(', $namaJ)[0] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ruang / Kelas</label>
                    <select name="kelas_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500" onchange="document.getElementById('siswa-report-form').submit()">
                        <option value="">Semua Kelas ({{ $matchingKelasList->count() }} Kelas Terpilih)</option>
                        @foreach($matchingKelasList as $k)
                            <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tombol Tampilkan & Reset -->
            <div class="flex items-center justify-between pt-2">
                <div class="text-xs text-slate-400">
                    @if($selectedTingkat !== 'all' || $selectedJurusan !== 'all' || $selectedKelasId)
                        <span class="text-sky-700 font-semibold bg-sky-50 px-2 py-0.5 rounded-md border border-sky-100">
                            Filter aktif: {{ $selectedTingkat !== 'all' ? 'Kelas '.$selectedTingkat : '' }} {{ $selectedJurusan !== 'all' ? '&bull; Jurusan '.$selectedJurusan : '' }} {{ $selectedKelasId ? '&bull; Kelas Terpilih' : '' }}
                        </span>
                    @else
                        <span>Menampilkan data seluruh angkatan & seluruh keahlian jurusan</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @if($selectedTingkat !== 'all' || $selectedJurusan !== 'all' || $selectedKelasId || ($filterMode === 'custom'))
                        <a href="{{ route('kepala-sekolah.report.siswa') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
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
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Distribusi Persentase Kehadiran & Alasan Ketidakhadiran Siswa</h2>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Analisis pivot rincian kehadiran: Hadir (Tepat Waktu) serta alasan spesifik sakit, izin, atau alpa yang tercatat pada log agenda KBM guru.
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
                        <p class="text-sm font-semibold text-slate-700">Belum ada rekaman kehadiran siswa pada kriteria filter ini.</p>
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
                                    <input type="text" id="filterReasonTable" placeholder="Cari keterangan..." class="w-full pl-7 pr-2.5 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:ring-1 focus:ring-sky-500">
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="w-full text-xs text-left" id="tableBreakdownAlasan">
                                        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                                            <tr>
                                                <th class="py-2.5 px-3">Row Labels (Keterangan Kehadiran Siswa)</th>
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
                                                                        'izin' => 'bg-sky-400',
                                                                        'alpa' => 'bg-red-500',
                                                                        'dispensasi' => 'bg-purple-500',
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
                                * Keterangan baru yang diinputkan guru saat presensi akan otomatis masuk dan terhitung persentasenya secara dinamis.
                            </p>
                        </div>

                        <!-- Kolom Kanan: Pie / Donut Chart Interaktif (5 Cols) -->
                        <div class="lg:col-span-5 bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 flex flex-col items-center">
                            <div class="w-full flex items-center justify-between border-b border-slate-200/60 pb-2 mb-3">
                                <span class="text-xs font-bold text-slate-800">Visualisasi Pie Chart</span>
                                <span class="text-[11px] text-slate-500 font-medium">Persentase Kehadiran</span>
                            </div>

                            <div class="w-full max-w-[280px] aspect-square relative flex items-center justify-center my-2">
                                <canvas id="alasanKehadiranPieChart"></canvas>
                            </div>

                            <!-- Legend List Singkat (Top 5 Alasan Terbanyak) -->
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
                    Data diagregasi secara otomatis dari tabel <code class="px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-800 font-mono text-[11px]">kehadiran_siswas</code> pada agenda mengajar guru yang terlaksana di sistem. Mencakup kehadiran tepat waktu (<code class="font-mono text-[11px]">-</code>) serta seluruh keterangan izin, sakit klinis, atau keperluan yang diinputkan guru.
                    Formula: <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700">(Frekuensi Alasan &divide; Total Seluruh Log Presensi) &times; 100%</span>.
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- TABEL REKAP KEHADIRAN SISWA INDIVIDUAL (PAGINASI PER SISWA)        -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="font-bold text-slate-900">Rekapitulasi Kehadiran Siswa Per Individu</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan rincian akumulasi jam per siswa sesuai kriteria angkatan dan jurusan yang dipilih.
                    </p>
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    Total: <strong>{{ $summary->total() }}</strong> Siswa Terdata
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-600 font-bold">
                        <tr>
                            <th class="text-left px-4 py-3">Nama Siswa</th>
                            <th class="text-left px-4 py-3">Kelas</th>
                            <th class="text-center px-4 py-3 text-emerald-600">Hadir</th>
                            <th class="text-center px-4 py-3 text-amber-600">Sakit</th>
                            <th class="text-center px-4 py-3 text-sky-600">Izin</th>
                            <th class="text-center px-4 py-3 text-rose-600">Alpa</th>
                            <th class="text-center px-4 py-3 text-purple-600">Disp.</th>
                            <th class="text-center px-4 py-3 text-slate-700">Total Sesi</th>
                            <th class="text-center px-4 py-3 text-blue-600">% Hadir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($summary as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                {{ $item['siswa']?->name ?? ('Siswa #'.($item['siswa_id'] ?? '-')) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                                    {{ $item['kelas_nama'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-emerald-700 font-semibold font-mono">{{ $item['hadir'] }}</td>
                            <td class="px-4 py-3 text-center text-amber-700 font-semibold font-mono">{{ $item['sakit'] }}</td>
                            <td class="px-4 py-3 text-center text-sky-700 font-semibold font-mono">{{ $item['izin'] }}</td>
                            <td class="px-4 py-3 text-center text-rose-700 font-semibold font-mono">{{ $item['alpa'] }}</td>
                            <td class="px-4 py-3 text-center text-purple-700 font-semibold font-mono">{{ $item['dispensasi'] }}</td>
                            <td class="px-4 py-3 text-center text-slate-600 font-mono font-bold">{{ $item['total'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $pct = $item['total'] > 0 ? round($item['hadir'] / $item['total'] * 100) : 0;
                                    $badgeClass = match (true) {
                                        $pct >= 90 => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                                        $pct >= 80 => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                                        $pct >= 70 => 'bg-amber-50 text-amber-700 border-amber-200',
                                        $pct >= 55 => 'bg-orange-50 text-orange-700 border-orange-200',
                                        default => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
                                    };
                                @endphp
                                <span class="text-xs px-2.5 py-1 rounded-lg border {{ $badgeClass }} inline-block w-14 text-center font-mono">
                                    {{ $pct }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-400 text-sm">
                                Tidak ada data rekapitulasi kehadiran siswa pada filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($summary->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $summary->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Chart.js CDN & Inisialisasi Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Client-side quick search untuk tabel alasan
            const searchInput = document.getElementById('filterReasonTable');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#tableBreakdownAlasan .reason-row');
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }

            // Inisialisasi Pie Chart Alasan Kehadiran
            const ctxPie = document.getElementById('alasanKehadiranPieChart');
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
                                    display: false // Menggunakan custom legend di bawah
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
    </script>
</x-dynamic-component>

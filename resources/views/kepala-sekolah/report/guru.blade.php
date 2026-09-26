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
    <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5 space-y-3" id="guru-report-form">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mode Filter:</span>
                <div class="inline-flex p-1 bg-slate-100 rounded-xl">
                    <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'semester' ? 'bg-white text-blue-600 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                        <input type="radio" name="filter_mode" value="semester" {{ ($filterMode ?? 'semester') === 'semester' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('guru-report-form').submit();">
                        Per Semester
                    </label>
                    <label class="cursor-pointer px-3 py-1 text-xs font-medium rounded-lg transition-all {{ ($filterMode ?? 'semester') === 'custom' ? 'bg-white text-blue-600 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                        <input type="radio" name="filter_mode" value="custom" {{ ($filterMode ?? 'semester') === 'custom' ? 'checked' : '' }} class="sr-only" onchange="document.getElementById('guru-report-form').submit();">
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
                            onchange="document.getElementById('guru-report-form').submit()">
                        <option value="all" {{ ($selectedTahunAjaran ?? '') === 'all' ? 'selected' : '' }}>Semua Tahun Ajaran</option>
                        @foreach($daftarTahunAjaran as $ta)
                        <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Semester</label>
                    <select name="semester" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="document.getElementById('guru-report-form').submit()">
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
                <label class="block text-xs font-medium text-slate-600 mb-1">Guru</label>
                <select name="guru_id" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="document.getElementById('guru-report-form').submit()">
                    <option value="">Semua Guru</option>
                    @foreach($guruList as $g)
                    <option value="{{ $g->id }}" {{ $selectedGuruId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                <select name="kelas_id" class="px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="document.getElementById('guru-report-form').submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $selectedKelasId == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center pb-0.5">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3 py-2.5 rounded-xl transition-colors">
                    <input type="checkbox" name="only_discrepancy" value="1" {{ !empty($onlyDiscrepancy) ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                           onchange="document.getElementById('guru-report-form').submit()">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Hanya Selisih Data
                        @if(($discrepancyCount ?? 0) > 0)
                        <span class="bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.5 rounded-full font-bold">{{ $discrepancyCount }}</span>
                        @endif
                    </span>
                </label>
            </div>
            <div>
                <button type="submit" class="px-4 py-2.5 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors">Tampilkan</button>
            </div>
        </div>
    </form>

    {{-- Summary cards --}}
    @if($summary->count() > 0)
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Ringkasan Per Guru</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ ($selectedTahunAjaran && $selectedTahunAjaran !== 'all') ? $selectedTahunAjaran.' (Semester '.ucfirst($selectedSemester).') • ' : '' }}{{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</p>
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
        @if($summary->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $summary->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- Detail table --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-slate-900">Detail Kehadiran ({{ $kehadiran->total() }} data)</h3>
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
                    @php
                        $siswaReport = $kh->pertemuan?->fotoBuktis?->first();
                        $isDiscrepant = $kh->has_discrepancy;
                        $fotoSrc = null;
                        if ($siswaReport && $siswaReport->foto_path) {
                            $fotoSrc = str_starts_with($siswaReport->foto_path, 'images/')
                                ? asset($siswaReport->foto_path)
                                : \Illuminate\Support\Facades\Storage::url($siswaReport->foto_path);
                        }
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
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full inline-block w-fit
                                        {{ match($kh->status) {
                                            'hadir' => 'badge-hadir',
                                            'sakit' => 'badge-sakit',
                                            'dispensasi' => 'badge-dispensasi',
                                            default => 'badge-alpa',
                                        } }}">
                                        {{ $kh->status_label }}
                                    </span>
                                    {{-- Thumbnail foto bukti siswa inline --}}
                                    @if($fotoSrc)
                                    <button type="button"
                                        onclick="openFotoModal('{{ $fotoSrc }}', '{{ addslashes($siswaReport->siswa->name ?? 'Siswa') }}', '{{ ucfirst($siswaReport->status_guru_dilaporkan) }}')"
                                        class="w-7 h-7 rounded-md overflow-hidden border border-slate-200 hover:border-blue-400 hover:scale-110 transition-all shrink-0 relative group"
                                        title="Klik untuk lihat foto bukti">
                                        <img src="{{ $fotoSrc }}" alt="Foto" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </div>
                                    </button>
                                    @endif
                                </div>
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

    @push('scripts')
    <script>
        function openFotoModal(src, nama, status) {
            document.getElementById('foto-modal-img').src = src;
            document.getElementById('foto-modal-nama').textContent = nama;
            document.getElementById('foto-modal-status').textContent = 'Dilaporkan: ' + status;
            document.getElementById('foto-modal').classList.remove('hidden');
        }
        function closeFotoModal(e) {
            document.getElementById('foto-modal').classList.add('hidden');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') document.getElementById('foto-modal').classList.add('hidden');
        });
    </script>
    @endpush
</x-dynamic-component>

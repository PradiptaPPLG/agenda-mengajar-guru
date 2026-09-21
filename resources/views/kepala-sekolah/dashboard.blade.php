<x-layouts.kepala-sekolah>
    <x-slot:title>Dashboard Kepala Sekolah</x-slot:title>

    <div class="space-y-6">
        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-emerald-600">{{ $stats['guru_hadir_hari_ini'] }}</p>
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                </div>
                <p class="text-xs text-slate-500 mt-1 font-medium">Guru Hadir Hari Ini</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-amber-600">{{ $stats['guru_terlambat_hari_ini'] }}</p>
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                </div>
                <p class="text-xs text-slate-500 mt-1 font-medium">Guru Terlambat</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-red-600">{{ $stats['guru_tidak_hadir_hari_ini'] }}</p>
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                </div>
                <p class="text-xs text-slate-500 mt-1 font-medium">Guru Tidak Hadir</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total_guru'] }}</p>
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <p class="text-xs text-slate-500 mt-1 font-medium">Total Guru Terdaftar</p>
            </div>
        </div>

        {{-- ═══ SECTION: MONITORING REAL-TIME BERBASIS KARTU & JAM BERJALAN ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">Monitoring Kehadiran Guru Real-Time (KBM Hari Ini)</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Memantau kehadiran guru per ruang kelas sesuai jam pelajaran aktif (Pukul {{ $nowTime }} WIB)</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="window.location.reload()" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh Otomatis
                    </button>
                </div>
            </div>

            {{-- Tabs Jam Pelajaran --}}
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pilih Jam Pelajaran</label>
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <a href="{{ request()->fullUrlWithQuery(['slot' => 'all']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors
                              {{ $selectedSlotKey === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Semua Jam
                    </a>
                    @foreach($timeSlots as $idx => $slot)
                    @php
                        $isActiveNow = ($idx === $currentActiveSlotIndex);
                        $isSelected = ($selectedSlotKey === (string) $idx);
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['slot' => $idx]) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5
                              {{ $isSelected ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        @if($isActiveNow)
                            <span class="w-2 h-2 rounded-full {{ $isSelected ? 'bg-white' : 'bg-emerald-500' }} animate-ping"></span>
                        @endif
                        <span>Jam {{ $slot['jam_ke'] }}</span>
                        <span class="opacity-75 text-[11px]">({{ $slot['label'] }})</span>
                        @if($isActiveNow)
                            <span class="text-[10px] px-1 rounded {{ $isSelected ? 'bg-white/20' : 'bg-emerald-100 text-emerald-800' }}">Sedang Berjalan</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Filter Bar: Tingkat, Status Utama, dan Sub-Alasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100">
                {{-- Filter Tingkat --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tingkat Kelas</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => 'all']) }}" {{ $selectedTingkat === 'all' ? 'selected' : '' }}>Semua Tingkat (10, 11, 12)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '10']) }}" {{ $selectedTingkat === '10' ? 'selected' : '' }}>Kelas 10 (X)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '11']) }}" {{ $selectedTingkat === '11' ? 'selected' : '' }}>Kelas 11 (XI)</option>
                        <option value="{{ request()->fullUrlWithQuery(['tingkat' => '12']) }}" {{ $selectedTingkat === '12' ? 'selected' : '' }}>Kelas 12 (XII)</option>
                    </select>
                </div>

                {{-- Filter Status Utama --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status Utama Kehadiran</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'all']) }}" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'hadir']) }}" {{ $selectedStatus === 'hadir' ? 'selected' : '' }}>🟢 Hadir (Tepat Waktu)</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'terlambat']) }}" {{ $selectedStatus === 'terlambat' ? 'selected' : '' }}>🟠 Terlambat</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'belum_hadir']) }}" {{ $selectedStatus === 'belum_hadir' ? 'selected' : '' }}>🔴 Belum Hadir / Belum Ada Konfirmasi</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'tidak_hadir']) }}" {{ $selectedStatus === 'tidak_hadir' ? 'selected' : '' }}>🔴 Tidak Hadir</option>
                    </select>
                </div>

                {{-- Filter Sub-Alasan Tidak Hadir --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Alasan / Sub-Kategori Tidak Hadir</label>
                    <select onchange="window.location.href = this.value" class="w-full text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'all']) }}" {{ $selectedAlasan === 'all' ? 'selected' : '' }}>Semua Jenis Ketidakhadiran</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'sakit']) }}" {{ $selectedAlasan === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'izin']) }}" {{ $selectedAlasan === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'rapat_dinas']) }}" {{ $selectedAlasan === 'rapat_dinas' ? 'selected' : '' }}>Rapat Dinas</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'dinas_luar']) }}" {{ $selectedAlasan === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'tugas_luar']) }}" {{ $selectedAlasan === 'tugas_luar' ? 'selected' : '' }}>Tugas Luar</option>
                        <option value="{{ request()->fullUrlWithQuery(['alasan' => 'tanpa_keterangan']) }}" {{ $selectedAlasan === 'tanpa_keterangan' ? 'selected' : '' }}>Tanpa Keterangan (Alpa)</option>
                    </select>
                </div>
            </div>

            {{-- ═══ CARD GRID MONITORING KEHADIRAN ═══ --}}
            @if($monitoringCards->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                @foreach($monitoringCards as $card)
                @php
                    $theme = match($card['status']) {
                        'hadir' => [
                            'border' => 'border-emerald-200',
                            'top' => 'bg-emerald-500',
                            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'dot' => 'bg-emerald-500',
                            'text' => 'Sudah Hadir',
                        ],
                        'terlambat' => [
                            'border' => 'border-amber-200',
                            'top' => 'bg-amber-500',
                            'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'dot' => 'bg-amber-500',
                            'text' => 'Terlambat',
                        ],
                        'tidak_hadir' => [
                            'border' => 'border-red-200',
                            'top' => 'bg-red-500',
                            'badge' => 'bg-red-50 text-red-700 border-red-200',
                            'dot' => 'bg-red-500',
                            'text' => 'Tidak Hadir',
                        ],
                        default => [
                            'border' => 'border-rose-200',
                            'top' => 'bg-rose-400',
                            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                            'dot' => 'bg-rose-500 animate-pulse',
                            'text' => 'Belum Hadir',
                        ],
                    };
                @endphp
                <div class="bg-white rounded-2xl border {{ $theme['border'] }} p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden flex flex-col justify-between">
                    {{-- Status Color Bar --}}
                    <div class="absolute top-0 left-0 right-0 h-1.5 {{ $theme['top'] }}"></div>

                    <div>
                        {{-- Header Kartu: Kelas & Jam --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold">
                                {{ $card['kelas_nama'] }}
                            </span>
                            <div class="text-right">
                                <span class="text-xs font-semibold text-slate-600">Jam Ke-{{ $card['jam_ke'] }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $card['slot_label'] }}</span>
                            </div>
                        </div>

                        {{-- Mapel & Guru --}}
                        <div class="space-y-1 mb-4">
                            <h3 class="font-bold text-slate-900 text-base leading-tight">{{ $card['mapel_nama'] }}</h3>
                            <p class="text-xs text-slate-600 font-medium flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="truncate">{{ $card['guru_nama'] }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Footer Status --}}
                    <div class="pt-3 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $theme['badge'] }}">
                                <span class="w-2 h-2 rounded-full {{ $theme['dot'] }}"></span>
                                {{ $theme['text'] }}
                            </span>

                            @if($card['waktu_hadir'])
                                <span class="text-[11px] text-slate-400 font-medium">{{ \Carbon\Carbon::parse($card['waktu_hadir'])->format('H:i') }} WIB</span>
                            @endif
                        </div>

                        {{-- Detail Alasan Tidak Hadir --}}
                        @if($card['alasan_label'])
                            <div class="mt-2.5 px-3 py-1.5 rounded-xl bg-red-50 text-[11px] text-red-700 font-medium border border-red-100 flex items-center justify-between">
                                <span>Alasan: <strong>{{ $card['alasan_label'] }}</strong></span>
                                @if($card['guru_pengganti'])
                                    <span class="text-blue-700">Pengganti: <strong>{{ $card['guru_pengganti'] }}</strong></span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="p-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                <p class="text-sm font-semibold text-slate-700">Tidak ada jadwal yang sesuai kriteria filter.</p>
                <p class="text-xs text-slate-400 mt-1">Coba ganti filter jam pelajaran, tingkat kelas, atau status kehadiran.</p>
            </div>
            @endif
        </div>

        {{-- ═══ SECTION: KEHADIRAN TERBARU (7 HARI) ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900">Catatan Kehadiran Guru Terkini (7 Hari)</h3>
                <a href="{{ route('kepala-sekolah.report.guru') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Lihat Laporan Lengkap →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Guru</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Mata Pelajaran</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Kelas</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentKehadiran as $kh)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $kh->guru->name }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $kh->pertemuan?->jadwal?->kelas?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full
                                    {{ $kh->status === 'hadir' ? 'badge-hadir' : ($kh->status === 'terlambat' ? 'badge-sakit' : 'badge-alpa') }}">
                                    {{ $kh->status_label }}
                                </span>
                                @if($kh->alasan_tidak_hadir)
                                    <span class="text-[11px] text-slate-500">({{ $kh->alasan_tidak_hadir_label }})</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs hidden lg:table-cell">{{ $kh->created_at->format('d/m H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 text-sm">Belum ada data kehadiran</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh setiap 60 detik agar card status selalu berubah otomatis per jam pelajaran
        setTimeout(function() {
            window.location.reload();
        }, 60000);
    </script>
    @endpush
</x-layouts.kepala-sekolah>

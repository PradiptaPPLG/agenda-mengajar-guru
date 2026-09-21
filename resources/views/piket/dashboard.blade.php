<x-layouts.piket>
    <x-slot:title>Monitoring Kehadiran Guru (Piket)</x-slot:title>

    <div class="space-y-6">
        {{-- Banner & Auto-Refresh Header --}}
        <div class="bg-gradient-to-r from-teal-600 to-emerald-600 rounded-2xl p-5 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white text-xs font-semibold backdrop-blur-xs">
                        Real-time KBM
                    </span>
                    <span class="text-xs text-teal-100" id="live-clock">Pukul {{ $nowTime }} WIB</span>
                </div>
                <h1 class="text-xl font-bold mt-1">Pengawasan Kehadiran Guru di Kelas</h1>
                <p class="text-xs text-teal-100 mt-0.5">Memantau status guru di ruang kelas untuk seluruh angkatan (Kelas 10, 11, dan 12) per jam pelajaran.</p>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto">
                <button onclick="window.location.reload()" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-semibold transition-colors flex items-center gap-2 backdrop-blur-xs cursor-pointer border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Segarkan Data
                </button>
            </div>
        </div>

        {{-- Banner Sistem Blok (hanya muncul jika ada kelas sistem blok) --}}
        @if($kelasSistemBlok->isNotEmpty())
            <x-blok-aktif-banner :mingguAktif="$mingguAktif" :kelasList="$kelasSistemBlok" />
        @endif

        {{-- KPI Summary Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">Total Jadwal Kelas</p>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-100 p-4 bg-emerald-50/30">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-emerald-600">{{ $stats['hadir'] }}</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                </div>
                <p class="text-xs text-slate-600 mt-0.5 font-medium">Sudah Hadir</p>
            </div>
            <div class="bg-white rounded-2xl border border-amber-100 p-4 bg-amber-50/30">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-amber-600">{{ $stats['terlambat'] }}</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                </div>
                <p class="text-xs text-slate-600 mt-0.5 font-medium">Terlambat</p>
            </div>
            <div class="bg-white rounded-2xl border border-rose-100 p-4 bg-rose-50/30">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-rose-600">{{ $stats['belum_hadir'] }}</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                </div>
                <p class="text-xs text-slate-600 mt-0.5 font-medium">Belum Hadir</p>
            </div>
            <div class="bg-white rounded-2xl border border-red-100 p-4 bg-red-50/30 col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold text-red-600">{{ $stats['tidak_hadir'] }}</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                </div>
                <p class="text-xs text-slate-600 mt-0.5 font-medium">Tidak Hadir</p>
            </div>
        </div>

        {{-- Filter & Jam Pelajaran Bar --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-4 shadow-xs">
            {{-- Tabs Jam Pelajaran --}}
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Pilih Jam Pelajaran</label>
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <a href="{{ request()->fullUrlWithQuery(['slot' => 'all']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors
                              {{ $selectedSlotKey === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Semua Jam
                    </a>
                    @foreach($timeSlots as $idx => $slot)
                    @php
                        $isActiveNow = ($idx === $currentActiveSlotIndex);
                        $isSelected = ($selectedSlotKey === (string) $idx);
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['slot' => $idx]) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5
                              {{ $isSelected ? 'bg-teal-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        @if($isActiveNow)
                            <span class="w-2 h-2 rounded-full {{ $isSelected ? 'bg-white' : 'bg-teal-500' }} animate-ping"></span>
                        @endif
                        <span>Jam {{ $slot['jam_ke'] }}</span>
                        <span class="opacity-75 text-[11px]">({{ $slot['label'] }})</span>
                        @if($isActiveNow)
                            <span class="text-[10px] px-1 rounded {{ $isSelected ? 'bg-white/20' : 'bg-teal-100 text-teal-800' }}">Sekarang</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Filter Tingkat & Status --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                {{-- Filter Tingkat (10, 11, 12) --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Tingkat:</span>
                    <div class="inline-flex rounded-xl bg-slate-100 p-0.5 text-xs font-semibold">
                        @foreach(['all' => 'Semua', '10' => 'Kelas 10', '11' => 'Kelas 11', '12' => 'Kelas 12'] as $tVal => $tLabel)
                        <a href="{{ request()->fullUrlWithQuery(['tingkat' => $tVal]) }}"
                           class="px-2.5 py-1 rounded-lg transition-colors {{ $selectedTingkat === $tVal ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            {{ $tLabel }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Filter Status --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Status:</span>
                    <select onchange="window.location.href = this.value" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'all']) }}" {{ $selectedStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'hadir']) }}" {{ $selectedStatus === 'hadir' ? 'selected' : '' }}>🟢 Sudah Hadir</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'terlambat']) }}" {{ $selectedStatus === 'terlambat' ? 'selected' : '' }}>🟠 Terlambat</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'belum_hadir']) }}" {{ $selectedStatus === 'belum_hadir' ? 'selected' : '' }}>🔴 Belum Hadir</option>
                        <option value="{{ request()->fullUrlWithQuery(['status' => 'tidak_hadir']) }}" {{ $selectedStatus === 'tidak_hadir' ? 'selected' : '' }}>🔴 Tidak Hadir</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Monitoring Cards Grid --}}
        @if($filteredItems->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($filteredItems as $item)
            @php
                $statusColor = match($item['status']) {
                    'hadir' => 'emerald',
                    'terlambat' => 'amber',
                    'tidak_hadir' => 'red',
                    default => 'rose',
                };
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden flex flex-col justify-between">
                {{-- Status color bar on top --}}
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-{{ $statusColor }}-500"></div>

                <div>
                    {{-- Header Card: Kelas & Jam --}}
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold">
                                {{ $item['kelas_nama'] }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-500">Jam Ke-{{ $item['jam_ke'] }}</span>
                            <span class="block text-[11px] text-slate-400">{{ $item['slot_label'] }}</span>
                        </div>
                    </div>

                    {{-- Mata Pelajaran & Guru --}}
                    <div class="space-y-1 mb-4">
                        <h3 class="font-bold text-slate-900 text-base leading-tight">{{ $item['mapel_nama'] }}</h3>
                        <p class="text-xs text-slate-600 font-medium flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $item['guru_nama'] }}
                        </p>
                    </div>
                </div>

                {{-- Status Kehadiran Footer --}}
                <div class="pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if($item['status'] === 'hadir')
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold text-emerald-700">Sudah Masuk Kelas</span>
                            @elseif($item['status'] === 'terlambat')
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                <span class="text-xs font-bold text-amber-700">Terlambat Masuk</span>
                            @elseif($item['status'] === 'tidak_hadir')
                                <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                                <span class="text-xs font-bold text-red-700">Tidak Hadir</span>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                                <span class="text-xs font-bold text-rose-600">Belum Hadir</span>
                            @endif
                        </div>

                        @if($item['status'] === 'belum_hadir')
                            <form action="{{ route('piket.teguran.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="guru_id" value="{{ $item['guru_id'] }}">
                                <input type="hidden" name="jadwal_id" value="{{ $item['jadwal_id'] }}">
                                <button type="submit" class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-[10px] font-bold rounded shadow-xs transition border border-red-200" title="Kirim notifikasi teguran ke guru">
                                    Tegur
                                </button>
                            </form>
                        @endif

                        @if($item['waktu_hadir'])
                            <span class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($item['waktu_hadir'])->format('H:i') }} WIB</span>
                        @endif
                    </div>

                    {{-- Detail Alasan atau Guru Pengganti --}}
                    @if($item['alasan'])
                        <div class="mt-2 px-2.5 py-1 rounded-lg bg-red-50 text-[11px] text-red-700 font-medium border border-red-100 flex items-center justify-between">
                            <span>Alasan: {{ $item['alasan'] }}</span>
                            @if($item['guru_pengganti'])
                                <span class="font-bold">Pengganti: {{ $item['guru_pengganti'] }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Jadwal Kelas</h3>
            <p class="text-xs text-slate-500 mt-1">Tidak ada jadwal yang cocok dengan filter jam pelajaran atau angkatan yang dipilih.</p>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Auto refresh setiap 60 detik untuk memperbarui status jam pelajaran dan kehadiran real-time
        setTimeout(function() {
            window.location.reload();
        }, 60000);
    </script>
    @endpush
</x-layouts.piket>

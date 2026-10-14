<x-layouts.guru>
    <x-slot:title>Dashboard BK</x-slot:title>

    <div class="px-4 py-4 space-y-6" x-data="{
        showModalKelas: false,
        search: '',
        tabTingkat: 'all',
        selectedKelas: {{ json_encode($assignedBkKelasIds) }},
        allKelasData: {{ json_encode($allKelas->map(fn($k) => ['id' => $k->id, 'nama' => $k->nama, 'tingkat' => (string)$k->tingkat, 'current_bk' => $k->guruBk?->name, 'is_my_bk' => $k->bk_id === auth()->id()])) }},
        toggleAllTingkat(tingkat, check) {
            let idsInTingkat = this.allKelasData
                .filter(k => tingkat === 'all' || k.tingkat === tingkat)
                .map(k => k.id);
            if (check) {
                this.selectedKelas = Array.from(new Set([...this.selectedKelas, ...idsInTingkat]));
            } else {
                this.selectedKelas = this.selectedKelas.filter(id => !idsInTingkat.includes(id));
            }
        },
        matches(k) {
            let matchSearch = !this.search || k.nama.toLowerCase().includes(this.search.toLowerCase());
            let matchTingkat = this.tabTingkat === 'all' || k.tingkat === this.tabTingkat;
            return matchSearch && matchTingkat;
        }
    }">
        {{-- Banner Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="p-1 bg-white/20 rounded-lg backdrop-blur-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <h1 class="text-xl font-bold">Dashboard Guru BK</h1>
                </div>
                <p class="text-xs text-blue-100 mt-1">Pemantauan Presensi Siswa Binaan BK</p>
                
                <div class="mt-3 flex items-center gap-2">
                    @if($isCustomAssigned)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 backdrop-blur-xs rounded-lg text-xs font-semibold text-white border border-white/20">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Membina {{ count($assignedBkKelasIds) }} Kelas Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-400/20 backdrop-blur-xs rounded-lg text-xs font-medium text-amber-100 border border-amber-300/30">
                            <span>💡</span>
                            Menampilkan seluruh kelas (Belum memilih kelas khusus)
                        </span>
                    @endif
                </div>
            </div>

                <a href="{{ route('monitoring.jadwal-terlewat') }}"
                   class="px-3.5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white active:scale-95 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5" title="Pantau KBM yang Belum Diisi Guru">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Jadwal Terlewat
                </a>
                <button type="button" @click="showModalKelas = true"
                        class="px-4 py-2.5 bg-white text-blue-700 hover:bg-blue-50 active:scale-95 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    Atur Kelas Binaan
                </button>
                <a href="{{ route('profile.index') }}"
                   class="p-2.5 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-colors" title="Buka Profil">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </a>
            </div>
        </div>

        <form method="GET" class="flex gap-2">
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="flex-1 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors">Lihat</button>
        </form>

        <div class="space-y-4">
            <h2 class="font-bold text-slate-800 text-lg">Rekapitulasi Kelas Binaan ({{ $date->translatedFormat('d F Y') }})</h2>
            
            @forelse($rekapKelas as $kelasId => $rekap)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                    <h3 class="font-bold text-slate-900">{{ $rekap['kelas']->nama }}</h3>
                    <span class="text-xs font-semibold px-2 py-1 bg-blue-100 text-blue-800 rounded-lg">Kelas Binaan</span>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100">
                        <p class="text-[10px] font-bold text-emerald-600 uppercase">Hadir</p>
                        <p class="text-xl font-black text-emerald-700">{{ $rekap['hadir'] }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 border border-amber-100">
                        <p class="text-[10px] font-bold text-amber-600 uppercase">Terlambat</p>
                        <p class="text-xl font-black text-amber-700">{{ $rekap['terlambat'] }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="text-[10px] font-bold text-blue-600 uppercase">Sakit/Izin</p>
                        <p class="text-xl font-black text-blue-700">{{ $rekap['sakit'] + $rekap['izin'] }}</p>
                    </div>
                    <div class="bg-rose-50 rounded-xl p-3 border border-rose-100">
                        <p class="text-[10px] font-bold text-rose-600 uppercase">Alpa</p>
                        <p class="text-xl font-black text-rose-700">{{ $rekap['alpa'] }}</p>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-sm text-slate-500 text-center py-4">Belum ada data kelas binaan.</p>
            @endforelse
        </div>

        <div class="space-y-4">
            <h2 class="font-bold text-slate-800 text-lg">Catatan Kehadiran Detail</h2>
            
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Siswa</th>
                                <th class="px-4 py-3">Mapel</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($kehadiran as $kh)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900 leading-tight">{{ $kh->siswa->name ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $kh->siswa?->siswaProfile?->kelas?->nama ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-slate-700">{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $kh->pertemuan?->jadwal?->jam_mulai ? \Carbon\Carbon::parse($kh->pertemuan->jadwal->jam_mulai)->format('H:i') . ' - ' . \Carbon\Carbon::parse($kh->pertemuan->jadwal->jam_selesai)->format('H:i') : '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if($kh->status === 'hadir')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-md">Hadir</span>
                                    @elseif($kh->status === 'terlambat')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded-md">Terlambat</span>
                                    @elseif(in_array($kh->status, ['sakit', 'izin']))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-1 rounded-md">{{ ucfirst($kh->status) }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-1 rounded-md">Alpa</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500 text-sm">Tidak ada catatan kehadiran pada tanggal ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Modal Atur Kelas Binaan --}}
        <div x-show="showModalKelas" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showModalKelas = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl bg-white rounded-2xl p-5 shadow-2xl border border-slate-200 space-y-4"
                     @click.outside="showModalKelas = false">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Atur Kelas Binaan BK</h3>
                                <p class="text-xs text-slate-500">Centang kelas yang menjadi tanggung jawab binaan Anda.</p>
                            </div>
                        </div>
                        <button type="button" @click="showModalKelas = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('profile.bk-kelas.update') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- Search & Tingkat Filter -->
                        <div class="space-y-2.5">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="relative flex-1">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text" x-model="search" placeholder="Cari nama kelas..."
                                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-medium text-slate-600 shrink-0">
                                    <button type="button" @click="tabTingkat = 'all'"
                                            :class="tabTingkat === 'all' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-lg">Semua</button>
                                    <button type="button" @click="tabTingkat = '10'"
                                            :class="tabTingkat === '10' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-lg">Kelas 10</button>
                                    <button type="button" @click="tabTingkat = '11'"
                                            :class="tabTingkat === '11' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-lg">Kelas 11</button>
                                    <button type="button" @click="tabTingkat = '12'"
                                            :class="tabTingkat === '12' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-lg">Kelas 12</button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="toggleAllTingkat(tabTingkat, true)" class="text-blue-600 hover:text-blue-800 font-medium">Pilih Semua</button>
                                    <span>•</span>
                                    <button type="button" @click="toggleAllTingkat(tabTingkat, false)" class="text-slate-500 hover:text-red-600 font-medium">Lepas Pilihan</button>
                                </div>
                                <span class="font-semibold text-blue-700" x-text="selectedKelas.length + ' kelas terpilih'"></span>
                            </div>
                        </div>

                        <!-- Checkbox list -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-1.5 border border-slate-200 rounded-xl bg-slate-50/50">
                            <template x-for="k in allKelasData" :key="k.id">
                                <label x-show="matches(k)"
                                       :class="selectedKelas.includes(k.id) ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-400/50' : 'border-slate-200 bg-white hover:border-slate-300'"
                                       class="flex items-start gap-2 p-2 rounded-xl border cursor-pointer transition-all">
                                    <input type="checkbox" name="bk_kelas_ids[]" :value="k.id" x-model="selectedKelas"
                                           class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4 shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-800 truncate" x-text="k.nama"></p>
                                        <p class="text-[10px] text-slate-500">Tingkat <span x-text="k.tingkat"></span></p>
                                        <template x-if="k.current_bk && !k.is_my_bk">
                                            <p class="text-[9px] text-amber-700 truncate mt-0.5" :title="'BK: ' + k.current_bk">
                                                BK: <span x-text="k.current_bk"></span>
                                            </p>
                                        </template>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="showModalKelas = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-sm transition-colors flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Kelas Binaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guru>

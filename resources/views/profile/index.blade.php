@php
    $layout = auth()->user()->role === 'siswa' ? 'layouts.siswa' : (auth()->user()->role === 'guru' ? 'layouts.guru' : 'layouts.app');
@endphp
<x-dynamic-component :component="$layout">
    <x-slot:title>Profil Saya</x-slot:title>

    <div class="p-4 md:p-6 space-y-6 max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-2xl border border-slate-200">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                    <p class="text-slate-500 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
            </div>

            <h3 class="text-sm font-bold text-slate-900 mb-3 uppercase tracking-wider">Informasi Akun</h3>
            <div class="space-y-4 text-sm">
                <div>
                    <span class="block text-slate-500 mb-0.5">Email</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->email }}</span>
                </div>
                
                @if(auth()->user()->role === 'siswa' && auth()->user()->siswaProfile)
                <div>
                    <span class="block text-slate-500 mb-0.5">NIS</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->siswaProfile->nis ?? '-' }}</span>
                </div>
                <div>
                    <span class="block text-slate-500 mb-0.5">Kelas</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->siswaProfile->kelas->nama ?? 'Belum berkelas' }}</span>
                </div>
                @endif
                
                @if(auth()->user()->role === 'guru' && auth()->user()->guruProfile)
                <div>
                    <span class="block text-slate-500 mb-0.5">NIP</span>
                    <span class="font-medium text-slate-900">{{ auth()->user()->guruProfile->nip ?? '-' }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($isGuruBk)
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm" x-data="{
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
            <div class="flex items-start justify-between gap-4 mb-4 pb-3 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-blue-100 text-blue-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Atur Kelas Binaan Guru BK</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Tentukan kelas binaan/dampingan BK Anda untuk pemantauan presensi dan catatan siswa.</p>
                        </div>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
                    <span x-text="selectedKelas.length"></span> Kelas Dipilih
                </span>
            </div>

            <form action="{{ route('profile.bk-kelas.update') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Filter & Search Controls -->
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="search" placeholder="Cari nama kelas (mis: X PPLG, XI DKV)..."
                                   class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                        </div>

                        <!-- Tab Tingkat -->
                        <div class="flex items-center bg-slate-100 p-1 rounded-xl text-xs font-medium text-slate-600 shrink-0">
                            <button type="button" @click="tabTingkat = 'all'"
                                    :class="tabTingkat === 'all' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg transition-all">Semua</button>
                            <button type="button" @click="tabTingkat = '10'"
                                    :class="tabTingkat === '10' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg transition-all">Kelas 10</button>
                            <button type="button" @click="tabTingkat = '11'"
                                    :class="tabTingkat === '11' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg transition-all">Kelas 11</button>
                            <button type="button" @click="tabTingkat = '12'"
                                    :class="tabTingkat === '12' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg transition-all">Kelas 12</button>
                        </div>
                    </div>

                    <!-- Action Helpers -->
                    <div class="flex items-center justify-between text-[11px] text-slate-500 px-0.5">
                        <div class="flex items-center gap-2">
                            <span>Aksi Cepat:</span>
                            <button type="button" @click="toggleAllTingkat(tabTingkat, true)" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                Pilih Semua <span x-show="tabTingkat !== 'all'">Tingkat Ini</span>
                            </button>
                            <span>•</span>
                            <button type="button" @click="toggleAllTingkat(tabTingkat, false)" class="text-slate-500 hover:text-red-600 font-medium hover:underline">
                                Lepas Pilihan
                            </button>
                        </div>
                        <span class="text-slate-400">Centang kelas yang Anda ampu</span>
                    </div>
                </div>

                <!-- Kelas Checklist Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-72 overflow-y-auto p-1.5 border border-slate-100 rounded-xl bg-slate-50/50">
                    <template x-for="k in allKelasData" :key="k.id">
                        <label x-show="matches(k)"
                               :class="selectedKelas.includes(k.id) ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-400/50' : 'border-slate-200 bg-white hover:border-slate-300'"
                               class="flex items-start gap-2.5 p-2.5 rounded-xl border cursor-pointer transition-all">
                            <input type="checkbox" name="bk_kelas_ids[]" :value="k.id" x-model="selectedKelas"
                                   class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4 shrink-0">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-800 truncate" x-text="k.nama"></p>
                                <p class="text-[10px] text-slate-500">Tingkat <span x-text="k.tingkat"></span></p>
                                <template x-if="k.current_bk && !k.is_my_bk">
                                    <p class="text-[9px] text-amber-700 truncate mt-0.5" :title="'BK saat ini: ' + k.current_bk">
                                        BK: <span x-text="k.current_bk"></span>
                                    </p>
                                </template>
                            </div>
                        </label>
                    </template>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-xl hover:bg-blue-700 transition-colors shadow-sm text-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Kelas Binaan BK
                    </button>
                </div>
            </form>
        </div>
        @endif

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Ubah Password</h3>
            <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required
                           class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 text-sm bg-slate-50">
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 text-sm bg-slate-50">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 text-sm bg-slate-50">
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="w-full bg-slate-900 text-white font-semibold py-2.5 rounded-xl hover:bg-slate-800 transition-colors shadow-sm text-sm">
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-red-600 mb-4 uppercase tracking-wider">Keluar Aplikasi</h3>
            <p class="text-sm text-slate-500 mb-4">Pastikan pekerjaan Anda sudah tersimpan sebelum keluar dari aplikasi.</p>
            <form method="POST" action="{{ route('logout') }}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal', { detail: { form: this } }));">
                @csrf
                <button type="submit" class="w-full bg-red-50 text-red-600 border border-red-200 font-semibold py-2.5 rounded-xl hover:bg-red-100 transition-colors shadow-sm text-sm flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
        
        <!-- Tambahan padding bawah agar tidak tertutup navbar -->
        <div class="h-10"></div>
    </div>
</x-dynamic-component>

<x-layouts.admin>
    <x-slot:title>Kenaikan Kelas & Tutup Tahun Ajaran</x-slot:title>

    <div x-data="{
        targetSchoolYear: '{{ $nextSchoolYear }}',
        updateSchoolYear: true,
        resetSemester: true,
        luluskanKelas12: true,
        
        tinggalKelasMap: {},
        
        modalOpen: false,
        modalLoading: false,
        currentKelas: { id: null, nama: '' },
        currentSiswaList: [],
        modalSearch: '',

        confirmModalOpen: false,

        openModal(kelasId, kelasNama) {
            this.currentKelas = { id: kelasId, nama: kelasNama };
            this.modalOpen = true;
            this.modalLoading = true;
            this.modalSearch = '';
            this.currentSiswaList = [];

            fetch('{{ url('admin/kenaikan-kelas/siswa') }}/' + kelasId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => {
                    if (!res.ok) throw new Error('Gagal memuat data');
                    return res.json();
                })
                .then(data => {
                    this.currentSiswaList = data.siswa || [];
                    this.modalLoading = false;
                })
                .catch(err => {
                    console.error(err);
                    alert('Gagal mengambil daftar siswa. Silakan coba lagi.');
                    this.modalLoading = false;
                });
        },

        toggleTinggalKelas(siswa) {
            if (this.tinggalKelasMap[siswa.profile_id]) {
                delete this.tinggalKelasMap[siswa.profile_id];
            } else {
                this.tinggalKelasMap[siswa.profile_id] = {
                    profile_id: siswa.profile_id,
                    name: siswa.name,
                    nis: siswa.nis,
                    kelas_id: this.currentKelas.id,
                    kelas_nama: this.currentKelas.nama
                };
            }
        },

        isTinggalKelas(profileId) {
            return !!this.tinggalKelasMap[profileId];
        },

        countTinggalByKelas(kelasId) {
            return Object.values(this.tinggalKelasMap).filter(s => s.kelas_id === kelasId).length;
        },

        totalTinggalKelas() {
            return Object.keys(this.tinggalKelasMap).length;
        },

        filteredSiswa() {
            if (!this.modalSearch) return this.currentSiswaList;
            const q = this.modalSearch.toLowerCase();
            return this.currentSiswaList.filter(s => 
                (s.name && s.name.toLowerCase().includes(q)) || 
                (s.nis && s.nis.toLowerCase().includes(q))
            );
        }
    }" class="space-y-6">

        {{-- Hero Header --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-600 to-blue-600 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/10">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-semibold uppercase tracking-wider mb-3">
                        <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Fitur Otomatisasi Akademik
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Kenaikan Kelas & Tutup Tahun Ajaran</h1>
                    <p class="mt-2 text-sm text-indigo-100 leading-relaxed">
                        Kelola transisi tahun ajaran secara otomatis dan aman: naikkan siswa kelas 10 ke 11, kelas 11 ke 12, luluskan kelas 12, tandai siswa tinggal kelas, serta perbarui periode tahun ajaran aktif dalam satu kali proses.
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-3">
                    <a href="{{ route('admin.kelas.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-sm font-semibold text-white transition-all backdrop-blur-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Kelas
                    </a>
                </div>
            </div>
            {{-- Decorative pattern --}}
            <div class="absolute -right-12 -bottom-12 w-64 h-64 rounded-full bg-white/5 pointer-events-none blur-2xl"></div>
        </div>

        {{-- Stat Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tahun Ajaran Aktif</p>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-xl font-bold text-slate-900">{{ $currentSchoolYear }}</p>
                </div>
                <p class="text-xs text-slate-400 mt-1">Semester {{ in_array($currentSemester, ['1', 'ganjil'], true) ? '1 (Ganjil)' : '2 (Genap)' }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-blue-100 p-4 shadow-xs bg-blue-50/20">
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Kelas 10 (Akan Naik 11)</p>
                <p class="text-2xl font-bold text-blue-700 mt-1">{{ $totalSiswa10 }} <span class="text-xs font-normal text-slate-500">Siswa</span></p>
                <p class="text-xs text-slate-500 mt-1">{{ $kelas10->count() }} Rombel Kelas</p>
            </div>

            <div class="bg-white rounded-2xl border border-indigo-100 p-4 shadow-xs bg-indigo-50/20">
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Kelas 11 (Akan Naik 12)</p>
                <p class="text-2xl font-bold text-indigo-700 mt-1">{{ $totalSiswa11 }} <span class="text-xs font-normal text-slate-500">Siswa</span></p>
                <p class="text-xs text-slate-500 mt-1">{{ $kelas11->count() }} Rombel Kelas</p>
            </div>

            <div class="bg-white rounded-2xl border border-purple-100 p-4 shadow-xs bg-purple-50/20">
                <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider">Kelas 12 (Akan Lulus)</p>
                <p class="text-2xl font-bold text-purple-700 mt-1">{{ $totalSiswa12 }} <span class="text-xs font-normal text-slate-500">Siswa</span></p>
                <p class="text-xs text-slate-500 mt-1">{{ $kelas12->count() }} Rombel Kelas</p>
            </div>
        </div>

        {{-- Form Kenaikan Kelas --}}
        <form id="form-kenaikan-kelas" action="{{ route('admin.kenaikan-kelas.process') }}" method="POST">
            @csrf

            {{-- Hidden inputs for Tinggal Kelas --}}
            <template x-for="profileId in Object.keys(tinggalKelasMap)" :key="profileId">
                <input type="hidden" name="tinggal_kelas_profiles[]" :value="profileId">
            </template>

            <div class="space-y-6">

                {{-- CARD 1: Konfigurasi Tahun Ajaran & Opsi Kelulusan --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">1. Konfigurasi Periode Tahun Ajaran & Kelulusan</h2>
                            <p class="text-xs text-slate-500">Tentukan periode kalender baru dan perlakuan untuk siswa tingkat akhir (Kelas 12).</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
                        <div class="space-y-4">
                            <div>
                                <label for="target_school_year" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Tahun Ajaran Baru (Tujuan)
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="text"
                                           name="target_school_year"
                                           id="target_school_year"
                                           x-model="targetSchoolYear"
                                           required
                                           placeholder="Contoh: 2027/2028"
                                           class="w-full text-sm font-semibold rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50">
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Sistem otomatis menghitung penambahan 1 tahun ajaran dari tahun saat ini ({{ $currentSchoolYear }}).</p>
                            </div>

                            <div class="pt-2 space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox"
                                           name="update_school_year"
                                           value="1"
                                           x-model="updateSchoolYear"
                                           class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <span class="text-sm font-semibold text-slate-800">Perbarui Tahun Ajaran Aktif</span>
                                        <p class="text-xs text-slate-500">Mengubah pengaturan sistem `school_year` ke tahun ajaran baru.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox"
                                           name="reset_semester"
                                           value="1"
                                           x-model="resetSemester"
                                           class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <span class="text-sm font-semibold text-slate-800">Reset Semester ke Semester 1 (Ganjil)</span>
                                        <p class="text-xs text-slate-500">Memulai tahun ajaran baru dari semester Ganjil secara otomatis.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-purple-50/60 border border-purple-100 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                    <h3 class="text-sm font-bold text-purple-900">Kelulusan Siswa Kelas 12</h3>
                                </div>
                                <p class="text-xs text-purple-800 leading-relaxed">
                                    Siswa kelas 12 yang lulus akan dinonaktifkan status akunnya (<code class="text-[11px] bg-purple-100 px-1 py-0.5 rounded text-purple-900">is_active = 0</code>) sehingga mereka tidak dapat lagi mengakses sistem absensi, namun data historis rekap presensi masa lalu tetap utuh dan aman.
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-purple-200/60">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox"
                                           name="luluskan_kelas_12"
                                           value="1"
                                           x-model="luluskanKelas12"
                                           class="rounded border-purple-300 text-purple-600 focus:ring-purple-500">
                                    <span class="text-xs font-bold text-purple-950">Luluskan Siswa Kelas 12 (Kecuali yang Ditandai Tinggal Kelas)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Opsi Siswa Kelas 12 Tidak Lulus / Tinggal Kelas --}}
                    <div x-data="{ showKelas12List: false }" class="mt-5 pt-4 border-t border-slate-100">
                        <button type="button" 
                                @click="showKelas12List = !showKelas12List"
                                class="flex items-center justify-between w-full text-left text-xs font-bold text-slate-700 hover:text-indigo-600 py-1 transition-colors">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>Ada siswa kelas 12 yang TIDAK lulus / tinggal kelas? Klik untuk menandai siswa</span>
                            </span>
                            <span class="text-indigo-600 flex items-center gap-1 font-semibold" x-text="showKelas12List ? 'Sembunyikan' : 'Buka Daftar Kelas 12'"></span>
                        </button>
                        
                        <div x-show="showKelas12List" style="display: none;" class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-2">
                            @foreach($kelas12 as $k12)
                                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">{{ $k12->nama }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $k12->siswa_profiles_count }} Siswa</p>
                                    </div>
                                    <button type="button"
                                            @click="openModal({{ $k12->id }}, @js($k12->nama))"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-lg border transition-colors cursor-pointer"
                                            :class="countTinggalByKelas({{ $k12->id }}) > 0 ? 'bg-red-50 text-red-700 border-red-200' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'">
                                        <span>Tandai Siswa</span>
                                        <span x-show="countTinggalByKelas({{ $k12->id }}) > 0"
                                              class="px-1.5 py-0.2 rounded-full bg-red-600 text-white text-[10px] font-bold"
                                              x-text="countTinggalByKelas({{ $k12->id }})"></span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- CARD 2: Pemetaan Kenaikan Kelas 11 -> Kelas 12 --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/40">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <span class="text-sm font-black">11&rarr;12</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">2. Pemetaan Kenaikan: Kelas 11 &rarr; Kelas 12</h2>
                                <p class="text-xs text-slate-500">Sistem telah merekomendasikan kelas tujuan secara otomatis berdasarkan jurusan dan nomor rombel.</p>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                            Total Rombel: <span class="font-bold text-slate-800">{{ $kelas11->count() }} Kelas</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-600">
                                <tr>
                                    <th class="px-5 py-3.5 text-left w-12">No</th>
                                    <th class="px-5 py-3.5 text-left">Kelas Asal (Tingkat 11)</th>
                                    <th class="px-5 py-3.5 text-center">Jml Siswa</th>
                                    <th class="px-5 py-3.5 text-center w-12"></th>
                                    <th class="px-5 py-3.5 text-left">Kelas Tujuan (Tingkat 12)</th>
                                    <th class="px-5 py-3.5 text-right">Siswa Tinggal Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($kelas11 as $index => $k11)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-5 py-3 text-xs text-slate-400">{{ $index + 1 }}</td>
                                    <td class="px-5 py-3">
                                        <div class="font-bold text-slate-900">{{ $k11->nama }}</div>
                                        <div class="text-[11px] text-slate-400">Tingkat {{ $k11->tingkat }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            {{ $k11->siswa_profiles_count }} Siswa
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-400">
                                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </td>
                                    <td class="px-5 py-3">
                                        <select name="mapping_11_to_12[{{ $k11->id }}]"
                                                class="w-full text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 font-medium">
                                            <option value="">-- Jangan Pindahkan (Biarkan) --</option>
                                            @foreach($kelas12 as $k12)
                                                <option value="{{ $k12->id }}" {{ ($mapping11to12[$k11->id] ?? null) == $k12->id ? 'selected' : '' }}>
                                                    {{ $k12->nama }} (Kapasitas saat ini: {{ $k12->siswa_profiles_count }} siswa)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button type="button"
                                                @click="openModal({{ $k11->id }}, @js($k11->nama))"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors"
                                                :class="countTinggalByKelas({{ $k11->id }}) > 0 ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>Tinggal Kelas</span>
                                            <span x-show="countTinggalByKelas({{ $k11->id }}) > 0"
                                                  class="px-1.5 py-0.2 rounded-full bg-red-600 text-white text-[10px] font-bold"
                                                  x-text="countTinggalByKelas({{ $k11->id }})"></span>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-6 text-center text-slate-400">Tidak ada data kelas tingkat 11.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CARD 3: Pemetaan Kenaikan Kelas 10 -> Kelas 11 --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/40">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <span class="text-sm font-black">10&rarr;11</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">3. Pemetaan Kenaikan: Kelas 10 &rarr; Kelas 11</h2>
                                <p class="text-xs text-slate-500">Sistem menyesuaikan singkatan Kurikulum Merdeka (AKL &rarr; AK, MPLB &rarr; MP, PPLG &rarr; RPL) secara otomatis.</p>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                            Total Rombel: <span class="font-bold text-slate-800">{{ $kelas10->count() }} Kelas</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-semibold text-slate-600">
                                <tr>
                                    <th class="px-5 py-3.5 text-left w-12">No</th>
                                    <th class="px-5 py-3.5 text-left">Kelas Asal (Tingkat 10)</th>
                                    <th class="px-5 py-3.5 text-center">Jml Siswa</th>
                                    <th class="px-5 py-3.5 text-center w-12"></th>
                                    <th class="px-5 py-3.5 text-left">Kelas Tujuan (Tingkat 11)</th>
                                    <th class="px-5 py-3.5 text-right">Siswa Tinggal Kelas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($kelas10 as $index => $k10)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-5 py-3 text-xs text-slate-400">{{ $index + 1 }}</td>
                                    <td class="px-5 py-3">
                                        <div class="font-bold text-slate-900">{{ $k10->nama }}</div>
                                        <div class="text-[11px] text-slate-400">Tingkat {{ $k10->tingkat }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            {{ $k10->siswa_profiles_count }} Siswa
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-400">
                                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </td>
                                    <td class="px-5 py-3">
                                        <select name="mapping_10_to_11[{{ $k10->id }}]"
                                                class="w-full text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 font-medium">
                                            <option value="">-- Jangan Pindahkan (Biarkan) --</option>
                                            @foreach($kelas11 as $k11)
                                                <option value="{{ $k11->id }}" {{ ($mapping10to11[$k10->id] ?? null) == $k11->id ? 'selected' : '' }}>
                                                    {{ $k11->nama }} (Kapasitas saat ini: {{ $k11->siswa_profiles_count }} siswa)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button type="button"
                                                @click="openModal({{ $k10->id }}, @js($k10->nama))"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors"
                                                :class="countTinggalByKelas({{ $k10->id }}) > 0 ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>Tinggal Kelas</span>
                                            <span x-show="countTinggalByKelas({{ $k10->id }}) > 0"
                                                  class="px-1.5 py-0.2 rounded-full bg-red-600 text-white text-[10px] font-bold"
                                                  x-text="countTinggalByKelas({{ $k10->id }})"></span>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-6 text-center text-slate-400">Tidak ada data kelas tingkat 10.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Action / Confirmation Footer --}}
                <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="space-y-1 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <h3 class="text-base font-bold">Siap Mengeksekusi Kenaikan Kelas?</h3>
                        </div>
                        <p class="text-xs text-slate-300 max-w-2xl">
                            Seluruh perubahan akan diproses secara transaksional dalam database. Siswa yang ditandai tinggal kelas (<span class="font-bold text-amber-300" x-text="totalTinggalKelas()">0</span> siswa) tidak akan dipindahkan dari rombelnya saat ini.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <button type="button"
                                @click="confirmModalOpen = true"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Proses Kenaikan Kelas Sekarang</span>
                        </button>
                    </div>
                </div>

            </div>
        </form>

        {{-- MODAL 1: Pilih Siswa Tinggal Kelas --}}
        <div x-show="modalOpen" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="modalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                     @click="modalOpen = false"></div>

                <div x-show="modalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block w-full max-w-2xl bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all my-8">

                    {{-- Modal Header --}}
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 text-[11px] font-bold" x-text="currentKelas.nama"></span>
                                <h3 class="text-base font-bold text-slate-900">Pilih Siswa yang Tinggal Kelas</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Tandai siswa yang TIDAK naik kelas. Siswa yang ditandai akan tetap berada di kelas ini.</p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Search & Stats in Modal --}}
                    <div class="p-5 border-b border-slate-100 bg-white space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="relative flex-1">
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text"
                                       x-model="modalSearch"
                                       placeholder="Cari nama atau NIS siswa..."
                                       class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="text-xs text-slate-500 shrink-0">
                                Ditandai: <span class="font-bold text-red-600" x-text="countTinggalByKelas(currentKelas.id)">0</span> Siswa
                            </div>
                        </div>
                    </div>

                    {{-- Students List --}}
                    <div class="max-h-96 overflow-y-auto p-4 space-y-2">
                        <template x-if="modalLoading">
                            <div class="py-12 text-center text-slate-400">
                                <svg class="w-8 h-8 animate-spin mx-auto text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-xs">Memuat daftar siswa...</p>
                            </div>
                        </template>

                        <template x-if="!modalLoading && currentSiswaList.length === 0">
                            <div class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada siswa terdaftar di kelas ini.
                            </div>
                        </template>

                        <template x-if="!modalLoading && currentSiswaList.length > 0">
                            <div class="space-y-1.5">
                                <template x-for="siswa in filteredSiswa()" :key="siswa.profile_id">
                                    <div class="flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer select-none"
                                         :class="isTinggalKelas(siswa.profile_id) ? 'bg-red-50/70 border-red-200' : 'bg-white border-slate-200 hover:border-indigo-200'"
                                         @click="toggleTinggalKelas(siswa)">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                                 :class="isTinggalKelas(siswa.profile_id) ? 'bg-red-200 text-red-800' : 'bg-slate-100 text-slate-700'"
                                                 x-text="siswa.name.charAt(0)">
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-slate-900" x-text="siswa.name"></p>
                                                <p class="text-[11px] text-slate-400">NIS: <span x-text="siswa.nis"></span></p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span x-show="!isTinggalKelas(siswa.profile_id)"
                                                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Naik Kelas
                                            </span>
                                            <span x-show="isTinggalKelas(siswa.profile_id)"
                                                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-100 text-red-800 border border-red-300">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Tinggal Kelas
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                        <p class="text-[11px] text-slate-500">Klik baris siswa untuk mengubah status Naik / Tinggal kelas.</p>
                        <button type="button" @click="modalOpen = false" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors">
                            Selesai & Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL 2: Konfirmasi Final Eksekusi --}}
        <div x-show="confirmModalOpen" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="confirm-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="confirmModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                     @click="confirmModalOpen = false"></div>

                <div x-show="confirmModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block w-full max-w-lg bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all my-8 p-6 sm:p-7">

                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>

                    <h3 class="text-lg font-bold text-slate-900" id="confirm-modal-title">Konfirmasi Eksekusi Kenaikan Kelas</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                        Anda akan melakukan perubahan massal pada seluruh data siswa sekolah:
                    </p>

                    <div class="my-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2 text-slate-700">
                        <div class="flex items-center justify-between">
                            <span>Tahun Ajaran Baru:</span>
                            <span class="font-bold text-slate-900" x-text="targetSchoolYear"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Luluskan Kelas 12:</span>
                            <span class="font-bold text-purple-700" x-text="luluskanKelas12 ? 'Ya (Nonaktifkan Akun)' : 'Tidak'"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Reset Semester ke 1:</span>
                            <span class="font-bold text-blue-700" x-text="resetSemester ? 'Ya' : 'Tidak'"></span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                            <span>Siswa Tinggal Kelas:</span>
                            <span class="font-bold text-red-600" x-text="totalTinggalKelas() + ' Siswa'"></span>
                        </div>
                    </div>

                    <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200 mb-5">
                        ⚠️ <strong>Peringatan:</strong> Pastikan Anda telah mengunduh/mencetak rekap absensi dan nilai semester genap sebelum melanjutkan.
                    </p>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button"
                                @click="confirmModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="button"
                                @click="document.getElementById('form-kenaikan-kelas').submit()"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/30 transition-all">
                            Ya, Eksekusi Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>

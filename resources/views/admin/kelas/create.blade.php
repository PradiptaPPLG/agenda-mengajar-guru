<x-layouts.admin>
    <x-slot:title>Tambah Kelas</x-slot:title>
    
    @push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function kelasForm() {
            return {
                tingkat: '{{ old('tingkat') }}',
                jurusan: '',
                nomorKelas: '',
                namaKelas: '{{ old('nama') }}',
                jurusans: {
                    'Akuntansi': 4,
                    'Akuntansi Syariah': 1,
                    'Pemasaran': 3,
                    'Pemasaran Ritel': 1,
                    'Kuliner': 2,
                    'Hotel': 2,
                    'Managemen Bisnis': 3,
                    'Desain Komunikasi Visual': 1,
                    'Rekayasa Perangkat Lunak': 1
                },
                updateNama() {
                    if (!this.tingkat || !this.jurusan) return;

                    if (this.jurusans[this.jurusan] === 1) {
                        this.namaKelas = `${this.tingkat} ${this.jurusan}`;
                    } else if (this.nomorKelas) {
                        this.namaKelas = `${this.tingkat} ${this.nomorKelas}`;
                    }
                }
            }
        }
    </script>
    @endpush

    <div class="max-w-lg" x-data="kelasForm()">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <form action="{{ route('admin.kelas.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tingkat <span class="text-red-500">*</span></label>
                    <select name="tingkat" x-model="tingkat" @change="updateNama()" required
                            class="w-full px-3.5 py-2.5 border {{ $errors->has('tingkat') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih tingkat...</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                    </select>
                    @error('tingkat')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Generator Nama Kelas</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Jurusan</label>
                            <select x-model="jurusan" @change="nomorKelas = ''; updateNama()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih jurusan...</option>
                                <template x-for="(count, jur) in jurusans" :key="jur">
                                    <option :value="jur" x-text="jur"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Pilihan Kelas</label>
                            <select x-model="nomorKelas" @change="updateNama()" :disabled="!jurusan || jurusans[jurusan] === 1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:bg-slate-100">
                                <option value="">Nomor...</option>
                                <template x-if="jurusan && jurusans[jurusan] > 1">
                                    <template x-for="i in jurusans[jurusan]" :key="i">
                                        <option :value="jurusan + ' ' + i" x-text="jurusan + ' ' + i"></option>
                                    </template>
                                </template>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">Memilih opsi di atas akan otomatis mengisi nama kelas di bawah.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Kelas <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" x-model="namaKelas" placeholder="Contoh: 10 Kuliner 1" required
                           class="w-full px-3.5 py-2.5 border {{ $errors->has('nama') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nama')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Wali Kelas</label>
                    <select name="wali_kelas_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih wali kelas...</option>
                        @foreach($guruList as $guru)
                        <option value="{{ $guru->id }}" {{ old('wali_kelas_id') == $guru->id ? 'selected' : '' }}>{{ $guru->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Guru BK</label>
                    <select name="bk_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih Guru BK...</option>
                        @foreach($guruList as $guru)
                        <option value="{{ $guru->id }}" {{ old('bk_id') == $guru->id ? 'selected' : '' }}>{{ $guru->name }}</option>

                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Ajaran <span class="text-red-500">*</span></label>
                    <input type="text" name="tahun_ajaran" value="{{ old('tahun_ajaran', '2025/2026') }}" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Pengaturan Sistem Blok --}}
                <div class="p-4 bg-blue-50/60 border border-blue-200 rounded-xl space-y-3" x-data="{ isBlok: {{ old('is_sistem_blok', false) ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Sistem Blok SMK
                            </p>
                            <p class="text-xs text-blue-700">Terapkan rotasi Kelompok A (Umum) & B (Kejuruan)</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_sistem_blok" value="1" x-model="isBlok" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div x-show="isBlok" x-transition class="pt-2 border-t border-blue-200/60 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Model Rotasi</label>
                                <select name="model_rotasi" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="rotasi_minggu" @selected(old('model_rotasi') === 'rotasi_minggu')>Rotasi Antar-Minggu</option>
                                    <option value="split_harian" @selected(old('model_rotasi') === 'split_harian')>Split Harian (Sub-Kelas)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Blok Awal (Minggu 1)</label>
                                <select name="blok_awal" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="kelompok_a" @selected(old('blok_awal', 'kelompok_a') === 'kelompok_a')>Kelompok A (Umum)</option>
                                    <option value="kelompok_b" @selected(old('blok_awal') === 'kelompok_b')>Kelompok B (Kejuruan)</option>
                                    <option value="split" @selected(old('blok_awal') === 'split')>Split (A & B Paralel)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">Simpan</button>
                    <a href="{{ route('admin.kelas.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>

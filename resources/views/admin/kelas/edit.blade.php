<x-layouts.admin>
    <x-slot:title>Edit Kelas</x-slot:title>
    
    @push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function kelasForm() {
            return {
                tingkat: '{{ old('tingkat', $kelas->tingkat) }}',
                jurusan: '',
                nomorKelas: '',
                namaKelas: '{{ old('nama', $kelas->nama) }}',
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        <!-- Kolom Kiri: Form Edit -->
        <div class="w-full h-full" x-data="kelasForm()">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 h-full flex flex-col">
                <form action="{{ route('admin.kelas.update', $kelas) }}" method="POST" class="space-y-4 flex-1 flex flex-col">
                    @csrf @method('PUT')
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tingkat <span class="text-red-500">*</span></label>
                        <select name="tingkat" x-model="tingkat" @change="updateNama()" required
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih tingkat...</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
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
                        <input type="text" name="nama" x-model="namaKelas" required
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Wali Kelas</label>
                        <select name="wali_kelas_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih wali kelas...</option>
                            @foreach($guruList as $guru)
                            <option value="{{ $guru->id }}" {{ old('wali_kelas_id', $kelas->wali_kelas_id) == $guru->id ? 'selected' : '' }}>{{ $guru->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Guru BK</label>
                        <select name="bk_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Guru BK...</option>
                            @foreach($guruList as $guru)
                            <option value="{{ $guru->id }}" {{ old('bk_id', $kelas->bk_id) == $guru->id ? 'selected' : '' }}>{{ $guru->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Pengaturan Sistem Blok --}}
                    <div class="p-4 bg-blue-50/60 border border-blue-200 rounded-xl space-y-3" x-data="{ isBlok: {{ old('is_sistem_blok', $kelas->is_sistem_blok) ? 'true' : 'false' }} }">
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
                                        <option value="rotasi_minggu" @selected(old('model_rotasi', $kelas->model_rotasi) === 'rotasi_minggu')>Rotasi Antar-Minggu</option>
                                        <option value="split_harian" @selected(old('model_rotasi', $kelas->model_rotasi) === 'split_harian')>Split Harian (Sub-Kelas)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Blok Awal (Minggu 1)</label>
                                    <select name="blok_awal" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                        <option value="kelompok_a" @selected(old('blok_awal', $kelas->blok_awal) === 'kelompok_a')>Kelompok A (Umum)</option>
                                        <option value="kelompok_b" @selected(old('blok_awal', $kelas->blok_awal) === 'kelompok_b')>Kelompok B (Kejuruan)</option>
                                        <option value="split" @selected(old('blok_awal', $kelas->blok_awal) === 'split')>Split (A & B Paralel)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pendorong tombol ke bawah -->
                    <div class="flex-1"></div>

                    <div class="flex items-center gap-3 pt-2 mt-auto">
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">Perbarui</button>
                        <a href="{{ route('admin.kelas.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Manajemen Siswa -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col h-full" x-data='{ tab: "current", allSiswa: @json($semuaSiswa), filterKelas: "", selectedSiswa: [], selectAll: false, searchSiswa: "" }'>
            <div class="flex border-b border-slate-200">
                <button @click="tab = 'current'" :class="tab === 'current' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="flex-1 py-3.5 text-sm transition-colors">Siswa di Kelas Ini</button>
                <button @click="tab = 'master'" :class="tab === 'master' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="flex-1 py-3.5 text-sm transition-colors">Pilih dari Master</button>
                <button @click="tab = 'excel'" :class="tab === 'excel' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-slate-500 hover:text-slate-700 font-medium'" class="flex-1 py-3.5 text-sm transition-colors">Import Excel</button>
            </div>
            
            <div class="p-6 h-[600px] overflow-y-auto">
                <!-- Tab: Current Siswa -->
                <div x-show="tab === 'current'" class="space-y-4">
                    <div class="flex justify-between items-center mb-2">
                        <p class="text-sm text-slate-600">Daftar siswa yang saat ini terdaftar di kelas <strong>{{ $kelas->nama }}</strong>.</p>
                        <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full" x-text="allSiswa.filter(s => s.kelas_id === {{ $kelas->id }}).length + ' Siswa'"></span>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">
                        <div class="max-h-[450px] overflow-y-auto divide-y divide-slate-100">
                            <template x-for="siswa in allSiswa.filter(s => s.kelas_id === {{ $kelas->id }})" :key="siswa.id">
                                <div class="flex items-center justify-between px-4 py-3 hover:bg-slate-50">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900" x-text="siswa.user.name"></p>
                                        <p class="text-xs text-slate-500">NIS: <span x-text="siswa.nis || '-'"></span></p>
                                    </div>
                                    <div class="flex items-center justify-end" x-data="{ openMenu: false }">
                                        <div class="relative inline-block text-left" @click.away="openMenu = false">
                                            <button @click="openMenu = !openMenu" type="button" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                            </button>
                                            <div x-show="openMenu" class="absolute right-0 z-[100] mt-1 w-36 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" style="display: none;">
                                                <div class="py-1">
                                                    <form :action="'{{ route('admin.kelas.siswa.remove', ['kelas' => $kelas->id, 'siswa' => 'SISWA_ID']) }}'.replace('SISWA_ID', siswa.id)" method="POST" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-delete-modal', { detail: { form: this } }));">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="group flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-red-600">
                                                            <svg class="mr-3 h-4 w-4 text-slate-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                            Keluarkan
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="allSiswa.filter(s => s.kelas_id === {{ $kelas->id }}).length === 0" class="p-8 text-center text-sm text-slate-500">
                                Belum ada siswa di kelas ini. <br>Silakan tambah dari Master Data atau Import Excel.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Master -->
                <div x-show="tab === 'master'" class="space-y-4">
                    <p class="text-sm text-slate-600">Pilih siswa dari data master dan pindahkan ke kelas ini.</p>
                    
                    <div class="flex gap-3">
                        <select x-model="filterKelas" class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Siswa (belum/sudah berkelas)</option>
                            <option value="null">Siswa Belum Memiliki Kelas</option>
                            @foreach(\App\Models\Kelas::orderBy('nama')->get() as $kls)
                                <option value="{{ $kls->id }}">Kelas {{ $kls->nama }}</option>
                            @endforeach
                        </select>
                        <input type="text" x-model="searchSiswa" placeholder="Cari nama/NIS..." class="w-1/3 px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    <form action="{{ route('admin.kelas.siswa.sync', $kelas) }}" method="POST" class="mt-4">
                        @csrf
                        
                        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">
                            <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center gap-3">
                                <input type="checkbox" x-model="selectAll" @change="
                                    let filtered = allSiswa.filter(s => 
                                        s.kelas_id !== {{ $kelas->id }} && 
                                        (filterKelas === '' || (filterKelas === 'null' && s.kelas_id === null) || s.kelas_id == filterKelas) &&
                                        s.user.name.toLowerCase().includes(searchSiswa.toLowerCase())
                                    );
                                    if(selectAll) {
                                        selectedSiswa = filtered.map(s => s.id);
                                    } else {
                                        selectedSiswa = [];
                                    }
                                " class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4 border-slate-300">
                                <span class="text-xs font-semibold text-slate-700 uppercase">Pilih Semua di Filter</span>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="siswa in allSiswa.filter(s => 
                                        s.kelas_id !== {{ $kelas->id }} && 
                                        (filterKelas === '' || (filterKelas === 'null' && s.kelas_id === null) || s.kelas_id == filterKelas) &&
                                        s.user.name.toLowerCase().includes(searchSiswa.toLowerCase())
                                    )" :key="siswa.id">
                                    <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 cursor-pointer">
                                        <input type="checkbox" name="siswa_ids[]" :value="siswa.id" x-model="selectedSiswa" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4 border-slate-300">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-slate-900" x-text="siswa.user.name"></p>
                                            <p class="text-xs text-slate-500">
                                                <span x-text="siswa.nis || 'No NIS'"></span>
                                                <span x-show="siswa.kelas" class="ml-1 text-blue-600" x-text="'- Kelas ' + (siswa.kelas ? siswa.kelas.nama : '')"></span>
                                            </p>
                                        </div>
                                    </label>
                                </template>
                                <div x-show="allSiswa.filter(s => s.kelas_id !== {{ $kelas->id }} && (filterKelas === '' || (filterKelas === 'null' && s.kelas_id === null) || s.kelas_id == filterKelas) && s.user.name.toLowerCase().includes(searchSiswa.toLowerCase())).length === 0" class="p-4 text-center text-sm text-slate-500">
                                    Tidak ada siswa yang cocok dengan filter.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50" :disabled="selectedSiswa.length === 0">
                            Tambahkan <span x-show="selectedSiswa.length > 0" x-text="selectedSiswa.length"></span> Siswa ke Kelas Ini
                        </button>
                    </form>
                </div>

                <!-- Tab: Excel -->
                <div x-show="tab === 'excel'" class="space-y-4" style="display: none;">
                    <p class="text-sm text-slate-600">Unggah file Excel (.xlsx / .csv) untuk membuat data murid baru dan otomatis memasukkan mereka ke kelas ini.</p>
                    
                    <div class="bg-blue-50 text-blue-700 p-4 rounded-xl text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-blue-950 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Ketentuan Kolom Excel:
                            </p>
                            <a href="{{ route('admin.kelas.siswa.template', $kelas) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Unduh Template
                            </a>
                        </div>
                        <ul class="list-disc pl-4 space-y-1">
                            <li>Harus ada kolom <strong>Nama Lengkap</strong> atau <strong>Nama</strong>.</li>
                            <li>Kolom <strong>Alamat Email</strong> opsional. Jika kosong, akan dibuatkan email otomatis.</li>
                            <li>Kolom <strong>NIS</strong> opsional namun direkomendasikan.</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.kelas.siswa.import', $kelas) }}" method="POST" enctype="multipart/form-data" class="space-y-4 border border-slate-200 p-4 rounded-xl">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">File Excel (.xlsx / .xls / .csv) <span class="text-red-500">*</span></label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors">
                            Mulai Import Data
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-layouts.admin>

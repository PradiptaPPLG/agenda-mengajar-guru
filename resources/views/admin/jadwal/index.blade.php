<x-layouts.admin>
    <x-slot:title>Jadwal Pelajaran</x-slot:title>
    <x-slot:actions>
        <div class="flex flex-wrap items-center gap-2" x-data="{ exportOpen: false, importOpen: false, salinOpen: false, isSubmitting: false, isSalinSubmitting: false }">
            {{-- Export Dropdown --}}
            <div class="relative">
                <button @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="exportOpen" style="display: none;" class="absolute right-0 mt-2 w-48 bg-white rounded-xl border border-slate-200 shadow-lg z-10 py-1">
                    <a href="{{ route('admin.jadwal.export.excel', request()->query()) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-emerald-600 font-medium">Export ke Excel</a>
                    <a href="{{ route('admin.jadwal.export.pdf', request()->query()) }}" target="_blank" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-red-600 font-medium">Export ke PDF</a>
                </div>
            </div>

            {{-- Bulk Delete Button --}}
            <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Hapus Terpilih (<span id="selected-count">0</span>)
            </button>

            {{-- Salin Jadwal Semester Button --}}
            <button @click="salinOpen = true; isSalinSubmitting = false;" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-semibold rounded-xl transition-colors shadow-xs border border-indigo-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                Salin Semester
            </button>

            {{-- Import Button --}}
            <button @click="importOpen = true; isSubmitting = false;" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-xl transition-colors shadow-xs border border-blue-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Excel
            </button>

            {{-- Tambah Jadwal --}}
            <a href="{{ route('admin.jadwal.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah
            </a>

            {{-- Salin Semester Modal --}}
            <div x-show="salinOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4 transition-all duration-300">
                <div @click.away="!isSalinSubmitting && (salinOpen = false)"
                     class="bg-white rounded-3xl w-full max-w-lg shadow-2xl shadow-slate-950/20 border border-slate-100 overflow-hidden relative">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold ring-4 ring-indigo-500/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-lg tracking-tight">Salin Jadwal ke Semester Lain</h3>
                                <p class="text-xs text-slate-500">Duplikasi jadwal pelajaran antar semester / tahun ajaran</p>
                            </div>
                        </div>
                        <button @click="salinOpen = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('admin.jadwal.salin-semester') }}" method="POST" class="p-6 space-y-4" @submit="isSalinSubmitting = true">
                        @csrf
                        {{-- Sumber --}}
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block">📌 Dari (Sumber Jadwal)</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tahun Ajaran Sumber</label>
                                    <select name="sumber_tahun_ajaran" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        @foreach($daftarTahunAjaran as $ta)
                                        <option value="{{ $ta }}" {{ $selectedTahunAjaran === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Semester Sumber</label>
                                    <select name="sumber_semester" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        @foreach($daftarSemester as $val => $label)
                                        <option value="{{ $val }}" {{ $selectedSemester === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Tujuan --}}
                        <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-200/80 space-y-3">
                            <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider block">🎯 Ke (Tujuan Jadwal Baru)</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran Tujuan</label>
                                    <input type="text" name="tujuan_tahun_ajaran" value="{{ $selectedTahunAjaran }}" required placeholder="Contoh: 2026/2027"
                                           class="w-full px-3 py-2 border border-indigo-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Semester Tujuan</label>
                                    <select name="tujuan_semester" required class="w-full px-3 py-2 border border-indigo-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        @foreach($daftarSemester as $val => $label)
                                        <option value="{{ $val }}" {{ ($selectedSemester === 'ganjil' ? 'genap' : 'ganjil') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="hapus_tujuan_dulu" value="1" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs text-slate-600 leading-snug">Hapus/timpa jadwal semester tujuan yang sudah ada sebelumnya (jika ada).</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="salinOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 active:scale-98 transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                <span>Mulai Salin Jadwal</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Import Modal --}}
            <div x-show="importOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4 transition-all duration-300">
                <div @click.away="!isSubmitting && (importOpen = false)" 
                     class="bg-white rounded-3xl w-full max-w-md shadow-2xl shadow-slate-950/20 border border-slate-100 overflow-hidden relative">

                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold ring-4 ring-blue-500/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-lg tracking-tight">Import Jadwal Excel</h3>
                                <p class="text-xs text-slate-500">Unggah berkas spreadsheet (.xlsx)</p>
                            </div>
                        </div>
                        <button x-show="!isSubmitting" @click="importOpen = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form x-show="!isSubmitting" action="{{ route('admin.jadwal.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4" @submit="isSubmitting = true"
                          x-data="{ clearFirst: false }">
                        @csrf
                        
                        <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200/80 rounded-2xl">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran Target</label>
                                <input type="text" name="tahun_ajaran" value="{{ $selectedTahunAjaran }}" required placeholder="Contoh: 2026/2027"
                                       class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Semester Target</label>
                                <select name="semester" required class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach($daftarSemester as $val => $label)
                                    <option value="{{ $val }}" {{ $selectedSemester === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="flex justify-between items-center text-sm font-semibold text-slate-700 mb-2">
                                <span>Pilih File Excel (.xlsx) <span class="text-red-500">*</span></span>
                                <a href="{{ route('admin.jadwal.template') }}" class="text-xs text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Unduh Template
                                </a>
                            </label>
                            <input type="file" name="file" accept=".xlsx,.xls" required 
                                   class="w-full text-sm text-slate-500 border border-slate-200 rounded-2xl p-1.5 cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                        </div>

                        {{-- Option: Hapus jadwal semester sebelum import --}}
                        <div>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <div class="relative mt-0.5 flex-shrink-0">
                                    <input type="checkbox" name="clear_before_import" value="1" x-model="clearFirst"
                                           class="peer w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer">
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">Hapus jadwal semester target sebelum import</span>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Hanya menghapus jadwal pada semester & tahun ajaran target di atas, tanpa mengganggu semester lain.</p>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="importOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors cursor-pointer">Batal</button>
                            <button type="submit"
                                    :class="clearFirst
                                        ? 'px-5 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white rounded-xl text-sm font-semibold shadow-lg shadow-red-600/25 active:scale-98 transition-all cursor-pointer flex items-center gap-2'
                                        : 'px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-sm font-semibold shadow-lg shadow-blue-600/25 active:scale-98 transition-all cursor-pointer flex items-center gap-2'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span x-text="clearFirst ? 'Hapus & Import Ulang' : 'Import Jadwal'"></span>
                            </button>
                        </div>
                    </form>

                    {{-- Animasi Loading Menarik & Jelas saat Import Sedang Berjalan --}}
                    <div x-show="isSubmitting" style="display: none;" class="p-8 text-center flex flex-col items-center justify-center">
                        <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full border-4 border-blue-100 border-t-blue-600 animate-spin"></div>
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 shadow-inner">
                                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                </svg>
                            </div>
                        </div>
                        <h4 class="text-base font-bold text-slate-900 mb-1">Sedang Mengimpor Jadwal...</h4>
                        <p class="text-xs text-slate-500 max-w-xs leading-relaxed mb-4">
                            Mohon tunggu sebentar, sistem sedang memproses jadwal. Jangan menutup halaman ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:actions>

    {{-- Filter Bar --}}
    <form method="GET" class="bg-white rounded-2xl border border-slate-200/80 p-4 mb-5 flex flex-wrap items-center gap-3 shadow-2xs" id="jadwal-filter-form">
        {{-- Filter Tahun Ajaran --}}
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Tahun Ajaran</label>
            <select name="tahun_ajaran" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    onchange="document.getElementById('jadwal-filter-form').submit()">
                <option value="all" {{ $selectedTahunAjaran === 'all' ? 'selected' : '' }}>Semua Tahun Ajaran</option>
                @foreach($daftarTahunAjaran as $ta)
                <option value="{{ $ta }}" {{ $selectedTahunAjaran === $ta ? 'selected' : '' }}>
                    {{ $ta }} {{ $ta === \App\Models\Setting::getTahunAjaranAktif() ? '⚡ [Aktif]' : '' }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Filter Semester --}}
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Semester</label>
            <select name="semester" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    onchange="document.getElementById('jadwal-filter-form').submit()">
                <option value="all" {{ $selectedSemester === 'all' ? 'selected' : '' }}>Semua Semester</option>
                @foreach($daftarSemester as $val => $label)
                <option value="{{ $val }}" {{ $selectedSemester === $val ? 'selected' : '' }}>
                    {{ $label }} {{ $val === \App\Models\Setting::getSemesterAktif() ? '⚡ [Aktif]' : '' }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Filter Kelas --}}
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kelas</label>
            <select name="kelas_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    onchange="document.getElementById('jadwal-filter-form').submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)
                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filter Guru --}}
        <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Guru Pengajar</label>
            <select name="guru_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    onchange="document.getElementById('jadwal-filter-form').submit()">
                <option value="">Semua Guru</option>
                @foreach($guruList as $g)
                <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        @if(request()->hasAny(['tahun_ajaran', 'semester', 'kelas_id', 'guru_id']))
        <div class="self-end pb-0.5">
            <a href="{{ route('admin.jadwal.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl border border-slate-200 transition-colors inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Reset Filter
            </a>
        </div>
        @endif
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <form action="{{ route('admin.jadwal.bulk-destroy') }}" method="POST" id="bulk-delete-form">
            @csrf
            <input type="hidden" name="delete_all" id="delete-all-input" value="0">
            <input type="hidden" name="tahun_ajaran" value="{{ $selectedTahunAjaran }}">
            <input type="hidden" name="semester" value="{{ $selectedSemester }}">
            <input type="hidden" name="kelas_id" value="{{ request('kelas_id') }}">
            <input type="hidden" name="guru_id" value="{{ request('guru_id') }}">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 font-bold">
                <tr>
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" id="select-all" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </th>
                    <th class="text-left px-4 py-3">Hari & Waktu</th>
                    <th class="text-left px-4 py-3">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3">Kelas</th>
                    <th class="text-left px-4 py-3 hidden lg:table-cell">Guru</th>
                    <th class="text-left px-4 py-3 hidden sm:table-cell">Periode</th>
                    <th class="text-right px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $hariNames = \App\Models\JadwalPelajaran::$namaHari; @endphp
                @forelse($jadwals as $j)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $j->id }}" class="row-checkbox rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-lg border border-blue-100">{{ $hariNames[$j->hari] ?? '-' }}</span>
                            <span class="text-slate-600 font-mono text-xs tabular-nums">
                                {{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        {{ $j->mataPelajaran->nama }}
                        @if($j->kelompok_blok && $j->kelompok_blok !== 'reguler')
                            <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-md {{ $j->kelompok_blok === 'kelompok_a' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $j->kelompok_blok === 'kelompok_a' ? 'Blok A' : 'Blok B' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-700 font-semibold">{{ $j->kelas?->nama ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600 hidden lg:table-cell">{{ $j->guru?->name ?? '-' }}</td>
                    <td class="px-4 py-3 hidden sm:table-cell">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[11px] font-bold text-slate-800">{{ $j->tahun_ajaran ?? '-' }}</span>
                            <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">{{ $j->semester ?? 'ganjil' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end">
                            <x-action-dropdown 
                                :editUrl="route('admin.jadwal.edit', $j)" 
                                :deleteUrl="route('admin.jadwal.destroy', $j)" 
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400 text-sm font-medium">Tidak ada data jadwal yang sesuai filter</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($jadwals->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $jadwals->links() }}</div>
        @endif
        </form>
    </div>
</x-layouts.admin>

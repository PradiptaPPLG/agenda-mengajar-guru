<x-layouts.admin>
    <x-slot:title>Custom Dynamic Report & Automated Excel/Pivot Builder</x-slot:title>

    @php
        $moduleColumnsJson = [];
        $defaultColumnsJson = [];
        foreach($modules as $mKey => $mVal) {
            $moduleColumnsJson[$mKey] = array_keys($mVal['columns']);
            $defaultColumnsJson[$mKey] = array_keys(array_filter($mVal['columns'], fn($c) => $c['default']));
        }
    @endphp

    <div class="space-y-6" x-data="{
        activeCategory: 'all',
        currentModule: 'rekap_kehadiran',
        includePivot: true,
        isSubmitting: false,
        selectedTingkatKehadiran: 'all',
        selectedKelasKehadiran: 'all',
        selectedTingkatSiswa: 'all',
        selectedKelasSiswa: 'all',
        dateStartKehadiran: '',
        dateEndKehadiran: '',
        allColumns: {{ json_encode($moduleColumnsJson) }},
        selectedColumns: {{ json_encode($defaultColumnsJson) }},

        selectAll() {
            this.selectedColumns[this.currentModule] = [...this.allColumns[this.currentModule]];
        },
        deselectAll() {
            this.selectedColumns[this.currentModule] = [];
        },
        toggleColumn(col) {
            const list = this.selectedColumns[this.currentModule] || [];
            const idx = list.indexOf(col);
            if (idx > -1) {
                list.splice(idx, 1);
            } else {
                list.push(col);
            }
            this.selectedColumns[this.currentModule] = list;
        },
        isColSelected(col) {
            return (this.selectedColumns[this.currentModule] || []).includes(col);
        },
        getSelectedCount() {
            return (this.selectedColumns[this.currentModule] || []).length;
        },
        getTotalCount() {
            return (this.allColumns[this.currentModule] || []).length;
        },
        toLocalDateString(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },
        setDatePreset(preset) {
            const today = new Date();
            if (preset === 'today') {
                const t = this.toLocalDateString(today);
                this.dateStartKehadiran = t;
                this.dateEndKehadiran   = t;
            } else if (preset === 'yesterday') {
                const y = new Date(today); y.setDate(y.getDate() - 1);
                const s = this.toLocalDateString(y);
                this.dateStartKehadiran = s;
                this.dateEndKehadiran   = s;
            } else if (preset === '7days') {
                const s = new Date(today); s.setDate(s.getDate() - 6);
                this.dateStartKehadiran = this.toLocalDateString(s);
                this.dateEndKehadiran   = this.toLocalDateString(today);
            } else if (preset === 'thismonth') {
                const s = new Date(today.getFullYear(), today.getMonth(), 1);
                this.dateStartKehadiran = this.toLocalDateString(s);
                this.dateEndKehadiran   = this.toLocalDateString(today);
            } else if (preset === 'reset') {
                this.dateStartKehadiran = '';
                this.dateEndKehadiran   = '';
            }
        }
    }">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    Custom Dynamic Report & Automated Excel/Pivot Builder
                </h1>
                <p class="text-xs text-slate-500 mt-1 max-w-3xl leading-relaxed">
                    Rancang dan ekspor laporan komprehensif data siswa maupun data guru. Pilih kolom dinamis, terapkan filter periode, dan peroleh file spreadsheet multi-sheet lengkap dengan <strong>Sheet Data Mentah</strong> serta <strong>Sheet Rekapitulasi Pivot Otomatis</strong>.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Fast Stream Engine (< 15ms)
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <span class="text-[11px] font-medium text-slate-500 block">Presensi Siswa</span>
                    <strong class="text-lg font-bold text-slate-900">{{ number_format($stats['rekap_kehadiran_count']) }}</strong>
                    <span class="text-[11px] text-slate-400 block">Record Agregasi</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 border border-purple-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <span class="text-[11px] font-medium text-slate-500 block">Master Siswa</span>
                    <strong class="text-lg font-bold text-slate-900">{{ number_format($stats['students_count']) }}</strong>
                    <span class="text-[11px] text-slate-400 block">Peserta Didik</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <span class="text-[11px] font-medium text-slate-500 block">Jurnal KBM Guru</span>
                    <strong class="text-lg font-bold text-slate-900">{{ number_format($stats['kehadiran_guru_count']) }}</strong>
                    <span class="text-[11px] text-slate-400 block">Sesi Presensi Mengajar</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 border border-sky-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <span class="text-[11px] font-medium text-slate-500 block">Master Guru</span>
                    <strong class="text-lg font-bold text-slate-900">{{ number_format($stats['gurus_count']) }}</strong>
                    <span class="text-[11px] text-slate-400 block">Dewan Guru Terdaftar</span>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <div class="flex items-center gap-2 font-bold mb-1">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Terjadi kendala saat memproses laporan:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Form Builder Container -->
        <form action="{{ route('super-admin.custom-report.export') }}" method="POST" @submit="isSubmitting = true; setTimeout(() => { isSubmitting = false; }, 3500);" class="space-y-6">
            @csrf

            <!-- Hidden inputs to submit selected columns for currentModule -->
            <input type="hidden" name="module" :value="currentModule">
            <template x-for="col in selectedColumns[currentModule]" :key="col">
                <input type="hidden" name="columns[]" :value="col">
            </template>
            <!-- Hidden date inputs for rekap_kehadiran date-mode (only submitted when active) -->
            <input type="hidden" name="start_date"
                   :disabled="currentModule !== 'rekap_kehadiran' || dateStartKehadiran === ''"
                   :value="dateStartKehadiran">
            <input type="hidden" name="end_date"
                   :disabled="currentModule !== 'rekap_kehadiran' || dateEndKehadiran === ''"
                   :value="dateEndKehadiran">

            <!-- STEP 1: Pilih Modul Utama -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center">1</span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Pilih Kategori & Modul Laporan Utama</h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">Dataset dipisahkan berdasarkan kategori (Siswa vs Guru) agar tidak bercampur dan mudah dipilih.</p>
                        </div>
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200/80 text-xs font-semibold overflow-x-auto">
                        <button type="button"
                                @click="activeCategory = 'all'"
                                class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                                :class="activeCategory === 'all' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            <span>Semua Modul</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeCategory === 'all' ? 'bg-slate-100 text-slate-700' : 'bg-slate-200 text-slate-600'">5</span>
                        </button>
                        <button type="button"
                                @click="activeCategory = 'siswa'; if (!['rekap_kehadiran', 'master_siswa'].includes(currentModule)) currentModule = 'rekap_kehadiran';"
                                class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                                :class="activeCategory === 'siswa' ? 'bg-white text-purple-700 font-bold shadow-xs' : 'text-slate-600 hover:text-purple-700'">
                            <span>🎓 Kategori Data Siswa</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeCategory === 'siswa' ? 'bg-purple-100 text-purple-700' : 'bg-slate-200 text-slate-600'">2</span>
                        </button>
                        <button type="button"
                                @click="activeCategory = 'guru'; if (!['kehadiran_guru', 'master_guru'].includes(currentModule)) currentModule = 'kehadiran_guru';"
                                class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                                :class="activeCategory === 'guru' ? 'bg-white text-amber-700 font-bold shadow-xs' : 'text-slate-600 hover:text-amber-700'">
                            <span>👨‍🏫 Kategori Data Guru</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeCategory === 'guru' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600'">2</span>
                        </button>
                        <button type="button"
                                @click="activeCategory = 'sistem'; currentModule = 'activity_log';"
                                class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
                                :class="activeCategory === 'sistem' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:text-blue-700'">
                            <span>⚙️ Log Sistem</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeCategory === 'sistem' ? 'bg-blue-100 text-blue-700' : 'bg-slate-200 text-slate-600'">1</span>
                        </button>
                    </div>
                </div>

                <!-- Categorized Modul Cards Container -->
                <div class="space-y-4">
                    <!-- KATEGORI 1: DATA SISWA -->
                    <div x-show="activeCategory === 'all' || activeCategory === 'siswa'" class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-purple-100 text-purple-800 text-[11px] font-bold flex items-center gap-1.5">
                                <span>🎓</span>
                                <span>Kategori: Data Siswa (Peserta Didik)</span>
                            </span>
                            <span class="text-[11px] text-slate-400">Presensi kehadiran & data pokok kesiswaan</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            @foreach(['rekap_kehadiran', 'master_siswa'] as $mKey)
                            @php $mod = $modules[$mKey]; @endphp
                            <div @click="currentModule = '{{ $mKey }}'"
                                 class="relative p-4 rounded-xl border-2 transition-all cursor-pointer select-none"
                                 :class="currentModule === '{{ $mKey }}' ? 'border-purple-600 bg-purple-50/40 ring-2 ring-purple-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $mod['badge'] }}">
                                        {{ $mod['key'] }}
                                    </span>
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                                         :class="currentModule === '{{ $mKey }}' ? 'border-purple-600 bg-purple-600 text-white' : 'border-slate-300 bg-white'">
                                        <svg x-show="currentModule === '{{ $mKey }}'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </div>
                                </div>
                                <h3 class="text-xs font-bold text-slate-900 mt-2.5">{{ $mod['label'] }}</h3>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ $mod['description'] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- KATEGORI 2: DATA GURU -->
                    <div x-show="activeCategory === 'all' || activeCategory === 'guru'" class="space-y-2.5">
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                            <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 text-[11px] font-bold flex items-center gap-1.5">
                                <span>👨‍🏫</span>
                                <span>Kategori: Data Guru & Tenaga Pengajar</span>
                            </span>
                            <span class="text-[11px] text-slate-400">Jurnal KBM, rekap presensi mengajar, penugasan mapel & wali kelas</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            @foreach(['kehadiran_guru', 'master_guru'] as $mKey)
                            @php $mod = $modules[$mKey]; @endphp
                            <div @click="currentModule = '{{ $mKey }}'"
                                 class="relative p-4 rounded-xl border-2 transition-all cursor-pointer select-none"
                                 :class="currentModule === '{{ $mKey }}' ? 'border-amber-600 bg-amber-50/40 ring-2 ring-amber-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $mod['badge'] }}">
                                        {{ $mod['key'] }}
                                    </span>
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                                         :class="currentModule === '{{ $mKey }}' ? 'border-amber-600 bg-amber-600 text-white' : 'border-slate-300 bg-white'">
                                        <svg x-show="currentModule === '{{ $mKey }}'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </div>
                                </div>
                                <h3 class="text-xs font-bold text-slate-900 mt-2.5">{{ $mod['label'] }}</h3>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ $mod['description'] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- KATEGORI 3: LOG SISTEM -->
                    <div x-show="activeCategory === 'all' || activeCategory === 'sistem'" class="space-y-2.5">
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                            <span class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-[11px] font-bold flex items-center gap-1.5">
                                <span>⚙️</span>
                                <span>Kategori: Sistem & Audit Trail</span>
                            </span>
                            <span class="text-[11px] text-slate-400">Rekam mutasi data dan jejak aktivitas akun</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            @php $mod = $modules['activity_log']; @endphp
                            <div @click="currentModule = 'activity_log'"
                                 class="relative p-4 rounded-xl border-2 transition-all cursor-pointer select-none"
                                 :class="currentModule === 'activity_log' ? 'border-blue-600 bg-blue-50/40 ring-2 ring-blue-500/20 shadow-xs' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $mod['badge'] }}">
                                        {{ $mod['key'] }}
                                    </span>
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                                         :class="currentModule === 'activity_log' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'">
                                        <svg x-show="currentModule === 'activity_log'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </div>
                                </div>
                                <h3 class="text-xs font-bold text-slate-900 mt-2.5">{{ $mod['label'] }}</h3>
                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ $mod['description'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Filter Periode & Parameter -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center">2</span>
                        <h2 class="text-sm font-bold text-slate-900">Tentukan Filter Data & Rentang Periode</h2>
                    </div>
                    <span class="text-xs text-slate-400">Parameter kontekstual sesuai modul terpilih</span>
                </div>

                <!-- Filters for rekap_kehadiran (Data Siswa) -->
                <div x-show="currentModule === 'rekap_kehadiran'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajaran" :disabled="currentModule !== 'rekap_kehadiran'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                            <option value="all">Semua Tahun Ajaran</option>
                            @foreach($tahunAjaranList as $ta)
                                <option value="{{ $ta }}" {{ $currentTahunAjaran === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Semester</label>
                        <select name="semester" :disabled="currentModule !== 'rekap_kehadiran'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                            <option value="all">Semua Semester (Ganjil & Genap)</option>
                            <option value="ganjil" {{ $currentSemester === 'ganjil' ? 'selected' : '' }}>Semester Ganjil (1)</option>
                            <option value="genap" {{ $currentSemester === 'genap' ? 'selected' : '' }}>Semester Genap (2)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tingkat / Angkatan</label>
                        <select name="tingkat"
                                x-model="selectedTingkatKehadiran"
                                @change="selectedKelasKehadiran = 'all'"
                                :disabled="currentModule !== 'rekap_kehadiran'"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                            <option value="all">Semua Tingkat (10, 11, 12)</option>
                            @foreach($tingkatList as $t)
                                <option value="{{ $t }}">Tingkat / Kelas {{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Filter Kelas Spesifik</label>
                        <select name="kelas_id"
                                x-model="selectedKelasKehadiran"
                                :disabled="currentModule !== 'rekap_kehadiran'"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                            <option value="all" x-text="selectedTingkatKehadiran === 'all' ? 'Semua Kelas (Seluruh Sekolah)' : 'Semua Kelas (Tingkat ' + selectedTingkatKehadiran + ')'"></option>
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls->id }}"
                                        :hidden="selectedTingkatKehadiran !== 'all' && String(selectedTingkatKehadiran) !== String('{{ $kls->tingkat }}')"
                                        :disabled="selectedTingkatKehadiran !== 'all' && String(selectedTingkatKehadiran) !== String('{{ $kls->tingkat }}')">
                                    {{ $kls->nama }} (Tingkat {{ $kls->tingkat }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Date / Per-Hari Filter Panel for rekap_kehadiran -->
                <div x-show="currentModule === 'rekap_kehadiran'" class="space-y-3" style="display:none;">
                    <div class="flex items-center gap-2 pt-1">
                        <div class="h-px flex-1 bg-gradient-to-r from-emerald-200 to-transparent"></div>
                        <span class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Filter Per Hari / Rentang Tanggal
                        </span>
                        <div class="h-px flex-1 bg-gradient-to-l from-emerald-200 to-transparent"></div>
                    </div>

                    <!-- Preset pills -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-semibold text-slate-500 shrink-0">Pilih Cepat:</span>
                        <button type="button" @click="setDatePreset('today')"
                                class="px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition-all"
                                :class="dateStartKehadiran && dateStartKehadiran === dateEndKehadiran && dateStartKehadiran === toLocalDateString(new Date())
                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                    : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:text-emerald-700'">
                            📅 Hari Ini
                        </button>
                        <button type="button" @click="setDatePreset('yesterday')"
                                class="px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition-all bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:text-emerald-700">
                            ◀ Kemarin
                        </button>
                        <button type="button" @click="setDatePreset('7days')"
                                class="px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition-all bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:text-emerald-700">
                            📊 7 Hari Terakhir
                        </button>
                        <button type="button" @click="setDatePreset('thismonth')"
                                class="px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition-all bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:text-emerald-700">
                            🗓 Bulan Ini
                        </button>
                        <button type="button" @click="setDatePreset('reset')"
                                x-show="dateStartKehadiran !== '' || dateEndKehadiran !== ''"
                                class="px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition-all bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100">
                            ✕ Reset Tanggal
                        </button>
                    </div>

                    <!-- Date range inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                Tanggal Mulai
                                <span class="ml-1 font-normal text-slate-400">(opsional — isi untuk filter perhari/range)</span>
                            </label>
                            <input type="date"
                                   x-model="dateStartKehadiran"
                                   class="w-full px-3 py-2 bg-slate-50 border rounded-xl text-xs focus:bg-white focus:outline-none transition-colors"
                                   :class="dateStartKehadiran ? 'border-emerald-400 ring-1 ring-emerald-200' : 'border-slate-200 focus:border-emerald-400'">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">
                                Tanggal Selesai
                                <span class="ml-1 font-normal text-slate-400">(kosongkan = sama dgn tanggal mulai)</span>
                            </label>
                            <input type="date"
                                   x-model="dateEndKehadiran"
                                   :min="dateStartKehadiran"
                                   class="w-full px-3 py-2 bg-slate-50 border rounded-xl text-xs focus:bg-white focus:outline-none transition-colors"
                                   :class="dateEndKehadiran ? 'border-emerald-400 ring-1 ring-emerald-200' : 'border-slate-200 focus:border-emerald-400'">
                        </div>
                    </div>

                    <!-- Active date filter info badge -->
                    <div x-show="dateStartKehadiran !== ''" class="flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-[11px]">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-emerald-800 font-medium">
                            Mode <strong>Per Tanggal</strong> aktif — data akan diambil langsung dari rekaman presensi harian (bukan rekap semester).
                            Rentang:
                            <strong x-text="dateStartKehadiran"></strong>
                            <span x-show="dateEndKehadiran && dateEndKehadiran !== dateStartKehadiran">
                                s/d <strong x-text="dateEndKehadiran"></strong>
                            </span>
                            <span x-show="!dateEndKehadiran || dateEndKehadiran === dateStartKehadiran"> (1 hari)</span>
                        </span>
                    </div>
                    <div x-show="dateStartKehadiran === ''" class="text-[11px] text-slate-400 italic">
                        💡 Biarkan kosong untuk ekspor berdasarkan <strong>Tahun Ajaran &amp; Semester</strong> di atas (mode cepat).
                    </div>
                </div>

                <!-- Filters for master_siswa (Data Siswa) -->
                <div x-show="currentModule === 'master_siswa'" style="display: none;" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-xs">

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tingkat / Angkatan</label>
                        <select name="tingkat"
                                x-model="selectedTingkatSiswa"
                                @change="selectedKelasSiswa = 'all'"
                                :disabled="currentModule !== 'master_siswa'"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-purple-500">
                            <option value="all">Semua Tingkat (10, 11, 12)</option>
                            @foreach($tingkatList as $t)
                                <option value="{{ $t }}">Tingkat / Kelas {{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Filter Kelas Spesifik</label>
                        <select name="kelas_id"
                                x-model="selectedKelasSiswa"
                                :disabled="currentModule !== 'master_siswa'"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-purple-500">
                            <option value="all" x-text="selectedTingkatSiswa === 'all' ? 'Semua Kelas (Seluruh Tingkat)' : 'Semua Kelas (Tingkat ' + selectedTingkatSiswa + ')'"></option>
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls->id }}"
                                        :hidden="selectedTingkatSiswa !== 'all' && String(selectedTingkatSiswa) !== String('{{ $kls->tingkat }}')"
                                        :disabled="selectedTingkatSiswa !== 'all' && String(selectedTingkatSiswa) !== String('{{ $kls->tingkat }}')">
                                    {{ $kls->nama }} (Tingkat {{ $kls->tingkat }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status Keaktifan Akun</label>
                        <select name="status_aktif" :disabled="currentModule !== 'master_siswa'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-purple-500">
                            <option value="all">Semua Status (Aktif & Nonaktif)</option>
                            <option value="1">Hanya Siswa Aktif</option>
                            <option value="0">Hanya Siswa Nonaktif</option>
                        </select>
                    </div>
                </div>

                <!-- Filters for kehadiran_guru (Data Guru) -->
                <div x-show="currentModule === 'kehadiran_guru'" style="display: none;" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai (KBM)</label>
                        <input type="date" name="start_date" :disabled="currentModule !== 'kehadiran_guru'" value="{{ request('start_date') }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai (KBM)</label>
                        <input type="date" name="end_date" :disabled="currentModule !== 'kehadiran_guru'" value="{{ request('end_date') }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Guru Pengajar</label>
                        <select name="guru_id" :disabled="currentModule !== 'kehadiran_guru'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                            <option value="all">Semua Guru</option>
                            @foreach($guruList as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status Presensi</label>
                        <select name="status_kehadiran" :disabled="currentModule !== 'kehadiran_guru'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                            <option value="all">Semua Status</option>
                            <option value="hadir">Hadir (Tepat Waktu)</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="tidak_hadir">Alpa / Tanpa Keterangan</option>
                            <option value="dispensasi">Dispensasi / Tugas Luar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Filter Kelas</label>
                        <select name="kelas_id" :disabled="currentModule !== 'kehadiran_guru'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                            <option value="all">Semua Kelas</option>
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls->id }}">{{ $kls->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filters for master_guru (Data Guru) -->
                <div x-show="currentModule === 'master_guru'" style="display: none;" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Mata Pelajaran Diampu</label>
                        <select name="mapel_id" :disabled="currentModule !== 'master_guru'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                            <option value="all">Semua Mata Pelajaran</option>
                            @foreach($mapelList as $mpl)
                                <option value="{{ $mpl->id }}">{{ $mpl->nama }} ({{ $mpl->kode }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Penugasan Struktural</label>
                        <select name="status_wali" :disabled="currentModule !== 'master_guru'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                            <option value="all">Semua Guru (Dengan/Tanpa Penugasan)</option>
                            <option value="ya">Hanya Wali Kelas</option>
                            <option value="tidak">Bukan Wali Kelas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status Keaktifan Akun</label>
                        <select name="status_aktif" :disabled="currentModule !== 'master_guru'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-amber-500">
                            <option value="all">Semua Status (Aktif & Nonaktif)</option>
                            <option value="1">Hanya Guru Aktif</option>
                            <option value="0">Hanya Guru Nonaktif</option>
                        </select>
                    </div>
                </div>

                <!-- Filters for activity_log (Log Sistem) -->
                <div x-show="currentModule === 'activity_log'" style="display: none;" class="grid grid-cols-1 sm:grid-cols-4 gap-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai (Start Date)</label>
                        <input type="date" name="start_date" :disabled="currentModule !== 'activity_log'" value="{{ request('start_date') }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai (End Date)</label>
                        <input type="date" name="end_date" :disabled="currentModule !== 'activity_log'" value="{{ request('end_date') }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori Aktivitas</label>
                        <select name="kategori" :disabled="currentModule !== 'activity_log'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                            <option value="">Semua Kategori</option>
                            <option value="keamanan">Keamanan & Akun</option>
                            <option value="akademik">Akademik & KBM</option>
                            <option value="kesiswaan">Kesiswaan & Presensi</option>
                            <option value="sistem">Pengaturan & Sistem</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role Pelaksana</label>
                        <select name="role" :disabled="currentModule !== 'activity_log'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500">
                            <option value="all">Semua Role</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="guru">Guru</option>
                            <option value="siswa">Siswa</option>
                            <option value="piket">Guru Piket</option>
                            <option value="tu">Tata Usaha</option>
                            <option value="kepala_sekolah">Kepala Sekolah</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Pilih Kolom Data (Dynamic Column Selection) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center">3</span>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-sm font-bold text-slate-900">Pilih Kolom Data yang Ingin Disertakan</h2>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                      :class="currentModule.includes('guru') ? 'bg-amber-100 text-amber-800 border border-amber-200' : (currentModule.includes('siswa') || currentModule === 'rekap_kehadiran' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200')"
                                      x-text="currentModule === 'rekap_kehadiran' ? '🎓 Rekap Kehadiran Siswa' : (currentModule === 'master_siswa' ? '🎓 Master Siswa' : (currentModule === 'kehadiran_guru' ? '👨‍🏫 Jurnal KBM Guru' : (currentModule === 'master_guru' ? '👨‍🏫 Master Guru' : '⚙️ Log Sistem')))"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Centang bidang data yang akan menjadi header tabel pada Sheet Data Mentah.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">
                            <span x-text="getSelectedCount()"></span> / <span x-text="getTotalCount()"></span> Kolom Terpilih
                        </span>
                        <button type="button" @click="selectAll()" class="px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                            Pilih Semua
                        </button>
                        <button type="button" @click="deselectAll()" class="px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                            Hapus Semua
                        </button>
                    </div>
                </div>

                <!-- Column Checkboxes per Module -->
                @foreach($modules as $mKey => $mod)
                <div x-show="currentModule === '{{ $mKey }}'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    @foreach($mod['columns'] as $colKey => $colDef)
                    <div @click="toggleColumn('{{ $colKey }}')"
                         class="p-3 rounded-xl border transition-all cursor-pointer select-none flex items-center gap-3"
                         :class="isColSelected('{{ $colKey }}') ? 'bg-blue-50/60 border-blue-400 ring-1 ring-blue-400/30' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100/60'">
                        <input type="checkbox"
                               :checked="isColSelected('{{ $colKey }}')"
                               @click.stop="toggleColumn('{{ $colKey }}')"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                        <div class="min-w-0">
                            <span class="text-xs font-bold block" :class="isColSelected('{{ $colKey }}') ? 'text-blue-900' : 'text-slate-800'">
                                {{ $colDef['label'] }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $colKey }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            <!-- STEP 4: Opsi Tambahan & Submit Action -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center">4</span>
                        <h2 class="text-sm font-bold text-slate-900">Opsi Ekspor & Unduh File</h2>
                    </div>
                </div>

                <!-- Checkbox Pivot Table Option -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-3.5">
                    <input type="checkbox" name="include_pivot" id="include_pivot" value="1"
                           x-model="includePivot"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-5 h-5 mt-0.5 cursor-pointer">
                    <label for="include_pivot" class="text-xs cursor-pointer select-none">
                        <strong class="font-bold text-slate-900 flex items-center gap-2">
                            <span>Sertakan Sheet Rekapitulasi / Pivot Otomatis (Tab ke-2 dalam file Excel)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700 font-bold">Rekomendasi</span>
                        </strong>
                        <p class="text-slate-500 mt-0.5 leading-relaxed">
                            File Excel yang diunduh akan otomatis memiliki Tab kedua (Sheet "Tabel Rekap - Pivot") yang memuat data agregasi ringkas (rekap per kelas, rata-rata kehadiran %, serta total akumulasi seluruh sekolah) sehingga siap ditinjau pimpinan tanpa perlu membuat tabel pivot manual di Microsoft Excel.
                        </p>
                    </label>
                </div>

                <!-- Submit Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                    <div class="text-xs text-slate-500 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Proteksi Memory Guard & Stream Chunking aktif. File sementara di server akan otomatis dihapus usai pengunduhan.</span>
                    </div>

                    <button type="submit"
                            :disabled="getSelectedCount() === 0 || isSubmitting"
                            class="px-6 py-2.5 rounded-xl font-bold text-xs text-white transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
                            :class="getSelectedCount() > 0 && !isSubmitting ? 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/20' : 'bg-slate-300 cursor-not-allowed'">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Generate & Download Excel (.xlsx)</span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Sedang Menyusun Spreadsheet...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>

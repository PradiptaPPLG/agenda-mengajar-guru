<x-layouts.admin title="Tahun Ajaran & Cut-Off Semester">
    <div class="space-y-6">
        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-sky-600 uppercase tracking-wider mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Siklus Akademik & Tata Kelola Data</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tahun Ajaran & Cut-Off Semester</h1>
                <p class="text-sm text-slate-500 mt-0.5">Kelola periode aktif dan kunci perubahan data historis (agenda & absensi) agar integritas rekapitulasi terjaga.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('modalAddPeriod').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white text-sm font-semibold rounded-xl shadow-sm shadow-sky-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Periode Baru</span>
                </button>
            </div>
        </div>

        <!-- Alert Notification -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-xs font-semibold">Tutup</button>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Tutup</button>
            </div>
        @endif

        <!-- Banner Info Cut-Off -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="p-2.5 bg-amber-500 text-white rounded-xl shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-950">Mekanisme Cut-Off Data & Kunci Semester</h3>
                    <p class="text-xs text-amber-800/90 mt-0.5 leading-relaxed">
                        Saat semester ditandai <strong>Terkunci (Cut-Off)</strong>, seluruh Guru dan Admin biasa hanya memiliki hak akses <em>Read-Only</em> (tidak dapat menambah, mengubah, atau menghapus agenda dan presensi pada periode tersebut). Hanya <strong>Super Admin</strong> yang memiliki bypass otorisasi perbaikan data.
                    </p>
                </div>
            </div>
            @if ($activeYear)
                <div class="shrink-0 bg-white/90 backdrop-blur-xs px-4 py-2.5 rounded-xl border border-amber-200 text-xs shadow-2xs">
                    <span class="text-slate-500 block">Periode Aktif Sekarang:</span>
                    <span class="font-bold text-slate-800 text-sm flex items-center gap-1.5 mt-0.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        {{ $activeYear->full_period_label }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-900">Daftar Siklus Periode Akademik</h3>
                <span class="text-xs text-slate-500 font-medium">{{ $academicYears->count() }} Periode Terdaftar</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4">Tahun Ajaran</th>
                            <th class="py-3 px-4">Semester</th>
                            <th class="py-3 px-4">Keterangan</th>
                            <th class="py-3 px-4 text-center">Status Berjalan</th>
                            <th class="py-3 px-4 text-center">Integritas Data (Cut-Off)</th>
                            <th class="py-3 px-4 text-right">Aksi Super Admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($academicYears as $year)
                            <tr class="hover:bg-slate-50/75 transition {{ $year->is_active ? 'bg-sky-50/30' : '' }}">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ substr($year->tahun_ajaran, 2, 2) }}
                                        </div>
                                        <span>{{ $year->tahun_ajaran }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $year->semester == 1 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/60' : 'bg-purple-50 text-purple-700 border border-purple-200/60' }}">
                                        {{ $year->semester_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500 max-w-xs truncate">
                                    {{ $year->keterangan ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if ($year->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-ping"></span>
                                            AKTIF
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400 font-medium">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if ($year->is_locked)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            TERKUNCI (Read-Only)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                            </svg>
                                            Dapat Diedit
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Toggle Lock Button -->
                                        <form method="POST" action="{{ route('super-admin.academic-years.toggle-lock', $year) }}" onsubmit="return confirm('Ubah status proteksi data untuk periode {{ $year->full_period_label }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-1.5 text-xs font-semibold rounded-lg border transition {{ $year->is_locked ? 'bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100' : 'bg-slate-50 text-slate-700 border-slate-300 hover:bg-slate-100' }}" title="{{ $year->is_locked ? 'Buka Kunci (Izinkan Edit)' : 'Kunci Cut-Off (Jadikan Read-Only)' }}">
                                                @if ($year->is_locked)
                                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>

                                        <!-- Activate Button -->
                                        @if (! $year->is_active)
                                            <form method="POST" action="{{ route('super-admin.academic-years.activate', $year) }}" onsubmit="return confirm('Jadikan {{ $year->full_period_label }} sebagai periode berjalan aktif?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 transition" title="Jadikan Periode Aktif">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Delete Button -->
                                        @if (! $year->is_active)
                                            <form method="POST" action="{{ route('super-admin.academic-years.destroy', $year) }}" onsubmit="return confirm('Hapus periode akademik {{ $year->full_period_label }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-xs text-rose-600 hover:text-rose-800 rounded-lg border border-rose-200 hover:bg-rose-50 transition" title="Hapus Periode">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 text-sm">
                                    Belum ada data periode akademik. Silakan tambah periode baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Periode Baru -->
    <div id="modalAddPeriod" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tambah Periode Akademik</h3>
                    <p class="text-xs text-slate-500">Buat siklus tahun ajaran dan semester baru</p>
                </div>
                <button type="button" onclick="document.getElementById('modalAddPeriod').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('super-admin.academic-years.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Ajaran <span class="text-rose-500">*</span></label>
                    <input type="text" name="tahun_ajaran" placeholder="Contoh: 2025/2026" required pattern="\d{4}/\d{4}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                    <span class="text-[11px] text-slate-400 mt-1 block">Format: YYYY/YYYY (misal: 2025/2026)</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Semester <span class="text-rose-500">*</span></label>
                    <select name="semester" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                        <option value="1">Semester Ganjil (1)</option>
                        <option value="2">Semester Genap (2)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan / Catatan</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Tahun Ajaran Baru Kurikulum Merdeka" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-slate-300">
                        <span class="text-xs font-semibold text-slate-700">Langsung jadikan sebagai periode aktif sekarang</span>
                    </label>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalAddPeriod').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-xs transition">
                        Simpan Periode
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>

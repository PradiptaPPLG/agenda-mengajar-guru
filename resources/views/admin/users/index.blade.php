<x-layouts.admin>
    <x-slot:title>Manajemen Guru</x-slot:title>
    
    @push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush

    <div x-data="{ showImport: false }">
        <!-- Header & Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <form action="{{ route('admin.users.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto" id="users-filter-form">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama guru..."
                           id="users-search-input"
                           class="w-full sm:w-64 px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
                           oninput="debouncedFilterSubmit('users-filter-form', 'users-search-input')">
                    @if(request()->has('search'))
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
            
            <div class="flex gap-3">
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Manual
                </a>
                <button @click="showImport = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Import Excel
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-3.5 font-bold text-slate-700">Nama Guru</th>
                        <th class="text-left px-5 py-3.5 font-bold text-slate-700">Role & Akses</th>
                        <th class="text-left px-5 py-3.5 font-bold text-slate-700">Mata Pelajaran & Kelas</th>
                        <th class="text-right px-5 py-3.5 font-bold text-slate-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    @php
                        $mapelList = $user->mapels->pluck('nama')
                            ->merge($user->jadwalPelajarans->pluck('mataPelajaran.nama'))
                            ->filter()
                            ->unique()
                            ->values();

                        $kelasMengajarList = $user->jadwalPelajarans->pluck('kelas.nama')
                            ->filter()
                            ->unique()
                            ->values();

                        $kaprogJurusan = $user->guruProfile?->kaprog_jurusan;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        {{-- Nama & NIP --}}
                        <td class="px-5 py-4 align-middle">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shrink-0 font-bold text-sm shadow-xs">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 text-sm block leading-tight">{{ $user->name }}</span>
                                    @if($user->guruProfile?->nip)
                                        <span class="text-xs font-medium text-slate-500 block mt-1">NIP. {{ $user->guruProfile->nip }}</span>
                                    @else
                                        <span class="text-xs text-slate-400 italic block mt-1">Tanpa NIP</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Role & Akses --}}
                        <td class="px-5 py-4 align-middle">
                            <div class="flex flex-col gap-1.5 items-start justify-center">
                                <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200/60 shadow-2xs">
                                    {{ Str::title(str_replace('_', ' ', $user->role)) }}
                                </span>
                                @if($user->roles->count() > 0)
                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                        @foreach($user->roles as $r)
                                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md {{ str_contains(strtolower($r->name), 'kaprog') ? 'bg-purple-100 text-purple-800 border border-purple-200' : (str_contains(strtolower($r->name), 'bk') ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : (str_contains(strtolower($r->name), 'wali') ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200')) }}">
                                                {{ $r->name }}
                                                @if(str_contains(strtolower($r->name), 'kaprog') && $kaprogJurusan)
                                                    ({{ $kaprogJurusan }})
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Mapel & Kelas --}}
                        <td class="px-5 py-4 align-middle">
                            <div class="flex flex-col gap-3.5">
                                {{-- Mapel --}}
                                <div class="flex items-center gap-2.5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider w-14 shrink-0">Mapel</span>
                                    <div class="flex flex-wrap gap-1.5 items-center">
                                        @if($mapelList->count() > 0)
                                            @foreach($mapelList->take(3) as $m)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/80 shadow-2xs">
                                                    {{ $m }}
                                                </span>
                                            @endforeach
                                            @if($mapelList->count() > 3)
                                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200" title="{{ $mapelList->slice(3)->join(', ') }}">
                                                    +{{ $mapelList->count() - 3 }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400 italic">Belum diset</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Mengajar Kelas --}}
                                <div class="flex items-center gap-2.5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider w-14 shrink-0">Kelas</span>
                                    <div class="flex flex-wrap gap-1.5 items-center">
                                        @if($kelasMengajarList->count() > 0)
                                            @foreach($kelasMengajarList->take(4) as $k)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs">
                                                    {{ $k }}
                                                </span>
                                            @endforeach
                                            @if($kelasMengajarList->count() > 4)
                                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200" title="{{ $kelasMengajarList->slice(4)->join(', ') }}">
                                                    +{{ $kelasMengajarList->count() - 4 }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400 italic">Belum ada jadwal</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-5 py-4 align-middle">
                            <div class="flex items-center justify-end">
                                <x-action-dropdown 
                                    :editUrl="route('admin.users.edit', $user)" 
                                    :deleteUrl="$user->id !== auth()->id() ? route('admin.users.destroy', $user) : null" 
                                />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-slate-400 text-sm">Tidak ada data guru ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
            @endif
        </div>

        <!-- Import Modal -->
        <div x-show="showImport" style="display: none;" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4 transition-all duration-300">
            <div @click.outside="showImport = false" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                 class="bg-white rounded-3xl w-full max-w-lg shadow-2xl shadow-slate-950/20 border border-slate-100 overflow-hidden relative">

                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold ring-4 ring-emerald-500/10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Import Data Guru</h3>
                            <p class="text-xs text-slate-500">Unggah berkas spreadsheet (.xlsx / .csv)</p>
                        </div>
                    </div>
                    <button @click="showImport = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50/50 text-blue-900 p-4 rounded-2xl text-sm mb-6 border border-blue-100 space-y-2">
                        <p class="font-semibold text-blue-950 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Ketentuan Kolom Excel:
                        </p>
                        <ul class="list-disc pl-5 text-xs text-blue-800/90 space-y-1.5">
                            <li>Harus ada kolom <strong>Nama</strong> atau <strong>Nama Lengkap</strong>.</li>
                            <li>Kolom <strong>NIP</strong> opsional namun direkomendasikan.</li>
                            <li>Kolom <strong>Email</strong> opsional. Jika kosong, akan dibuatkan email otomatis.</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">File Excel / CSV <span class="text-red-500">*</span></label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                                   class="w-full text-sm text-slate-500 border border-slate-200 rounded-2xl p-1.5 cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all">
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="showImport = false" class="px-4 py-2.5 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-colors cursor-pointer">Batal</button>
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-600/25 active:scale-98 transition-all cursor-pointer">Mulai Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

<x-layouts.admin>
    <x-slot:title>Manajemen Siswa</x-slot:title>

    @push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush

    <div x-data="{ showImport: false }">
        <!-- Header & Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <form action="{{ route('admin.siswa.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto" id="siswa-filter-form">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIS..."
                           id="siswa-search-input"
                           class="w-full sm:w-64 px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
                           oninput="debouncedFilterSubmit('siswa-filter-form', 'siswa-search-input')">
                    <select name="kelas_id" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white"
                            onchange="document.getElementById('siswa-filter-form').submit()">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $kelas)
                            <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->nama }}
                            </option>
                        @endforeach
                    </select>
                    @if(request()->hasAny(['search', 'kelas_id']))
                        <a href="{{ route('admin.siswa.index') }}" class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    Hapus Terpilih (<span id="selected-count">0</span>)
                </button>
                <button @click="showImport = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Import Excel
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <form action="{{ route('admin.siswa.bulk-destroy') }}" method="POST" id="bulk-delete-form">
                @csrf
                <input type="hidden" name="delete_all" id="delete-all-input" value="0">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="kelas_id" value="{{ request('kelas_id') }}">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-6 py-4 w-10">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-4 font-semibold">Nama Siswa</th>
                            <th class="px-6 py-4 font-semibold">NIS</th>
                            <th class="px-6 py-4 font-semibold">Kelas Saat Ini</th>
                            <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($siswa as $s)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $s->user->id }}" class="row-checkbox rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900 flex items-center gap-2">
                                    {{ $s->user->name }}
                                    @if($s->kelas && $s->kelas->blok_awal === 'split' && $s->kelompok_blok)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $s->kelompok_blok === 'kelompok_a' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $s->kelompok_blok === 'kelompok_a' ? 'Kel. A' : 'Kel. B' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $s->nis ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if($s->kelas)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $s->kelas->nama }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                        Belum berkelas
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="{{ route('admin.siswa.toggle-active', $s->user) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition-colors {{ $s->user->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100' }}">
                                            @if($s->user->is_active)
                                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Aktif
                                            @else
                                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                Nonaktif
                                            @endif
                                        </button>
                                    </form>
                                    <x-action-dropdown 
                                        :editUrl="route('admin.users.edit', $s->user)" 
                                        :deleteUrl="route('admin.users.destroy', $s->user)" 
                                    />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <p class="text-base font-medium text-slate-900 mb-1">Tidak ada data siswa</p>
                                <p class="text-sm">Silakan gunakan fitur Import Excel untuk menambahkan data massal.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </form>
            @if($siswa->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $siswa->links() }}
            </div>
            @endif
        </div>

        <!-- Import Modal -->
        <div x-show="showImport" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4 transition-all duration-300">
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
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Import Data Siswa</h3>
                            <p class="text-xs text-slate-500">Unggah berkas spreadsheet (.xlsx / .csv)</p>
                        </div>
                    </div>
                    <button @click="showImport = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50/50 text-blue-900 p-4 rounded-2xl text-sm mb-6 border border-blue-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-blue-950 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Ketentuan Kolom Excel:
                            </p>
                            <a href="{{ route('admin.siswa.template') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Unduh Template
                            </a>
                        </div>
                        <ul class="list-disc pl-5 text-xs text-blue-800/90 space-y-1.5">
                            <li>Harus ada kolom <strong>Nama</strong> atau <strong>Nama Lengkap</strong>.</li>
                            <li>Kolom <strong>Kelas</strong> opsional (misal: "12 AK-1").</li>
                            <li>Kolom <strong>Email</strong> opsional (otomatis jika kosong).</li>
                            <li>Kolom <strong>NIS / NISN</strong> opsional namun direkomendasikan.</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.siswa.import') }}" method="POST" enctype="multipart/form-data">
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
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const btnBulkDelete = document.getElementById('btn-bulk-delete');
            const selectedCount = document.getElementById('selected-count');
            const deleteAllInput = document.getElementById('delete-all-input');
            const totalDataCount = {{ $siswa->total() }};

            function updateBulkDeleteButton() {
                const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                
                if (deleteAllInput.value === '1') {
                    selectedCount.textContent = `Semua ${totalDataCount}`;
                } else {
                    selectedCount.textContent = checkedCount;
                }
                
                if (checkedCount > 0) {
                    btnBulkDelete.classList.remove('hidden');
                } else {
                    btnBulkDelete.classList.add('hidden');
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    rowCheckboxes.forEach(cb => cb.checked = isChecked);
                    
                    if (isChecked && totalDataCount > rowCheckboxes.length) {
                        deleteAllInput.value = '1';
                    } else {
                        deleteAllInput.value = '0';
                    }
                    
                    updateBulkDeleteButton();
                });
            }

            rowCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const allChecked = document.querySelectorAll('.row-checkbox:checked').length === rowCheckboxes.length;
                    selectAll.checked = allChecked && rowCheckboxes.length > 0;
                    
                    if (!this.checked) {
                        deleteAllInput.value = '0';
                    } else if (selectAll.checked && totalDataCount > rowCheckboxes.length) {
                        deleteAllInput.value = '1';
                    }
                    
                    updateBulkDeleteButton();
                });
            });

            const bulkDeleteForm = document.getElementById('bulk-delete-form');
            if (bulkDeleteForm) {
                bulkDeleteForm.addEventListener('submit', function(e) {
                    const msg = deleteAllInput.value === '1' 
                        ? `PERHATIAN: Anda akan menghapus SELURUH ${totalDataCount} siswa (termasuk di halaman lain). Yakin ingin melanjutkan?` 
                        : 'Yakin ingin menghapus siswa yang Anda centang?';
                        
                    if (!confirm(msg)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.admin>

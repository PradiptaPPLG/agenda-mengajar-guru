<x-layouts.admin>
    <x-slot:title>Jadwal Pelajaran</x-slot:title>
    <x-slot:actions>
        <div class="flex items-center gap-2" x-data="{ exportOpen: false, importOpen: false }">
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

            {{-- Import Button --}}
            <button @click="importOpen = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-xl transition-colors shadow-xs border border-blue-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Excel
            </button>

            {{-- Tambah Jadwal --}}
            <a href="{{ route('admin.jadwal.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah
            </a>

            {{-- Import Modal --}}
            <div x-show="importOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-md p-4 transition-all duration-300">
                <div @click.away="importOpen = false" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-90 translate-y-4"
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
                        <button @click="importOpen = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form action="{{ route('admin.jadwal.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
                        @csrf
                        <div class="mb-5">
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
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="importOpen = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-sm font-semibold shadow-lg shadow-blue-600/25 active:scale-98 transition-all cursor-pointer">Import Jadwal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </x-slot:actions>

    <form method="GET" class="flex flex-wrap gap-3 mb-5" id="jadwal-filter-form">
        <select name="kelas_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="document.getElementById('jadwal-filter-form').submit()">
            <option value="">Semua Kelas</option>
            @foreach($kelasList as $k)
            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
            @endforeach
        </select>
        <select name="guru_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="document.getElementById('jadwal-filter-form').submit()">
            <option value="">Semua Guru</option>
            @foreach($guruList as $g)
            <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <form action="{{ route('admin.jadwal.bulk-destroy') }}" method="POST" id="bulk-delete-form">
            @csrf
            <input type="hidden" name="delete_all" id="delete-all-input" value="0">
            <input type="hidden" name="kelas_id" value="{{ request('kelas_id') }}">
            <input type="hidden" name="guru_id" value="{{ request('guru_id') }}">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" id="select-all" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Hari</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Waktu</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Kelompok</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Guru</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $hariNames = \App\Models\JadwalPelajaran::$namaHari; @endphp
                @forelse($jadwals as $j)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $j->id }}" class="row-checkbox rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">{{ $hariNames[$j->hari] ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                        {{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $j->mataPelajaran->nama }}</td>
                    <td class="px-4 py-3 hidden sm:table-cell">
                        @if($j->kelompok_blok === 'kelompok_a')
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                                📘 Kel. A
                            </span>
                        @elseif($j->kelompok_blok === 'kelompok_b')
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">
                                🔧 Kel. B
                            </span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $j->kelas?->nama ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $j->guru?->name ?? '-' }}</td>
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
                <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada jadwal</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($jadwals->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $jadwals->links() }}</div>
        @endif
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const btnBulkDelete = document.getElementById('btn-bulk-delete');
            const selectedCount = document.getElementById('selected-count');
            const deleteAllInput = document.getElementById('delete-all-input');
            const totalDataCount = {{ $jadwals->total() }};

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
                        ? `PERHATIAN: Anda akan menghapus SELURUH ${totalDataCount} jadwal pelajaran (termasuk di halaman lain). Yakin ingin melanjutkan?` 
                        : 'Yakin ingin menghapus jadwal yang Anda centang?';
                        
                    if (!confirm(msg)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.admin>

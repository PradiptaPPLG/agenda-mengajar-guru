<x-layouts.admin>
    <x-slot:title>Mata Pelajaran</x-slot:title>
    <x-slot:actions>
        <div class="flex items-center gap-2">
            <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Hapus Terpilih (<span id="selected-count">0</span>)
            </button>
            <a href="{{ route('admin.mata-pelajaran.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah
            </a>
        </div>
    </x-slot:actions>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form action="{{ route('admin.mata-pelajaran.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto" id="mapel-filter-form">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari pelajaran..."
                           id="mapel-search-input"
                           class="w-full sm:w-64 pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           oninput="debouncedFilterSubmit('mapel-filter-form', 'mapel-search-input')">
                </div>
                <select name="jenis" onchange="this.form.submit()" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm font-semibold bg-white focus:ring-2 focus:ring-blue-500 text-slate-700">
                    <option value="">Semua Kategori (Umum & Kejuruan)</option>
                    <option value="umum" {{ request('jenis') === 'umum' ? 'selected' : '' }}>Kelompok Umum (Normatif & Adaptif)</option>
                    <option value="produktif" {{ request('jenis') === 'produktif' ? 'selected' : '' }}>Kelompok Kejuruan (Produktif)</option>
                </select>
                @if(request()->filled('search') || request()->filled('jenis'))
                    <a href="{{ route('admin.mata-pelajaran.index') }}" class="px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl text-sm font-semibold transition-colors flex items-center">Reset</a>
                @endif
            </form>
        </div>

        <form action="{{ route('admin.mata-pelajaran.bulk-destroy') }}" method="POST" id="bulk-delete-form">
            @csrf
            <input type="hidden" name="delete_all" id="delete-all-input" value="0">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="jenis" value="{{ request('jenis') }}">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" id="select-all" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Kode</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Kategori</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($mataPelajarans as $mp)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $mp->id }}" class="row-checkbox rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-900">{{ $mp->nama }}</p>
                        @if(in_array($mp->jenis, ['produktif', 'adaptif']) && $mp->kelas->count() > 0)
                            <p class="text-xs text-slate-500 mt-0.5">Spesifik {{ $mp->kelas->count() }} kelas</p>
                        @endif
                    </td>
                    <td class="px-4 py-3"><span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $mp->kode }}</span></td>
                    <td class="px-4 py-3">
                        @if(in_array($mp->jenis, ['produktif', 'adaptif', 'kejuruan']))
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                Kejuruan (Produktif)
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                Umum (Normatif & Adaptif)
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end">
                            <x-action-dropdown 
                                :editUrl="route('admin.mata-pelajaran.edit', $mp)" 
                                :deleteUrl="route('admin.mata-pelajaran.destroy', $mp)" 
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada mata pelajaran</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($mataPelajarans->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $mataPelajarans->links() }}</div>
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
            const totalDataCount = {{ $mataPelajarans->total() }};

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
                        ? `PERHATIAN: Anda akan menghapus SELURUH ${totalDataCount} mata pelajaran (termasuk di halaman lain). Jadwal yang terkait dengan mapel ini juga akan terhapus. Yakin?` 
                        : 'Yakin ingin menghapus mata pelajaran yang Anda centang?';
                        
                    if (!confirm(msg)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.admin>

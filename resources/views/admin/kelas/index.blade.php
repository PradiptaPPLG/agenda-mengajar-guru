<x-layouts.admin>
    <x-slot:title>Manajemen Kelas</x-slot:title>
    <x-slot:actions>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.kalender-blok.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-xl border border-blue-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Kalender Blok
            </a>
            <button type="submit" form="bulk-delete-form" id="btn-bulk-delete" class="hidden inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Hapus Terpilih (<span id="selected-count">0</span>)
            </button>
            <a href="{{ route('admin.kelas.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Kelas
            </a>
        </div>
    </x-slot:actions>

    @if(!$hasKalender && $kelas->contains('is_sistem_blok', true))
        <div class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <h3 class="text-sm font-bold text-amber-800">Kalender Blok Belum Tersedia</h3>
                <p class="text-xs text-amber-700 mt-1">Beberapa kelas telah disetel untuk Sistem Blok, namun saat ini tidak ada Kalender Blok yang terdaftar. Fitur rotasi guru/jadwal pada kelas-kelas ini tidak akan berjalan hingga Anda membuat kalender baru.</p>
                <a href="{{ route('admin.kalender-blok.index') }}" class="inline-block mt-2 text-xs font-semibold text-amber-700 hover:text-amber-900 underline">Kelola Kalender Blok &rarr;</a>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <form action="{{ route('admin.kelas.bulk-destroy') }}" method="POST" id="bulk-delete-form">
            @csrf
            <input type="hidden" name="delete_all" id="delete-all-input" value="0">
            <input type="hidden" name="search" value="{{ request('search') }}">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" id="select-all" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Tingkat</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Sistem Blok</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Wali Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Guru BK</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Siswa</th>
                    <td class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</td>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($kelas as $k)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $k->id }}" class="row-checkbox rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $k->nama }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $k->tingkat }}</td>
                    <td class="px-4 py-3">
                        @if($k->is_sistem_blok)
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($k->blok_awal === 'kelompok_b')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            Blok B (Kejuruan)
                                        </span>
                                    @elseif($k->blok_awal === 'kelompok_a')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                            Blok A (Umum)
                                        </span>
                                    @elseif($k->blok_awal === 'split')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold bg-purple-100 text-purple-800 px-2.5 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                                            Split (A & B Paralel)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-full">
                                            Blok Aktif
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium">
                                    {{ $k->model_rotasi === 'split_harian' ? 'Split Harian' : 'Rotasi Mingguan' }}
                                </span>
                            </div>
                        @else
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center text-xs font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                                    Reguler (Non-Blok)
                                </span>
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $k->waliKelas?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $k->guruBk?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">{{ $k->siswa_profiles_count }} siswa</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end">
                            <x-action-dropdown 
                                :detailUrl="route('admin.kelas.show', $k)"
                                :editUrl="route('admin.kelas.edit', $k)" 
                                :deleteUrl="route('admin.kelas.destroy', $k)" 
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada kelas</td></tr>
                @endforelse
            </tbody>
        </table>
        </form>

        @if($kelas->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $kelas->links() }}
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');
            const btnBulkDelete = document.getElementById('btn-bulk-delete');
            const selectedCount = document.getElementById('selected-count');
            const deleteAllInput = document.getElementById('delete-all-input');
            const totalDataCount = {{ $kelas->total() }};

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
                        ? `PERHATIAN: Anda akan menghapus SELURUH ${totalDataCount} kelas (termasuk di halaman lain). Data siswa dan jadwal di dalamnya akan TERHAPUS. Yakin?` 
                        : 'Yakin ingin menghapus kelas yang Anda centang? Data siswa dan jadwal di dalamnya akan TERHAPUS.';
                        
                    if (!confirm(msg)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.admin>

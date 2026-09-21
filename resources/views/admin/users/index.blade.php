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
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
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

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Email</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Role</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-blue-700">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-900 block">{{ $user->name }}</span>
                                    @if($user->guruProfile)
                                        <span class="text-xs text-slate-500">NIP: {{ $user->guruProfile->nip ?? '-' }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                                Guru
                            </span>
                        </td>
                        <td class="px-4 py-3">
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
                        <td colspan="4" class="px-4 py-10 text-center text-slate-400 text-sm">Tidak ada data guru ditemukan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $users->links() }}</div>
            @endif
        </div>

        <!-- Import Modal -->
        <div x-show="showImport" style="display: none;" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div @click.outside="showImport = false" class="bg-white rounded-2xl w-full max-w-lg shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Import Data Guru</h3>
                    <button @click="showImport = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 text-blue-700 p-4 rounded-xl text-sm mb-6 space-y-2">
                        <p class="font-semibold">Ketentuan Kolom Excel (.xlsx / .csv):</p>
                        <ul class="list-disc pl-4 space-y-1">
                            <li>Harus ada kolom <strong>Nama</strong> atau <strong>Nama Lengkap</strong>.</li>
                            <li>Kolom <strong>NIP</strong> opsional namun direkomendasikan.</li>
                            <li>Kolom <strong>Email</strong> opsional. Jika kosong, akan dibuatkan email otomatis.</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-2">File Excel / CSV <span class="text-red-500">*</span></label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        </div>
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" @click="showImport = false" class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl">Mulai Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>

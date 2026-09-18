<x-layouts.admin>
    <x-slot:title>Manajemen Siswa</x-slot:title>

    @push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush

    <div x-data="{ showImport: false }">
        <!-- Header & Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <form action="{{ route('admin.siswa.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIS..." 
                           class="w-full sm:w-64 px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                    <select name="kelas_id" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Kelas</option>
                        <option value="null" {{ request('kelas_id') === 'null' ? 'selected' : '' }}>Belum Masuk Kelas</option>
                        @foreach($kelasList as $kelas)
                            <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->nama }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-xl text-sm font-semibold transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'kelas_id']))
                        <a href="{{ route('admin.siswa.index') }}" class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="showImport = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Import Excel
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <tr>
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
                                <div class="font-medium text-slate-900">{{ $s->user->name }}</div>
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
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <p class="text-base font-medium text-slate-900 mb-1">Tidak ada data siswa</p>
                                <p class="text-sm">Silakan gunakan fitur Import Excel untuk menambahkan data massal.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($siswa->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $siswa->links() }}
            </div>
            @endif
        </div>

        <!-- Import Modal -->
        <div x-show="showImport" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div @click.outside="showImport = false" class="bg-white rounded-2xl w-full max-w-lg shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Import Data Siswa</h3>
                    <button @click="showImport = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 text-blue-700 p-4 rounded-xl text-sm mb-6 space-y-2">
                        <p class="font-semibold">Ketentuan Kolom Excel (.xlsx / .csv):</p>
                        <ul class="list-disc pl-4 space-y-1">
                            <li>Harus ada kolom <strong>Nama</strong> atau <strong>Nama Lengkap</strong>.</li>
                            <li>Kolom <strong>Kelas</strong> opsional. Jika diisi dengan nama kelas yang sesuai di database (misal: "12 AK-1"), siswa akan otomatis masuk kelas tersebut.</li>
                            <li>Kolom <strong>Email</strong> opsional. Jika kosong, akan dibuatkan email otomatis.</li>
                            <li>Kolom <strong>NIS</strong> opsional namun direkomendasikan untuk mencegah data ganda.</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.siswa.import') }}" method="POST" enctype="multipart/form-data">
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

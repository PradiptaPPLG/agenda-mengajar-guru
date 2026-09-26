<x-layouts.admin>
    <x-slot:title>Manajemen Semua Pengguna</x-slot:title>
    
    @push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        let filterTimeout;
        function debouncedFilterSubmit(formId, inputId) {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                document.getElementById(formId).submit();
            }, 500);
        }
    </script>
    @endpush

    <div>
        <!-- Header & Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="w-full">
                <form action="{{ route('admin.pengguna.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 w-full" id="pengguna-filter-form">
                    {{-- Role Utama --}}
                    <select name="role" onchange="document.getElementById('pengguna-filter-form').submit()" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Role Sistem</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="kepala_sekolah" {{ request('role') == 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                        <option value="tu" {{ request('role') == 'tu' ? 'selected' : '' }}>Tata Usaha (TU)</option>
                        <option value="guru" {{ request('role') == 'guru' ? 'selected' : '' }}>Guru</option>
                        <option value="piket" {{ request('role') == 'piket' ? 'selected' : '' }}>Piket</option>
                    </select>

                    {{-- Jabatan / Role Tambahan --}}
                    <select name="jabatan" onchange="document.getElementById('pengguna-filter-form').submit()" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Jabatan / Akses</option>
                        <option value="kaprog" {{ request('jabatan') == 'kaprog' ? 'selected' : '' }}>Kepala Program (Kaprog)</option>
                        <option value="bk" {{ request('jabatan') == 'bk' ? 'selected' : '' }}>Guru BK</option>
                        <option value="wali_kelas" {{ request('jabatan') == 'wali_kelas' ? 'selected' : '' }}>Wali Kelas</option>
                        <option value="piket" {{ request('jabatan') == 'piket' ? 'selected' : '' }}>Petugas Piket</option>
                    </select>

                    {{-- Search Input & Button --}}
                    <div class="flex items-center gap-1.5 flex-1 min-w-[240px] max-w-md">
                        <div class="relative w-full">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, atau email..."
                                   id="pengguna-search-input"
                                   class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium transition-colors shrink-0">
                            Cari
                        </button>
                    </div>

                    @if(request()->has('search') || request()->has('role') || request()->has('jabatan'))
                        <a href="{{ route('admin.pengguna.index') }}" class="px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Desktop Table (Hidden on Mobile) -->
        <div class="hidden md:block bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Email</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Role Utama</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Akses Tambahan</th>
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
                                        <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 capitalize">
                                    {{ str_replace('_', ' ', $user->role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $spatieRole)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $spatieRole->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 italic">Tidak ada</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end">
                                    @if($user->role !== 'super_admin' || auth()->user()->role === 'super_admin')
                                    <x-action-dropdown 
                                        :editUrl="route('admin.pengguna.edit', [$user, 'redirect_to' => request()->fullUrl()])" 
                                    />
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                Belum ada data pengguna ditemukan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden space-y-4">
            @forelse($users as $user)
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                            <span class="text-sm font-bold text-blue-700">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 capitalize mt-1">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-2 mb-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Email:</span>
                        <span class="text-slate-700 font-medium">{{ $user->email ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block mb-1">Akses Tambahan:</span>
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles as $spatieRole)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $spatieRole->name }}
                                </span>
                            @empty
                                <span class="text-xs text-slate-400 italic">Tidak ada</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-3 border-t border-slate-100">
                    @if($user->role !== 'super_admin' || auth()->user()->role === 'super_admin')
                    <a href="{{ route('admin.pengguna.edit', [$user, 'redirect_to' => request()->fullUrl()]) }}" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Edit Akses
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 text-center">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <p class="text-slate-500 font-medium">Belum ada data pengguna</p>
            </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>
</x-layouts.admin>

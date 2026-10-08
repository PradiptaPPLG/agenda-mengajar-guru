<x-layouts.admin>
    <x-slot:title>Manajemen Semua Pengguna</x-slot:title>
    
    <div>
        <!-- Header & Tambah Pengguna Button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Manajemen Semua Pengguna</h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola akun, role utama sistem (Kepala Sekolah, Pengawas, Admin, Guru, dll), dan hak akses tambahan.</p>
            </div>
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <a href="{{ route('admin.pengguna.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-xs hover:shadow-sm transition-all duration-150">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Pengguna</span>
                </a>
            </div>
        </div>

        <!-- Filters Form -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 shadow-xs">
            <form action="{{ route('admin.pengguna.index') }}" method="GET" class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 w-full" id="pengguna-filter-form">
                {{-- Role Utama --}}
                <select name="role" onchange="liveSearchFilter('pengguna-filter-form', 'pengguna-table-container', 'pengguna-search-input')" class="w-full sm:w-auto px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">Semua Role Sistem</option>
                    @if(auth()->user()->isSuperAdmin())
                        <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    @endif
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="kepala_sekolah" {{ request('role') == 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                    <option value="pengawas" {{ request('role') == 'pengawas' ? 'selected' : '' }}>Pengawas Sekolah</option>
                    <option value="tu" {{ request('role') == 'tu' ? 'selected' : '' }}>Tata Usaha (TU)</option>
                    <option value="guru" {{ request('role') == 'guru' ? 'selected' : '' }}>Guru</option>
                    <option value="piket" {{ request('role') == 'piket' ? 'selected' : '' }}>Piket</option>
                </select>

                {{-- Jabatan / Role Tambahan --}}
                <select name="jabatan" onchange="liveSearchFilter('pengguna-filter-form', 'pengguna-table-container', 'pengguna-search-input')" class="w-full sm:w-auto px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">Semua Jabatan / Akses</option>
                    <option value="kaprog" {{ request('jabatan') == 'kaprog' ? 'selected' : '' }}>Kepala Program (Kaprog)</option>
                    <option value="bk" {{ request('jabatan') == 'bk' ? 'selected' : '' }}>Guru BK</option>
                    <option value="wali_kelas" {{ request('jabatan') == 'wali_kelas' ? 'selected' : '' }}>Wali Kelas</option>
                    <option value="piket" {{ request('jabatan') == 'piket' ? 'selected' : '' }}>Petugas Piket</option>
                    <option value="pengawas" {{ request('jabatan') == 'pengawas' ? 'selected' : '' }}>Pengawas Pembina</option>
                </select>

                {{-- Search Input & Button --}}
                <div class="flex items-center gap-1.5 flex-1 min-w-[240px] max-w-md">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIP, atau email..."
                               id="pengguna-search-input"
                               autocomplete="off"
                               class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 bg-white"
                               oninput="liveSearchFilter('pengguna-filter-form', 'pengguna-table-container', 'pengguna-search-input')">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-medium transition-colors shrink-0">
                        Cari
                    </button>
                </div>

                @if(request()->filled('search') || request()->filled('role') || request()->filled('jabatan'))
                    <a href="{{ route('admin.pengguna.index') }}" class="px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-sm font-semibold transition-colors">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div id="pengguna-table-container">
            <!-- Desktop Table -->
            <div class="hidden md:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama & Identitas</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Email</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Role Utama</th>
                                <th class="text-left px-4 py-3 font-semibold text-slate-600">Akses Tambahan</th>
                                <th class="text-center px-4 py-3 font-semibold text-slate-600">Status</th>
                                <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($users as $user)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full {{ in_array($user->role, ['kepala_sekolah', 'pengawas']) ? 'bg-purple-100 text-purple-700' : ($user->role === 'super_admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700') }} flex items-center justify-center shrink-0 font-bold text-xs">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                            @if($user->guruProfile?->nip)
                                                <p class="text-xs text-slate-500 font-mono">NIP: {{ $user->guruProfile->nip }}</p>
                                            @elseif($user->guruProfile?->kaprog_jurusan)
                                                <p class="text-xs text-slate-500">{{ $user->guruProfile->kaprog_jurusan }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ $user->email ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColor = match ($user->role) {
                                            'super_admin' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                            'admin' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'kepala_sekolah' => 'bg-violet-100 text-violet-800 border-violet-200',
                                            'pengawas' => 'bg-purple-100 text-purple-800 border-purple-200',
                                            'piket' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'tu' => 'bg-teal-100 text-teal-800 border-teal-200',
                                            default => 'bg-blue-100 text-blue-800 border-blue-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $badgeColor }}">
                                        {{ $user->role_label }}
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
                                <td class="px-4 py-3 text-center">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end">
                                        @if($user->role !== 'super_admin' || auth()->user()->isSuperAdmin())
                                        <x-action-dropdown 
                                            :editUrl="route('admin.pengguna.edit', [$user, 'redirect_to' => request()->fullUrl()])" 
                                            :deleteUrl="($user->id !== auth()->id() && ($user->role !== 'super_admin' || auth()->user()->isSuperAdmin())) ? route('admin.pengguna.destroy', $user) : null"
                                            deleteMessage="Apakah Anda yakin ingin menghapus akun {{ $user->name }}?"
                                        />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
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
                <div class="bg-white p-4 rounded-xl shadow-xs border border-slate-200">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ in_array($user->role, ['kepala_sekolah', 'pengawas']) ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center shrink-0 font-bold text-sm">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                @if($user->guruProfile?->nip)
                                    <p class="text-xs text-slate-500 font-mono">NIP: {{ $user->guruProfile->nip }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800">
                                        {{ $user->role_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 mb-4 text-sm pt-2 border-t border-slate-100">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Email:</span>
                            <span class="text-slate-700 font-mono text-xs">{{ $user->email ?? '-' }}</span>
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
                        @if($user->role !== 'super_admin' || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.pengguna.edit', [$user, 'redirect_to' => request()->fullUrl()]) }}" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit Pengguna
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="bg-white p-8 rounded-xl shadow-xs border border-slate-200 text-center">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <p class="text-slate-500 font-medium">Belum ada data pengguna</p>
                </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>

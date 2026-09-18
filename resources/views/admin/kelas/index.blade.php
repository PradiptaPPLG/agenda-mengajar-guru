<x-layouts.admin>
    <x-slot:title>Manajemen Kelas</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('admin.kelas.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kelas
        </a>
    </x-slot:actions>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Tingkat</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Tahun Ajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Wali Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Siswa</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($kelas as $k)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $k->nama }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $k->tingkat }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ $k->tahun_ajaran }}</td>
                    <td class="px-4 py-3 text-slate-500 hidden lg:table-cell">{{ $k->waliKelas?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">{{ $k->siswa_profiles_count }} siswa</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end">
                            <x-action-dropdown 
                                :editUrl="route('admin.kelas.edit', $k)" 
                                :deleteUrl="route('admin.kelas.destroy', $k)" 
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada kelas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
